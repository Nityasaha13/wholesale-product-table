<?php
/*
Plugin Name: Wholesale Product Table for WooCommerce
Description: Displays a wholesale product table via a shortcode with configurable columns, AJAX add-to-cart, variable product dropdowns (with updated price and image), pagination, search, and category filtering.
Version: 1.0
Author: Crescentek
Text Domain: wholesale-product-table
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPT_VERSION', '1.0' );

// Ensure WooCommerce is active.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

class Wholesale_Product_Table_Plugin {

    private $plugin_basename;

    public function __construct() {

        $this->plugin_basename = plugin_basename(__FILE__);
        add_filter('plugin_action_links_' . $this->plugin_basename, array($this, 'setting_page_link'));
        add_filter( 'plugin_row_meta', array( $this, 'addon_plugin_links' ), 10, 2 );

        // On activation, create Wholesale Order page.
        register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

        // Admin settings page.
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

        // Shortcode.
        add_shortcode( 'wholesale_product_table', array( $this, 'display_product_table' ) );

        // Enqueue scripts and styles.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_wpt_product_search', array( $this, 'ajax_product_search' ) );
        add_action( 'wp_ajax_nopriv_wpt_product_search', array( $this, 'ajax_product_search' ) );
        add_action( 'wp_ajax_wpt_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_wpt_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
    }

    public function setting_page_link($links) {
        // Add your custom links
        $settings_link = '<a href="' . admin_url('admin.php?page=wholesale-product-table-settings') . '">' . __('Settings', 'wholesale-product-table') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function addon_plugin_links( $links, $file ) {
        if ( $file === $this->plugin_basename ) {
            $links[] = '<a href="javascript:void(0);" rel="noopener">' .
                       __( 'Donate', 'wholesale-product-table' ) . '</a>';
        }

        return $links;
    }

    public function plugin_activation() {
        $page_title   = 'Wholesale Order';
        $page_content = '[wholesale_product_table]';
        $page_check   = get_page_by_title( $page_title );
        $page_data    = array(
            'post_type'    => 'page',
            'post_title'   => $page_title,
            'post_content' => $page_content,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        );
        if ( ! isset( $page_check->ID ) ) {
            wp_insert_post( $page_data );
        }
    }

    public function register_admin_menu() {
        add_menu_page(
            'Wholesale Product Table Settings',
            'Wholesale Table',
            'manage_options',
            'wholesale-product-table-settings',
            array( $this, 'admin_settings_page' ),
            'dashicons-admin-generic',
            56
        );
    }

    public function admin_settings_page() {
        if ( isset( $_POST['wpt_settings_nonce'] ) && wp_verify_nonce( $_POST['wpt_settings_nonce'], 'wpt_save_settings' ) ) {
            $selected_columns = isset( $_POST['selected_columns'] ) ? array_map( 'sanitize_text_field', $_POST['selected_columns'] ) : array();
            update_option( 'wpt_selected_columns', $selected_columns );
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        $default_columns = array( 'image', 'product_name', 'sku', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart' );
        $selected_columns = get_option( 'wpt_selected_columns', $default_columns );
        ?>
        <div class="wrap">
            <h1>Wholesale Product Table Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'wpt_save_settings', 'wpt_settings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Select Columns to Display</th>
                        <td>
                            <label><input type="checkbox" name="selected_columns[]" value="image" <?php checked( in_array( 'image', $selected_columns ) ); ?> /> Image</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="product_name" <?php checked( in_array( 'product_name', $selected_columns ) ); ?> /> Product Name</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="sku" <?php checked( in_array( 'sku', $selected_columns ) ); ?> /> SKU</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="category" <?php checked( in_array( 'category', $selected_columns ) ); ?> /> Category</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="price" <?php checked( in_array( 'price', $selected_columns ) ); ?> /> Price</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="in_stock" <?php checked( in_array( 'in_stock', $selected_columns ) ); ?> /> Stock Status</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="quantity" <?php checked( in_array( 'quantity', $selected_columns ) ); ?> /> Quantity</label><br>
                            <label><input type="checkbox" name="selected_columns[]" value="add_to_cart" <?php checked( in_array( 'add_to_cart', $selected_columns ) ); ?> /> Add to Cart</label><br>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if ( class_exists( 'WooCommerce' ) ) {
            // Enqueue our JS file.
            wp_enqueue_script( 'wpt-script', plugin_dir_url( __FILE__ ) . 'assets/js/wpt.js', array( 'jquery' ), WPT_VERSION, true );
            wp_localize_script( 'wpt-script', 'wpt_ajax_params', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wpt_ajax_nonce' ),
                'cart_url' => wc_get_cart_url()
            ) );
            // Enqueue our CSS file.
            wp_enqueue_style( 'wpt-style', plugin_dir_url( __FILE__ ) . 'assets/css/wpt.css', array(), WPT_VERSION );
        }
    }

    /**
     * The shortcode outputs the search box, category filter, table structure, and pagination container.
     */
    public function display_product_table() {
        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        ) );
        $selected_columns = get_option( 'wpt_selected_columns', array( 'image', 'product_name', 'sku', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart' ) );
        ob_start();
        ?>
        <div class="wpt-controls">
            <div class="search">
                <input type="text" id="wpt-search" placeholder="Search products..." />
            </div>
            <div class="filter">
                <select id="wpt-category-filter">
                    <option value="all">All Categories</option>
                    <?php
                    if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                        foreach ( $categories as $cat ) {
                            echo '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div> 
        </div>
        <table class="wholesale-product-table">
            <thead>
                <tr>
                    <?php if ( in_array( 'image', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Image</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'product_name', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Product Name</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'sku', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">SKU</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'category', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Category</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'price', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Price</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'in_stock', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Stock Status</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'quantity', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Quantity</th>
                    <?php endif; ?>
                    <?php if ( in_array( 'add_to_cart', $selected_columns ) ) : ?>
                        <th class="wpt-table-head">Add to Cart</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="wpt-table-body">
                <!-- Rows loaded via AJAX -->
            </tbody>
        </table>

        <div id="wpt-pagination">
            <!-- Pagination loaded via AJAX -->
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Build the product table rows for a given WP_Query.
     * For variable products, include a dropdown for variations.
     */
    private function get_product_table_rows( $products, $selected_columns ) {
        ob_start();
        if ( $products->have_posts() ) :
            while ( $products->have_posts() ) :
                $products->the_post();
                $product    = wc_get_product( get_the_ID() );
                $product_id = $product->get_id();
                ?>
                <tr>
                    <?php if ( in_array( 'image', $selected_columns ) ) : ?>
                        <td class="wpt-image-cell">
                            <?php echo $product->get_image(); ?>
                        </td>
                    <?php endif; ?>

                    <?php if ( in_array( 'product_name', $selected_columns ) ) : ?>
                        <td class="wpt-product-name-cell">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            <?php if ( $product->is_type( 'variable' ) ) : 
                                $variations = $product->get_available_variations();
                                if ( ! empty( $variations ) ) : ?>
                                    <br/>
                                    <select class="wpt-variation-select" data-product-id="<?php echo esc_attr( $product_id ); ?>">
                                        <option value="">-- Select Variation --</option>
                                        <?php foreach ( $variations as $variation ) : 
                                            $var_id        = $variation['variation_id'];
                                            $var_price     = $variation['display_price'];
                                            $var_price_html= ! empty( $variation['price_html'] ) ? $variation['price_html'] : wc_price( $var_price );
                                            $attributes    = $variation['attributes']; 
                                            // Build a label from attributes.
                                            $attr_labels = array();
                                            foreach ( $attributes as $attr => $val ) {
                                                $attr_clean = str_replace( array( 'attribute_pa_', 'attribute_' ), '', $attr );
                                                $attr_labels[] = ucfirst( $attr_clean ) . ': ' . $val;
                                            }
                                            $variation_label = implode( ', ', $attr_labels );
                                            // Variation image (if available)
                                            $variation_image = isset( $variation['image']['src'] ) ? $variation['image']['src'] : '';
                                            ?>
                                            <option value="<?php echo esc_attr( $var_id ); ?>"
                                                data-price-html="<?php echo esc_attr( $var_price_html ); ?>"
                                                data-image="<?php echo esc_attr( $variation_image ); ?>"
                                                data-attributes="<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>">
                                                <?php echo esc_html( $variation_label ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <?php if ( in_array( 'sku', $selected_columns ) ) : ?>
                        <td class="wpt-sku-cell"><?php echo $product->get_sku(); ?></td>
                    <?php endif; ?>

                    <?php if ( in_array( 'category', $selected_columns ) ) : ?>
                        <td class="wpt-category-cell">
                            <?php 
                            $terms = get_the_terms( $product_id, 'product_cat' );
                            if ( $terms && ! is_wp_error( $terms ) ) {
                                $cats = wp_list_pluck( $terms, 'name' );
                                echo esc_html( implode( ', ', $cats ) );
                            }
                            ?>
                        </td>
                    <?php endif; ?>

                    <?php if ( in_array( 'price', $selected_columns ) ) : ?>
                        <td class="wpt-price-cell">
                            <?php echo $product->get_price_html(); ?>
                        </td>
                    <?php endif; ?>

                    <?php if ( in_array( 'in_stock', $selected_columns ) ) : ?>
                        <td class="wpt-stock-cell">
                            <?php 
                            if ( $product->is_in_stock() ) {
                                $stock = $product->get_stock_quantity();
                                echo $stock ? $stock . ' in stock' : 'In stock';
                            } else {
                                echo 'Out of stock';
                            }
                            ?>
                        </td>
                    <?php endif; ?>

                    <?php if ( in_array( 'quantity', $selected_columns ) ) : ?>
                        <td class="wpt-quantity-cell">
                            <input type="number" class="wpt-quantity" data-product-id="<?php echo esc_attr( $product_id ); ?>" value="1" min="1" />
                        </td>
                    <?php endif; ?>

                    <?php if ( in_array( 'add_to_cart', $selected_columns ) ) : ?>
                        <td class="wpt-add-to-cart-cell">
                            <button class="wpt-add-to-cart" data-product-id="<?php echo esc_attr( $product_id ); ?>">
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
            <tr><td colspan="10" class="wpt-no-products">No products found.</td></tr>
            <?php
        endif;
        return ob_get_clean();
    }

    /**
     * Build pagination links for AJAX.
     */
    private function get_pagination_links( $max_pages, $current_page ) {
        if ( $max_pages <= 1 ) {
            return '';
        }
        $links = paginate_links( array(
            'base'      => '%_%',
            'format'    => '?paged=%#%',
            'current'   => $current_page,
            'total'     => $max_pages,
            'type'      => 'array',
            'prev_text' => '&laquo; Prev',
            'next_text' => 'Next &raquo;',
        ) );
        if ( is_array( $links ) ) {
            return '<div class="wpt-pagination-links">' . implode( ' ', $links ) . '</div>';
        }
        return '';
    }

    /**
     * AJAX handler for search, filtering, and pagination.
     */
    public function ajax_product_search() {
        check_ajax_referer( 'wpt_ajax_nonce', 'nonce' );

        $search_query    = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
        $filter_category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : 'all';
        $page            = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

        $selected_columns = get_option( 'wpt_selected_columns', array( 'image', 'product_name', 'sku', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart' ) );

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 10,
            'paged'          => $page,
        );
        if ( ! empty( $search_query ) ) {
            $args['s'] = $search_query;
        }
        if ( 'all' !== $filter_category ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $filter_category,
                )
            );
        }

        $products = new WP_Query( $args );
        $rows     = $this->get_product_table_rows( $products, $selected_columns );
        $pagination_html = $this->get_pagination_links( $products->max_num_pages, $page );

        wp_send_json_success( array(
            'html'       => $rows,
            'pagination' => $pagination_html,
        ) );
    }

    /**
     * AJAX handler for Add to Cart.
     */
    public function ajax_add_to_cart() {
        check_ajax_referer( 'wpt_ajax_nonce', 'nonce' );

        $parent_id       = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $variation_id    = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
        $quantity        = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;
        $variation_attrs = isset( $_POST['variation_attrs'] ) ? json_decode( stripslashes( $_POST['variation_attrs'] ), true ) : array();

        if ( $parent_id && function_exists( 'WC' ) ) {
            if ( $variation_id > 0 ) {
                $cart_item_key = WC()->cart->add_to_cart( $parent_id, $quantity, $variation_id, $variation_attrs );
            } else {
                // For simple products.
                $cart_item_key = WC()->cart->add_to_cart( $parent_id, $quantity );
            }
            if ( $cart_item_key ) {
                wp_send_json_success( array( 'message' => 'Product added to cart.' ) );
            }
        }
        wp_send_json_error( array( 'message' => 'Failed to add product to cart.' ) );
    }
}

new Wholesale_Product_Table_Plugin();
