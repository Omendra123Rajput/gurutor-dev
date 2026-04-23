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

// ============================================================================
// Live cart total shortcode — [gmat_cart_total]
// ------------------------------------------------------------------------
// Drop into the Elementor checkout sidebar to replace the static "$299/month"
// text. Renders the current WC cart total; updates automatically when a
// coupon is applied or removed (via WC fragments).
// ============================================================================

add_shortcode('gmat_cart_total', 'gmat_checkout_cart_total_shortcode');
function gmat_checkout_cart_total_shortcode($atts = array()) {
    if (!function_exists('WC') || !WC()->cart) {
        return '';
    }
    return gmat_checkout_cart_total_html();
}

/**
 * Build the live cart-total HTML. Wrapped in .gmat-cart-total so WC's
 * update_order_review AJAX can swap it via fragments.
 */
function gmat_checkout_cart_total_html() {
    $cart = WC()->cart;
    if (!$cart) {
        return '<span class="gmat-cart-total"></span>';
    }

    $cart->calculate_totals();

    $total_html    = $cart->get_total();
    $discount_html = '';
    $subtotal_html = '';

    if ($cart->get_discount_total() > 0) {
        $subtotal_html = '<span class="gmat-cart-total__was">' . wp_kses_post($cart->get_cart_subtotal()) . '</span>';
        $discount_html = '<span class="gmat-cart-total__savings">'
            . esc_html__('You save ', 'gurutor')
            . wp_kses_post(wc_price($cart->get_discount_total()))
            . '</span>';
    }

    return '<span class="gmat-cart-total">'
        . $subtotal_html
        . '<span class="gmat-cart-total__now">' . wp_kses_post($total_html) . '</span>'
        . $discount_html
        . '</span>';
}

/**
 * Inject fresh cart-total HTML into WC's update_order_review AJAX fragments,
 * so every coupon apply / remove redraws the sidebar total automatically.
 */
add_filter('woocommerce_update_order_review_fragments', 'gmat_checkout_cart_total_fragment');
function gmat_checkout_cart_total_fragment($fragments) {
    $fragments['.gmat-cart-total'] = gmat_checkout_cart_total_html();
    return $fragments;
}

// ============================================================================
// Coupon toggle: swap "Have a coupon?" banner for "Coupon applied · Remove"
// when one or more coupons are active on the cart.
// ============================================================================

/**
 * Build the .woocommerce-form-coupon-toggle inner HTML based on applied coupons.
 */
function gmat_checkout_coupon_toggle_html() {
    $cart = function_exists('WC') ? WC()->cart : null;
    $applied = ($cart && method_exists($cart, 'get_applied_coupons')) ? $cart->get_applied_coupons() : array();

    ob_start();
    ?>
    <div class="woocommerce-form-coupon-toggle">
        <?php if (!empty($applied)) : ?>
            <div class="woocommerce-info gmat-coupon-applied" role="status">
                <span class="gmat-coupon-applied__label"><?php esc_html_e('Coupon applied:', 'gurutor'); ?></span>
                <?php foreach ($applied as $code) : ?>
                    <span class="gmat-coupon-applied__pill">
                        <strong class="gmat-coupon-applied__code"><?php echo esc_html(strtoupper($code)); ?></strong>
                        <a href="#"
                           class="gmat-coupon-applied__remove"
                           data-coupon="<?php echo esc_attr($code); ?>"
                           aria-label="<?php echo esc_attr(sprintf(__('Remove coupon %s', 'gurutor'), $code)); ?>">
                            &times; <?php esc_html_e('Remove', 'gurutor'); ?>
                        </a>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="woocommerce-info" role="status">
                <?php esc_html_e('Have a coupon?', 'gurutor'); ?>
                <a href="#"
                   role="button"
                   aria-label="<?php esc_attr_e('Enter your coupon code', 'gurutor'); ?>"
                   aria-controls="woocommerce-checkout-form-coupon"
                   aria-expanded="false"
                   class="showcoupon"><?php esc_html_e('Click here to enter your code', 'gurutor'); ?></a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Fragment swap so the toggle refreshes on every update_checkout.
 */
add_filter('woocommerce_update_order_review_fragments', 'gmat_checkout_coupon_toggle_fragment');
function gmat_checkout_coupon_toggle_fragment($fragments) {
    $fragments['.woocommerce-form-coupon-toggle'] = gmat_checkout_coupon_toggle_html();
    return $fragments;
}

/**
 * Localize remove-coupon nonce + wc-ajax URL for the inline remover script.
 */
add_action('wp_enqueue_scripts', 'gmat_checkout_coupon_remover_inline', 20);
function gmat_checkout_coupon_remover_inline() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    $handle = wp_script_is('wc-checkout', 'enqueued') ? 'wc-checkout' : 'jquery';

    $wc_ajax = class_exists('WC_AJAX') ? WC_AJAX::get_endpoint('remove_coupon') : '';

    wp_localize_script($handle, 'gmatCouponRemove', array(
        'ajax'  => esc_url_raw($wc_ajax),
        'nonce' => wp_create_nonce('remove-coupon'),
    ));

    $inline = <<<JS
jQuery(function($){
    $(document.body).on('click', '.gmat-coupon-applied__remove', function(e){
        e.preventDefault();
        var code = $(this).data('coupon');
        if (!code || !window.gmatCouponRemove) return;
        $.ajax({
            type: 'POST',
            url: gmatCouponRemove.ajax,
            data: { security: gmatCouponRemove.nonce, coupon: code },
            dataType: 'html'
        }).always(function(){
            $(document.body).trigger('update_checkout');
        });
    });
});
JS;

    wp_add_inline_script($handle, $inline);
}
