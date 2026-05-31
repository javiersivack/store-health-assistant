<?php
/**
 * Plugin Name: Store Health Assistant for WooCommerce
 * Description: Finds hidden WooCommerce issues that may be costing sales.
 * Version: 0.1.0
 * Author: Javier Sivack
 * Text Domain: store-health-assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHA_VERSION', '0.1.0');
define('SHA_PLUGIN_FILE', __FILE__);
define('SHA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHA_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SHA_PLUGIN_DIR . 'includes/class-sha-admin-page.php';
require_once SHA_PLUGIN_DIR . 'includes/class-sha-product-scanner.php';

SHA_Admin_Page::init();