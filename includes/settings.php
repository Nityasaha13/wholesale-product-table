<?php

if( ! defined( 'ABSPATH' )) exit;


if( ! class_exists('WPTW_Settings')){

    class WPTW_Settings{

        public function __construct(){
            $this->init();
        }

        public function init(){
            // Admin settings page.
            add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
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
                $selected_style = isset( $_POST['wpt_table_style'] ) ? sanitize_text_field($_POST['wpt_table_style'] ) : '';

                update_option( 'wptw_selected_columns', $selected_columns );
                update_option( 'wptw_table_style', $selected_style );
                echo '<div class="updated notice is-dismissible"><p>Settings saved.</p></div>';
            }
            $default_columns = array( 'image', 'product_name', 'sku', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart' );
            $default_style = 'default';

            $selected_columns = get_option( 'wptw_selected_columns', $default_columns );
            $selected_style = get_option( 'wptw_table_style', $default_style );
            ?>
            <div class="wrap">
                <h1>Wholesale Product Table Settings</h1>
                <form method="post" action="">
                    <?php wp_nonce_field( 'wpt_save_settings', 'wpt_settings_nonce' ); ?>
                    <table class="wpt form-table">
                        <tr>
                            <th scope="row">Select Columns to Display</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="image" <?php checked( in_array( 'image', $selected_columns ) ); ?> /> Image</label><br>
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="product_name" <?php checked( in_array( 'product_name', $selected_columns ) ); ?> /> Product Name</label><br>
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="sku" <?php checked( in_array( 'sku', $selected_columns ) ); ?> /> SKU</label><br>
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="category" <?php checked( in_array( 'category', $selected_columns ) ); ?> /> Category</label><br>
                                <label><input type="checkbox" name="selected_columns[]" value="price" <?php checked( in_array( 'price', $selected_columns ) ); ?> /> Price</label><br>
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="in_stock" <?php checked( in_array( 'in_stock', $selected_columns ) ); ?> /> Stock Status</label><br>
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="quantity" <?php checked( in_array( 'quantity', $selected_columns ) ); ?> /> Quantity</label><br>
                                <label class="wpt-input"><input type="checkbox" name="selected_columns[]" value="add_to_cart" <?php checked( in_array( 'add_to_cart', $selected_columns ) ); ?> /> Add to Cart</label><br>
                            </td>
                        </tr>
                        <tr>
                            <th>Select Table Style</th>
                            <td class="wpt-input-container">
                                <label class="wpt-input"><input type="radio" name="wpt_table_style" value="default" <?php checked( 'default', $selected_style ); ?> /> Default Style</label><br>
                                <label class="wpt-input"><input type="radio" name="wpt_table_style" value="plugin" <?php checked( 'plugin', $selected_style ); ?> /> Plugin Style</label><br>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

    }

    new WPTW_Settings();

}