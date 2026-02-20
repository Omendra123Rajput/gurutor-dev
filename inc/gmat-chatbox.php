<?php
/**
 * GMAT AI Chatbox
 *
 * Floating AI assistant widget for paid course users.
 * Proxies messages from the WordPress frontend to the FastAPI backend on AWS EC2.
 *
 * Architecture:
 *   Chat UI (jQuery) → WP AJAX → FastAPI (AWS EC2) → AWS Bedrock (Claude) → Response → UI
 *
 * Dependencies:
 *   - inc/free-trial-grassblade-xapi.php  (gurutor_user_has_active_paid_access)
 *   - wp-config.php constants: GMAT_CHATBOX_API_URL, GMAT_CHATBOX_API_KEY
 *
 * API Spec for AI team:
 *   Endpoint: POST {GMAT_CHATBOX_API_URL}
 *   Request:  { user_id, session_id, message, key }
 *   Response: { reply, status, key, timestamp }
 */

if (!defined('ABSPATH')) exit;


// ============================================================================
// CONSTANTS
// ============================================================================

if (!defined('GMAT_CHATBOX_COURSE_ID'))      define('GMAT_CHATBOX_COURSE_ID', 8112);
if (!defined('GMAT_CHATBOX_RATE_LIMIT'))     define('GMAT_CHATBOX_RATE_LIMIT', 20);       // messages per window
if (!defined('GMAT_CHATBOX_RATE_WINDOW'))    define('GMAT_CHATBOX_RATE_WINDOW', 60);      // seconds
if (!defined('GMAT_CHATBOX_API_TIMEOUT'))    define('GMAT_CHATBOX_API_TIMEOUT', 30);      // seconds
if (!defined('GMAT_CHATBOX_MAX_MSG_LENGTH')) define('GMAT_CHATBOX_MAX_MSG_LENGTH', 2000); // characters


// ============================================================================
// HELPER: Format plain-text AI response into structured HTML
// ============================================================================

/**
 * Convert plain-text AI response to well-structured HTML.
 *
 * Handles:
 *   - \n newlines into paragraph breaks
 *   - Bullet points (•, -, *) into <ul><li> lists
 *   - Numbered lists (1., 2.) into <ol><li> lists
 *   - **bold** and *italic* markdown
 *   - Lines ending with ":" treated as bold headings
 *   - Consecutive blank lines collapsed
 *
 * If the reply already contains HTML tags (<p>, <ul>, <li>, <br>, <ol>),
 * it is returned as-is (the API already formatted it).
 *
 * @param string $text  Raw AI reply text
 * @return string       HTML-formatted reply
 */
function gmat_chatbox_format_reply($text) {
    if (empty($text)) return '';

    // If the reply already has HTML block-level tags, return as-is
    if (preg_match('/<(p|ul|ol|li|br|div|h[1-6]|table|blockquote)\b/i', $text)) {
        return $text;
    }

    // Normalize line endings
    $text = str_replace(array("\r\n", "\r"), "\n", $text);

    // Split into lines
    $lines = explode("\n", $text);
    $html  = '';
    $in_ul = false;
    $in_ol = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Skip empty lines — close any open lists
        if ($trimmed === '') {
            if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
            if ($in_ol) { $html .= '</ol>'; $in_ol = false; }
            continue;
        }

        // Check for bullet point: •, -, * (but not ** which is bold marker)
        $is_bullet = false;
        $bullet_content = '';
        if (preg_match('/^[•\-\*]\s+(.+)$/', $trimmed, $m) && !preg_match('/^\*\*/', $trimmed)) {
            $is_bullet = true;
            $bullet_content = $m[1];
        }

        // Check for numbered list: 1., 2., etc.
        $is_numbered = false;
        $numbered_content = '';
        if (preg_match('/^\d+[\.\)]\s+(.+)$/', $trimmed, $m)) {
            $is_numbered = true;
            $numbered_content = $m[1];
        }

        if ($is_bullet) {
            // Close ordered list if switching
            if ($in_ol) { $html .= '</ol>'; $in_ol = false; }
            if (!$in_ul) { $html .= '<ul>'; $in_ul = true; }
            $html .= '<li>' . gmat_chatbox_format_inline($bullet_content) . '</li>';
        } elseif ($is_numbered) {
            // Close unordered list if switching
            if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
            if (!$in_ol) { $html .= '<ol>'; $in_ol = true; }
            $html .= '<li>' . gmat_chatbox_format_inline($numbered_content) . '</li>';
        } else {
            // Close any open lists before paragraph text
            if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
            if ($in_ol) { $html .= '</ol>'; $in_ol = false; }

            // Lines ending with ":" that look like section headers (e.g., "Explanation:", "Why Other Options Are Wrong:")
            if (preg_match('/^(.+):$/', $trimmed, $m) && mb_strlen($trimmed) < 80) {
                $html .= '<p><strong>' . esc_html($m[1]) . ':</strong></p>';
            } else {
                $html .= '<p>' . gmat_chatbox_format_inline($trimmed) . '</p>';
            }
        }
    }

    // Close any remaining open lists
    if ($in_ul) $html .= '</ul>';
    if ($in_ol) $html .= '</ol>';

    return $html;
}

/**
 * Format inline markdown: **bold**, *italic*, `code`
 *
 * @param string $text
 * @return string
 */
function gmat_chatbox_format_inline($text) {
    // Escape HTML entities first for safety
    $text = esc_html($text);

    // **bold** → <strong>bold</strong>
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    // *italic* → <em>italic</em> (but not inside already-processed strong tags)
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);

    // `code` → <code>code</code>
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

    return $text;
}


// ============================================================================
// GUARD: Should chatbox be active on this page?
// ============================================================================

/**
 * Check if the chatbox should render/enqueue on the current page.
 * Returns true only for logged-in paid users viewing course 8112.
 *
 * @return bool
 */
function gmat_chatbox_should_load() {
    // Must be on LearnDash course singular page
    if (!is_singular('sfwd-courses')) return false;

    // Must be course 8112
    if (get_the_ID() !== GMAT_CHATBOX_COURSE_ID) return false;

    // Must be logged in
    if (!is_user_logged_in()) return false;

    // Must have active paid access
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return false;

    return true;
}


// ============================================================================
// ENQUEUE ASSETS
// ============================================================================

function gmat_chatbox_enqueue_assets() {
    if (!gmat_chatbox_should_load()) return;

    $dir = get_stylesheet_directory();

    // Use file modification time for cache busting (theme version is static)
    $css_ver = filemtime($dir . '/css/gmat-chatbox.css');
    $js_ver  = filemtime($dir . '/js/gmat-chatbox.js');

    wp_enqueue_style(
        'gmat-chatbox',
        get_stylesheet_directory_uri() . '/css/gmat-chatbox.css',
        array(),
        $css_ver
    );

    wp_enqueue_script(
        'gmat-chatbox',
        get_stylesheet_directory_uri() . '/js/gmat-chatbox.js',
        array('jquery'),
        $js_ver,
        true
    );

    $user = wp_get_current_user();

    wp_localize_script('gmat-chatbox', 'gmatChatbox', array(
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('gmat_chatbox_nonce'),
        'userId'       => 'wp_user_' . get_current_user_id(),
        'userName'     => esc_js($user->first_name ? $user->first_name : $user->display_name),
        'maxMsgLength' => GMAT_CHATBOX_MAX_MSG_LENGTH,
    ));
}
add_action('wp_enqueue_scripts', 'gmat_chatbox_enqueue_assets');


// ============================================================================
// RENDER: Output chatbox HTML in wp_footer
// ============================================================================

function gmat_chatbox_render() {
    if (!gmat_chatbox_should_load()) return;
    ?>

    <!-- GMAT AI Chatbox — Overlay (mobile) -->
    <div class="gmat-cb__overlay" id="gmat-cb-overlay" aria-hidden="true"></div>

    <!-- GMAT AI Chatbox — FAB Button -->
    <button class="gmat-cb__fab" id="gmat-cb-fab" type="button" aria-label="Open AI Chat Assistant" aria-expanded="false" aria-controls="gmat-cb">
        <!-- Chat bubble icon (clean, minimal) -->
        <!-- Graduation cap + AI sparkle icon: instantly signals GMAT AI assistant -->
        <svg class="gmat-cb__fab-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" fill="#fff">
            <!-- Mortarboard flat top (diamond) -->
            <path d="M12 3L2 8l10 5 10-5-10-5z"/>
            <!-- Cap body — fills in below the board on receive -->
            <path d="M7 11.2v3.8c0 1.7 2.24 3 5 3s5-1.3 5-3v-3.8l-5 2.5-5-2.5z"/>
            <!-- Tassel pole (thin rect right side) -->
            <path d="M19.4 8h1.2v5h-1.2z"/>
            <!-- Tassel ball -->
            <circle cx="20" cy="14.2" r="1.3"/>
            <!-- AI sparkle — 4-point star top-right signals intelligence/AI -->
            <path d="M20.5 1l.55 1.7 1.7.55-1.7.55-.55 1.7-.55-1.7-1.7-.55 1.7-.55z"/>
        </svg>
        <!-- Close X icon -->
        <svg class="gmat-cb__fab-close" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
        <span class="gmat-cb__fab-badge" id="gmat-cb-badge" style="display:none;" aria-hidden="true">0</span>
    </button>

    <!-- GMAT AI Chatbox — Chat Panel -->
    <div class="gmat-cb" id="gmat-cb" role="dialog" aria-label="GMAT AI Assistant" aria-hidden="true">

        <!-- Header -->
        <div class="gmat-cb__header">
            <div class="gmat-cb__header-info">
                <div class="gmat-cb__avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                        <circle cx="20" cy="4" r="1.5" opacity="0.85"/>
                        <circle cx="22" cy="6.5" r="0.8" opacity="0.6"/>
                    </svg>
                </div>
                <div class="gmat-cb__header-text">
                    <h3 class="gmat-cb__title">GMAT AI Assistant</h3>
                    <span class="gmat-cb__status" id="gmat-cb-status">Online</span>
                </div>
            </div>
            <div class="gmat-cb__header-actions">
                <button class="gmat-cb__btn-new" id="gmat-cb-new" type="button" aria-label="New conversation" title="New conversation">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                    </svg>
                </button>
                <button class="gmat-cb__btn-close" id="gmat-cb-close" type="button" aria-label="Close chat">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages area -->
        <div class="gmat-cb__messages" id="gmat-cb-messages" role="log" aria-live="polite" aria-label="Chat messages">
            <!-- Messages rendered by JS -->
        </div>

        <!-- Typing indicator -->
        <div class="gmat-cb__typing" id="gmat-cb-typing" style="display:none;" aria-hidden="true">
            <div class="gmat-cb__typing-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                    <circle cx="20" cy="4" r="1.5" opacity="0.85"/>
                    <circle cx="22" cy="6.5" r="0.8" opacity="0.6"/>
                </svg>
            </div>
            <div class="gmat-cb__typing-content">
                <div class="gmat-cb__typing-dots">
                    <span></span><span></span><span></span>
                </div>
                <span class="gmat-cb__typing-text">AI is thinking…</span>
            </div>
        </div>

        <!-- Input area -->
        <div class="gmat-cb__input-area">
            <div class="gmat-cb__input-wrap">
                <textarea
                    class="gmat-cb__input"
                    id="gmat-cb-input"
                    placeholder="Ask me anything about GMAT..."
                    rows="1"
                    maxlength="<?php echo esc_attr(GMAT_CHATBOX_MAX_MSG_LENGTH); ?>"
                    aria-label="Type your message"
                ></textarea>
                <button class="gmat-cb__send" id="gmat-cb-send" type="button" aria-label="Send message" disabled>
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
            <div class="gmat-cb__char-count" id="gmat-cb-charcount" aria-hidden="true">
                <span id="gmat-cb-charcount-num">0</span>/<?php echo esc_html(GMAT_CHATBOX_MAX_MSG_LENGTH); ?>
            </div>
        </div>

    </div>

    <?php
}
add_action('wp_footer', 'gmat_chatbox_render', 50);


// ============================================================================
// AJAX HANDLER: Proxy message to external AI backend
// ============================================================================

function gmat_chatbox_send_message() {
    // 1. Verify nonce (CSRF protection)
    check_ajax_referer('gmat_chatbox_nonce', 'nonce');

    // 2. Authentication check
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to use the AI assistant.'), 403);
    }

    // 3. Paid access authorization
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) {
        wp_send_json_error(array('message' => 'An active subscription is required to use the AI assistant.'), 403);
    }

    // 4. API configuration check
    if (!defined('GMAT_CHATBOX_API_URL') || empty(GMAT_CHATBOX_API_URL)) {
        error_log('GMAT Chatbox: GMAT_CHATBOX_API_URL not defined in wp-config.php');
        wp_send_json_error(array('message' => 'Chat service is not configured. Please contact support.'), 500);
    }

    if (!defined('GMAT_CHATBOX_API_KEY') || empty(GMAT_CHATBOX_API_KEY)) {
        error_log('GMAT Chatbox: GMAT_CHATBOX_API_KEY not defined in wp-config.php');
        wp_send_json_error(array('message' => 'Chat service is not configured. Please contact support.'), 500);
    }

    // 5. Rate limiting (transient-based, per-user)
    $user_id  = get_current_user_id();
    $rate_key = 'gmat_cb_rate_' . $user_id;
    $rate_count = (int) get_transient($rate_key);

    if ($rate_count >= GMAT_CHATBOX_RATE_LIMIT) {
        wp_send_json_error(array(
            'message' => 'You\'re sending messages too quickly. Please wait a moment.',
            'code'    => 'rate_limited',
        ), 429);
    }

    // Increment rate counter
    set_transient($rate_key, $rate_count + 1, GMAT_CHATBOX_RATE_WINDOW);

    // 6. Input sanitization
    $message    = isset($_POST['message'])    ? sanitize_textarea_field(wp_unslash($_POST['message']))    : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id']))     : '';

    // 7. Input validation
    if (empty(trim($message))) {
        wp_send_json_error(array('message' => 'Message cannot be empty.'), 400);
    }

    if (mb_strlen($message) > GMAT_CHATBOX_MAX_MSG_LENGTH) {
        wp_send_json_error(array('message' => 'Message is too long. Maximum ' . GMAT_CHATBOX_MAX_MSG_LENGTH . ' characters.'), 400);
    }

    if (empty($session_id) || strlen($session_id) > 64) {
        wp_send_json_error(array('message' => 'Invalid session. Please refresh the page.'), 400);
    }

    // 8. Build request payload for external FastAPI backend
    $request_body = array(
        'user_id'    => 'wp_user_' . $user_id,
        'session_id' => $session_id,
        'message'    => $message,
        'key'        => GMAT_CHATBOX_API_KEY,
    );

    // 9. Call external API via wp_remote_post
    $response = wp_remote_post(GMAT_CHATBOX_API_URL, array(
        'headers'   => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
        'body'      => wp_json_encode($request_body),
        'timeout'   => GMAT_CHATBOX_API_TIMEOUT,
        'sslverify' => true,
    ));

    // 10. Handle network/transport errors
    if (is_wp_error($response)) {
        error_log('GMAT Chatbox API Error: ' . $response->get_error_message());
        wp_send_json_error(array(
            'message' => 'Unable to reach the AI service. Please try again.',
            'code'    => 'network_error',
        ), 502);
    }

    // 11. Parse response
    $http_code = wp_remote_retrieve_response_code($response);
    $body      = wp_remote_retrieve_body($response);
    $data      = json_decode($body, true);

    // 12. Validate response structure
    if ($http_code !== 200) {
        error_log('GMAT Chatbox API HTTP ' . $http_code . ': ' . $body);
        wp_send_json_error(array(
            'message' => 'The AI service is temporarily unavailable. Please try again.',
            'code'    => 'api_error',
        ), 502);
    }

    if (!is_array($data) || empty($data['status']) || $data['status'] !== 'success') {
        error_log('GMAT Chatbox API invalid response: ' . $body);
        wp_send_json_error(array(
            'message' => 'The AI service returned an unexpected response. Please try again.',
            'code'    => 'api_error',
        ), 502);
    }

    if (empty($data['reply'])) {
        error_log('GMAT Chatbox API empty reply: ' . $body);
        wp_send_json_error(array(
            'message' => 'The AI service returned an empty response. Please try again.',
            'code'    => 'api_error',
        ), 502);
    }

    // 13. Validate API key in response (mutual authentication)
    if (!isset($data['key']) || $data['key'] !== GMAT_CHATBOX_API_KEY) {
        error_log('GMAT Chatbox API key mismatch in response');
        wp_send_json_error(array(
            'message' => 'Authentication error with AI service. Please contact support.',
            'code'    => 'auth_error',
        ), 502);
    }

    // 14. Format plain-text reply into structured HTML, then sanitize
    $formatted_reply = gmat_chatbox_format_reply($data['reply']);

    wp_send_json_success(array(
        'reply'     => wp_kses_post($formatted_reply),
        'timestamp' => isset($data['timestamp']) ? sanitize_text_field($data['timestamp']) : current_time('c'),
    ));
}
add_action('wp_ajax_gmat_chatbox_send', 'gmat_chatbox_send_message');
// Note: No wp_ajax_nopriv_ hook — only authenticated users can use the chatbox
