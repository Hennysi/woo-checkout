<?php
/*
Plugin Name: WooCommerce Checkout
Description: This plugin replace defaul checkout to custom in WooCommerce.
Version: 1.0
Author: Softcery Test Task
License: GPL2
Requires Plugins: woocommerce
*/



// HELPS //

// For change header use filter
// add_filter('sc_checkout_header_type', ...);

// For change footer use filter
// add_filter('sc_checkout_footer_type', ...);




///////////


if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'inc/sc-init.php';

function sc_init()
{
    \SoftceryCheckout\Init::run();
}
add_action('plugins_loaded', 'SC_init');
