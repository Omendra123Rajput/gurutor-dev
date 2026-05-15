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
define('GMAT_ANALYSE_AI_API_TIMEOUT', 300);
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

    $current_user  = wp_get_current_user();
    $student_name  = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
    $date_format   = get_option('date_format');
    if (empty($date_format)) $date_format = 'F j, Y';

    wp_localize_script('gmat-analyse-ai', 'gmatAnalyseAI', array(
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('gmat_analyse_ai_nonce'),
        'postId'          => get_the_ID(),
        'lessonLabel'     => $meta['label'],
        'lessonKey'       => $meta['lesson_key'],
        'studentName'     => $student_name,
        'reportDate'      => date_i18n($date_format),
        'reportTypeLbl'   => __('Performance Report', 'gurutor'),
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

    $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';

    if (empty($session_id) || strlen($session_id) > 64) {
        wp_send_json_error(array('message' => 'Invalid session. Please refresh the page.'), 400);
    }

    error_log('[AAI] send_data start | user=' . get_current_user_id() . ' | lesson_key=' . $meta['lesson_key'] . ' | post_id=' . $post_id . ' | session_id=' . $session_id);

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
        'session_id'     => $session_id,
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
// HELPER: Markdown -> safe HTML (hero banner, tables, sections, prose)
// ============================================================================

function gmat_analyse_ai_format_markdown($md) {
    if (!is_string($md) || $md === '') return '';

    // Normalize line endings
    $md = str_replace(array("\r\n", "\r"), "\n", $md);

    // Repair adjacent header mash-ups like "Heading###" -> "Heading\n###"
    $md = preg_replace('/([^\n#])(#{2,6}\s)/', "$1\n$2", $md);

    $html = '';

    // ----------------------------------------------------------------------
    // 1. Extract hero PASS/FAIL banner from the very top
    // ----------------------------------------------------------------------
    if (preg_match('/^[\s\n]*(PASS|FAIL)[ \t]*\n+([^\n]+(?:\n(?!##|---)[^\n]+)*)/i', $md, $hm)) {
        $status     = strtoupper(trim($hm[1]));
        $msg_block  = trim($hm[2]);
        $score      = '';
        $threshold  = '';

        // Pull "N / M" and optional "(Threshold: X)"
        if (preg_match('/(\d+)\s*\/\s*(\d+)/', $msg_block, $sm)) {
            $score = $sm[1] . ' / ' . $sm[2];
        }
        if (preg_match('/\(Threshold:\s*(\d+)\)/i', $msg_block, $tm)) {
            $threshold = 'Threshold: ' . $tm[1];
        }

        // Strip score/threshold pattern from the descriptive message
        $msg = preg_replace('/(\d+)\s*\/\s*(\d+)\s*(?:\(Threshold:\s*\d+\))?/i', '', $msg_block);
        $msg = trim($msg, " \t\n.");

        $cls = ($status === 'PASS') ? 'gmat-aai-hero--pass' : 'gmat-aai-hero--fail';

        $html .= '<div class="gmat-aai-hero ' . esc_attr($cls) . '">'
              .    '<span class="gmat-aai-hero__pill">' . esc_html($status) . '</span>'
              .    '<p class="gmat-aai-hero__msg">' . esc_html($msg) . '</p>';
        if ($score !== '' || $threshold !== '') {
            $html .= '<div class="gmat-aai-hero__score-wrap">';
            if ($score !== '') {
                $html .= '<span class="gmat-aai-hero__score">' . esc_html($score) . '</span>';
            }
            if ($threshold !== '') {
                $html .= '<span class="gmat-aai-hero__threshold">' . esc_html($threshold) . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Remove the hero block from the source
        $md = preg_replace('/^[\s\n]*(PASS|FAIL)[ \t]*\n+([^\n]+(?:\n(?!##|---)[^\n]+)*)/i', '', $md, 1);
    }

    // ----------------------------------------------------------------------
    // 2. Strip stray "---" horizontal rules (we use section borders instead)
    // ----------------------------------------------------------------------
    $md = preg_replace('/^\s*---+\s*$/m', '', $md);

    // ----------------------------------------------------------------------
    // 3. Split on ## / ### headers and process each chunk
    // ----------------------------------------------------------------------
    $parts = preg_split('/^(#{2,6})\s+(.+?)\s*$/m', $md, -1, PREG_SPLIT_DELIM_CAPTURE);
    $count = count($parts);

    for ($i = 0; $i < $count; $i++) {
        $chunk = $parts[$i];

        if (isset($parts[$i + 1]) && isset($parts[$i + 2]) && preg_match('/^#{2,6}$/', $parts[$i + 1])) {
            $html .= gmat_analyse_ai_render_body_chunk($chunk);

            $level = strlen($parts[$i + 1]);
            $title = trim($parts[$i + 2]);
            $html .= gmat_analyse_ai_render_section_header($title, $level);

            $i += 2;
            continue;
        }

        $html .= gmat_analyse_ai_render_body_chunk($chunk);
    }

    // ----------------------------------------------------------------------
    // 4. Sanitize: allow tables + custom span classes
    // ----------------------------------------------------------------------
    return wp_kses($html, gmat_analyse_ai_allowed_html());
}


// ============================================================================
// HELPER: Allowed HTML tag/attr table for coaching report rendering
// Single source of truth — shared by format_markdown() and PDF handler.
// ============================================================================

function gmat_analyse_ai_allowed_html() {
    $allowed = wp_kses_allowed_html('post');
    $extra_attrs = array('class' => true);
    foreach (array('h3', 'h4', 'h5', 'div', 'span', 'p', 'table', 'thead', 'tbody', 'tr', 'th', 'td') as $tag) {
        if (!isset($allowed[$tag])) $allowed[$tag] = array();
        $allowed[$tag] = array_merge($allowed[$tag], $extra_attrs);
    }
    return $allowed;
}


// ============================================================================
// HELPER: Render a section header (## or ###) with reference-style classes
// ============================================================================

function gmat_analyse_ai_render_section_header($title, $level) {
    $title = trim($title);
    if ($title === '') return '';

    if ($level >= 3) {
        // ### -> sub-section
        return '<h4 class="gmat-aai-subsection">' . esc_html($title) . '</h4>';
    }

    // ## -> primary section. Detect numbered prefix or "INSTRUCTOR SUMMARY"
    if (preg_match('/^(\d+)\s*[-—–]\s*(.+)$/u', $title, $m)) {
        return '<h3 class="gmat-aai-section gmat-aai-section--numbered">'
             .   '<span class="gmat-aai-section__num">' . esc_html($m[1]) . '</span>'
             .   '<span class="gmat-aai-section__title">' . esc_html(trim($m[2])) . '</span>'
             . '</h3>';
    }

    if (stripos($title, 'INSTRUCTOR SUMMARY') !== false) {
        return '<h3 class="gmat-aai-section gmat-aai-section--instructor">'
             .   '<span class="gmat-aai-section__title">' . esc_html($title) . '</span>'
             . '</h3>';
    }

    return '<h3 class="gmat-aai-section">'
         .   '<span class="gmat-aai-section__title">' . esc_html($title) . '</span>'
         . '</h3>';
}


// ============================================================================
// HELPER: Render a body chunk -> tables become real tables, prose via chatbox
// ============================================================================

function gmat_analyse_ai_render_body_chunk($text) {
    if (!is_string($text) || trim($text) === '') return '';

    $lines = explode("\n", $text);
    $total = count($lines);
    $out   = '';
    $i     = 0;

    while ($i < $total) {
        $line    = $lines[$i];
        $trimmed = trim($line);

        // Detect a markdown table: a "|...|" header row followed by a
        // "|---|---|..." separator row.
        if ($trimmed !== '' && substr($trimmed, 0, 1) === '|'
            && isset($lines[$i + 1])
            && preg_match('/^\s*\|[\s\-:|]+\|\s*$/', $lines[$i + 1])) {

            $table_lines = array($trimmed);
            $j = $i + 1;
            // Capture the separator + all subsequent body rows
            while ($j < $total) {
                $tl = trim($lines[$j]);
                if ($tl === '' || substr($tl, 0, 1) !== '|') break;
                $table_lines[] = $tl;
                $j++;
            }

            // Flush any pending prose first
            $out .= '';
            $out .= gmat_analyse_ai_render_table($table_lines);

            $i = $j;
            continue;
        }

        // Otherwise, accumulate prose lines until next table / end
        $prose_buf = array();
        while ($i < $total) {
            $cur = $lines[$i];
            $ct  = trim($cur);
            if ($ct !== '' && substr($ct, 0, 1) === '|'
                && isset($lines[$i + 1])
                && preg_match('/^\s*\|[\s\-:|]+\|\s*$/', $lines[$i + 1])) {
                break;
            }
            $prose_buf[] = $cur;
            $i++;
        }

        $prose = implode("\n", $prose_buf);
        if (trim($prose) !== '' && function_exists('gmat_chatbox_format_reply')) {
            $out .= gmat_chatbox_format_reply($prose);
        } elseif (trim($prose) !== '') {
            $out .= '<p>' . esc_html($prose) . '</p>';
        }
    }

    return $out;
}


// ============================================================================
// HELPER: Render a markdown table block as styled HTML
// ============================================================================

function gmat_analyse_ai_render_table($lines) {
    if (count($lines) < 2) return '';

    $header_line = array_shift($lines);
    array_shift($lines); // drop separator row

    $headers = gmat_analyse_ai_split_table_row($header_line);
    if (empty($headers)) return '';

    $thead = '<thead><tr>';
    foreach ($headers as $h) {
        $thead .= '<th>' . gmat_analyse_ai_format_cell($h) . '</th>';
    }
    $thead .= '</tr></thead>';

    $tbody = '<tbody>';
    foreach ($lines as $row_line) {
        $cells = gmat_analyse_ai_split_table_row($row_line);
        if (empty($cells)) continue;

        // Pad to header width
        while (count($cells) < count($headers)) $cells[] = '';

        // Determine row tint based on last cell content
        $last      = trim(end($cells));
        $row_class = '';
        if ($last === '✓' || $last === '✔') {
            $row_class = ' class="gmat-aai-tr--pass"';
        } elseif ($last === '✗' || $last === '✘' || $last === 'x' || $last === 'X') {
            $row_class = ''; // neutral, marker alone provides red emphasis
        }

        $tbody .= '<tr' . $row_class . '>';
        foreach ($cells as $c) {
            $tbody .= '<td>' . gmat_analyse_ai_format_cell($c) . '</td>';
        }
        $tbody .= '</tr>';
    }
    $tbody .= '</tbody>';

    return '<div class="gmat-aai-table-wrap"><table class="gmat-aai-table">' . $thead . $tbody . '</table></div>';
}


// ============================================================================
// HELPER: Split a "| a | b | c |" markdown row into trimmed cells
// ============================================================================

function gmat_analyse_ai_split_table_row($line) {
    $line = trim($line);
    if ($line === '' || substr($line, 0, 1) !== '|') return array();

    // Strip leading & trailing pipes
    $line = preg_replace('/^\|/', '', $line);
    $line = preg_replace('/\|\s*$/', '', $line);

    $cells = explode('|', $line);
    foreach ($cells as $k => $c) {
        $cells[$k] = trim($c);
    }
    return $cells;
}


// ============================================================================
// HELPER: Format a single table cell — inline markdown + colorized marks
// ============================================================================

function gmat_analyse_ai_format_cell($cell) {
    if ($cell === '') return '';

    if (function_exists('gmat_chatbox_format_inline')) {
        $html = gmat_chatbox_format_inline($cell);
    } else {
        $html = esc_html($cell);
    }

    // Colorize result symbols (operate on already-escaped HTML)
    $html = str_replace(
        array('✓', '✔', '✗', '✘'),
        array(
            '<span class="gmat-aai-mark gmat-aai-mark--ok">✓</span>',
            '<span class="gmat-aai-mark gmat-aai-mark--ok">✓</span>',
            '<span class="gmat-aai-mark gmat-aai-mark--fail">✗</span>',
            '<span class="gmat-aai-mark gmat-aai-mark--fail">✗</span>',
        ),
        $html
    );

    return $html;
}


// ============================================================================
// AJAX: Download report as PDF (streams directly to browser, no disk write)
// ============================================================================

add_action('wp_ajax_gmat_analyse_ai_download_pdf', 'gmat_analyse_ai_download_pdf');
function gmat_analyse_ai_download_pdf() {
    if (!wp_doing_ajax()) {
        wp_die('Forbidden', 'Forbidden', array('response' => 403));
    }

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

    // Client-supplied HTML — re-sanitised below. Length-capped at 2x upstream cap
    // to allow for HTML tag overhead beyond GMAT_ANALYSE_AI_MAX_REPORT_BYTES (50 KB).
    $report_html_raw = isset($_POST['report_html']) ? wp_unslash($_POST['report_html']) : '';
    if (!is_string($report_html_raw) || trim($report_html_raw) === '') {
        wp_send_json_error(array('message' => 'No report to download. Please re-analyse first.'), 400);
    }

    if (strlen($report_html_raw) > (GMAT_ANALYSE_AI_MAX_REPORT_BYTES * 2)) {
        $report_html_raw = substr($report_html_raw, 0, GMAT_ANALYSE_AI_MAX_REPORT_BYTES * 2);
    }

    // Defence in depth — re-run wp_kses() with the same allowed-tag table the
    // modal renderer uses. Client cannot inject <script>, event handlers, etc.
    $coaching_html = wp_kses($report_html_raw, gmat_analyse_ai_allowed_html());

    if (trim($coaching_html) === '') {
        wp_send_json_error(array('message' => 'Report content was empty after sanitisation.'), 400);
    }

    // Server-derived metadata — NEVER read these from the POST body.
    $current_user = wp_get_current_user();
    $student_name = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
    $date_format  = get_option('date_format');
    if (empty($date_format)) $date_format = 'F j, Y';
    $report_date  = date_i18n($date_format);

    $lesson_label = (string) $meta['label'];
    $lesson_key   = (string) $meta['lesson_key'];

    // Logo: theme-local SVG, embedded INLINE (not via <img>). Dompdf 3.x renders
    // inline <svg> via php-svg-lib. Try multiple candidate paths in case the
    // filename differs between local + staging deploys.
    $theme_path = get_stylesheet_directory();
    $logo_candidates = array(
        $theme_path . '/GURUTOR-logo (1).svg',
        $theme_path . '/GURUTOR-logo.svg',
        $theme_path . '/images/GURUTOR-logo.svg',
        $theme_path . '/images/gurutor-logo.svg',
    );

    $logo_svg = '';
    foreach ($logo_candidates as $candidate) {
        if (file_exists($candidate)) {
            $raw = file_get_contents($candidate);
            if (is_string($raw) && $raw !== '') {
                // Strip any XML prolog / DOCTYPE — Dompdf only wants the <svg> element.
                $raw = preg_replace('/^\s*<\?xml[^>]*\?>\s*/', '', $raw);
                $raw = preg_replace('/^\s*<!DOCTYPE[^>]*>\s*/i', '', $raw);
                // Drop explicit width/height on the root <svg> so the wrapping
                // container's CSS dimensions take effect (preserves viewBox).
                $raw = preg_replace('/(<svg\b[^>]*?)\s+width="[^"]*"/i', '$1', $raw, 1);
                $raw = preg_replace('/(<svg\b[^>]*?)\s+height="[^"]*"/i', '$1', $raw, 1);
                $logo_svg = $raw;
                break;
            }
        }
    }

    if ($logo_svg === '') {
        error_log('[AAI-PDF] Logo SVG not found — checked: ' . implode(' | ', $logo_candidates));
    }

    // ------------------------------------------------------------------------
    // Load Dompdf (lazy — only when this handler runs)
    // ------------------------------------------------------------------------
    $dompdf_autoload = $theme_path . '/lib/dompdf/autoload.inc.php';
    if (!file_exists($dompdf_autoload)) {
        error_log('[AAI-PDF] Dompdf autoloader missing at: ' . $dompdf_autoload);
        wp_send_json_error(array('message' => 'PDF library not installed.'), 500);
    }
    require_once $dompdf_autoload;

    if (!class_exists('Dompdf\\Dompdf')) {
        error_log('[AAI-PDF] Dompdf class not available after autoload');
        wp_send_json_error(array('message' => 'PDF library failed to load.'), 500);
    }

    // ------------------------------------------------------------------------
    // Render HTML template into buffer
    // ------------------------------------------------------------------------
    $template_path = $theme_path . '/inc/templates/pdf-analyse-ai.php';
    if (!file_exists($template_path)) {
        error_log('[AAI-PDF] PDF template missing at: ' . $template_path);
        wp_send_json_error(array('message' => 'PDF template missing.'), 500);
    }

    ob_start();
    include $template_path;
    $html = ob_get_clean();

    // ------------------------------------------------------------------------
    // Generate PDF
    // ------------------------------------------------------------------------
    try {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);          // block remote fetches
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');      // ships with Dompdf, supports ✓ ✗
        $options->set('chroot', $theme_path);             // restrict file:// to theme dir

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf_binary = $dompdf->output();
    } catch (\Throwable $e) {
        error_log('[AAI-PDF] render error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'PDF generation failed.'), 500);
    }

    // ------------------------------------------------------------------------
    // Stream PDF binary to browser (no disk write)
    // ------------------------------------------------------------------------
    $filename = sanitize_file_name('Gurutor-Coaching-Report-' . $lesson_key . '-' . date('Y-m-d') . '.pdf');
    if (substr($filename, -4) !== '.pdf') $filename .= '.pdf';

    // Strip any stray output / buffer noise before headers
    while (ob_get_level() > 0) { ob_end_clean(); }

    nocache_headers();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf_binary));
    header('X-Content-Type-Options: nosniff');

    echo $pdf_binary;
    exit;
}
