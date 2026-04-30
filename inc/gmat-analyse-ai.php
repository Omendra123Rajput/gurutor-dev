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
define('GMAT_ANALYSE_AI_MAX_REPORT_BYTES', 51200); // 50 KB hard cap on coaching_report
define('GMAT_ANALYSE_AI_META_PREFIX', '_gmat_analyse_ai_report_');


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
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('gmat_analyse_ai_nonce'),
        'postId'          => get_the_ID(),
        'lessonLabel'     => $meta['label'],
        'hasCachedReport' => false, // caching disabled — always fetch fresh
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

    error_log('[AAI] send_data start | user=' . get_current_user_id() . ' | lesson_key=' . $meta['lesson_key'] . ' | post_id=' . $post_id);

    // Fetch all statements for this activity (completed + answered)
    $user = wp_get_current_user();
    $base_filters = array(
        'agent_email'        => $user->user_email,
        'activity_id'        => $meta['activity_url'],
        'related_activities' => true,
        'limit'              => 200,
    );

    // Completed statements (completion/duration data)
    $completed_result = grassblade_fetch_statements(array_merge($base_filters, array(
        'verb' => 'http://adlnet.gov/expapi/verbs/completed',
    )));

    // Answered statements (scores, answers, per-question data)
    $answered_result = grassblade_fetch_statements(array_merge($base_filters, array(
        'verb' => 'http://adlnet.gov/expapi/verbs/answered',
    )));

    if (is_wp_error($completed_result) && is_wp_error($answered_result)) {
        error_log('GMAT Analyse AI: LRS fetch error — ' . $completed_result->get_error_message());
        wp_send_json_error(array('message' => 'Failed to fetch exercise data.'), 502);
    }

    $completed_stmts = (!is_wp_error($completed_result) && isset($completed_result['statements'])) ? $completed_result['statements'] : array();
    $answered_stmts  = (!is_wp_error($answered_result) && isset($answered_result['statements']))   ? $answered_result['statements']  : array();
    $statements = array_merge($completed_stmts, $answered_stmts);

    if (empty($statements)) {
        error_log('[AAI] no LRS statements');
        wp_send_json_error(array('message' => 'No completion data found.'), 404);
    }

    error_log('[AAI] LRS counts | completed=' . count($completed_stmts) . ' | answered=' . count($answered_stmts) . ' | total=' . count($statements));

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
        error_log('[AAI] wp_remote_post WP_Error: ' . $response->get_error_message());
        wp_send_json_error(array('message' => 'Unable to reach AI service.'), 502);
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $body      = wp_remote_retrieve_body($response);
    $body_len  = strlen((string) $body);

    error_log('[AAI] HTTP=' . $http_code . ' | body_bytes=' . $body_len);

    if ($http_code !== 200) {
        error_log('[AAI] non-200 body sample: ' . substr((string) $body, 0, 2000));
        wp_send_json_error(array('message' => 'AI service error.'), 502);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log('[AAI] json_decode failed | last_error=' . json_last_error_msg() . ' | body sample: ' . substr((string) $body, 0, 2000));
        wp_send_json_error(array('message' => 'Invalid AI response.'), 502);
    }

    error_log('[AAI] decoded keys=' . implode(',', array_keys($data)));

    $cr_top = isset($data['coaching_report'])                    ? $data['coaching_report']                    : null;
    $cr_nest = isset($data['chatbot_response']['coaching_report']) ? $data['chatbot_response']['coaching_report'] : null;
    error_log('[AAI] coaching_report top: ' . (is_string($cr_top) ? 'str(' . strlen($cr_top) . ')' : gettype($cr_top))
        . ' | nested: '                       . (is_string($cr_nest) ? 'str(' . strlen($cr_nest) . ')' : gettype($cr_nest)));

    $report = gmat_analyse_ai_normalize_report($data);

    error_log('[AAI] normalized | coaching_html_bytes=' . strlen((string) $report['coaching_report_html'])
        . ' | weaknesses=' . count($report['weaknesses']));

    if (empty($report['coaching_report_html'])) {
        error_log('[AAI] EMPTY coaching_report_html | full body sample: ' . substr((string) $body, 0, 2000));
    }

    wp_send_json_success(array(
        'cached'    => false,
        'report'    => $report,
        'cached_at' => time(),
    ));
}


// ============================================================================
// HELPER: Normalize raw API response into safe, render-ready array
// ============================================================================

function gmat_analyse_ai_normalize_report($raw) {
    $total     = isset($raw['total_questions'])     ? absint($raw['total_questions'])     : 0;
    $attempted = isset($raw['attempted_questions']) ? absint($raw['attempted_questions']) : 0;
    $correct   = isset($raw['correct'])             ? absint($raw['correct'])             : 0;
    $incorrect = isset($raw['incorrect'])           ? absint($raw['incorrect'])           : 0;

    $accuracy_pct = 0;
    if (isset($raw['accuracy']) && is_numeric($raw['accuracy'])) {
        $accuracy_pct = (int) round(floatval($raw['accuracy']) * 100);
    } elseif ($attempted > 0) {
        $accuracy_pct = (int) round(($correct / $attempted) * 100);
    }
    if ($accuracy_pct < 0)   $accuracy_pct = 0;
    if ($accuracy_pct > 100) $accuracy_pct = 100;

    $weaknesses = array();
    if (!empty($raw['weaknesses']) && is_array($raw['weaknesses'])) {
        foreach ($raw['weaknesses'] as $w) {
            if (!is_array($w)) continue;

            $questions = array();
            if (!empty($w['questions']) && is_array($w['questions'])) {
                foreach ($w['questions'] as $q) {
                    if (!is_array($q)) continue;
                    $questions[] = array(
                        'question_id'    => isset($q['question_id'])    ? sanitize_text_field($q['question_id'])    : '',
                        'student_answer' => isset($q['student_answer']) ? sanitize_text_field($q['student_answer']) : '',
                        'correct_answer' => isset($q['correct_answer']) ? sanitize_text_field($q['correct_answer']) : '',
                    );
                }
            }

            $weaknesses[] = array(
                'topic'           => isset($w['topic'])           ? sanitize_text_field($w['topic'])    : '',
                'subtopic'        => isset($w['subtopic'])        ? sanitize_text_field($w['subtopic']) : '',
                'incorrect_count' => isset($w['incorrect_count']) ? absint($w['incorrect_count'])      : 0,
                'questions'       => $questions,
            );
        }
    }

    $coaching_md = '';
    if (!empty($raw['coaching_report']) && is_string($raw['coaching_report'])) {
        $coaching_md = $raw['coaching_report'];
    } elseif (!empty($raw['chatbot_response']['coaching_report']) && is_string($raw['chatbot_response']['coaching_report'])) {
        $coaching_md = $raw['chatbot_response']['coaching_report'];
    }

    if (strlen($coaching_md) > GMAT_ANALYSE_AI_MAX_REPORT_BYTES) {
        $coaching_md = substr($coaching_md, 0, GMAT_ANALYSE_AI_MAX_REPORT_BYTES);
    }

    $coaching_html = $coaching_md !== '' ? gmat_analyse_ai_format_markdown($coaching_md) : '';

    error_log('[AAI] normalize | md_bytes=' . strlen($coaching_md) . ' | html_bytes=' . strlen($coaching_html));

    return array(
        'lesson_key'           => isset($raw['lesson_key']) ? sanitize_text_field($raw['lesson_key']) : '',
        'total_questions'      => $total,
        'attempted'            => $attempted,
        'correct'              => $correct,
        'incorrect'            => $incorrect,
        'accuracy_pct'         => $accuracy_pct,
        'weaknesses'           => $weaknesses,
        'coaching_report_html' => $coaching_html,
    );
}


// ============================================================================
// HELPER: Markdown -> safe HTML (## / ### headers + reuses chatbox formatter)
// ============================================================================

function gmat_analyse_ai_format_markdown($md) {
    if (!is_string($md) || $md === '') return '';

    // Normalize line endings
    $md = str_replace(array("\r\n", "\r"), "\n", $md);

    // Repair adjacent header mash-ups like "Heading###" -> "Heading\n###"
    $md = preg_replace('/([^\n#])(#{2,6}\s)/', "$1\n$2", $md);

    // Split on ATX-style headers (## or ###); preserve hash count via capture
    $parts = preg_split('/^(#{2,6})\s+(.+?)\s*$/m', $md, -1, PREG_SPLIT_DELIM_CAPTURE);

    $html  = '';
    $count = count($parts);

    for ($i = 0; $i < $count; $i++) {
        $chunk = $parts[$i];

        // Header tuple: hashes at $i+1, title text at $i+2 (delim-capture pattern)
        if (isset($parts[$i + 1]) && isset($parts[$i + 2]) && preg_match('/^#{2,6}$/', $parts[$i + 1])) {
            // Body chunk (before next header)
            if (function_exists('gmat_chatbox_format_reply')) {
                $html .= gmat_chatbox_format_reply($chunk);
            } else {
                $html .= '<p>' . esc_html($chunk) . '</p>';
            }

            $level = strlen($parts[$i + 1]); // 2 -> h3, 3 -> h4, 4+ -> h5
            $tag   = ($level === 2) ? 'h3' : (($level === 3) ? 'h4' : 'h5');
            $title = trim($parts[$i + 2]);
            $html .= '<' . $tag . ' class="gmat-aai-h gmat-aai-h--' . $level . '">' . esc_html($title) . '</' . $tag . '>';

            $i += 2; // skip the two captured groups
            continue;
        }

        // Trailing/standalone body chunk
        if (function_exists('gmat_chatbox_format_reply')) {
            $html .= gmat_chatbox_format_reply($chunk);
        } else {
            $html .= '<p>' . esc_html($chunk) . '</p>';
        }
    }

    // Allow our header tags + the standard chatbox-style HTML
    $allowed = wp_kses_allowed_html('post');
    $allowed['h3'] = array('class' => true);
    $allowed['h4'] = array('class' => true);
    $allowed['h5'] = array('class' => true);

    return wp_kses($html, $allowed);
}
