<?php
/**
 * GMAT Dynamic Study Plan — v4
 *
 * Overrides the LearnDash course 8112 page with a dynamic study plan
 * based on the user's intake preference (verbal-first or quant-first).
 *
 * v4 changes:
 *   - xAPI-based tracking via GrassBlade LRS (replaces LearnDash activity)
 *   - Accordion on units within each section card
 *   - Batch xAPI queries (only 2 API calls per page load)
 *   - Static cache for xAPI data within page load
 *   - Hardcoded xapi_slug values mapped to actual GrassBlade activity IDs
 *   - Admin-configurable xAPI URLs for DI lessons not yet available
 *
 * Dependencies:
 *   - inc/gmat-study-plan-admin.php       (lesson ID mapping + defaults)
 *   - inc/gmat-intake-form.php            (user preference meta)
 *   - inc/free-trial-grassblade-xapi.php  (xAPI LRS functions)
 */

if (!defined('ABSPATH')) exit;

// Course ID to override
if (!defined('GMAT_SP_COURSE_ID')) define('GMAT_SP_COURSE_ID', 8112);


// ============================================================================
// ASSET ENQUEUE
// ============================================================================

function gmat_sp_enqueue_assets() {
    if (!is_singular('sfwd-courses') || get_the_ID() !== GMAT_SP_COURSE_ID) return;
    if (!is_user_logged_in()) return;

    $v = wp_get_theme()->get('Version');

    wp_enqueue_style('gmat-study-plan', get_stylesheet_directory_uri() . '/css/gmat-study-plan.css', array(), $v);
    wp_enqueue_script('gmat-study-plan', get_stylesheet_directory_uri() . '/js/gmat-study-plan.js', array('jquery'), $v, true);

    wp_localize_script('gmat-study-plan', 'gmatStudyPlan', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gmat_sp_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'gmat_sp_enqueue_assets');


// ============================================================================
// OVERRIDE COURSE 8112 CONTENT
// ============================================================================

function gmat_sp_override_course_content($content) {
    if (!is_singular('sfwd-courses') || get_the_ID() !== GMAT_SP_COURSE_ID) return $content;
    if (!is_user_logged_in()) return $content;

    // Only for paid users
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return $content;

    $user_id = get_current_user_id();

    // Get preference — default to verbal (re-read from DB every time so GMAT Settings changes take effect)
    $preference = get_user_meta($user_id, '_gmat_intake_section_preference', true);
    if (!in_array($preference, array('verbal', 'quant'))) {
        $preference = 'verbal';
    }

    // Get all lesson IDs (defaults merged with admin overrides)
    $lesson_ids = gmat_sp_get_lesson_ids();

    // Build the plan
    $plan = ($preference === 'verbal')
        ? gmat_sp_build_verbal_first($user_id, $lesson_ids)
        : gmat_sp_build_quant_first($user_id, $lesson_ids);

    ob_start();
    gmat_sp_render($plan, $preference, $user_id, $lesson_ids);
    return ob_get_clean();
}
add_filter('the_content', 'gmat_sp_override_course_content', 999);

// Also remove the default LearnDash course content (lesson list) below
function gmat_sp_remove_ld_content($content, $post) {
    if (!isset($post->ID) || $post->ID !== GMAT_SP_COURSE_ID) return $content;
    if (!is_user_logged_in()) return $content;
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return $content;
    return '';
}
add_filter('learndash_content', 'gmat_sp_remove_ld_content', 999, 2);


// ============================================================================
// HELPER: xAPI-based 3-state lesson tracking via GrassBlade LRS
// Returns: 'completed' | 'in-progress' | 'not-started'
//
// Strategy:
//   1) On first call, batch-fetch ALL user statements (completed + attempted)
//      in just 2 API calls and cache in a static variable.
//   2) Build a lookup map: xAPI activity_id → status
//   3) Map lesson_key → xAPI activity_id using hardcoded xapi_slug values
//      (confirmed from actual GrassBlade xAPI responses)
//   4) For DI lessons without slugs yet, use admin-saved xAPI URLs
//   5) Check: completed verb first, then attempted verb, else not-started
//
// xAPI verbs used:
//   - http://adlnet.gov/expapi/verbs/completed  (with result.completion=true)
//   - http://adlnet.gov/expapi/verbs/attempted   (lesson started / in-progress)
//   - http://adlnet.gov/expapi/verbs/experienced (ignored — just page visits)
//
// xAPI activity ID format: http://www.uniqueurl.com/{xapi_slug}
//   The xapi_slug values come from the actual GrassBlade content identifiers.
//   For DI lessons not yet available, admins can enter the full xAPI URL in
//   Settings > GMAT Study Plan.
//
// Debug: Add ?gmat_sp_debug_xapi=1 to the course URL (admin only)
// ============================================================================

/**
 * Core xAPI data fetcher — fetches and caches both status map and pass/fail signals.
 * Makes exactly 2 API calls per page load (completed + attempted).
 * Returns array with two keys:
 *   'status_map'   => activity_id => 'completed' | 'in-progress'
 *   'pass_fail'    => variable_name => 'Pass' | 'Fail'
 */
function gmat_sp_fetch_xapi_data($user_id) {
    static $cache = array();

    if (isset($cache[$user_id])) {
        return $cache[$user_id];
    }

    $status_map = array();
    $pass_fail_signals = array();

    $user = get_userdata($user_id);
    if (!$user || empty($user->user_email)) {
        $cache[$user_id] = array('status_map' => $status_map, 'pass_fail' => $pass_fail_signals);
        return $cache[$user_id];
    }

    $email = $user->user_email;

    if (!function_exists('grassblade_fetch_statements')) {
        $cache[$user_id] = array('status_map' => $status_map, 'pass_fail' => $pass_fail_signals);
        return $cache[$user_id];
    }

    // ── Fetch COMPLETED statements (verb = completed) ──
    $completed_result = grassblade_fetch_statements(array(
        'agent_email' => $email,
        'verb'        => 'http://adlnet.gov/expapi/verbs/completed',
        'limit'       => 500,
    ));

    if (!is_wp_error($completed_result) && is_array($completed_result)) {
        $statements = isset($completed_result['statements']) ? $completed_result['statements'] : $completed_result;
        if (is_array($statements)) {
            foreach ($statements as $stmt) {
                if (!isset($stmt['object']['id'])) continue;

                $activity_id = $stmt['object']['id'];

                // Only mark as completed if result.completion is true OR result.success is true
                $is_completed = false;
                if (isset($stmt['result']['completion']) && $stmt['result']['completion'] === true) {
                    $is_completed = true;
                }
                if (isset($stmt['result']['success']) && $stmt['result']['success'] === true) {
                    $is_completed = true;
                }
                // If no result block at all (or empty result {}), the completed verb alone is sufficient
                if (!isset($stmt['result']) || empty($stmt['result'])) {
                    $is_completed = true;
                }

                if ($is_completed) {
                    $status_map[$activity_id] = 'completed';
                }

                // ── Parse pass/fail signals from object name ──
                // Exercises emit completed statements where object.definition.name
                // contains JSON like: {"CR_Exercise_4_Pass_or_Fail": "Fail"}
                $obj_name = '';
                if (isset($stmt['object']['definition']['name']['en-US'])) {
                    $obj_name = trim($stmt['object']['definition']['name']['en-US']);
                }

                if (!empty($obj_name) && substr($obj_name, 0, 1) === '{') {
                    // Sanitize: strip BOM, fix smart quotes, remove invalid UTF-8
                    $obj_name = preg_replace('/^\xEF\xBB\xBF/', '', $obj_name); // BOM
                    $obj_name = str_replace(
                        array("\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99"),
                        array('"', '"', "'", "'"),
                        $obj_name
                    ); // smart quotes → straight quotes
                    if (function_exists('mb_convert_encoding')) {
                        $obj_name = mb_convert_encoding($obj_name, 'UTF-8', 'UTF-8');
                    }
                    $pf_data = json_decode($obj_name, true);
                    if ($pf_data === null && json_last_error() !== JSON_ERROR_NONE) {
                        // Last resort: strip all non-ASCII, rebuild JSON manually
                        if (preg_match('/"([A-Za-z0-9_]+_Pass_or_Fail)"\s*:\s*"(Pass|Fail)"/', $obj_name, $m)) {
                            $pf_data = array($m[1] => $m[2]);
                        }
                    }
                    if (is_array($pf_data)) {
                        foreach ($pf_data as $var_name => $value) {
                            if (strpos($var_name, '_Pass_or_Fail') !== false) {
                                // Store the most recent signal (statements come newest first)
                                // Trim the value to handle trailing whitespace from xAPI data
                                if (!isset($pass_fail_signals[$var_name])) {
                                    $pass_fail_signals[$var_name] = trim($value);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // ── Fetch ATTEMPTED statements (verb = attempted) ──
    $attempted_result = grassblade_fetch_statements(array(
        'agent_email' => $email,
        'verb'        => 'http://adlnet.gov/expapi/verbs/attempted',
        'limit'       => 500,
    ));

    if (!is_wp_error($attempted_result) && is_array($attempted_result)) {
        $statements = isset($attempted_result['statements']) ? $attempted_result['statements'] : $attempted_result;
        if (is_array($statements)) {
            foreach ($statements as $stmt) {
                if (!isset($stmt['object']['id'])) continue;

                $activity_id = $stmt['object']['id'];

                // Only set to in-progress if NOT already completed
                if (!isset($status_map[$activity_id])) {
                    $status_map[$activity_id] = 'in-progress';
                }
            }
        }
    }

    $cache[$user_id] = array('status_map' => $status_map, 'pass_fail' => $pass_fail_signals);
    return $cache[$user_id];
}

/**
 * Get xAPI status map for a user. Wrapper around gmat_sp_fetch_xapi_data().
 * Returns associative array: activity_id => 'completed' | 'in-progress'
 */
function gmat_sp_get_xapi_status_map($user_id) {
    $data = gmat_sp_fetch_xapi_data($user_id);
    return $data['status_map'];
}

/**
 * Get the pass/fail signal map for a user.
 * Returns: variable_name => "Pass" | "Fail"
 * No additional API calls — uses the same cached data.
 */
function gmat_sp_get_pass_fail_map($user_id) {
    $data = gmat_sp_fetch_xapi_data($user_id);
    return $data['pass_fail'];
}


/**
 * Map of pass/fail variable names to lesson keys.
 * These variable names are emitted by exercises as JSON in xAPI object names.
 *
 * For quant exercises, the QLE_* variables map to the specific lesson
 * that was failed within the exercise (granular per-topic signals).
 */
function gmat_sp_get_pass_fail_variable_map() {
    return array(
        // CR Exercises
        'CR_Exercise_4_Pass_or_Fail' => 'cr_exercise_4',
        'CR_Exercise_5_Pass_or_Fail' => 'cr_exercise_5',
        'CR_Exercise_6_Pass_or_Fail' => 'cr_exercise_6',
        'CR_Exercise_7_Pass_or_Fail' => 'cr_exercise_7',
        'CR_Exercise_8_Pass_or_Fail' => 'cr_exercise_8',

        // Verbal Reviews
        'Unit2_Verbal_Review_Pass_or_Fail' => 'verbal_review_2',
        'Unit3_Verbal_Review_Pass_or_Fail' => 'verbal_review_3',
        'Unit4_Verbal_Review_Pass_or_Fail' => 'verbal_review_4',
        'Unit5_Verbal_Review_Pass_or_Fail' => 'verbal_review_5',

        // Quant Lessons (direct pass/fail from the lesson itself)
        'PSS_Lesson_1_Pass_or_Fail' => 'pss_lesson_1',
        'ALG_Lesson_1_Pass_or_Fail' => 'algebra_1',
        'WP_Lesson_1_Pass_or_Fail'  => 'word_problems_1',
        'NP_Lesson_1_Pass_or_Fail'  => 'number_props_1',
        'FPR_Lesson_1_Pass_or_Fail' => 'fprs_1',
        'PSS_Lesson_2_Pass_or_Fail' => 'pss_lesson_2',
        'ALG_Lesson_2_Pass_or_Fail' => 'algebra_2',
        'NP_Lesson_2_Pass_or_Fail'  => 'number_props_2',
        'WP_Lesson_2_Pass_or_Fail'  => 'word_problems_2',
        'FPR_Lesson_2_Pass_or_Fail' => 'fprs_2',
        'ALG_Lesson_3_Pass_or_Fail' => 'algebra_3',
        'NP_Lesson_3_Pass_or_Fail'  => 'number_props_3',
        'WP_Lesson_3_Pass_or_Fail'  => 'word_problems_3',
        'ALG_Lesson_4_Pass_or_Fail' => 'algebra_4',
        'WP_Lesson_4_Pass_or_Fail'  => 'word_problems_4',
        'ALG_Lesson_5_Pass_or_Fail' => 'algebra_5',
        'WP_Lesson_5_Pass_or_Fail'  => 'word_problems_5',
        'WP_Lesson_6_Pass_or_Fail'  => 'word_problems_6',
        'WP_Lesson_7_Pass_or_Fail'  => 'word_problems_7',

        // Quant Exercise GRANULAR pass/fail (maps to the specific lesson failed within the exercise)
        'QLE_1_ALG1_Pass_or_Fail' => 'algebra_1',
        'QLE_1_NP1_Pass_or_Fail'  => 'number_props_1',
        'QLE_1_WP1_Pass_or_Fail'  => 'word_problems_1',
        'QLE_1_PSS1_Pass_or_Fail' => 'pss_lesson_1',
        'QLE_2_ALG2_Pass_or_Fail' => 'algebra_2',
        'QLE_2_NP2_Pass_or_Fail'  => 'number_props_2',
        'QLE_2_WP2_Pass_or_Fail'  => 'word_problems_2',
        'QLE_2_PSS2_Pass_or_Fail' => 'pss_lesson_2',
        'QLE_2_FPR1_Pass_or_Fail' => 'fprs_1',
        'QLE_3_ALG3_Pass_or_Fail' => 'algebra_3',
        'QLE_3_FPR2_Pass_or_Fail' => 'fprs_2',
        'QLE_3_WP3_Pass_or_Fail'  => 'word_problems_3',
        'QLE_3_WP4_Pass_or_Fail'  => 'word_problems_4',
        'QLE_4_ALG4_Pass_or_Fail' => 'algebra_4',
        'QLE_4_WP5_Pass_or_Fail'  => 'word_problems_5',
        'QLE_4_WP6_Pass_or_Fail'  => 'word_problems_6',
        'QLE_5_ALG5_Pass_or_Fail' => 'algebra_5',
        'QLE_5_NP3_Pass_or_Fail'  => 'number_props_3',
        'QLE_5_WP7_Pass_or_Fail'  => 'word_problems_7',

        // Quant Review Sets — granular pass/fail per topic area within each review
        // If any variable for a review shows "Fail", the review is considered failed
        'QRS__Unit2_ALG5_Pass_or_Fail' => 'quant_review_2',
        'QRS__Unit2_NP3_Pass_or_Fail'  => 'quant_review_2',
        'QRS__Unit2_WP7_Pass_or_Fail'  => 'quant_review_2',
        'QRS__Unit3_ALG5_Pass_or_Fail' => 'quant_review_3',
        'QRS__Unit3_NP3_Pass_or_Fail'  => 'quant_review_3',
        'QRS__Unit3_WP7_Pass_or_Fail'  => 'quant_review_3',
        'QRS__Unit4_ALG5_Pass_or_Fail' => 'quant_review_4',
        'QRS__Unit4_NP3_Pass_or_Fail'  => 'quant_review_4',
        'QRS__Unit4_WP7_Pass_or_Fail'  => 'quant_review_4',
        'QRS__Unit5_ALG5_Pass_or_Fail' => 'quant_review_5',
        'QRS__Unit5_NP3_Pass_or_Fail'  => 'quant_review_5',
        'QRS__Unit5_WP7_Pass_or_Fail'  => 'quant_review_5',
    );
}

/**
 * Get the pass/fail result of an exercise from xAPI signals.
 * NO fallback heuristic — only returns a result when an explicit signal exists.
 *
 * @param int    $user_id
 * @param string $lesson_key  The exercise lesson key (e.g., 'cr_exercise_4')
 * @return string 'fail' | 'pass' | 'none' (no signal exists)
 */
function gmat_sp_get_exercise_result($user_id, $lesson_key) {
    $pf_map  = gmat_sp_get_pass_fail_map($user_id);
    $var_map = gmat_sp_get_pass_fail_variable_map();

    foreach ($var_map as $var_name => $mapped_key) {
        if ($mapped_key === $lesson_key && isset($pf_map[$var_name])) {
            return ($pf_map[$var_name] === 'Fail') ? 'fail' : 'pass';
        }
    }
    return 'none';
}

/**
 * Get the pass/fail result of a review set (verbal or quant) from xAPI signals.
 * For reviews with multiple variables (QRS_*), returns 'fail' if ANY variable shows Fail.
 * NO fallback heuristic — only returns a result when an explicit signal exists.
 *
 * @param int    $user_id
 * @param string $review_key  e.g., 'verbal_review_2', 'quant_review_3'
 * @return string 'fail' | 'pass' | 'none' (no signal exists)
 */
function gmat_sp_get_review_result($user_id, $review_key) {
    $pf_map  = gmat_sp_get_pass_fail_map($user_id);
    $var_map = gmat_sp_get_pass_fail_variable_map();

    $has_any_signal = false;
    foreach ($var_map as $var_name => $mapped_key) {
        if ($mapped_key !== $review_key) continue;
        if (!isset($pf_map[$var_name])) continue;

        $has_any_signal = true;
        if ($pf_map[$var_name] === 'Fail') {
            return 'fail';
        }
    }
    return $has_any_signal ? 'pass' : 'none';
}

/**
 * Get the list of lesson keys that failed within a specific quant exercise,
 * using the granular QLE_* pass/fail variables.
 * NO fallback — returns empty array if no QLE signals exist.
 *
 * @param int    $user_id
 * @param int    $exercise_num  1-5 (which quant exercise)
 * @param array  $learn_keys    The lesson keys from this unit's learn section
 * @param array  $lesson_ids
 * @return array  Lesson keys that failed (empty if no signals)
 */
function gmat_sp_get_quant_exercise_failures($user_id, $exercise_num, $learn_keys, $lesson_ids) {
    $pf_map  = gmat_sp_get_pass_fail_map($user_id);
    $var_map = gmat_sp_get_pass_fail_variable_map();

    $prefix = 'QLE_' . $exercise_num . '_';
    $failed_keys = array();

    foreach ($var_map as $var_name => $mapped_key) {
        if (strpos($var_name, $prefix) !== 0) continue;
        if (isset($pf_map[$var_name]) && $pf_map[$var_name] === 'Fail') {
            $failed_keys[] = $mapped_key;
        }
    }

    return array_unique($failed_keys);
}


/**
 * Build a mapping: lesson_key => xAPI activity ID
 *
 * Uses the hardcoded xapi_slug from gmat_sp_get_lesson_keys() which are
 * the actual GrassBlade content identifiers confirmed from xAPI responses.
 *
 * For DI lessons that don't have slugs yet, checks admin-saved xAPI URLs
 * from Settings > GMAT Study Plan (option: gmat_study_plan_xapi_urls).
 *
 * xAPI activity ID = http://www.uniqueurl.com/{xapi_slug}
 *
 * Uses static cache so this is built only once per page load.
 */
function gmat_sp_get_slug_map($lesson_ids) {
    static $slug_map_cache = null;

    if ($slug_map_cache !== null) {
        return $slug_map_cache;
    }

    $slug_map_cache = array();

    $lesson_keys = gmat_sp_get_lesson_keys();

    // Get admin-saved xAPI URLs for DI lessons without hardcoded slugs
    $saved_xapi_urls = array();
    if (function_exists('gmat_sp_get_saved_xapi_urls')) {
        $saved_xapi_urls = gmat_sp_get_saved_xapi_urls();
    }

    foreach ($lesson_ids as $lesson_key => $post_id) {
        $post_id = intval($post_id);
        if ($post_id <= 0) continue;

        // 1) Use hardcoded xapi_slug if available
        if (isset($lesson_keys[$lesson_key]['xapi_slug']) && !empty($lesson_keys[$lesson_key]['xapi_slug'])) {
            $slug_map_cache[$lesson_key] = 'http://www.uniqueurl.com/' . $lesson_keys[$lesson_key]['xapi_slug'];
            continue;
        }

        // 2) Fallback: check admin-saved xAPI URLs (for DI lessons not yet available)
        if (isset($saved_xapi_urls[$lesson_key]) && !empty($saved_xapi_urls[$lesson_key])) {
            $slug_map_cache[$lesson_key] = $saved_xapi_urls[$lesson_key];
        }
    }

    return $slug_map_cache;
}


/**
 * Get the 3-state status for a specific lesson key.
 * Uses batched xAPI data (no per-lesson API calls).
 *
 * @param int    $user_id
 * @param string $lesson_key
 * @param array  $lesson_ids
 * @return string 'completed' | 'in-progress' | 'not-started'
 */
function gmat_sp_get_status($user_id, $lesson_key, $lesson_ids) {
    $post_id = isset($lesson_ids[$lesson_key]) ? intval($lesson_ids[$lesson_key]) : 0;
    if ($post_id <= 0) return 'not-started';

    // Get the xAPI status map (cached after first call)
    $xapi_map = gmat_sp_get_xapi_status_map($user_id);

    // Get the slug map (cached after first call)
    $slug_map = gmat_sp_get_slug_map($lesson_ids);

    // Find the xAPI activity ID for this lesson
    if (!isset($slug_map[$lesson_key])) {
        return 'not-started';
    }

    $activity_id = $slug_map[$lesson_key];

    // Check the xAPI status
    if (isset($xapi_map[$activity_id])) {
        return $xapi_map[$activity_id]; // 'completed' or 'in-progress'
    }

    return 'not-started';
}


function gmat_sp_is_complete($user_id, $lesson_key, $lesson_ids) {
    return gmat_sp_get_status($user_id, $lesson_key, $lesson_ids) === 'completed';
}




/**
 * Get the URL for a lesson/topic/quiz
 */
function gmat_sp_get_url($lesson_key, $lesson_ids) {
    $post_id = isset($lesson_ids[$lesson_key]) ? intval($lesson_ids[$lesson_key]) : 0;
    if ($post_id <= 0) return '#';
    return get_permalink($post_id);
}


/**
 * Get the LearnDash topic name for a lesson post (for "Topic: ..." subtitle)
 */
function gmat_sp_get_topic_name($lesson_key, $lesson_ids) {
    $post_id = isset($lesson_ids[$lesson_key]) ? intval($lesson_ids[$lesson_key]) : 0;
    if ($post_id <= 0) return '';

    $post_type = get_post_type($post_id);

    // If this is a topic, get its parent lesson name as the "Topic" subtitle
    if ($post_type === 'sfwd-topic') {
        $lesson_id = get_post_meta($post_id, 'lesson_id', true);
        if ($lesson_id) {
            return get_the_title($lesson_id);
        }
    }

    // For lessons, get the first topic title if available
    if ($post_type === 'sfwd-lessons' && function_exists('learndash_get_topic_list')) {
        $topics = learndash_get_topic_list($post_id, GMAT_SP_COURSE_ID);
        if (!empty($topics) && isset($topics[0])) {
            return $topics[0]->post_title;
        }
    }

    return '';
}


// ============================================================================
// VERBAL-FIRST STUDY PLAN STRUCTURE
// ============================================================================

function gmat_sp_build_verbal_first($user_id, $ids) {
    $plan = array();

    // ── VERBAL SECTION ──
    $verbal_units = array();

    // Verbal Unit 1 — Foundations of GMAT Reasoning
    $verbal_units[] = array(
        'title' => 'Unit 1 – Foundations of GMAT Reasoning',
        'description' => 'This unit builds the foundation for GMAT Verbal by teaching how Critical Reasoning and Reading Comprehension are structured and tested. You\'ll learn how to deconstruct arguments, identify question types, and read passages strategically so you can focus on reasoning instead of getting overwhelmed by content.',
        'learn' => array('intro_verbal', 'intro_quant', 'intro_di', 'cr_lesson_1', 'cr_lesson_2', 'rc_lesson_1'),
        'practice' => array('cr_exercise_1', 'cr_exercise_2'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 2 — Core Argument and Reading Skills
    $verbal_units[] = array(
        'title' => 'Unit 2 – Core Argument and Reading Skills',
        'description' => 'This unit develops your ability to classify arguments and understand the core tasks in assumption-based questions. You\'ll also begin applying structured strategies to Reading Comprehension, learning how question type and language patterns guide correct answers.',
        'learn' => array('cr_lesson_3', 'cr_lesson_4', 'rc_lesson_2', 'rc_lesson_3'),
        'practice' => array('cr_exercise_3', 'rc_exercise_1'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 3 — Strategic Decision-Making in CR
    $cr4_result = gmat_sp_get_exercise_result($user_id, 'cr_exercise_4');
    $verbal_units[] = array(
        'title' => 'Unit 3 – Strategic Decision-Making in CR',
        'description' => 'This unit trains you to handle plan-based arguments, one of the most common and misunderstood CR types. You\'ll practice targeting goals, constraints, and assumptions while reinforcing Unit 2 skills under exam-like conditions.',
        'learn' => array('cr_lesson_5'),
        'practice' => array('cr_exercise_4'),
        'review' => array('verbal_review_2'),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 4 — Regular Argument Analysis
    $v4_extra_review = array();
    $v4_suggested_lessons = array();
    if ($cr4_result === 'fail') {
        $v4_extra_review[] = 'cr_lesson_5';
        $v4_suggested_lessons['cr_lesson_5'] = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $cr5_result = gmat_sp_get_exercise_result($user_id, 'cr_exercise_5');
    $verbal_units[] = array(
        'title' => 'Unit 4 – Regular Argument Analysis',
        'description' => 'This unit focuses on "regular" CR arguments and strengthens your ability to evaluate how information affects conclusions. You\'ll refine your ability to identify logical gaps and eliminate attractive but irrelevant answers under time pressure. You\'ll finish up the unit with a thorough review of the verbal concepts from Unit 3 under exam-like conditions.',
        'learn' => array('cr_lesson_6'),
        'practice' => array('cr_exercise_5'),
        'review' => array_merge($v4_extra_review, array('verbal_review_3')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'suggested_lessons' => $v4_suggested_lessons,
    );

    // Verbal Unit 5 — Explanation-Based Reasoning
    $v5_extra_review = array();
    $v5_suggested_lessons = array();
    if ($cr5_result === 'fail') {
        $v5_extra_review[] = 'cr_lesson_6';
        $v5_suggested_lessons['cr_lesson_6'] = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $cr6_result = gmat_sp_get_exercise_result($user_id, 'cr_exercise_6');
    $verbal_units[] = array(
        'title' => 'Unit 5 – Explanation-Based Reasoning',
        'description' => 'This unit builds mastery of explanation arguments by teaching you how to recognize observation-and-explanation reasoning and the specific thought patterns that correct answers consistently address. You\'ll then reinforce the concepts from Unit 4 by completing a Unit 4 Verbal Review Set under exam-like conditions.',
        'learn' => array('cr_lesson_7'),
        'practice' => array('cr_exercise_6'),
        'review' => array_merge($v5_extra_review, array('verbal_review_4')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'suggested_lessons' => $v5_suggested_lessons,
    );

    // Verbal Unit 6 — Advanced Logical Structures
    $v6_extra_review = array();
    $v6_suggested_lessons = array();
    if ($cr6_result === 'fail') {
        $v6_extra_review[] = 'cr_lesson_7';
        $v6_suggested_lessons['cr_lesson_7'] = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $verbal_units[] = array(
        'title' => 'Unit 6 – Advanced Logical Structures',
        'description' => 'This unit covers advanced CR families, including structure- and evidence-based questions. You\'ll learn to analyze arguments independent of topic content and apply advanced reasoning skills consistently across difficult questions before revisiting the concepts from Unit 5 under exam-like conditions.',
        'learn' => array('cr_lesson_8', 'cr_lesson_9'),
        'practice' => array('cr_exercise_7', 'cr_exercise_8'),
        'review' => array_merge($v6_extra_review, array('verbal_review_5')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'suggested_lessons' => $v6_suggested_lessons,
    );

    $plan[] = array('section' => 'Verbal', 'units' => $verbal_units);

    // ── QUANT SECTION ──
    $quant_units = array();

    // Quant Unit 1 — Section Structure & Scoring
    $quant_units[] = array(
        'title' => 'Unit 1 – Section Structure & Scoring',
        'description' => '',
        'learn' => array('intro_quant'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Quant Unit 2 — Strategic Algebra and Translation
    $q2_learn = array('pss_lesson_1', 'algebra_1', 'word_problems_1', 'number_props_1');
    // Use granular QLE_1 pass/fail signals for exercise failures
    $q2_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 1, $q2_learn, $ids);
    $quant_units[] = array(
        'title' => 'Unit 2 – Strategic Algebra and Translation',
        'description' => 'This unit introduces core problem-solving strategies like smart numbers and working backwards, alongside essential algebra, number properties, and translation skills. You\'ll learn how to simplify problems strategically instead of defaulting to brute-force math.',
        'learn' => $q2_learn,
        'practice' => array('quant_exercise_1'),
        'review' => array(),
        'suggest' => !empty($q2_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q2_exercise_failures,
    );

    // Quant Unit 3 — Structure, Estimation, and Multi-Step Reasoning
    $q3_learn = array('pss_lesson_2', 'number_props_2', 'algebra_2', 'word_problems_2', 'fprs_1');
    $q3_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 2, $q3_learn, $ids);
    // Add Unit 2 failed lessons to review (from QLE_1 signals)
    $q3_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 1, $q2_learn, $ids);
    // Verbal cross-suggest — only show when explicit pass/fail signal exists
    $q3_cross_links = array();
    $vr2_result = gmat_sp_get_review_result($user_id, 'verbal_review_2');
    if ($vr2_result === 'fail') {
        $q3_cross_links = array('rc_exercise_1', 'verbal_review_2');
    } elseif ($vr2_result === 'pass') {
        $q3_cross_links = array('verbal_review_2');
    }
    $quant_units[] = array(
        'title' => 'Unit 3 – Structure, Estimation, and Multi-Step Reasoning',
        'description' => 'This unit deepens your ability to recognize structure in algebra, number properties, and word problems. You\'ll learn estimation, remainders, quadratics, inequalities, and overlapping sets while avoiding common GMAT language traps. You\'ll finish the unit by revisiting the concepts from Quant Unit 2 under exam-like conditions.',
        'learn' => $q3_learn,
        'practice' => array('quant_exercise_2'),
        'review' => array_merge($q3_review_extra, array('quant_review_2')),
        'suggest' => !empty($q3_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q3_exercise_failures,
        'cross_suggest' => '',
        'cross_suggest_links' => $q3_cross_links,
    );

    // Quant Unit 4 — Advanced Word Problems and Abstraction
    $q4_learn = array('fprs_2', 'algebra_3', 'word_problems_3', 'word_problems_4');
    $q4_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 3, $q4_learn, $ids);
    $q4_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 2, $q3_learn, $ids);
    $q4_cross_links = array();
    $vr3_result = gmat_sp_get_review_result($user_id, 'verbal_review_3');
    if ($vr3_result === 'fail') {
        $q4_cross_links = array('cr_lesson_5', 'cr_exercise_4', 'verbal_review_3');
    } elseif ($vr3_result === 'pass') {
        $q4_cross_links = array('verbal_review_3');
    }
    $quant_units[] = array(
        'title' => 'Unit 4 – Advanced Word Problems and Abstraction',
        'description' => 'This unit focuses on higher-level abstraction, including functions, sequences, rates with changing conditions, combinatorics, and statistics. You\'ll learn how to manage complexity by choosing the right structure rather than adding equations. You\'ll finish the unit by revisiting the concepts from Quant Unit 3 under exam-like conditions.',
        'learn' => $q4_learn,
        'practice' => array('quant_exercise_3'),
        'review' => array_merge($q4_review_extra, array('quant_review_3')),
        'suggest' => !empty($q4_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q4_exercise_failures,
        'cross_suggest' => '',
        'cross_suggest_links' => $q4_cross_links,
    );

    // Quant Unit 5 — Systems, Probability, and Weighted Reasoning
    $q5_learn = array('algebra_4', 'word_problems_5', 'word_problems_6');
    $q5_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 4, $q5_learn, $ids);
    $q5_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 3, $q4_learn, $ids);
    $q5_cross_links = array();
    $vr4_result = gmat_sp_get_review_result($user_id, 'verbal_review_4');
    if ($vr4_result === 'fail') {
        $q5_cross_links = array('cr_lesson_6', 'cr_exercise_5', 'verbal_review_4');
    } elseif ($vr4_result === 'pass') {
        $q5_cross_links = array('verbal_review_4');
    }
    $quant_units[] = array(
        'title' => 'Unit 5 – Systems, Probability, and Weighted Reasoning',
        'description' => 'This unit develops advanced reasoning skills involving systems, weighted averages, probability, and unit conversions. You\'ll learn how to split complex problems into simpler cases and avoid common structural mistakes. You\'ll finish the unit by revisiting the concepts from Quant Unit 4 under exam-like conditions.',
        'learn' => $q5_learn,
        'practice' => array('quant_exercise_4'),
        'review' => array_merge($q5_review_extra, array('quant_review_4')),
        'suggest' => !empty($q5_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q5_exercise_failures,
        'cross_suggest' => '',
        'cross_suggest_links' => $q5_cross_links,
    );

    // Quant Unit 6 — Patterns, Constraints, and Edge Cases
    $q6_learn = array('number_props_3', 'word_problems_7', 'algebra_5');
    $q6_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 5, $q6_learn, $ids);
    $q6_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 4, $q5_learn, $ids);
    $q6_cross_links = array();
    $vr5_result = gmat_sp_get_review_result($user_id, 'verbal_review_5');
    if ($vr5_result === 'fail') {
        $q6_cross_links = array('cr_lesson_7', 'cr_exercise_6', 'verbal_review_5');
    } elseif ($vr5_result === 'pass') {
        $q6_cross_links = array('verbal_review_5');
    }
    $quant_units[] = array(
        'title' => 'Unit 6 – Patterns, Constraints, and Edge Cases',
        'description' => 'This unit covers advanced number properties, probability structures, statistics bounds, and algebraic techniques like conjugation. The focus is on recognizing patterns, managing constraints, and handling high-difficulty questions efficiently.',
        'learn' => $q6_learn,
        'practice' => array('quant_exercise_5'),
        'review' => array_merge($q6_review_extra, array('quant_review_5')),
        'suggest' => !empty($q6_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q6_exercise_failures,
        'cross_suggest' => '',
        'cross_suggest_links' => $q6_cross_links,
    );

    $plan[] = array('section' => 'Quant', 'units' => $quant_units);

    // ── DATA INSIGHTS SECTION ──
    $di_units = array();

    // DI Unit 1 — Data Sufficiency and Logical Control
    $di_units[] = array(
        'title' => 'Unit 1 – Data Sufficiency and Logical Control',
        'description' => 'This unit builds mastery of Data Sufficiency by teaching structured evaluation methods, rephrasing, and logical testing strategies. You\'ll learn how to determine sufficiency confidently without unnecessary calculation, while reinforcing core reasoning skills.',
        'learn' => array('intro_di', 'di_lesson_1', 'di_lesson_2', 'di_lesson_3'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // DI Unit 2 — Interpreting Visual and Tabular Data
    $di_units[] = array(
        'title' => 'Unit 2 – Interpreting Visual and Tabular Data',
        'description' => 'This unit focuses on extracting meaning from graphs and tables under time pressure. You\'ll learn how to filter information, avoid visual traps, and make accurate yes/no decisions using structured analysis.',
        'learn' => array('di_lesson_4', 'di_lesson_5'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // DI Unit 3 — Multi-Source and Multi-Step Reasoning
    $di_units[] = array(
        'title' => 'Unit 3 – Multi-Source and Multi-Step Reasoning',
        'description' => 'This unit trains you to synthesize information across multiple sources and conditions. You\'ll learn how to translate complex setups, manage interdependent information, and apply quantitative reasoning in integrated contexts.',
        'learn' => array('di_lesson_6', 'di_lesson_7'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    $plan[] = array('section' => 'Data Insights', 'units' => $di_units);

    return $plan;
}


// ============================================================================
// QUANT-FIRST STUDY PLAN STRUCTURE
// ============================================================================

function gmat_sp_build_quant_first($user_id, $ids) {
    $plan = array();

    // ── QUANT SECTION ──
    $quant_units = array();

    // Quant Unit 1 — Orientation and Foundations
    $quant_units[] = array(
        'title' => 'Unit 1 – Orientation and Foundations',
        'description' => 'This unit introduces the structure of the GMAT and how Quant, Verbal, and Data Insights are tested. It sets the foundation for effective study strategies.',
        'learn' => array('intro_quant', 'intro_verbal', 'intro_di'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Quant Unit 2 — Strategic Algebra and Translation
    $q2_learn = array('pss_lesson_1', 'algebra_1', 'word_problems_1', 'number_props_1');
    $q2_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 1, $q2_learn, $ids);
    $quant_units[] = array(
        'title' => 'Unit 2 – Strategic Algebra and Translation',
        'description' => 'This unit introduces core problem-solving strategies like smart numbers and working backwards, alongside essential algebra, number properties, and translation skills. You\'ll learn how to simplify problems strategically instead of defaulting to brute-force math.',
        'learn' => $q2_learn,
        'practice' => array('quant_exercise_1'),
        'review' => array(),
        'suggest' => !empty($q2_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q2_exercise_failures,
    );

    // Quant Unit 3 — Structure, Estimation, and Multi-Step Reasoning
    $q3_learn = array('pss_lesson_2', 'number_props_2', 'algebra_2', 'word_problems_2', 'fprs_1');
    $q3_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 2, $q3_learn, $ids);
    $q3_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 1, $q2_learn, $ids);
    $quant_units[] = array(
        'title' => 'Unit 3 – Structure, Estimation, and Multi-Step Reasoning',
        'description' => 'This unit deepens your ability to recognize structure in algebra, number properties, and word problems. You\'ll learn estimation, remainders, quadratics, inequalities, and overlapping sets while avoiding common GMAT language traps. You\'ll finish the unit by revisiting the concepts from Quant Unit 2 under exam-like conditions.',
        'learn' => $q3_learn,
        'practice' => array('quant_exercise_2'),
        'review' => array_merge($q3_review_extra, array('quant_review_2')),
        'suggest' => !empty($q3_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q3_exercise_failures,
    );

    // Quant Unit 4 — Advanced Word Problems and Abstraction
    $q4_learn = array('fprs_2', 'algebra_3', 'word_problems_3', 'word_problems_4');
    $q4_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 3, $q4_learn, $ids);
    $q4_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 2, $q3_learn, $ids);
    $quant_units[] = array(
        'title' => 'Unit 4 – Advanced Word Problems and Abstraction',
        'description' => 'This unit focuses on higher-level abstraction, including functions, sequences, rates with changing conditions, combinatorics, and statistics. You\'ll learn how to manage complexity by choosing the right structure rather than adding equations. You\'ll finish the unit by revisiting the concepts from Quant Unit 3 under exam-like conditions.',
        'learn' => $q4_learn,
        'practice' => array('quant_exercise_3'),
        'review' => array_merge($q4_review_extra, array('quant_review_3')),
        'suggest' => !empty($q4_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q4_exercise_failures,
    );

    // Quant Unit 5 — Systems, Probability, and Weighted Reasoning
    $q5_learn = array('algebra_4', 'word_problems_5', 'word_problems_6');
    $q5_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 4, $q5_learn, $ids);
    $q5_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 3, $q4_learn, $ids);
    $quant_units[] = array(
        'title' => 'Unit 5 – Systems, Probability, and Weighted Reasoning',
        'description' => 'This unit develops advanced reasoning skills involving systems, weighted averages, probability, and unit conversions. You\'ll learn how to split complex problems into simpler cases and avoid common structural mistakes. You\'ll finish the unit by revisiting the concepts from Quant Unit 4 under exam-like conditions.',
        'learn' => $q5_learn,
        'practice' => array('quant_exercise_4'),
        'review' => array_merge($q5_review_extra, array('quant_review_4')),
        'suggest' => !empty($q5_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q5_exercise_failures,
    );

    // Quant Unit 6 — Patterns, Constraints, and Edge Cases
    $q6_learn = array('number_props_3', 'word_problems_7', 'algebra_5');
    $q6_exercise_failures = gmat_sp_get_quant_exercise_failures($user_id, 5, $q6_learn, $ids);
    $q6_review_extra = gmat_sp_get_quant_exercise_failures($user_id, 4, $q5_learn, $ids);
    $quant_units[] = array(
        'title' => 'Unit 6 – Patterns, Constraints, and Edge Cases',
        'description' => 'This unit covers advanced number properties, probability structures, statistics bounds, and algebraic techniques like conjugation. The focus is on recognizing patterns, managing constraints, and handling high-difficulty questions efficiently. You\'ll finish the unit by revisiting the concepts from Quant Unit 5 under exam-like conditions.',
        'learn' => $q6_learn,
        'practice' => array('quant_exercise_5'),
        'review' => array_merge($q6_review_extra, array('quant_review_5')),
        'suggest' => !empty($q6_exercise_failures) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q6_exercise_failures,
    );

    $plan[] = array('section' => 'Quant', 'units' => $quant_units);

    // ── VERBAL SECTION ──
    $verbal_units = array();

    // Verbal Unit 1 — Foundations of GMAT Reasoning
    $verbal_units[] = array(
        'title' => 'Unit 1 – Foundations of GMAT Reasoning',
        'description' => 'This unit introduces GMAT Verbal reasoning, including how Critical Reasoning and Reading Comprehension are structured and scored.',
        'learn' => array('intro_verbal', 'cr_lesson_1', 'cr_lesson_2', 'rc_lesson_1'),
        'practice' => array('cr_exercise_1', 'cr_exercise_2'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 2 — Core Argument and Reading Skills
    $verbal_units[] = array(
        'title' => 'Unit 2 – Core Argument and Reading Skills',
        'description' => 'This unit teaches you to classify CR arguments and apply strategic approaches to RC question types and answer-choice language.',
        'learn' => array('cr_lesson_3', 'cr_lesson_4', 'rc_lesson_2', 'rc_lesson_3'),
        'practice' => array('cr_exercise_3', 'rc_exercise_1'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 3 — Strategic Decision-Making in CR
    $cr4_result = gmat_sp_get_exercise_result($user_id, 'cr_exercise_4');
    $v3_cross_links = array();
    $qr2_result = gmat_sp_get_review_result($user_id, 'quant_review_2');
    if ($qr2_result === 'fail') {
        $v3_cross_links = array('quant_exercise_1', 'quant_review_2');
    } elseif ($qr2_result === 'pass') {
        $v3_cross_links = array('quant_review_2');
    }
    $verbal_units[] = array(
        'title' => 'Unit 3 – Strategic Decision-Making in CR',
        'description' => 'This unit focuses on plan-argument reasoning in CR and consolidates verbal skills through a timed review.',
        'learn' => array('cr_lesson_5'),
        'practice' => array('cr_exercise_4'),
        'review' => array('verbal_review_2'),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v3_cross_links,
    );

    // Verbal Unit 4 — Regular Argument Analysis
    $v4_extra_review = array();
    $v4_suggested_lessons = array();
    if ($cr4_result === 'fail') {
        $v4_extra_review[] = 'cr_lesson_5';
        $v4_suggested_lessons['cr_lesson_5'] = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $cr5_result = gmat_sp_get_exercise_result($user_id, 'cr_exercise_5');
    $v4_cross_links = array();
    $qr3_result = gmat_sp_get_review_result($user_id, 'quant_review_3');
    if ($qr3_result === 'fail') {
        $v4_cross_links = array('quant_exercise_2', 'quant_review_3');
    } elseif ($qr3_result === 'pass') {
        $v4_cross_links = array('quant_review_3');
    }
    $verbal_units[] = array(
        'title' => 'Unit 4 – Regular Argument Analysis',
        'description' => 'This unit advances your CR skills with regular-argument analysis and continues to build verbal reasoning under timed conditions.',
        'learn' => array('cr_lesson_6'),
        'practice' => array('cr_exercise_5'),
        'review' => array_merge($v4_extra_review, array('verbal_review_3')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v4_cross_links,
        'suggested_lessons' => $v4_suggested_lessons,
    );

    // Verbal Unit 5 — Explanation-Based Reasoning
    $v5_extra_review = array();
    $v5_suggested_lessons = array();
    if ($cr5_result === 'fail') {
        $v5_extra_review[] = 'cr_lesson_6';
        $v5_suggested_lessons['cr_lesson_6'] = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $cr6_result = gmat_sp_get_exercise_result($user_id, 'cr_exercise_6');
    $v5_cross_links = array();
    $qr4_result = gmat_sp_get_review_result($user_id, 'quant_review_4');
    if ($qr4_result === 'fail') {
        $v5_cross_links = array('quant_exercise_3', 'quant_review_4');
    } elseif ($qr4_result === 'pass') {
        $v5_cross_links = array('quant_review_4');
    }
    $verbal_units[] = array(
        'title' => 'Unit 5 – Explanation-Based Reasoning',
        'description' => 'This unit teaches explanation-argument reasoning and uses thought pattern recognition to avoid common CR traps.',
        'learn' => array('cr_lesson_7'),
        'practice' => array('cr_exercise_6'),
        'review' => array_merge($v5_extra_review, array('verbal_review_4')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v5_cross_links,
        'suggested_lessons' => $v5_suggested_lessons,
    );

    // Verbal Unit 6 — Advanced Logical Structures
    $v6_extra_review = array();
    $v6_suggested_lessons = array();
    if ($cr6_result === 'fail') {
        $v6_extra_review[] = 'cr_lesson_7';
        $v6_suggested_lessons['cr_lesson_7'] = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $v6_cross_links = array();
    $qr5_result = gmat_sp_get_review_result($user_id, 'quant_review_5');
    if ($qr5_result === 'fail') {
        $v6_cross_links = array('quant_exercise_4', 'quant_review_5');
    } elseif ($qr5_result === 'pass') {
        $v6_cross_links = array('quant_review_5');
    }
    $verbal_units[] = array(
        'title' => 'Unit 6 – Advanced Logical Structures',
        'description' => 'This unit completes verbal training with structure-family and evidence-family question types, plus a final comprehensive verbal review.',
        'learn' => array('cr_lesson_8', 'cr_lesson_9'),
        'practice' => array('cr_exercise_7', 'cr_exercise_8'),
        'review' => array_merge($v6_extra_review, array('verbal_review_5')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'suggested_lessons' => $v6_suggested_lessons,
        'cross_suggest' => '',
        'cross_suggest_links' => $v6_cross_links,
    );

    $plan[] = array('section' => 'Verbal', 'units' => $verbal_units);

    // ── DATA INSIGHTS SECTION ──
    $di_units = array();

    // DI Unit 1 — Data Sufficiency and Logical Control
    $di_units[] = array(
        'title' => 'Unit 1 – Data Sufficiency and Logical Control',
        'description' => 'This unit builds mastery of Data Sufficiency by teaching structured evaluation methods, rephrasing, and logical testing strategies. You\'ll learn how to determine sufficiency confidently without unnecessary calculation, while reinforcing core reasoning skills.',
        'learn' => array('intro_di', 'di_lesson_1', 'di_lesson_2', 'di_lesson_3'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // DI Unit 2 — Interpreting Visual and Tabular Data
    $di_units[] = array(
        'title' => 'Unit 2 – Interpreting Visual and Tabular Data',
        'description' => 'This unit focuses on extracting meaning from graphs and tables under time pressure. You\'ll learn how to filter information, avoid visual traps, and make accurate yes/no decisions using structured analysis.',
        'learn' => array('di_lesson_4', 'di_lesson_5'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // DI Unit 3 — Multi-Source and Multi-Step Reasoning
    $di_units[] = array(
        'title' => 'Unit 3 – Multi-Source and Multi-Step Reasoning',
        'description' => 'This unit trains you to synthesize information across multiple sources and conditions. You\'ll learn how to translate complex setups, manage interdependent information, and apply quantitative reasoning in integrated contexts.',
        'learn' => array('di_lesson_6', 'di_lesson_7'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    $plan[] = array('section' => 'Data Insights', 'units' => $di_units);

    return $plan;
}


// ============================================================================
// Format a lesson description (newline-separated points) into an HTML list
// ============================================================================

function gmat_sp_format_description($desc) {
    if (empty($desc)) return '';

    $lines = explode("\n", $desc);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, 'strlen');

    if (count($lines) <= 1) {
        return '<p>' . esc_html($desc) . '</p>';
    }

    $html = '<ul>';
    foreach ($lines as $line) {
        $html .= '<li>' . esc_html($line) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}


// ============================================================================
// RENDER THE STUDY PLAN — 3 main sections stacked vertically
// ============================================================================

function gmat_sp_render($plan, $preference, $user_id, $lesson_ids) {
    $all_keys = gmat_sp_get_lesson_keys();

    // Calculate per-section and overall progress
    $section_stats = array();
    $total_items = 0;
    $completed_items = 0;
    foreach ($plan as $section) {
        $sec_total = 0;
        $sec_done  = 0;
        foreach ($section['units'] as $unit) {
            foreach (array('learn', 'practice', 'review') as $type) {
                foreach ($unit[$type] as $lk) {
                    $sec_total++;
                    $total_items++;
                    if (gmat_sp_is_complete($user_id, $lk, $lesson_ids)) {
                        $sec_done++;
                        $completed_items++;
                    }
                }
            }
        }
        $section_stats[$section['section']] = array('done' => $sec_done, 'total' => $sec_total);
    }
    $progress_pct = $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;
    ?>
    <div id="gmat-study-plan">

        <!-- ── Overall Progress ── -->
        <div class="gmat-sp-overall">
            <div class="gmat-sp-overall__label">GMAT Overall Progress</div>
            <div class="gmat-sp-overall__bar-wrap">
                <div class="gmat-sp-overall__bar" style="width: <?php echo $progress_pct; ?>%;"></div>
            </div>
            <div class="gmat-sp-overall__pct"><?php echo $progress_pct; ?>% Complete</div>
        </div>

        <!-- ── Course Progress Breakdown (3 cards) ── -->
        <div class="gmat-sp-breakdown">
            <h3 class="gmat-sp-breakdown__title">Course Progress Breakdown</h3>
            <div class="gmat-sp-breakdown__cards">
                <?php
                $color_map = array('Verbal' => '#22c55e', 'Quant' => '#5b6abf', 'Data Insights' => '#3b82f6');
                foreach ($plan as $section) :
                    $st = $section_stats[$section['section']];
                    $sec_pct = $st['total'] > 0 ? round(($st['done'] / $st['total']) * 100) : 0;
                    $bar_color = isset($color_map[$section['section']]) ? $color_map[$section['section']] : '#5b6abf';
                ?>
                    <div class="gmat-sp-breakdown__card">
                        <div class="gmat-sp-breakdown__card-top">
                            <span class="gmat-sp-breakdown__card-label"><?php echo esc_html($section['section']); ?> Modules Completed</span>
                            <span class="gmat-sp-breakdown__card-count"><?php echo intval($st['done']); ?>/<?php echo intval($st['total']); ?></span>
                        </div>
                        <div class="gmat-sp-breakdown__card-bar-wrap">
                            <div class="gmat-sp-breakdown__card-bar" style="width: <?php echo $sec_pct; ?>%; background: <?php echo $bar_color; ?>;"></div>
                        </div>
                        <div class="gmat-sp-breakdown__card-pct" style="color: <?php echo $bar_color; ?>;"><?php echo $sec_pct; ?>% Complete</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── 3 Main Sections stacked vertically ── -->
        <?php foreach ($plan as $si => $section) :
            $sec_label_map = array('Verbal' => 'Verbal Modules', 'Quant' => 'Quant Modules', 'Data Insights' => 'Data Insights Modules');
            $sec_title = isset($sec_label_map[$section['section']]) ? $sec_label_map[$section['section']] : $section['section'] . ' Modules';
        ?>
            <div class="gmat-sp-section">
                <h2 class="gmat-sp-section__title"><?php echo esc_html($sec_title); ?></h2>
                <div class="gmat-sp-section__card">
                    <?php foreach ($section['units'] as $ui => $unit) :

                        // ── Calculate unit progress for header ──
                        $unit_total = 0;
                        $unit_done  = 0;
                        $unit_has_progress = false;
                        foreach (array('learn', 'practice', 'review') as $t) {
                            foreach ($unit[$t] as $lk) {
                                $unit_total++;
                                $st = gmat_sp_get_status($user_id, $lk, $lesson_ids);
                                if ($st === 'completed') {
                                    $unit_done++;
                                } elseif ($st === 'in-progress') {
                                    $unit_has_progress = true;
                                }
                            }
                        }

                        // Determine unit state for classes
                        $unit_state = 'not-started';
                        if ($unit_done === $unit_total && $unit_total > 0) {
                            $unit_state = 'completed';
                        } elseif ($unit_done > 0 || $unit_has_progress) {
                            $unit_state = 'in-progress';
                        }

                        $unit_pct = $unit_total > 0 ? round(($unit_done / $unit_total) * 100) : 0;
                    ?>
                        <div class="gmat-sp-unit gmat-sp-unit--<?php echo $unit_state; ?>" data-unit-state="<?php echo $unit_state; ?>">
                            <!-- ── Unit Accordion Header ── -->
                            <div class="gmat-sp-unit__header">
                                <div class="gmat-sp-unit__header-left">
                                    <span class="gmat-sp-unit__state-icon gmat-sp-unit__state-icon--<?php echo $unit_state; ?>">
                                        <?php if ($unit_state === 'completed') : ?>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" fill="#22c55e"/><path d="M6 10l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <?php elseif ($unit_state === 'in-progress') : ?>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" fill="#f68525"/><circle cx="10" cy="10" r="3" fill="#fff"/></svg>
                                        <?php else : ?>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="#cbd5e1" stroke-width="1.5"/></svg>
                                        <?php endif; ?>
                                    </span>
                                    <span class="gmat-sp-unit__title"><?php echo esc_html($unit['title']); ?></span>
                                    <span class="gmat-sp-unit__progress-text"><?php echo intval($unit_done); ?>/<?php echo intval($unit_total); ?> Lessons</span>
                                </div>
                                <div class="gmat-sp-unit__header-right">
                                    <?php if ($unit_pct > 0) : ?>
                                        <span class="gmat-sp-unit__pct"><?php echo $unit_pct; ?>%</span>
                                    <?php endif; ?>
                                    <span class="gmat-sp-unit__chevron">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- ── Unit Accordion Body ── -->
                            <div class="gmat-sp-unit__body">
                              <div class="gmat-sp-unit__body-inner">
                                <?php if (!empty($unit['description'])) : ?>
                                    <p class="gmat-sp-unit__desc"><?php echo esc_html($unit['description']); ?></p>
                                <?php endif; ?>
                                <?php
                                $sub_sections = array(
                                    'learn'    => 'Learn',
                                    'practice' => 'Practice',
                                    'review'   => 'Review',
                                );
                                foreach ($sub_sections as $type => $type_label) :
                                    if (empty($unit[$type])) continue;
                                    $lesson_num = 1;
                                ?>
                                    <div class="gmat-sp-subsection">
                                        <div class="gmat-sp-subsection__badge gmat-sp-subsection__badge--<?php echo $type; ?>">
                                            UNIT <?php echo ($ui + 1); ?> - <?php echo strtoupper($type_label); ?>
                                        </div>

                                        <div class="gmat-sp-lesson-list">
                                            <?php
                                            $suggested_lessons = isset($unit['suggested_lessons']) ? $unit['suggested_lessons'] : array();
                                            foreach ($unit[$type] as $lk) :
                                                $status  = gmat_sp_get_status($user_id, $lk, $lesson_ids);
                                                $label   = isset($all_keys[$lk]) ? $all_keys[$lk]['label'] : $lk;
                                                $url     = gmat_sp_get_url($lk, $lesson_ids);
                                                $has_id  = isset($lesson_ids[$lk]) && intval($lesson_ids[$lk]) > 0;
                                                $topic   = isset($all_keys[$lk]['topic']) && !empty($all_keys[$lk]['topic']) ? $all_keys[$lk]['topic'] : '';
                                                $is_suggested  = isset($suggested_lessons[$lk]);
                                                $suggest_text  = $is_suggested ? $suggested_lessons[$lk] : '';
                                                $desc = $is_suggested ? $suggest_text : (isset($all_keys[$lk]['desc']) ? $all_keys[$lk]['desc'] : '');
                                                $card_classes = 'gmat-sp-lesson gmat-sp-lesson--' . $status;
                                                if ($is_suggested) $card_classes .= ' gmat-sp-lesson--suggested';
                                            ?>
                                                <div class="<?php echo $card_classes; ?>">
                                                    <div class="gmat-sp-lesson__top-row">
                                                        <div class="gmat-sp-lesson__number-col">
                                                            <span class="gmat-sp-lesson__number gmat-sp-lesson__number--<?php echo $status; ?>"><?php echo $lesson_num; ?></span>
                                                        </div>
                                                        <div class="gmat-sp-lesson__info">
                                                            <span class="gmat-sp-lesson__name"><?php echo esc_html($label); ?></span>
                                                            <?php if ($topic) : ?>
                                                                <span class="gmat-sp-lesson__topic">Topic: <?php echo esc_html($topic); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="gmat-sp-lesson__actions">
                                                            <?php if ($is_suggested) : ?>
                                                                <span class="gmat-sp-lesson__suggested-badge">Suggested</span>
                                                            <?php endif; ?>
                                                            <?php if ($status === 'completed') : ?>
                                                                <span class="gmat-sp-lesson__status-badge gmat-sp-lesson__status-badge--completed">Completed</span>
                                                                <?php if ($has_id) : ?>
                                                                    <a href="<?php echo esc_url($url); ?>" class="gmat-sp-lesson__btn gmat-sp-lesson__btn--review">Review</a>
                                                                <?php endif; ?>
                                                            <?php elseif ($status === 'in-progress') : ?>
                                                                <span class="gmat-sp-lesson__status-badge gmat-sp-lesson__status-badge--progress">In Progress</span>
                                                                <?php if ($has_id) : ?>
                                                                    <a href="<?php echo esc_url($url); ?>" class="gmat-sp-lesson__btn gmat-sp-lesson__btn--continue">Continue</a>
                                                                <?php endif; ?>
                                                            <?php else : ?>
                                                                <?php if ($has_id) : ?>
                                                                    <a href="<?php echo esc_url($url); ?>" class="gmat-sp-lesson__btn">Start Lesson</a>
                                                                <?php else : ?>
                                                                    <span class="gmat-sp-lesson__btn gmat-sp-lesson__btn--disabled">Coming Soon</span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($desc) : ?>
                                                            <span class="gmat-sp-lesson__expand-icon">
                                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="<?php echo $is_suggested ? '#b45309' : '#94a3b8'; ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($desc) : ?>
                                                        <div class="gmat-sp-lesson__desc">
                                                            <div class="gmat-sp-lesson__desc-inner">
                                                                <?php if ($is_suggested) : ?>
                                                                    <p class="gmat-sp-lesson__suggest-label">Areas to focus on:</p>
                                                                <?php endif; ?>
                                                                <?php echo gmat_sp_format_description($desc); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php
                                                $lesson_num++;
                                            endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; // sub_sections

                                
                                // Suggest areas of focus for this unit
                                $suggest             = isset($unit['suggest']) ? $unit['suggest'] : '';
                                $suggest_links       = isset($unit['suggest_links']) ? $unit['suggest_links'] : array();
                                $cross_suggest       = isset($unit['cross_suggest']) ? $unit['cross_suggest'] : '';
                                $cross_suggest_links = isset($unit['cross_suggest_links']) ? $unit['cross_suggest_links'] : array();
                                $suggest_redo        = isset($unit['suggest_redo']) ? $unit['suggest_redo'] : array();

                                $has_suggest = ($suggest || !empty($suggest_links) || $cross_suggest || !empty($cross_suggest_links) || !empty($suggest_redo));

                                if ($has_suggest) : ?>
                                    <div class="gmat-sp-suggest">
                                        <div class="gmat-sp-suggest__icon">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="#f68525" stroke-width="1.5"/><path d="M10 6v5M10 14h.01" stroke="#f68525" stroke-width="2" stroke-linecap="round"/></svg>
                                        </div>
                                        <div class="gmat-sp-suggest__content">
                                            <strong>Suggested areas of focus</strong>
                                            <?php if ($suggest) : ?>
                                                <p><?php echo esc_html($suggest); ?></p>
                                            <?php endif; ?>

                                            <?php // Clickable suggest links (e.g. "CR Lesson 8 & CR Exercise 7")
                                            if (!empty($suggest_links)) : ?>
                                                <p class="gmat-sp-suggest__links">
                                                    <?php
                                                    $link_parts = array();
                                                    foreach ($suggest_links as $slk) {
                                                        $sl_label = isset($all_keys[$slk]) ? $all_keys[$slk]['label'] : $slk;
                                                        $sl_url   = gmat_sp_get_url($slk, $lesson_ids);
                                                        if ($sl_url && $sl_url !== '#') {
                                                            $link_parts[] = '<a href="' . esc_url($sl_url) . '" class="gmat-sp-suggest__link">' . esc_html($sl_label) . '</a>';
                                                        } else {
                                                            $link_parts[] = esc_html($sl_label);
                                                        }
                                                    }
                                                    echo implode(' &amp; ', $link_parts);
                                                    ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($suggest_redo)) : ?>
                                                <p class="gmat-sp-suggest__redo">Recommend redoing:
                                                    <?php
                                                    $redo_parts = array();
                                                    foreach ($suggest_redo as $rk) {
                                                        $rk_label = isset($all_keys[$rk]) ? $all_keys[$rk]['label'] : $rk;
                                                        $rk_url   = gmat_sp_get_url($rk, $lesson_ids);
                                                        if ($rk_url && $rk_url !== '#') {
                                                            $redo_parts[] = '<a href="' . esc_url($rk_url) . '" class="gmat-sp-suggest__link">' . esc_html($rk_label) . '</a>';
                                                        } else {
                                                            $redo_parts[] = esc_html($rk_label);
                                                        }
                                                    }
                                                    echo implode(', ', $redo_parts);
                                                    ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php // Cross-suggest with clickable links
                                            if ($cross_suggest || !empty($cross_suggest_links)) : ?>
                                                <p class="gmat-sp-suggest__cross"><strong>Also suggested:</strong>
                                                    <?php if (!empty($cross_suggest_links)) :
                                                        $cross_parts = array();
                                                        foreach ($cross_suggest_links as $clk) {
                                                            $cl_label = isset($all_keys[$clk]) ? $all_keys[$clk]['label'] : $clk;
                                                            $cl_url   = gmat_sp_get_url($clk, $lesson_ids);
                                                            if ($cl_url && $cl_url !== '#') {
                                                                $cross_parts[] = '<a href="' . esc_url($cl_url) . '" class="gmat-sp-suggest__link">' . esc_html($cl_label) . '</a>';
                                                            } else {
                                                                $cross_parts[] = esc_html($cl_label);
                                                            }
                                                        }
                                                        echo implode(' &amp; ', $cross_parts);
                                                    elseif ($cross_suggest) :
                                                        echo esc_html($cross_suggest);
                                                    endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                              </div><!-- /.gmat-sp-unit__body-inner -->
                            </div><!-- /.gmat-sp-unit__body -->
                        </div><!-- /.gmat-sp-unit -->
                    <?php endforeach; // units ?>
                </div>
            </div>
        <?php endforeach; // sections ?>
    </div>
    <?php
}


// ============================================================================
// AJAX: Debug xAPI mapping (admin only) — ?gmat_sp_debug_xapi=1 on course page
// ============================================================================

function gmat_sp_debug_xapi_mapping($content) {
    // Only for admins on the study plan course page with ?gmat_sp_debug_xapi=1
    if (!is_singular('sfwd-courses') || get_the_ID() !== GMAT_SP_COURSE_ID) return $content;
    if (!current_user_can('manage_options')) return $content;
    if (!isset($_GET['gmat_sp_debug_xapi'])) return $content;

    // Allow admin to debug a specific user: ?gmat_sp_debug_user=123
    $user_id = get_current_user_id();
    if (isset($_GET['gmat_sp_debug_user'])) {
        $debug_uid = intval($_GET['gmat_sp_debug_user']);
        if ($debug_uid > 0 && get_userdata($debug_uid)) {
            $user_id = $debug_uid;
        }
    }
    $lesson_ids = gmat_sp_get_lesson_ids();

    // Force load xAPI data
    $xapi_map = gmat_sp_get_xapi_status_map($user_id);
    $slug_map = gmat_sp_get_slug_map($lesson_ids);

    $lesson_keys = gmat_sp_get_lesson_keys();

    ob_start();
    echo '<div style="background:#fff;border:2px solid #00409E;padding:20px;margin:20px;font-family:monospace;font-size:12px;max-width:1200px;">';
    echo '<h2 style="color:#00409E;">GMAT Study Plan &mdash; xAPI Tracking Debug</h2>';
    $debug_user = get_userdata($user_id);
    echo '<p><strong>Debugging user:</strong> ' . esc_html($debug_user->user_login) . ' (ID: ' . $user_id . ', email: ' . esc_html($debug_user->user_email) . ')</p>';
    echo '<p style="color:#666;">Tip: Add <code>&amp;gmat_sp_debug_user=USER_ID</code> to debug a different user.</p>';

    // 0. Show raw completed statement object names (for debugging pass/fail parsing)
    $user = get_userdata($user_id);
    if ($user && !empty($user->user_email) && function_exists('grassblade_fetch_statements')) {
        $raw_completed = grassblade_fetch_statements(array(
            'agent_email' => $user->user_email,
            'verb'        => 'http://adlnet.gov/expapi/verbs/completed',
            'limit'       => 50,
        ));
        $raw_stmts = array();
        if (!is_wp_error($raw_completed) && is_array($raw_completed)) {
            $raw_stmts = isset($raw_completed['statements']) ? $raw_completed['statements'] : $raw_completed;
        }

        echo '<h3>Raw Completed Statements (' . count($raw_stmts) . '):</h3>';
        echo '<table style="border-collapse:collapse;width:100%;">';
        echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Object ID</th><th style="border:1px solid #ccc;padding:4px;">Object Name (en-US)</th><th style="border:1px solid #ccc;padding:4px;">Has Result?</th><th style="border:1px solid #ccc;padding:4px;">JSON Parse?</th></tr>';
        foreach ($raw_stmts as $s) {
            $obj_id = isset($s['object']['id']) ? $s['object']['id'] : '(none)';
            $obj_name = isset($s['object']['definition']['name']['en-US']) ? $s['object']['definition']['name']['en-US'] : '(none)';
            $has_result = isset($s['result']) ? (empty($s['result']) ? 'empty {}' : 'yes') : 'no';
            $trimmed = trim($obj_name);
            $json_ok = '—';
            if (!empty($trimmed) && substr($trimmed, 0, 1) === '{') {
                $decoded = json_decode($trimmed, true);
                $json_ok = is_array($decoded) ? 'OK: ' . implode(', ', array_map(function($k, $v) { return $k . '=' . $v; }, array_keys($decoded), $decoded)) : 'FAIL (json_last_error=' . json_last_error() . ')';
            }
            echo '<tr>';
            echo '<td style="border:1px solid #ccc;padding:4px;font-size:10px;word-break:break-all;">' . esc_html($obj_id) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:4px;word-break:break-all;">' . esc_html($obj_name) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($has_result) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($json_ok) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // 1. Show all xAPI activity statuses found for this user
    echo '<h3>All xAPI Activity Statuses (' . count($xapi_map) . '):</h3>';
    echo '<table style="border-collapse:collapse;width:100%;">';
    echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Activity ID</th><th style="border:1px solid #ccc;padding:4px;">Status</th><th style="border:1px solid #ccc;padding:4px;">Matched To</th></tr>';
    // Build reverse map: activity_id => lesson_key
    $reverse_map = array();
    foreach ($slug_map as $lk => $aid) {
        $reverse_map[$aid] = $lk;
    }
    foreach ($xapi_map as $aid => $st) {
        $matched_key = isset($reverse_map[$aid]) ? $reverse_map[$aid] : '—';
        $row_bg = ($matched_key === '—') ? '#fff3e0' : '#e8f5e9';
        echo '<tr style="background:' . $row_bg . ';">';
        echo '<td style="border:1px solid #ccc;padding:4px;font-size:11px;">' . esc_html($aid) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($st) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;font-weight:bold;">' . esc_html($matched_key) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // 2. Show mapping results for ALL lesson keys
    echo '<h3 style="margin-top:20px;">Lesson Key &rarr; Activity ID Mapping:</h3>';
    echo '<table style="border-collapse:collapse;width:100%;">';
    echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Lesson Key</th><th style="border:1px solid #ccc;padding:4px;">Label</th><th style="border:1px solid #ccc;padding:4px;">xAPI Activity ID</th><th style="border:1px solid #ccc;padding:4px;">Tracking Status</th></tr>';
    foreach ($lesson_keys as $lk => $meta) {
        $mapped_aid = isset($slug_map[$lk]) ? $slug_map[$lk] : 'NOT MAPPED';
        $st = 'not-started';
        if (isset($slug_map[$lk]) && isset($xapi_map[$slug_map[$lk]])) {
            $st = $xapi_map[$slug_map[$lk]];
        }

        if ($mapped_aid === 'NOT MAPPED') {
            $bg = '#ffebee'; // red — no xAPI URL
        } elseif ($st === 'completed') {
            $bg = '#e8f5e9'; // green
        } elseif ($st === 'in-progress') {
            $bg = '#fff8e1'; // yellow
        } else {
            $bg = '#fff';    // white — mapped but not started
        }

        echo '<tr style="background:' . $bg . ';">';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($lk) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($meta['label']) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;font-size:11px;">' . esc_html($mapped_aid) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($st) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // 3. Show pass/fail signals
    $pf_map = gmat_sp_get_pass_fail_map($user_id);
    $var_map = gmat_sp_get_pass_fail_variable_map();

    echo '<h3 style="margin-top:20px;">Pass/Fail Signals (' . count($pf_map) . ' detected):</h3>';
    if (empty($pf_map)) {
        echo '<p style="color:#999;">No pass/fail signals found for this user.</p>';
    } else {
        echo '<table style="border-collapse:collapse;width:100%;">';
        echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Variable Name</th><th style="border:1px solid #ccc;padding:4px;">Result</th><th style="border:1px solid #ccc;padding:4px;">Maps To Lesson Key</th></tr>';
        foreach ($pf_map as $var_name => $result) {
            $mapped_lesson = isset($var_map[$var_name]) ? $var_map[$var_name] : '—';
            $bg = ($result === 'Pass') ? '#e8f5e9' : '#ffebee';
            echo '<tr style="background:' . $bg . ';">';
            echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($var_name) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:4px;font-weight:bold;">' . esc_html($result) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($mapped_lesson) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // 4. Show exercise results (3-state: pass/fail/none)
    $exercise_keys = array('cr_exercise_4', 'cr_exercise_5', 'cr_exercise_6', 'cr_exercise_7', 'cr_exercise_8');
    echo '<h3 style="margin-top:20px;">Exercise Results (pass/fail/none):</h3>';
    echo '<table style="border-collapse:collapse;width:100%;">';
    echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Exercise Key</th><th style="border:1px solid #ccc;padding:4px;">Result</th></tr>';
    foreach ($exercise_keys as $ek) {
        $result = gmat_sp_get_exercise_result($user_id, $ek);
        $bg = ($result === 'fail') ? '#fff3e0' : (($result === 'pass') ? '#e8f5e9' : '#ffffff');
        $color = ($result === 'fail') ? '#d32f2f' : (($result === 'pass') ? '#2e7d32' : '#999');
        echo '<tr style="background:' . $bg . ';">';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($ek) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;font-weight:bold;color:' . $color . ';">' . esc_html($result) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // 5. Show review results (3-state: pass/fail/none)
    $review_keys = array('verbal_review_2', 'verbal_review_3', 'verbal_review_4', 'verbal_review_5', 'quant_review_2', 'quant_review_3', 'quant_review_4', 'quant_review_5');
    echo '<h3 style="margin-top:20px;">Review Results (pass/fail/none):</h3>';
    echo '<table style="border-collapse:collapse;width:100%;">';
    echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Review Key</th><th style="border:1px solid #ccc;padding:4px;">Result</th></tr>';
    foreach ($review_keys as $rk) {
        $result = gmat_sp_get_review_result($user_id, $rk);
        $bg = ($result === 'fail') ? '#fff3e0' : (($result === 'pass') ? '#e8f5e9' : '#ffffff');
        $color = ($result === 'fail') ? '#d32f2f' : (($result === 'pass') ? '#2e7d32' : '#999');
        echo '<tr style="background:' . $bg . ';">';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($rk) . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;font-weight:bold;color:' . $color . ';">' . esc_html($result) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // 6. Show quant exercise granular failures (QLE)
    echo '<h3 style="margin-top:20px;">Quant Exercise Failures (QLE signals):</h3>';
    echo '<table style="border-collapse:collapse;width:100%;">';
    echo '<tr style="background:#f0f0f0;"><th style="border:1px solid #ccc;padding:4px;">Exercise #</th><th style="border:1px solid #ccc;padding:4px;">Failed Lesson Keys</th></tr>';
    $qle_learn_sets = array(
        1 => array('pss_lesson_1', 'algebra_1', 'word_problems_1', 'number_props_1'),
        2 => array('pss_lesson_2', 'number_props_2', 'algebra_2', 'word_problems_2', 'fprs_1'),
        3 => array('fprs_2', 'algebra_3', 'word_problems_3', 'word_problems_4'),
        4 => array('algebra_4', 'word_problems_5', 'word_problems_6'),
        5 => array('number_props_3', 'word_problems_7', 'algebra_5'),
    );
    foreach ($qle_learn_sets as $num => $keys) {
        $failures = gmat_sp_get_quant_exercise_failures($user_id, $num, $keys, $lesson_ids);
        $display = !empty($failures) ? implode(', ', $failures) : '(none)';
        $bg = !empty($failures) ? '#fff3e0' : '#ffffff';
        echo '<tr style="background:' . $bg . ';">';
        echo '<td style="border:1px solid #ccc;padding:4px;">QLE ' . $num . '</td>';
        echo '<td style="border:1px solid #ccc;padding:4px;">' . esc_html($display) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<p style="margin-top:15px;color:#666;"><strong>Legend:</strong> Green = completed/pass. Yellow = in-progress. White = not started. Red = no xAPI URL / fail.</p>';
    echo '</div>';

    return ob_get_clean() . $content;
}
add_filter('the_content', 'gmat_sp_debug_xapi_mapping', 1);


// ============================================================================
// AJAX: Refresh study plan (for re-rendering after lesson completion)
// ============================================================================

function gmat_sp_ajax_refresh() {
    check_ajax_referer('gmat_sp_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $user_id    = get_current_user_id();
    $preference = get_user_meta($user_id, '_gmat_intake_section_preference', true);
    if (!in_array($preference, array('verbal', 'quant'))) {
        $preference = 'verbal';
    }

    $lesson_ids = gmat_sp_get_lesson_ids();
    $plan = ($preference === 'verbal')
        ? gmat_sp_build_verbal_first($user_id, $lesson_ids)
        : gmat_sp_build_quant_first($user_id, $lesson_ids);

    ob_start();
    gmat_sp_render($plan, $preference, $user_id, $lesson_ids);
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_gmat_sp_refresh', 'gmat_sp_ajax_refresh');
