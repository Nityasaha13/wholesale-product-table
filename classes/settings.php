<?php

if( ! defined( 'ABSPATH' )) exit;


if( ! class_exists('WPTW_Settings')){

    class WPTW_Settings{

        public function __construct() {
        $this->init();
    }

    public function init() {
        // Admin settings page.
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Hook the method properly to the class instance
        add_filter('woocommerce_get_price_html', array($this, 'show_wholesale_price_html'), 20, 2);
    }

    public function register_admin_menu() {
        add_menu_page(
            'Wholesale Product Table Settings',
            'Wholesale Table',
            'manage_options',
            'wholesale-product-table-settings',
            array($this, 'admin_settings_page'),
            'dashicons-admin-generic',
            56
        );
    }

    public function admin_settings_page() {
        include_once WPTP_PLUGIN_BASE_PATH . 'admin/settings.php';
    }

    public function apply_discount_settings($price, $product) {
        $enabled_discounts = get_option('wpt_enabled_wholesale_discount');
        $discount_type = get_option('wpt_wholesale_discount_type');
        $discount_value = get_option('wpt_wholesale_discount_value');
        $include_categories_raw = get_option('wpt_include_categories', '');
        $exclude_categories_raw = get_option('wpt_exclude_categories', '');

        // Handle empty values and explode by comma
        $include_categories = array();
        $exclude_categories = array();

        if (!empty($include_categories_raw)) {
            $include_categories = array_filter(array_map('intval', array_map('trim', explode(',', $include_categories_raw))));
        }

        if (!empty($exclude_categories_raw)) {
            $exclude_categories = array_filter(array_map('intval', array_map('trim', explode(',', $exclude_categories_raw))));
        }

        // Check if wholesale discount is enabled and has a valid value
        if ($enabled_discounts != 'wholesale_discount_on' || empty($discount_value) || $discount_value <= 0) {
            return $price;
        }

        // Skip variable products
        if ($product->is_type('variable')) {
            return $price;
        }

        // Get product categories
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
        
        if (is_wp_error($product_categories)) {
            return $price;
        }

        // Check include categories (if not empty, product must be in one of these categories)
        if (!empty($include_categories)) {
            if (empty(array_intersect($product_categories, $include_categories))) {
                return $price; // Product not in included categories
            }
        }

        // Check exclude categories (if product is in any excluded category, skip discount)
        if (!empty($exclude_categories)) {
            if (!empty(array_intersect($product_categories, $exclude_categories))) {
                return $price; // Product is in excluded categories
            }
        }

        // Apply discount based on type
        $discounted_price = $price;
        
        if ($discount_type === 'percentage') {
            $discounted_price = $price - ($price * ($discount_value / 100));
        } elseif ($discount_type === 'fixed') {
            $discounted_price = $price - $discount_value;
        }

        // Ensure price doesn't go below zero
        return max(0, $discounted_price);
    }

    public function show_wholesale_price_html($price_html, $product) {
        // Get the original price
        $original_price = $product->get_price();
        
        // Skip if no original price
        if (empty($original_price)) {
            return $price_html;
        }

        // Apply discount settings
        $discounted_price = $this->apply_discount_settings($original_price, $product);

        // Only modify price HTML if discount is applied
        if ($discounted_price < $original_price && $discounted_price >= 0) {
            $price_html = '<span class="wholesale-label">Wholesale Price: </span>';
            $price_html .= '<del>' . wc_price($original_price) . '</del> ';
            $price_html .= '<ins>' . wc_price($discounted_price) . '</ins>';
        }

        return $price_html;
    }

        //No use of this function, but keeping it for future use.
        public function admin_settings_page_backup() {
            if ( isset( $_POST['wpt_settings_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['wpt_settings_nonce'] ), 'wpt_save_settings' ) ) {

                $selected_columns = isset( $_POST['selected_columns'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['selected_columns'] ) ) : array();
                $selected_style = isset( $_POST['wpt_table_style'] ) ? sanitize_text_field( wp_unslash( $_POST['wpt_table_style'] ) ) : '';
                $selected_products = isset( $_POST['wpt_wholesale_products'] ) ? sanitize_text_field( wp_unslash( $_POST['wpt_wholesale_products'] ) ) : '';
                $selected_pro_cat = isset( $_POST['wpt_category'] ) ? sanitize_text_field( wp_unslash( $_POST['wpt_category'] ) ) : '';
                $selected_pro_pp = isset( $_POST['wpt_wholesale_product_pp'] ) ? sanitize_text_field( wp_unslash( $_POST['wpt_wholesale_product_pp'] ) ) : '';
            
                update_option( 'wptw_selected_columns', $selected_columns );
                update_option( 'wptw_table_style', $selected_style );
                update_option( 'wptw_wholesale_products_opt', $selected_products );
                update_option( 'wptw_wholesale_product_category', $selected_pro_cat );
                update_option( 'wptw_wholesale_product_pp', $selected_pro_pp );
            
                echo '<div class="updated notice is-dismissible"><p>Settings saved.</p></div>';
            }
            

            $selected_columns = get_option( 'wptw_selected_columns' );
            $selected_style = get_option( 'wptw_table_style' );
            $selected_products = get_option( 'wptw_wholesale_products_opt' );
            $selected_pro_cat = get_option( 'wptw_wholesale_product_category' );
            $selected_pro_pp = get_option( 'wptw_wholesale_product_pp' );

            ?>
            <div class="wrap">
                <h1>Wholesale Product Table Settings</h1>
                <form method="post" action="">
                    <?php wp_nonce_field( 'wpt_save_settings', 'wpt_settings_nonce' ); ?>
                    <table class="wpt form-table">
                        <tr>
                            <th scope="row">Select Columns to Display</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="image" <?php checked( in_array( 'image', $selected_columns ) ); ?> /> Image</label> 
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="product_name" <?php checked( in_array( 'product_name', $selected_columns ) ); ?> /> Product Name</label> 
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="sku" <?php checked( in_array( 'sku', $selected_columns ) ); ?> /> SKU</label> 
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="category" <?php checked( in_array( 'category', $selected_columns ) ); ?> /> Category</label> 
                                <label><input type="checkbox" name="selected_columns[]" value="price" <?php checked( in_array( 'price', $selected_columns ) ); ?> /> Price</label> 
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="in_stock" <?php checked( in_array( 'in_stock', $selected_columns ) ); ?> /> Stock Status</label> 
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="quantity" <?php checked( in_array( 'quantity', $selected_columns ) ); ?> /> Quantity</label> 
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="add_to_cart" <?php checked( in_array( 'add_to_cart', $selected_columns ) ); ?> /> Add to Cart</label> 
                            </td>
                        </tr>
                        <tr>
                            <th>Select Table Style</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input"><input type="radio" name="wpt_table_style" value="default" <?php checked( 'default', $selected_style ); ?> /> Default Style</label> 
                                <label class="wpt-input"><input type="radio" name="wpt_table_style" value="plugin" <?php checked( 'plugin', $selected_style ); ?> /> Plugin Style</label> 
                            </td>
                        </tr>
                        <tr>
                            <th>Select Wholesale Products</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input"><input type="radio" name="wpt_wholesale_products" value="all" <?php checked( 'all', $selected_products ); ?>/> All Products</label> 
                                <label class="wpt-input"><input type="radio" name="wpt_wholesale_products" value="category" <?php checked( 'category', $selected_products ); ?> /> Select a Category</label> 
                            </td>
                        </tr>                   
                        <tr class="wpt-category-dropdown">
                            <th>Select a Category as Wholesale Product</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input">
                                    <?php $categories = wptw_get_product_categories(); ?>
                                    <select name="wpt_category" id="wpt-cat-select">
                                        <option value="all" <?php selected( $selected_pro_cat, 'all' , false ) ?>>All Categories</option>
                                        <?php
                                            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                                                foreach ( $categories as $cat ) {
                                                    echo '<option value="' . esc_attr( $cat->term_id ) . '" ' . selected( $selected_pro_cat, $cat->term_id, false ) . '>' . esc_html( $cat->name ) . '</option>';
                                                }
                                            }
                                        ?>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Products Per Page</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input"><input type="text" name="wpt_wholesale_product_pp" value="<?php echo esc_attr($selected_pro_pp)  ?>"/></label>  
                            </td>
                        </tr>
                        
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

    }

}