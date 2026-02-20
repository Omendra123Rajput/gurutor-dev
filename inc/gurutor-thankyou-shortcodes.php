<?php
/**
 * Thank You Page Shortcodes
 * 
 * Add this code to your theme's functions.php or include it in a separate file
 * 
 * Shortcodes:
 * [gurutor_activation_code] - Displays random activation code (only for Test 2 users on first purchase) or Go to Course CTA
 * [gurutor_order_details] - Displays order details (Order ID, Course, Amount Paid)
 */

if (!defined('ABSPATH')) exit;

/**
 * Shortcode to display activation code or Go to Course CTA
 * 
 * Shows activation code ONLY if:
 * 1. User has completed Test 2 (has bucket data)
 * 2. AND this is their FIRST purchase of a paid plan (only 1 order with product 7008 or 7009)
 * 
 * Shows "Go to Course" CTA if:
 * - User doesn't have Test 2 bucket data (normal purchase)
 * - OR user has already purchased a paid plan before (e.g., upgrading from month-to-month to 6-month)
 * 
 * Usage: [gurutor_activation_code]
 */
function gurutor_activation_code_shortcode($atts) {
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="gurutor-activation-wrapper"><p>Please log in to view your details.</p></div>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Check if user has Test 2 bucket data (completed Test 2)
    $has_test2_bucket_data = false;
    if (function_exists('grassblade_get_latest_bucket_data_test2')) {
        $latest_buckets_test2 = grassblade_get_latest_bucket_data_test2($current_user->user_email);
        $has_test2_bucket_data = !empty($latest_buckets_test2);
    }
    
    // Check if user has previously purchased a paid plan
    // We count the total number of paid orders - if more than 1, user has previous purchases
    $has_previous_paid_purchase = false;
    $paid_product_ids = array(7008, 7009); // Month to Month and 6-month Package
    
    if (function_exists('wc_get_orders')) {
        // Get all completed/processing orders for this user
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ));
        
        // Count how many orders contain paid products
        $paid_orders_count = 0;
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                if (in_array($product_id, $paid_product_ids)) {
                    $paid_orders_count++;
                    break; // Count this order once, move to next order
                }
            }
        }
        
        // If more than 1 paid order exists, user has previous purchases
        // (1 order = current/first purchase, >1 = has previous purchases)
        $has_previous_paid_purchase = ($paid_orders_count > 1);
    }
    
    // Show activation code ONLY if:
    // 1. User has Test 2 bucket data
    // 2. AND user has NOT previously purchased a paid plan (this is their first paid order)
    $show_activation_code = $has_test2_bucket_data && !$has_previous_paid_purchase;
    
    if ($show_activation_code) {
        return gurutor_render_activation_code();
    } else {
        return gurutor_render_go_to_course_cta();
    }
}
add_shortcode('gurutor_activation_code', 'gurutor_activation_code_shortcode');


/**
 * Render the activation code box (for first-time Test 2 users)
 */
function gurutor_render_activation_code() {
    // Define the 4 activation codes
    $activation_codes = array(
        '162491870',
        '854632974',
        '552397461',
        '997821664'
    );
    
    // Get a random activation code
    $random_index = array_rand($activation_codes);
    $activation_code = $activation_codes[$random_index];
    
    // Generate unique ID for this instance
    $unique_id = 'activation-code-' . wp_rand(1000, 9999);
    
    ob_start();
    ?>

    <div class="gurutor-activation-wrapper">
        <h3>View Your Detailed Test Results</h3>
        <p>Please paste this code into the Test Score module to access your detailed results.</p>
        
        <div class="gurutor-activation-code-box">
            <span class="gurutor-activation-code" id="<?php echo esc_attr($unique_id); ?>"><?php echo esc_html($activation_code); ?></span>
            <button class="gurutor-copy-btn" onclick="gurutorCopyCode('<?php echo esc_attr($unique_id); ?>', this)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                <span>Copy</span>
            </button>
        </div>
    </div>
    
    <script>
    function gurutorCopyCode(elementId, button) {
        var codeElement = document.getElementById(elementId);
        var code = codeElement.textContent;
        
        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(function() {
                // Success
                button.classList.add('copied');
                button.querySelector('span').textContent = 'Copied!';
                
                setTimeout(function() {
                    button.classList.remove('copied');
                    button.querySelector('span').textContent = 'Copy';
                }, 2000);
            }).catch(function() {
                // Fallback
                gurutorFallbackCopy(code, button);
            });
        } else {
            // Fallback for older browsers
            gurutorFallbackCopy(code, button);
        }
    }
    
    function gurutorFallbackCopy(text, button) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            button.classList.add('copied');
            button.querySelector('span').textContent = 'Copied!';
            
            setTimeout(function() {
                button.classList.remove('copied');
                button.querySelector('span').textContent = 'Copy';
            }, 2000);
        } catch (err) {
            console.error('Copy failed', err);
        }
        
        document.body.removeChild(textArea);
    }
    </script>
    <?php
    
    return ob_get_clean();
}


/**
 * Render the "Go to Course" CTA (for normal users or users with previous purchases)
 */
function gurutor_render_go_to_course_cta() {
    // Get the Gurutor's Recommended GMAT Program course URL dynamically
    $course_url = '';
    
    // Method 1: Try to get course by slug
    $course_query = new WP_Query(array(
        'post_type' => 'sfwd-courses',
        'name' => 'gurutors-recommended-gmat-program',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if ($course_query->have_posts()) {
        $course_query->the_post();
        $course_url = get_permalink();
        wp_reset_postdata();
    }
    
    // Method 2: Fallback - try using get_page_by_path
    if (empty($course_url)) {
        $course = get_page_by_path('gurutors-recommended-gmat-program', OBJECT, 'sfwd-courses');
        if ($course) {
            $course_url = get_permalink($course->ID);
        }
    }
    
    // Method 3: Final fallback - construct URL manually
    if (empty($course_url)) {
        $course_url = home_url('/courses/gurutors-recommended-gmat-program/');
    }
    
    ob_start();
    ?>
    
    <div class="gurutor-goto-course-wrapper">
        <!-- <h3>🎉 Thank You for Your Purchase!</h3> -->
        <p>Your subscription is now active. Start your GMAT preparation journey now.</p>
        
        <a href="<?php echo esc_url($course_url); ?>" class="gurutor-goto-course-btn">
            Go to Course
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </a>
    </div>
    <?php
    
    return ob_get_clean();
}


/**
 * Shortcode to display order details on thank you page
 * Usage: [gurutor_order_details]
 */
function gurutor_order_details_shortcode($atts) {
    // Get order ID from URL parameter (WooCommerce passes this on thank you page)
    $order_id = isset($_GET['order-received']) ? absint($_GET['order-received']) : 0;
    
    // Also check for 'order' parameter as fallback
    if (!$order_id) {
        $order_id = isset($_GET['order']) ? absint($_GET['order']) : 0;
    }
    
    // Try to get from query var
    if (!$order_id) {
        $order_id = absint(get_query_var('order-received'));
    }
    
    // If still no order ID, try to get the most recent order for the current user
    if (!$order_id && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $customer_orders = wc_get_orders(array(
            'customer' => $current_user->ID,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        if (!empty($customer_orders)) {
            $order_id = $customer_orders[0]->get_id();
        }
    }
    
    // If no order found, return message
    if (!$order_id) {
        return '<div class="gurutor-order-details-wrapper"><p>Order details not available.</p></div>';
    }
    
    // Get the order
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return '<div class="gurutor-order-details-wrapper"><p>Order not found.</p></div>';
    }
    
    // Get order details
    $order_number = $order->get_order_number();
    $order_total = $order->get_total();
    $currency_symbol = get_woocommerce_currency_symbol();
    
    // Get product/course name
    $course_name = '';
    $items = $order->get_items();
    foreach ($items as $item) {
        $course_name = $item->get_name();
        break; // Get first item
    }
    
    ob_start();
    ?>    
    <div class="gurutor-order-details-wrapper">
        <div class="gurutor-order-detail-row">
            <span class="gurutor-order-label">Order ID:</span>
            <span class="gurutor-order-value">#<?php echo esc_html($order_number); ?></span>
        </div>
        
        <div class="gurutor-order-detail-row">
            <span class="gurutor-order-label">Course:</span>
            <span class="gurutor-order-value"><?php echo esc_html($course_name); ?></span>
        </div>
        
        <div class="gurutor-order-detail-row">
            <span class="gurutor-order-label">Amount Paid:</span>
            <span class="gurutor-order-value amount"><?php echo esc_html($currency_symbol . number_format($order_total, 0)); ?></span>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('gurutor_order_details', 'gurutor_order_details_shortcode');