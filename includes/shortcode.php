<?php

if( ! defined('ABSPATH') ) exit;

if( ! class_exists('WPTW_Shortcode') ){

    class WPTW_Shortcode{

        public function __construct(){
            $this->init();
        }

        public function init(){

            if(!shortcode_exists('wholesale_product_table')){
                // Shortcode.
                add_shortcode('wholesale_product_table', array($this, 'display_product_table'));
            }
            
        }

        /**
         * The shortcode outputs the search box, category filter, table structure, and pagination container.
         */
        public function display_product_table(){
            $categories = get_terms(array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
            ));
            $selected_columns = get_option('wpt_selected_columns', array('image', 'product_name', 'sku', 'category', 'price', 'in_stock', 'quantity', 'add_to_cart'));
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
                        if (! is_wp_error($categories) && ! empty($categories)) {
                            foreach ($categories as $cat) {
                                echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <table class="wholesale-product-table">
                <thead>
                    <tr>
                        <?php if (in_array('image', $selected_columns)) : ?>
                            <th class="wpt-table-head">Image</th>
                        <?php endif; ?>
                        <?php if (in_array('product_name', $selected_columns)) : ?>
                            <th class="wpt-table-head">Product Name</th>
                        <?php endif; ?>
                        <?php if (in_array('sku', $selected_columns)) : ?>
                            <th class="wpt-table-head">SKU</th>
                        <?php endif; ?>
                        <?php if (in_array('category', $selected_columns)) : ?>
                            <th class="wpt-table-head">Category</th>
                        <?php endif; ?>
                        <?php if (in_array('price', $selected_columns)) : ?>
                            <th class="wpt-table-head">Price</th>
                        <?php endif; ?>
                        <?php if (in_array('in_stock', $selected_columns)) : ?>
                            <th class="wpt-table-head">Stock Status</th>
                        <?php endif; ?>
                        <?php if (in_array('quantity', $selected_columns)) : ?>
                            <th class="wpt-table-head">Quantity</th>
                        <?php endif; ?>
                        <?php if (in_array('add_to_cart', $selected_columns)) : ?>
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

    }

    new WPTW_Shortcode();
}