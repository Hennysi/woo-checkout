<?php
if (!defined('ABSPATH')) {
    exit;
}

$products_in_cart = \SoftceryCheckout\Checkout::sc_get_products();
$summary_cart = \SoftceryCheckout\Checkout::sc_get_summary();
$gateway_cart = \SoftceryCheckout\Checkout::sc_get_gateway();

do_action('sc_checkout_header'); ?>

<div class="sc-checkout-container">
    <h1><?php _e('Checkout', 'sc-checkout'); ?></h1>

    <div class="sc-checkout-notify">
        <p></p>
    </div>

    <div class="sc-checkout-form">
        <form method="POST" class="sc-checkout">
            <div class="input-wrapper">
                <label for="sc-name"><?php _e('Name', 'sc-checkout') ?></label>
                <input type="text" name="sc-name" id="sc-name" required>
            </div>

            <div class="input-wrapper">
                <label for="sc-address"><?php _e('Address', 'sc-checkout') ?></label>
                <input type="text" name="sc-address" id="sc-address" required>
            </div>

            <div class="input-wrapper">
                <label for="sc-email"><?php _e('Email', 'sc-checkout') ?></label>
                <input type="email" name="sc-email" id="sc-email" required>
            </div>
        </form>

        <div class="sc-checkout-summary">
            <div class="sc-checkout-block">
                <div class="sc-products">
                    <?php if ($products_in_cart): ?>
                        <?php foreach ($products_in_cart as $product): ?>
                            <div class="sc-product">
                                <div class="sc-product-thumb">
                                    <?php echo $product['product_image'] ?>
                                </div>

                                <div class="sc-product-info">
                                    <p class="name"><?php echo $product['product_name'] ?> <span class="counter">(<?php echo $product['product_quantity'] ?> pcs.)</span></p>
                                    <p class="price"><?php echo $product['product_total_price'] ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sc-checkout-block">
                <div class="sc-gateways">
                    <?php if ($gateway_cart): ?>
                        <?php foreach ($gateway_cart as $index => $gateway): ?>
                            <div class="sc-gateway">
                                <input type="radio" name="sc-gateway" id="sc-<?php echo $gateway['id'] ?>" value="<?php echo $gateway['id'] ?>" <?php echo $index == 0 ? 'checked' : null ?>>
                                <label for="sc-<?php echo $gateway['id'] ?>"><?php echo $gateway['title'] ?></label>

                                <?php if ($gateway['gateway_fields']): ?>
                                    <div class="sc-gateway-field" style="display: none">
                                        <?php $gateway['gateway_fields']->payment_fields(); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sc-checkout-block">
                <div class="sc-total">
                    <p class="sc-total-line sc-summary-subtotal"><?php _e('Subtotal') ?>: <span class="value"><?php echo $summary_cart['subtotal'] ?></span></p>
                    <p class="sc-total-line sc-summary-discount"><?php _e('Discount') ?>: <span class="value"><?php echo $summary_cart['discount_total'] ?></span></p>
                    <p class="sc-total-line sc-summary-coupon <?php echo $summary_cart['applied_coupon'] ? null : 'sc-hide' ?>"><?php _e('Coupon') ?>: <span class="remove-coupon"><?php _e('remove', 'sc-checkout') ?></span> <span class="value"><?php echo $summary_cart['applied_coupon'] ?></p>
                    <p class="sc-total-line sc-summary-total"><?php _e('Total') ?>: <span class="value"><?php echo $summary_cart['total'] ?></span></p>
                </div>
            </div>

            <div class="sc-checkout-block">
                <div class="sc-coupon">
                    <div class="input-wrapper">
                        <input type="text" name="sc-coupon" id="sc-coupon" placeholder="<?php _e('Enter your coupon here...', 'sc-checkout') ?>">
                        <a href="#" class="button"><?php _e('Apply Coupon') ?></a>
                    </div>
                </div>
            </div>

            <div class="sc-checkout-block">
                <div class="sc-pay">
                    <a href="#" class="button"><?php _e('Pay') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php do_action('sc_checkout_footer'); ?>