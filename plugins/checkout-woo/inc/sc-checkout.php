<?php

namespace SoftceryCheckout;

class Checkout
{
    public static function run()
    {
        self::init_actions();
    }

    public static function init_actions()
    {
        add_filter('template_include', [__CLASS__, 'sc_checkout_reinit']);

        add_action('sc_checkout_header', [__CLASS__, 'sc_checkout_header']);
        add_action('sc_checkout_footer', [__CLASS__, 'sc_checkout_footer']);

        add_action('wp_ajax_sc_proceed_pay', [__CLASS__, 'sc_proceed_payment']);
        add_action('wp_ajax_nopriv_sc_proceed_pay', [__CLASS__, 'sc_proceed_payment']);

        add_action('wp_ajax_sc_apply_coupon', [__CLASS__, 'sc_apply_coupon']);
        add_action('wp_ajax_nopriv_sc_apply_coupon', [__CLASS__, 'sc_apply_coupon']);

        add_action('wp_ajax_sc_remove_coupon', [__CLASS__, 'sc_remove_coupon']);
        add_action('wp_ajax_nopriv_sc_remove_coupon', [__CLASS__, 'sc_remove_coupon']);
    }

    /**
     * Overrides the default checkout template
     *
     * @return string
     */
    public static function sc_checkout_reinit($template)
    {
        if (is_checkout() && !is_wc_endpoint_url()) {
            $checkout_template = plugin_dir_path(__DIR__) . 'templates/checkout.php';

            if (file_exists($checkout_template)) {
                return $checkout_template;
            }
        }
        return $template;
    }

    public static function sc_checkout_header()
    {
        $header_type = apply_filters('sc_checkout_header_type', 'default');

        if ($header_type == 'default') {
            get_header();
        } else {
            get_header($header_type);
        }
    }

    public static function sc_checkout_footer()
    {
        $footer_type = apply_filters('sc_checkout_footer_type', 'default');

        if ($footer_type == 'default') {
            get_footer();
        } else {
            get_footer($footer_type);
        }
    }

    /**
     * Receiving items that are in the cart
     *
     * @return array
     */
    public static function sc_get_products()
    {
        $response = [];

        if (WC()->cart) {
            $cart_items = WC()->cart->get_cart();

            foreach ($cart_items as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];

                $product_quantity = $cart_item['quantity'];
                $product_id = $product->get_id();
                $product_name = $product->get_name();
                $product_image = get_the_post_thumbnail_url($product_id);
                $product_image = $product->get_image();
                $product_price = $product->get_price();
                $product_total_price = $product_price * $product_quantity;

                $response[] = [
                    'product_id' => $product_id,
                    'product_name' => $product_name,
                    'product_image' => $product_image,
                    'product_quantity' => $product_quantity,
                    'product_price' => $product_price,
                    'product_total_price' => wc_price($product_total_price),
                ];
            }
        }

        return $response;
    }

    /**
     * Retrieving the cart total
     *
     * @return array
     */
    public static function sc_get_summary()
    {
        if (WC()->cart) {
            $subtotal = WC()->cart->get_subtotal();

            $discount_total = WC()->cart->get_discount_total();

            $total = WC()->cart->get_total('edit');

            $formatted_subtotal = wc_price($subtotal);
            $formatted_discount_total = wc_price($discount_total);
            $formatted_total = wc_price($total);

            $applied_coupon = WC()->cart->get_applied_coupons();

            return [
                'subtotal' => $formatted_subtotal,
                'discount_total' => $formatted_discount_total,
                'total' => $formatted_total,
                'applied_coupon' => $applied_coupon ? $applied_coupon[0] : null
            ];
        }
    }

    /**
     * Retrieving woo active gateways
     *
     * @return array
     */
    public static function sc_get_gateway()
    {
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

        $available_methods = [];

        foreach ($payment_gateways as $gateway) {
            $available_methods[] = [
                'id' => $gateway->id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'gateway_fields' => $gateway->has_fields() ? $gateway : false
            ];
        }

        return $available_methods;
    }

    /**
     * AJAX order processing
     *
     * @return array
     */
    public static function sc_proceed_payment()
    {
        $name = isset($_POST['sc-name']) ? $_POST['sc-name'] : null;
        $address = isset($_POST['sc-address']) ? $_POST['sc-address'] : null;
        $email = isset($_POST['sc-email']) ? $_POST['sc-email'] : null;
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;

        $current_user = get_current_user_id() ?: null;
        $items = self::sc_get_products();
        $user_info = [
            'name' => $name,
            'address' => $address,
            'email' => $email,
        ];

        $order_id = self::sc_create_order($current_user, $items, $user_info, $payment_method);

        if ($order_id) {
            $order = wc_get_order($order_id);

            if ($payment_method === 'ppcp') {
                $paypal_gateway = WC()->payment_gateways()->payment_gateways()['ppcp'];
                $payment_result = $paypal_gateway->process_payment($order_id);

                if (!empty($payment_result['result']) && $payment_result['result'] === 'success') {
                    $payment_url = $payment_result['redirect'];
                    $success = true;
                } else {
                    $payment_url = false;
                    $success = false;
                }
            } else {
                $payment_url = wc_get_endpoint_url('order-received', $order_id, wc_get_checkout_url());
                $is_thank_you = true;
            }

            $success = true;
        } else {
            $success = false;
            $payment_url = false;
        }

        $response = [
            'success' => $success,
            'payment_url' => $payment_url,
            'is_thank_you' => $is_thank_you
        ];

        wp_send_json($response);
    }

    /**
     * Create new order in woo dashboard
     * 
     * @param int|null $user_id User ID if the user is authorized. Null if the user is a guest.
     * @param array    $items List of items to be added to the order.
     * @param array    $user_info User information, including email and address.
     * @param string   $payment_method Method of Payment.
     *
     * @return int
     */
    public static function sc_create_order($user_id = null, $items, $user_info, $payment_method)
    {
        $order = wc_create_order();

        if ($user_id) {
            $order->set_customer_id($user_id);
        } else {
            $order->set_customer_id(0);
            $order->set_billing_email($user_info['email']);
        }

        $order->set_billing_address_1($user_info['address']);
        $order->set_shipping_address_1($user_info['address']);

        $order->set_billing_first_name($user_info['name']);
        $order->set_shipping_first_name($user_info['name']);

        foreach ($items as $item) {
            $product = wc_get_product($item['product_id']);

            if ($product) {
                $order->add_product($product, $item['product_quantity']);
            }
        }

        $applied_coupon = WC()->cart->get_applied_coupons();
        if ($applied_coupon) {
            $order->apply_coupon($applied_coupon[0]);
        }

        $order->set_payment_method($payment_method);
        $order->set_payment_method_title($payment_method);

        $order->set_status('pending');

        $order->calculate_totals();
        $order->save();

        return $order->get_id();
    }

    /**
     * AJAX add coupon
     *
     * @return array
     */
    public static function sc_apply_coupon()
    {
        $coupon = isset($_POST['coupon']) ? $_POST['coupon'] : null;

        if ($coupon && WC()->cart->get_cart_contents_count() > 0) {
            WC()->cart->apply_coupon($coupon);
            WC()->cart->calculate_totals();

            if (WC()->cart->has_discount($coupon)) {
                $success = true;
                $message = __('Coupon applyed!', 'sc-checkout');

                $summary = self::sc_get_summary();
            } else {
                $success = false;
                $message = __('Coupon not found!', 'sc-checkout');
            }
        } else {
            $success = false;
            $message = __('Enter coupon', 'sc-checkout');
        }

        $response = [
            'success' => $success,
            'message' => $message,
            'summary' => $summary
        ];

        wp_send_json($response);
    }

    /**
     * AJAX remove coupon
     *
     * @return array
     */
    public static function sc_remove_coupon()
    {
        if (WC()->cart) {
            $applied_coupons = WC()->cart->get_applied_coupons();

            foreach ($applied_coupons as $coupon_code) {
                WC()->cart->remove_coupon($coupon_code);
            }

            WC()->cart->calculate_totals();

            $success = true;
            $message = __('Coupon was removed!', 'sc-checkout');
            $summary = self::sc_get_summary();
        } else {
            $success = false;
            $message = __('Something went wrong!', 'sc-checkout');
        }

        $response = [
            'success' => $success,
            'message' => $message,
            'summary' => $summary
        ];

        wp_send_json($response);
    }
}
