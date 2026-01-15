<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WPTW_Main')):

    class WPTW_Main{

        public function __construct(){

            // On activation, create Wholesale Order page.
            register_activation_hook(__FILE__, array($this, 'plugin_activation'));

            $this->init();

        }

        public function init(){

            require_once WPTP_PLUGIN_BASE_PATH . 'includes/ExtraFunctions.php';

            require_once WPTP_PLUGIN_BASE_PATH . 'classes/Shortcode.php';
            $shortcode = new WPTW_Shortcode();

            require_once WPTP_PLUGIN_BASE_PATH . 'classes/APIs.php';
            $api = new WPTW_APIs();

            require_once WPTP_PLUGIN_BASE_PATH . 'classes/Ajax.php';
            $ajax = new WPTW_Ajax();

            require_once WPTP_PLUGIN_BASE_PATH . 'classes/Settings.php';
            $setting = new WPTW_Settings();


            // Enqueue scripts and styles.
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }


        public function plugin_activation(){
            $page_title   = 'Wholesale Order';
            $page_content = '[wholesale_product_table]';
            
            // Use WP_Query to check for the page by title
            $page_check = new WP_Query(array(
                'post_type'   => 'page',
                'post_title'  => $page_title,
                'posts_per_page' => 1,
            ));
        
            if (!$page_check->have_posts()) {
                $page_data = array(
                    'post_type'    => 'page',
                    'post_title'   => $page_title,
                    'post_content' => $page_content,
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id(),
                );
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

            $plugin_url = plugin_dir_url( __FILE__ );

            wp_enqueue_style( 'react_base-style', $plugin_url . '../build/index.css' );
            wp_enqueue_script( 'react_base-script', $plugin_url . '../build/index.js', [ 'wp-element', 'wp-i18n', 'react-jsx-runtime' ], WPTW_VERSION, true );

            if(isset($post) && is_a($post, 'WP_Post') && has_shortcode(  $post->post_content, 'wholesale_product_table')){
                // Enqueue our JS file.
                wp_enqueue_script('wpt-script',$plugin_url . '../assets/js/wpt.js', array('jquery'), WPTW_VERSION, false);
                wp_localize_script('wpt-script', 'wpt_ajax_params', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('wpt_ajax_nonce'),
                    'cart_url' => wc_get_cart_url()
                ));

                // Enqueue our CSS file.
                wp_enqueue_style('wpt-style', $plugin_url . '../assets/css/wpt-frontend.css', array(), WPTW_VERSION);

                $selected_style = get_option( 'wptw_table_style' );

                if($selected_style === 'plugin'){
                    wp_enqueue_style('wpt-table2-style', $plugin_url . '../assets/css/wpt-table-plugin.css', array(), WPTW_VERSION);
                }else{
                    wp_enqueue_style('wpt-table1-style', $plugin_url . '../assets/css/wpt-table-default.css', array(), WPTW_VERSION);
                }
            }
        }

        public function admin_enqueue_scripts(){
            $plugin_url = plugin_dir_url( __FILE__ );

            wp_enqueue_style('wpt-admin-style', $plugin_url . '../assets/css/wpt-admin.css', array(), WPTW_VERSION);
            wp_enqueue_script('wpt-admin-script', $plugin_url . '../assets/js/wpt-admin.js', array('jquery'), WPTW_VERSION, false);

            wp_enqueue_style( 'react_base-style', $plugin_url . '../build/index.css' );
            wp_enqueue_script( 'react_base-script', $plugin_url . '../build/index.js', [ 'wp-element', 'wp-i18n', 'react-jsx-runtime' ], WPTW_VERSION, true );
        }
        
    }

endif;
