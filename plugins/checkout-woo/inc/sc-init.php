<?php

namespace SoftceryCheckout;

class Init
{
    public static function run()
    {
        self::load_dependencies();
        self::load_scripts();

        \SoftceryCheckout\Checkout::run();
    }

    private static function load_dependencies()
    {
        require_once plugin_dir_path(__FILE__) . 'sc-checkout.php';
    }

    private static function load_scripts()
    {
        wp_enqueue_style('sc-styles', plugin_dir_url(__DIR__) . 'assets/css/styles.css', [], '1.0');
        wp_enqueue_script('sc-scripts', plugin_dir_url(__DIR__) . 'assets/js/scripts.js', [], '1.0');

        wp_localize_script('sc-scripts', 'sc', array(
            'ajaxurl'   => admin_url('admin-ajax.php')
        ));
    }
}
