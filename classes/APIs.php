<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class WPTW_APIs {

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the APIs
        $this->init();
    }

    /**
     * Initialize the APIs
     */
    public function init() {
        // Add API endpoints or other initialization code here
        add_action( 'rest_api_init', [ $this, 'register_api_endpoints' ] );

    }

    /**
     * Register API Endpoints. /wp-json/react-base/v1/data
     *
     * @return void
     */
    public function register_api_endpoints() {

        register_rest_route( 'wpt/v1', '/categories', [
            'methods'  => 'GET',
            'callback' => [ $this, 'wptw_get_categories' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'wpt/v1', '/settings/wholesale-table', [
            'methods'  => 'GET',
            'callback' => [$this, 'wptw_get_wholesale_table_settings'],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'wpt/v1', '/settings/discount-rules', [
            'methods'  => 'GET',
            'callback' => [$this, 'wptw_get_discount_rules_settings'],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'wpt/v1', '/settings/wholesale-table', [
            'methods'  => 'POST',
            'callback' => [$this, 'wptw_save_wholesale_table_settings'],
            'permission_callback' => function ( WP_REST_Request $request ) {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'selected_columns' => [
                    'required' => false,
                    'type'     => 'array',
                    'items'    => [
                        'type' => 'string',
                    ],
                ],
                'table_style' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'wholesale_products_opt' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'wholesale_product_category' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'wholesale_product_pp' => [
                    'required' => false,
                    'type'     => 'integer',
                ]
            ],
        ] );

        register_rest_route( 'wpt/v1', '/settings/discount-rules', [
            'methods'  => 'POST',
            'callback' => [$this, 'wptw_save_discount_rules_settings'],
            'permission_callback' => function ( WP_REST_Request $request ) {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'wpt_enabled_wholesale_discount' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'wholesale_discount_type' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'wholesale_discount_value' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'include_categories' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'exclude_categories' => [
                    'required' => false,
                    'type'     => 'string',
                ],
            ],
        ] );
        
    }

    public function wptw_get_categories(){
        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        ));
 
        return rest_ensure_response( $categories );
    }

    public function wptw_get_wholesale_table_settings() {
        $settings = array(
            'selected_columns' => get_option( 'wptw_selected_columns', [] ),
            'table_style'      => get_option( 'wptw_table_style', '' ),
            'wholesale_products_opt' => get_option( 'wptw_wholesale_products_opt', '' ),
            'wholesale_product_category' => get_option( 'wptw_wholesale_product_category', '' ),
            'wholesale_product_pp' => get_option( 'wptw_wholesale_product_pp', '' )
        );

        return rest_ensure_response( $settings );
    }

    public function wptw_get_discount_rules_settings() {
        $settings = array(
            'wpt_enabled_wholesale_discount' => get_option( 'wpt_enabled_wholesale_discount', false ),
            'wpt_wholesale_discount_type' => get_option( 'wpt_wholesale_discount_type', 'percentage' ),
            'wpt_wholesale_discount_value' => get_option( 'wpt_wholesale_discount_value', '' ),
            'wpt_include_categories' => get_option( 'wpt_include_categories', '' ),
            'wpt_exclude_categories' => get_option( 'wpt_exclude_categories', '' )
        );

        return rest_ensure_response( $settings );
    }

    public function wptw_save_wholesale_table_settings( WP_REST_Request $request ) {
        $settings = $request->get_json_params();

        if ( isset( $settings['selected_columns'] ) ) {
            update_option( 'wptw_selected_columns', $settings['selected_columns'] );
        }
        if ( isset( $settings['table_style'] ) ) {
            update_option( 'wptw_table_style', sanitize_text_field( $settings['table_style'] ) );
        }
        if ( isset( $settings['wholesale_products_opt'] ) ) {
            update_option( 'wptw_wholesale_products_opt', sanitize_text_field( $settings['wholesale_products_opt'] ) );
        }
        if ( isset( $settings['wholesale_product_category'] ) ) {
            update_option( 'wptw_wholesale_product_category', sanitize_text_field( $settings['wholesale_product_category'] ) );
        }
        if ( isset( $settings['wholesale_product_pp'] ) ) {
            update_option( 'wptw_wholesale_product_pp', intval( $settings['wholesale_product_pp'] ) );
        }

        return rest_ensure_response( [ 'status' => 'success' ] );
    }

    public function wptw_save_discount_rules_settings( WP_REST_Request $request ) {
        $settings = $request->get_json_params();

        if( isset( $settings['wpt_enabled_wholesale_discount'] ) ) {
            update_option( 'wpt_enabled_wholesale_discount', $settings['wpt_enabled_wholesale_discount'] );
        }
        if( isset( $settings['wpt_wholesale_discount_type'] ) ) {
            update_option( 'wpt_wholesale_discount_type', sanitize_text_field( $settings['wpt_wholesale_discount_type'] ) );
        }   
        if( isset( $settings['wpt_wholesale_discount_value'] ) ) {
            update_option( 'wpt_wholesale_discount_value', sanitize_text_field( $settings['wpt_wholesale_discount_value'] ) );
        }
        if( isset( $settings['wpt_include_categories'] ) ) {
            update_option( 'wpt_include_categories', sanitize_text_field( $settings['wpt_include_categories'] ) ); 
        }
        if( isset( $settings['wpt_exclude_categories'] ) ) {
            update_option( 'wpt_exclude_categories', sanitize_text_field( $settings['wpt_exclude_categories'] ) );
        }

        return rest_ensure_response( [ 'status' => 'success' ] );
    }
}