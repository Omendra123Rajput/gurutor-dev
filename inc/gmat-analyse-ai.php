<?php
/**
 * GMAT Analyse with AI — Lesson page CTA
 *
 * Adds "Analyse with AI" button on course 8112 lesson pages.
 * Shows after xAPI completion. Fetches LRS statements and sends
 * user_id + lesson title + LRS response to external AI API.
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// Constants
// ============================================================================

define('GMAT_ANALYSE_AI_COURSE_ID', 8112);
define('GMAT_ANALYSE_AI_API_TIMEOUT', 30);


// ============================================================================
// Should-load gate
// ============================================================================

function gmat_analyse_ai_should_load() {
    if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) return false;

    $course_id = function_exists('learndash_get_course_id')
        ? learndash_get_course_id(get_the_ID())
        : 0;
    if (intval($course_id) !== GMAT_ANALYSE_AI_COURSE_ID) return false;

    if (!is_user_logged_in()) return false;

    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return false;

    return true;
}


// ============================================================================
// Reverse lookup: post_id -> lesson_key, xapi_slug, label
// ============================================================================

function gmat_analyse_ai_get_lesson_meta($post_id) {
    $post_id = intval($post_id);
    if ($post_id <= 0) return false;

    $lesson_ids  = gmat_sp_get_lesson_ids();
    $lesson_keys = gmat_sp_get_lesson_keys();
    $slug_map    = gmat_sp_get_slug_map($lesson_ids);

    $found_key = null;
    foreach ($lesson_ids as $key => $id) {
        if (intval($id) === $post_id) {
            $found_key = $key;
            break;
        }
    }

    if (!$found_key) return false;

    $label = isset($lesson_keys[$found_key]['label']) ? $lesson_keys[$found_key]['label'] : get_the_title($post_id);
    $activity_url = isset($slug_map[$found_key]) ? $slug_map[$found_key] : '';

    if (empty($activity_url)) return false;

    return array(
        'lesson_key'   => $found_key,
        'activity_url' => $activity_url,
        'label'        => $label,
    );
}


// ============================================================================
// Enqueue assets
// ============================================================================

add_action('wp_enqueue_scripts', 'gmat_analyse_ai_enqueue_assets');
function gmat_analyse_ai_enqueue_assets() {
    if (!gmat_analyse_ai_should_load()) return;

    $meta = gmat_analyse_ai_get_lesson_meta(get_the_ID());
    if (!$meta) return;

    $theme_dir = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();

    wp_enqueue_style(
        'gmat-analyse-ai',
        $theme_dir . '/css/gmat-analyse-ai.css',
        array(),
        filemtime($theme_path . '/css/gmat-analyse-ai.css')
    );

    wp_enqueue_script(
        'gmat-analyse-ai',
        $theme_dir . '/js/gmat-analyse-ai.js',
        array('jquery'),
        filemtime($theme_path . '/js/gmat-analyse-ai.js'),
        true
    );

    wp_localize_script('gmat-analyse-ai', 'gmatAnalyseAI', array(
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('gmat_analyse_ai_nonce'),
        'postId'      => get_the_ID(),
        'lessonLabel' => $meta['label'],
    ));
}


// ============================================================================
// AJAX: Check completion (direct LRS query, bypasses static cache)
// ============================================================================

add_action('wp_ajax_gmat_analyse_ai_check_completion', 'gmat_analyse_ai_check_completion');
function gmat_analyse_ai_check_completion() {
    check_ajax_referer('gmat_analyse_ai_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not authenticated.'), 403);
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $meta = gmat_analyse_ai_get_lesson_meta($post_id);

    if (!$meta) {
        wp_send_json_error(array('message' => 'Lesson not found.'), 404);
    }

    $user = wp_get_current_user();
    $result = grassblade_fetch_statements(array(
        'agent_email'        => $user->user_email,
        'activity_id'        => $meta['activity_url'],
        'verb'               => 'http://adlnet.gov/expapi/verbs/completed',
        'related_activities' => true,
        'limit'              => 1,
    ));

    $completed = false;
    if (!is_wp_error($result) && !empty($result['statements'])) {
        $completed = true;
    }

    wp_send_json_success(array('completed' => $completed));
}


// ============================================================================
// AJAX: Fetch LRS statements and send to external API
// ============================================================================

add_action('wp_ajax_gmat_analyse_ai_send_data', 'gmat_analyse_ai_send_data');
function gmat_analyse_ai_send_data() {
    check_ajax_referer('gmat_analyse_ai_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not authenticated.'), 403);
    }

    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) {
        wp_send_json_error(array('message' => 'Active subscription required.'), 403);
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $meta = gmat_analyse_ai_get_lesson_meta($post_id);

    if (!$meta) {
        wp_send_json_error(array('message' => 'Lesson not found.'), 404);
    }

    // Fetch all completed statements for this activity
    $user = wp_get_current_user();
    $lrs_result = grassblade_fetch_statements(array(
        'agent_email'        => $user->user_email,
        'activity_id'        => $meta['activity_url'],
        'verb'               => 'http://adlnet.gov/expapi/verbs/completed',
        'related_activities' => true,
        'limit'              => 200,
    ));

    if (is_wp_error($lrs_result)) {
        error_log('GMAT Analyse AI: LRS fetch error — ' . $lrs_result->get_error_message());
        wp_send_json_error(array('message' => 'Failed to fetch exercise data.'), 502);
    }

    $statements = isset($lrs_result['statements']) ? $lrs_result['statements'] : array();

    if (empty($statements)) {
        wp_send_json_error(array('message' => 'No completion data found.'), 404);
    }

    // Build payload
    $payload = array(
        'user_id'        => 'wp_user_' . get_current_user_id(),
        'lesson_title'   => $meta['label'],
        'lesson_key'     => $meta['lesson_key'],
        'lrs_statements' => $statements,
    );

    // Send to external AI API (URL and key from wp-config.php)
    if (!defined('GMAT_ANALYSE_AI_API_URL') || empty(GMAT_ANALYSE_AI_API_URL)) {
        error_log('GMAT Analyse AI: GMAT_ANALYSE_AI_API_URL not defined in wp-config.php');
        wp_send_json_error(array('message' => 'AI service not configured.'), 500);
    }

    if (!defined('GMAT_ANALYSE_AI_API_KEY') || empty(GMAT_ANALYSE_AI_API_KEY)) {
        error_log('GMAT Analyse AI: GMAT_ANALYSE_AI_API_KEY not defined in wp-config.php');
        wp_send_json_error(array('message' => 'AI service not configured.'), 500);
    }

    $response = wp_remote_post(GMAT_ANALYSE_AI_API_URL, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . GMAT_ANALYSE_AI_API_KEY,
        ),
        'body'      => wp_json_encode($payload),
        'timeout'   => GMAT_ANALYSE_AI_API_TIMEOUT,
        'sslverify' => false, // ngrok tunnel
    ));

    if (is_wp_error($response)) {
        error_log('GMAT Analyse AI: API error — ' . $response->get_error_message());
        wp_send_json_error(array('message' => 'Unable to reach AI service.'), 502);
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        error_log('GMAT Analyse AI: API HTTP ' . $http_code . ': ' . wp_remote_retrieve_body($response));
        wp_send_json_error(array('message' => 'AI service error.'), 502);
    }

    wp_send_json_success(array(
        'sent'             => true,
        'message'          => 'Analysis data sent successfully.',
        'statements_count' => count($statements),
    ));
}
