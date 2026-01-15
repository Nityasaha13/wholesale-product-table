<?php

/**
 * Plugin Name:  Wholesale Order Table And Discounts
 * Description: Displays a wholesale product table via a shortcode with configurable columns, AJAX add-to-cart, variable product dropdowns (with updated price and image), pagination, search, and category filtering.
 * Version: 1.0.0
 * Author: Nitya Saha
 * Requires plugins: woocommerce
 * Author URI: https://profiles.wordpress.org/nityasaha/
 * Text Domain: wholesale-product-table
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('WPTW_VERSION', '1.0.0');
define('WPTP_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));


class WholesaleProductTable
{
    private $plugin_basename;

    public function __construct()
    {
        // Load the main class
        require_once WPTP_PLUGIN_BASE_PATH . 'classes/Main.php';

        // Initialize the main class
        $main = new WPTW_Main();


        $this->plugin_basename = plugin_basename(__FILE__);
        add_filter('plugin_action_links_' . $this->plugin_basename, array($this, 'setting_page_link'));
        add_filter('plugin_row_meta', array($this, 'addon_plugin_links'), 10, 2);
    }


    public function setting_page_link($links){
        // Add your custom links
        $settings_link = '<a href="' . admin_url('admin.php?page=wholesale-product-table-settings') . '">' . __('Settings', 'wholesale-product-table') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function addon_plugin_links($links, $file){
        if ($file === $this->plugin_basename) {
            $links[] = __('<a href="https://buymeacoffee.com/nityasaha" style="font-weight:bold;color:#00d300;font-size:15px;">Donate</a>', 'wholesale-product-table');
            $links[] = __('Made with Love ❤️', 'wholesale-product-table');
        }

        return $links;
    }
}

// Initialize the plugin
$react_base = new WholesaleProductTable();
