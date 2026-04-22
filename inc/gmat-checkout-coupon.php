<?php
/**
 * GMAT Checkout Coupon
 *
 * Thin layer over WooCommerce's native coupon feature for the paid subscription
 * checkout (products 7008, 7009). The heavy lifting — enabling coupons, creating
 * codes, setting rules — is done in the WooCommerce admin UI.
 *
 * What this file adds:
 *   - Safety filter so coupons stay enabled even if a plugin tries to turn them off.
 *   - `?coupon=CODE` URL parameter auto-apply at checkout (shareable promo links).
 *   - CSS polish so the native coupon toggle + form match the theme look.
 *   - Optional admin-side note printed in the coupon edit screen describing the
 *     paid-product-only convention.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('GMAT_COUPON_PAID_PRODUCT_IDS')) {
    define('GMAT_COUPON_PAID_PRODUCT_IDS', array(7008, 7009));
}

/**
 * Safety net: keep native coupons enabled at all times. WooCommerce reads
 * `woocommerce_enable_coupons` setting first; this filter is evaluated last.
 */
add_filter('woocommerce_coupons_enabled', '__return_true', 99);

/**
 * Auto-apply a coupon when the user lands on checkout with ?coupon=CODE.
 * Runs once per session per code and is a no-op if the coupon is invalid.
 */
add_action('template_redirect', 'gmat_checkout_auto_apply_coupon_from_url');
function gmat_checkout_auto_apply_coupon_from_url() {
    if (is_admin() || !function_exists('is_checkout') || !function_exists('WC')) {
        return;
    }
    if (!is_checkout() && !is_cart()) {
        return;
    }
    if (empty($_GET['coupon'])) {
        return;
    }

    $raw  = wp_unslash($_GET['coupon']);
    $code = wc_format_coupon_code(sanitize_text_field($raw));
    if ($code === '') {
        return;
    }

    $cart = WC()->cart;
    if (!$cart) {
        return;
    }
    if ($cart->has_discount($code)) {
        return;
    }

    $cart->apply_coupon($code);
}

/**
 * Enqueue the small CSS polish on checkout only.
 */
add_action('wp_enqueue_scripts', 'gmat_checkout_coupon_enqueue');
function gmat_checkout_coupon_enqueue() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    $rel  = '/css/gmat-checkout-coupon.css';
    $path = get_stylesheet_directory() . $rel;
    if (!file_exists($path)) {
        return;
    }
    wp_enqueue_style(
        'gmat-checkout-coupon',
        get_stylesheet_directory_uri() . $rel,
        array(),
        filemtime($path)
    );
}

/**
 * Admin hint on the coupon edit screen so the client remembers that codes
 * should be scoped to paid products 7008 / 7009.
 */
add_action('woocommerce_coupon_options', 'gmat_checkout_coupon_admin_hint', 5);
function gmat_checkout_coupon_admin_hint() {
    $ids = implode(', ', GMAT_COUPON_PAID_PRODUCT_IDS);
    echo '<div class="notice notice-info inline" style="margin:10px 12px;padding:8px 12px;">';
    echo '<strong>Gurutor tip:</strong> for paid-subscription promo codes, set <em>Product IDs</em> to ';
    echo '<code>' . esc_html($ids) . '</code> under the "Usage restriction" tab.';
    echo '</div>';
}
