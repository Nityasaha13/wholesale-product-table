<?php

if (! defined('ABSPATH')) exit;

if (! class_exists('WPTW_Shortcode')) {

    class WPTW_Shortcode
    {
        public function __construct()
        {
            $this->init();
        }

        public function init()
        {
            if (!shortcode_exists('wholesale_product_table')) {
                // Shortcode.
                add_shortcode('wholesale_product_table', array($this, 'display_product_table'));
            }
        }


        public function display_product_table_react()
        {
            return "<div id='wpt-product-table'></div>";
        }

        /**
         * The shortcode outputs the search box, category filter, table structure, and pagination container.
         */
        public function display_product_table()
        {
            $categories = wptw_get_product_categories();
            
            $selected_columns = get_option('wptw_selected_columns');
            $selected_category_opt = get_option('wptw_wholesale_products_opt') ?? '';
            $selected_categories = get_option('wptw_wholesale_product_category') ?? '';

            $exp_cats = explode(',', $selected_categories);
            $cat_count = array_filter($exp_cats, 'trim');
            $cat_count = count($cat_count);

            // print_r($exp_cats);
            // die;

            ob_start();
            ?>

            <div class="wpt-container-wraper">
                <div class="wpt-controls">
                    <div class="search">
                        <input type="text" id="wpt-search" placeholder="Search products..." />
                    </div>
                    <?php if( $selected_category_opt === 'all' || $cat_count >= 2) : ?>
                    <div class="filter">
                        <select id="wpt-category-filter">
                            <option value="all">All Categories</option>
                            <?php
                            if (! is_wp_error($categories) && ! empty($categories)) {
                                foreach ($categories as $cat) {
                                    if ( $selected_category_opt === 'all') {
                                        // Show all categories
                                        echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                                    } else {
                                        // Show only selected categories
                                        if (in_array($cat->term_id, $exp_cats) ) {
                                            echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                                        }
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="wpt-sort">
                        <label for="wpt-sort">Sort by:</label>
                        <select name="wpt-sort-select" id="wpt-sort-select">
                            <option value="default">Default</option>
                            <option value="price_asc">Price - Low to High</option>
                            <option value="price_desc">Price - High to Low</option>
                            <option value="name_asc">Name - A to Z</option>
                            <option value="name_desc">Name - Z to A</option>
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
                            <?php if (in_array('price', $selected_columns)) : ?>
                                <th class="wpt-table-head">Price</th>
                            <?php endif; ?>
                            <?php if (in_array('category', $selected_columns)) : ?>
                                <th class="wpt-table-head">Category</th>
                            <?php endif; ?>
                            <?php if (in_array('sku', $selected_columns)) : ?>
                                <th class="wpt-table-head">SKU</th>
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
            </div>

            <?php
            return ob_get_clean();
        }
    }

}
