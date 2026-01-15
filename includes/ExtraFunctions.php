<?php

if( ! defined('ABSPATH') ) exit;


if( ! function_exists('wptw_get_product_categories') ){
    function wptw_get_product_categories(){
        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        ));
        return $categories;
    }
}

