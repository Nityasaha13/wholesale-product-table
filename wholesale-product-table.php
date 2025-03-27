<?php

/*
Plugin Name: Wholesale Product Table for WooCommerce
Description: Displays a wholesale product table via a shortcode with configurable columns, AJAX add-to-cart, variable product dropdowns (with updated price and image), pagination, search, and category filtering.
Version: 1.0
Author: Nitya Saha
Requires plugins: woocommerce
Author URI: https://profiles.wordpress.org/nityasaha/
Text Domain: wholesale-product-table
*/

if (! defined('ABSPATH')) {
    exit;
}

define('WPTW_VERSION', '1.0');

// Ensure WooCommerce is active.
if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    echo '<div class="updated notice is-dismissible"><p>WooCommerce is required to activate Wholesale Product Table.</p></div>';
    return;
}

if (! class_exists('WPTW_Main')):

    class WPTW_Main{
        private $plugin_basename;

        public function __construct(){

            // On activation, create Wholesale Order page.
            register_activation_hook(__FILE__, array($this, 'plugin_activation'));

            $this->init();

        }

        public function init(){

            $this->plugin_basename = plugin_basename(__FILE__);
            add_filter('plugin_action_links_' . $this->plugin_basename, array($this, 'setting_page_link'));
            add_filter('plugin_row_meta', array($this, 'addon_plugin_links'), 10, 2);

            $this->include_required_files();

            // Enqueue scripts and styles.
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }

        public function include_required_files(){

            require_once 'classes/settings.php';
            require_once 'classes/ajax.php';
            require_once 'classes/shortcode.php';
            require_once 'includes/wpt_global.php';
        }

        public function setting_page_link($links){
            // Add your custom links
            $settings_link = '<a href="' . admin_url('admin.php?page=wholesale-product-table-settings') . '">' . __('Settings', 'wholesale-product-table') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        public function addon_plugin_links($links, $file){
            if ($file === $this->plugin_basename) {
                $links[] = __('<a href="https://buymeacoffee.com/nityasaha">Donate</a>', 'wholesale-product-table');
                $links[] = __('Made with Love ❤️', 'wholesale-product-table');
            }

            return $links;
        }

        public function plugin_activation(){
            $page_title   = 'Wholesale Order';
            $page_content = '[wholesale_product_table]';
            $page_check   = get_page_by_title($page_title);
            $page_data    = array(
                'post_type'    => 'page',
                'post_title'   => $page_title,
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            );
            if (! isset($page_check->ID)) {
                wp_insert_post($page_data);
            }

            self::default_setting_options();
        }

        public function default_setting_options(){
            
            $default_columns = array( 'image', 'product_name', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart' );

            if( ! get_option('wptw_selected_columns') ){
                update_option('wptw_selected_columns', $default_columns);
            }
                
            if( ! get_option('wptw_table_style') ){
                update_option('wptw_table_style', 'default');
            }
                
            if( ! get_option('wptw_wholesale_products_opt') ){
                update_option('wptw_wholesale_products_opt', 'all');
            }

            if( ! get_option('wptw_wholesale_product_category') ){
                update_option('wptw_wholesale_product_category', 'all');
            }
                
            if( ! get_option('wptw_wholesale_product_pp') ){
                update_option('wptw_wholesale_product_pp', 10);
            }
        }


        public function frontend_enqueue_scripts(){
            global $post;

            if(isset($post) && is_a($post, 'WP_Post') && has_shortcode(  $post->post_content, 'wholesale_product_table')){
                // Enqueue our JS file.
                wp_enqueue_script('wpt-script', plugin_dir_url(__FILE__) . 'assets/js/wpt.js', array('jquery'), WPTW_VERSION, false);
                wp_localize_script('wpt-script', 'wpt_ajax_params', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('wpt_ajax_nonce'),
                    'cart_url' => wc_get_cart_url()
                ));

                // Enqueue our CSS file.
                wp_enqueue_style('wpt-style', plugin_dir_url(__FILE__) . 'assets/css/wpt-frontend.css', array(), WPTW_VERSION);

                $selected_style = get_option( 'wptw_table_style' );

                if($selected_style === 'plugin'){
                    wp_enqueue_style('wpt-table2-style', plugin_dir_url(__FILE__) . 'assets/css/wpt-table-plugin.css', array(), WPTW_VERSION);
                }else{
                    wp_enqueue_style('wpt-table1-style', plugin_dir_url(__FILE__) . 'assets/css/wpt-table-default.css', array(), WPTW_VERSION);
                }
            }
        }

        public function admin_enqueue_scripts(){
            wp_enqueue_style('wpt-admin-style', plugin_dir_url(__FILE__) . 'assets/css/wpt-admin.css', array(), WPTW_VERSION);
        }
        
    }

    new WPTW_Main();

endif;
