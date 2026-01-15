<?php

if (! defined('ABSPATH')) exit;

if (! class_exists('WPTW_Ajax')) {
    class WPTW_Ajax{

        public function __construct(){
            $this->init();
        }

        public function init(){

            // AJAX handlers.
            add_action('wp_ajax_wpt_product_search', array($this, 'ajax_product_search'));
            add_action('wp_ajax_nopriv_wpt_product_search', array($this, 'ajax_product_search'));
            add_action('wp_ajax_wpt_add_to_cart', array($this, 'ajax_add_to_cart'));
            add_action('wp_ajax_nopriv_wpt_add_to_cart', array($this, 'ajax_add_to_cart'));
        }

        /**
         * AJAX handler for search, filtering, and pagination.
         */
        public function ajax_product_search(){
            check_ajax_referer('wpt_ajax_nonce', 'nonce');

            $search_query    = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
            $filter_category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : 'all';
            $sort            = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'default';
            $page            = isset($_POST['page']) ? absint(wp_unslash($_POST['page'])) : 1;

            $selected_columns = get_option('wptw_selected_columns', array('image', 'product_name', 'sku', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart'));
            $selected_ppp = get_option('wptw_wholesale_product_pp', 10);
            $selected_products_opt = get_option('wptw_wholesale_products_opt') ?? '';   
            $selected_p_category = get_option('wptw_wholesale_product_category') ?? '';
            $arr_selected_p_category = explode(',', $selected_p_category);

            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => $selected_ppp,
                'paged'          => $page,
            );


            if ($selected_products_opt !== 'all' && !empty($selected_p_category) && !empty($arr_selected_p_category)) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => array_filter(array_map('intval', $arr_selected_p_category)),
                    )
                );
            }

            // Sorting logic.
            if($sort){
                switch ($sort) {
                    case 'price_asc':
                        $args['meta_key'] = '_price';
                        $args['orderby']  = 'meta_value_num';
                        $args['order']    = 'ASC';
                        break;
                    case 'price_desc':
                        $args['meta_key'] = '_price';
                        $args['orderby']  = 'meta_value_num';
                        $args['order']    = 'DESC';
                        break;
                    case 'name_asc':
                        $args['orderby'] = 'title';
                        $args['order']   = 'ASC';
                        break;
                    case 'name_desc':
                        $args['orderby'] = 'title';
                        $args['order']   = 'DESC';
                        break;
                    default:
                        // Default sorting by date.
                        $args['orderby'] = 'date';
                        $args['order']   = 'DESC';
                }
            }

            // Category filtering on table
            if ($filter_category !== 'all') {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $filter_category,
                    )
                );
            }

            if (! empty($search_query)) {
                $args['s'] = $search_query;
            }

            $products = new WP_Query($args);
            $rows     = $this->get_product_table_rows($products, $selected_columns);
            $pagination_html = $this->get_pagination_links($products->max_num_pages, $page);

            wp_send_json_success(array(
                'html'       => $rows,
                'pagination' => $pagination_html,
            ));
        }

        /**
         * AJAX handler for Add to Cart.
         */
        public function ajax_add_to_cart(){
            check_ajax_referer('wpt_ajax_nonce', 'nonce');

            $parent_id       = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $variation_id    = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
            $quantity        = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            $variation_attrs = isset($_POST['variation_attrs']) ? json_decode(wp_verify_nonce(wp_unslash($_POST['variation_attrs']), true)) : array();

            if ($parent_id && function_exists('WC')) {
                if ($variation_id > 0) {
                    $cart_item_key = WC()->cart->add_to_cart($parent_id, $quantity, $variation_id, $variation_attrs);
                } else {
                    // For simple products.
                    $cart_item_key = WC()->cart->add_to_cart($parent_id, $quantity);
                }
                if ($cart_item_key) {
                    wp_send_json_success(array('message' => 'Product added to cart.'));
                }
            }
            wp_send_json_error(array('message' => 'Failed to add product to cart.'));
        }

        /**
         * Build the product table rows for a given WP_Query.
         * For variable products, include a dropdown for variations.
         */
        public function get_product_table_rows($products, $selected_columns){

            ob_start();
            $product_count = $products->found_posts;

            if ($products->have_posts()) :
                while ($products->have_posts()) :
                    $products->the_post();
                    $product    = wc_get_product(get_the_ID());
                    $product_id = $product->get_id();
                    ?>
                    <tr>
                        <?php if (in_array('image', $selected_columns)) : ?>
                            <td class="wpt-image-cell">
                                <?php echo wp_kses_post($product->get_image()); ?>
                            </td>
                        <?php endif; ?>

                        <?php if (in_array('product_name', $selected_columns)) : ?>
                            <td class="wpt-product-name-cell">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                <?php if ($product->is_type('variable')) :
                                    $variations = $product->get_available_variations();
                                    if (! empty($variations)) : ?>
                                        <br />
                                        <select class="wpt-variation-select" data-product-id="<?php echo esc_attr($product_id); ?>">
                                            <option value="">-- Select Variation --</option>
                                            <?php foreach ($variations as $variation) :
                                                $var_id        = $variation['variation_id'];
                                                $var_price     = $variation['display_price'];
                                                $var_price_html = ! empty($variation['price_html']) ? $variation['price_html'] : wc_price($var_price);
                                                $attributes    = $variation['attributes'];
                                                // Build a label from attributes.
                                                $attr_labels = array();
                                                foreach ($attributes as $attr => $val) {
                                                    $attr_clean = str_replace(array('attribute_pa_', 'attribute_'), '', $attr);
                                                    $attr_labels[] = ucfirst($attr_clean) . ': ' . $val;
                                                }
                                                $variation_label = implode(', ', $attr_labels);
                                                // Variation image (if available)
                                                $variation_image = isset($variation['image']['src']) ? $variation['image']['src'] : '';
                                            ?>
                                                <option value="<?php echo esc_attr($var_id); ?>"
                                                    data-price-html="<?php echo esc_attr($var_price_html); ?>"
                                                    data-image="<?php echo esc_attr($variation_image); ?>"
                                                    data-attributes="<?php echo esc_attr(wp_json_encode($attributes)); ?>">
                                                    <?php echo esc_html($variation_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('price', $selected_columns)) : ?>
                            <td class="wpt-price-cell">
                                <?php echo wp_kses_post($product->get_price_html()); ?>
                            </td>
                        <?php endif; ?>

                        <?php if (in_array('category', $selected_columns)) : ?>
                            <td class="wpt-category-cell">
                                <?php
                                $terms = get_the_terms($product_id, 'product_cat');
                                if ($terms && ! is_wp_error($terms)) {
                                    $cats = wp_list_pluck($terms, 'name');
                                    echo esc_html(implode(', ', $cats));
                                }
                                ?>
                            </td>
                        <?php endif; ?>

                        <?php if (in_array('sku', $selected_columns)) : ?>
                            <td class="wpt-sku-cell"><?php echo wp_kses_post($product->get_sku()); ?></td>
                        <?php endif; ?>

                        <?php if (in_array('in_stock', $selected_columns)) : ?>
                            <td class="wpt-stock-cell">
                                <?php
                                if ($product->is_in_stock()) {
                                    $stock = $product ->get_stock_quantity();
                                    echo $stock ? esc_html($stock . ' in stock') : 'In stock';
                                } else {
                                    echo 'Out of stock';
                                }
                                ?>
                            </td>
                        <?php endif; ?>

                        <?php if (in_array('quantity', $selected_columns)) : ?>
                            <td class="wpt-quantity-cell">
                                <input type="number" class="wpt-quantity" data-product-id="<?php echo esc_attr($product_id); ?>" value="1" min="1" />
                            </td>
                        <?php endif; ?>

                        <?php if (in_array('add_to_cart', $selected_columns)) : ?>
                            <td class="wpt-add-to-cart-cell">
                                <button class="wpt-add-to-cart" data-product-id="<?php echo esc_attr($product_id); ?>">
                                    Add to Cart
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <tr>
                    <td colspan="10" class="wpt-no-products">No products found.</td>
                </tr>
                <?php
            endif;
            ?>
            <tr class="wpt-product-count">
                <td colspan="10" class="wpt-product-count-cell">Total <?php echo esc_html($product_count); ?> products found</td>
            </tr>
            <?php
            return ob_get_clean();
        }

        /**
         * Build pagination links for AJAX.
         */
        public function get_pagination_links($max_pages, $current_page){
            if ($max_pages <= 1) {
                return '';
            }
            $links = paginate_links(array(
                'base'      => '%_%',
                'format'    => '?paged=%#%',
                'current'   => $current_page,
                'total'     => $max_pages,
                'type'      => 'array',
                'prev_text' => '&laquo; Prev',
                'next_text' => 'Next &raquo;',
            ));
            if (is_array($links)) {
                return '<div class="wpt-pagination-links">' . implode(' ', $links) . '</div>';
            }
            return '';
        }
    }

    new WPTW_Ajax();
}
