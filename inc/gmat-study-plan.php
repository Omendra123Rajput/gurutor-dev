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
 * Fetch and cache all xAPI statuses for a user.
 * Makes exactly 2 API calls per page load (completed + attempted).
 * Returns associative array: activity_id => 'completed' | 'in-progress'
 */
function gmat_sp_get_xapi_status_map($user_id) {
    static $cache = array();

    // Return from cache if already fetched for this user
    if (isset($cache[$user_id])) {
        return $cache[$user_id];
    }

    $status_map = array();

    // Get user email for xAPI agent lookup
    $user = get_userdata($user_id);
    if (!$user || empty($user->user_email)) {
        $cache[$user_id] = $status_map;
        return $status_map;
    }

    $email = $user->user_email;

    // Check that the GrassBlade function exists
    if (!function_exists('grassblade_fetch_statements')) {
        $cache[$user_id] = $status_map;
        return $status_map;
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
                // If no result block at all, the completed verb alone is sufficient
                if (!isset($stmt['result'])) {
                    $is_completed = true;
                }

                if ($is_completed) {
                    $status_map[$activity_id] = 'completed';
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

    // Cache the result for this page load
    $cache[$user_id] = $status_map;
    return $status_map;
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
 * Check if a lesson/exercise has been attempted but NOT completed.
 * This is the condition for showing conditional review lessons and suggestions.
 * Returns true only when the user has started but not finished the exercise.
 */
function gmat_sp_is_attempted_not_complete($user_id, $lesson_key, $lesson_ids) {
    $status = gmat_sp_get_status($user_id, $lesson_key, $lesson_ids);
    return ($status === 'in-progress');
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

    // Verbal Unit 1
    $verbal_units[] = array(
        'title' => 'Unit 1',
        'learn' => array('intro_verbal', 'intro_quant', 'intro_di', 'cr_lesson_1', 'cr_lesson_2', 'rc_lesson_1'),
        'practice' => array('cr_exercise_1', 'cr_exercise_2'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 2
    $verbal_units[] = array(
        'title' => 'Unit 2',
        'learn' => array('cr_lesson_3', 'cr_lesson_4', 'rc_lesson_2', 'rc_lesson_3'),
        'practice' => array('cr_exercise_3', 'rc_exercise_1'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 3
    // Suggestion only shown when CR Exercise 4 has been attempted but not completed
    $v3_suggest = '';
    $v3_suggest_links = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_4', $ids)) {
        $v3_suggest = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $verbal_units[] = array(
        'title' => 'Unit 3',
        'learn' => array('cr_lesson_5'),
        'practice' => array('cr_exercise_4'),
        'review' => array('verbal_review_2'),
        'suggest' => $v3_suggest,
        'suggest_links' => $v3_suggest_links,
        'suggest_redo' => array(),
    );

    // Verbal Unit 4 — conditional: if CR Exercise 4 attempted but not passed, add CR Lesson 5 to review
    $v4_extra_review = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_4', $ids)) {
        $v4_extra_review[] = 'cr_lesson_5';
    }
    $v4_suggest = '';
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_5', $ids)) {
        $v4_suggest = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $verbal_units[] = array(
        'title' => 'Unit 4',
        'learn' => array('cr_lesson_6'),
        'practice' => array('cr_exercise_5'),
        'review' => array_merge($v4_extra_review, array('verbal_review_3')),
        'suggest' => $v4_suggest,
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 5 — conditional: if CR Exercise 5 attempted but not passed, add CR Lesson 6 to review
    $v5_extra_review = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_5', $ids)) {
        $v5_extra_review[] = 'cr_lesson_6';
    }
    $v5_suggest = '';
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_6', $ids)) {
        $v5_suggest = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $verbal_units[] = array(
        'title' => 'Unit 5',
        'learn' => array('cr_lesson_7'),
        'practice' => array('cr_exercise_6'),
        'review' => array_merge($v5_extra_review, array('verbal_review_4')),
        'suggest' => $v5_suggest,
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 6 — conditional: if CR Exercise 6 attempted but not passed, add CR Lesson 7 to review
    $v6_extra_review = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_6', $ids)) {
        $v6_extra_review[] = 'cr_lesson_7';
    }
    $verbal_units[] = array(
        'title' => 'Unit 6',
        'learn' => array('cr_lesson_8', 'cr_lesson_9'),
        'practice' => array('cr_exercise_7', 'cr_exercise_8'),
        'review' => array_merge($v6_extra_review, array('verbal_review_5')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    $plan[] = array('section' => 'Verbal', 'units' => $verbal_units);

    // ── QUANT SECTION ──
    $quant_units = array();

    // Quant Unit 1
    $quant_units[] = array(
        'title' => 'Unit 1',
        'learn' => array('intro_quant'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Quant Unit 2
    $q2_learn = array('pss_lesson_1', 'algebra_1', 'word_problems_1', 'number_props_1');
    $q2_not_passed = array();
    foreach ($q2_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q2_not_passed[] = $lk;
        }
    }
    $quant_units[] = array(
        'title' => 'Unit 2',
        'learn' => $q2_learn,
        'practice' => array('quant_exercise_1'),
        'review' => array(),
        'suggest' => !empty($q2_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q2_not_passed,
    );

    // Quant Unit 3
    $q3_learn = array('pss_lesson_2', 'number_props_2', 'algebra_2', 'word_problems_2', 'fprs_1');
    $q3_not_passed = array();
    foreach ($q3_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q3_not_passed[] = $lk;
        }
    }
    // Add Unit 2 lessons to review only if they were attempted but not completed
    $q3_review_extra = array();
    foreach ($q2_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q3_review_extra[] = $lk;
        }
    }
    // Verbal cross-suggest — only when relevant exercises not completed
    $q3_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'verbal_review_2', $ids)) {
        $q3_cross_links[] = 'rc_exercise_1';
        $q3_cross_links[] = 'verbal_review_2';
    } else {
        $q3_cross_links[] = 'verbal_review_2';
    }
    $quant_units[] = array(
        'title' => 'Unit 3',
        'learn' => $q3_learn,
        'practice' => array('quant_exercise_2'),
        'review' => array_merge($q3_review_extra, array('quant_review_2')),
        'suggest' => !empty($q3_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q3_not_passed,
        'cross_suggest' => '',
        'cross_suggest_links' => $q3_cross_links,
    );

    // Quant Unit 4
    $q4_learn = array('fprs_2', 'algebra_3', 'word_problems_3', 'word_problems_4');
    $q4_not_passed = array();
    foreach ($q4_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q4_not_passed[] = $lk;
        }
    }
    $q4_review_extra = array();
    foreach ($q3_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q4_review_extra[] = $lk;
        }
    }
    $q4_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'verbal_review_3', $ids)) {
        $q4_cross_links[] = 'cr_exercise_4';
        $q4_cross_links[] = 'verbal_review_3';
    } else {
        $q4_cross_links[] = 'verbal_review_3';
    }
    $quant_units[] = array(
        'title' => 'Unit 4',
        'learn' => $q4_learn,
        'practice' => array('quant_exercise_3'),
        'review' => array_merge($q4_review_extra, array('quant_review_3')),
        'suggest' => !empty($q4_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q4_not_passed,
        'cross_suggest' => '',
        'cross_suggest_links' => $q4_cross_links,
    );

    // Quant Unit 5
    $q5_learn = array('algebra_4', 'word_problems_5', 'word_problems_6');
    $q5_not_passed = array();
    foreach ($q5_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q5_not_passed[] = $lk;
        }
    }
    $q5_review_extra = array();
    foreach ($q4_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q5_review_extra[] = $lk;
        }
    }
    $q5_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'verbal_review_4', $ids)) {
        $q5_cross_links[] = 'cr_exercise_5';
        $q5_cross_links[] = 'verbal_review_4';
    } else {
        $q5_cross_links[] = 'verbal_review_4';
    }
    $quant_units[] = array(
        'title' => 'Unit 5',
        'learn' => $q5_learn,
        'practice' => array('quant_exercise_4'),
        'review' => array_merge($q5_review_extra, array('quant_review_4')),
        'suggest' => !empty($q5_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q5_not_passed,
        'cross_suggest' => '',
        'cross_suggest_links' => $q5_cross_links,
    );

    // Quant Unit 6
    $q6_learn = array('number_props_3', 'word_problems_7', 'algebra_5');
    $q6_not_passed = array();
    foreach ($q6_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q6_not_passed[] = $lk;
        }
    }
    $q6_review_extra = array();
    foreach ($q5_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q6_review_extra[] = $lk;
        }
    }
    $q6_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'verbal_review_5', $ids)) {
        $q6_cross_links[] = 'cr_exercise_6';
        $q6_cross_links[] = 'verbal_review_5';
    } else {
        $q6_cross_links[] = 'verbal_review_5';
    }
    $quant_units[] = array(
        'title' => 'Unit 6',
        'learn' => $q6_learn,
        'practice' => array('quant_exercise_5'),
        'review' => array_merge($q6_review_extra, array('quant_review_5')),
        'suggest' => !empty($q6_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q6_not_passed,
        'cross_suggest' => '',
        'cross_suggest_links' => $q6_cross_links,
    );

    $plan[] = array('section' => 'Quant', 'units' => $quant_units);

    // ── DATA INSIGHTS SECTION ──
    $di_units = array();

    // DI Unit 1 — suggest links to CR Lesson 8 & CR Exercise 7 if exercise 7 not completed
    $di1_suggest_links = array();
    if (!gmat_sp_is_complete($user_id, 'cr_exercise_7', $ids)) {
        $di1_suggest_links = array('cr_lesson_8', 'cr_exercise_7');
    } else {
        $di1_suggest_links = array('cr_exercise_7');
    }
    $di_units[] = array(
        'title' => 'Unit 1',
        'learn' => array('intro_di', 'di_lesson_1', 'di_lesson_2', 'di_lesson_3'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => $di1_suggest_links,
        'suggest_redo' => array(),
    );

    // DI Unit 2
    $di2_suggest_links = array();
    if (!gmat_sp_is_complete($user_id, 'cr_exercise_8', $ids)) {
        $di2_suggest_links = array('cr_lesson_9', 'cr_exercise_8');
    } else {
        $di2_suggest_links = array('cr_exercise_8');
    }
    $di_units[] = array(
        'title' => 'Unit 2',
        'learn' => array('di_lesson_4', 'di_lesson_5'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => $di2_suggest_links,
        'suggest_redo' => array(),
    );

    // DI Unit 3
    $di_units[] = array(
        'title' => 'Unit 3',
        'learn' => array('di_lesson_6', 'di_lesson_7'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array('quant_exercise_5'),
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

    // Quant Unit 1
    $quant_units[] = array(
        'title' => 'Unit 1',
        'learn' => array('intro_quant', 'intro_verbal', 'intro_di'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Quant Unit 2
    $q2_learn = array('pss_lesson_1', 'algebra_1', 'word_problems_1', 'number_props_1');
    $q2_not_passed = array();
    foreach ($q2_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q2_not_passed[] = $lk;
        }
    }
    $quant_units[] = array(
        'title' => 'Unit 2',
        'learn' => $q2_learn,
        'practice' => array('quant_exercise_1'),
        'review' => array(),
        'suggest' => !empty($q2_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q2_not_passed,
    );

    // Quant Unit 3
    $q3_learn = array('pss_lesson_2', 'number_props_2', 'algebra_2', 'word_problems_2', 'fprs_1');
    $q3_not_passed = array();
    foreach ($q3_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q3_not_passed[] = $lk;
        }
    }
    $q3_review_extra = array();
    foreach ($q2_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q3_review_extra[] = $lk;
        }
    }
    $quant_units[] = array(
        'title' => 'Unit 3',
        'learn' => $q3_learn,
        'practice' => array('quant_exercise_2'),
        'review' => array_merge($q3_review_extra, array('quant_review_2')),
        'suggest' => !empty($q3_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q3_not_passed,
    );

    // Quant Unit 4
    $q4_learn = array('fprs_2', 'algebra_3', 'word_problems_3', 'word_problems_4');
    $q4_not_passed = array();
    foreach ($q4_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q4_not_passed[] = $lk;
        }
    }
    $q4_review_extra = array();
    foreach ($q3_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q4_review_extra[] = $lk;
        }
    }
    $quant_units[] = array(
        'title' => 'Unit 4',
        'learn' => $q4_learn,
        'practice' => array('quant_exercise_3'),
        'review' => array_merge($q4_review_extra, array('quant_review_3')),
        'suggest' => !empty($q4_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q4_not_passed,
    );

    // Quant Unit 5
    $q5_learn = array('algebra_4', 'word_problems_5', 'word_problems_6');
    $q5_not_passed = array();
    foreach ($q5_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q5_not_passed[] = $lk;
        }
    }
    $q5_review_extra = array();
    foreach ($q4_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q5_review_extra[] = $lk;
        }
    }
    $quant_units[] = array(
        'title' => 'Unit 5',
        'learn' => $q5_learn,
        'practice' => array('quant_exercise_4'),
        'review' => array_merge($q5_review_extra, array('quant_review_4')),
        'suggest' => !empty($q5_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q5_not_passed,
    );

    // Quant Unit 6
    $q6_learn = array('number_props_3', 'word_problems_7', 'algebra_5');
    $q6_not_passed = array();
    foreach ($q6_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q6_not_passed[] = $lk;
        }
    }
    $q6_review_extra = array();
    foreach ($q5_learn as $lk) {
        if (gmat_sp_is_attempted_not_complete($user_id, $lk, $ids)) {
            $q6_review_extra[] = $lk;
        }
    }
    $quant_units[] = array(
        'title' => 'Unit 6',
        'learn' => $q6_learn,
        'practice' => array('quant_exercise_5'),
        'review' => array_merge($q6_review_extra, array('quant_review_5')),
        'suggest' => !empty($q6_not_passed) ? 'You need to improve your understanding/planning/solving' : '',
        'suggest_links' => array(),
        'suggest_redo' => $q6_not_passed,
    );

    $plan[] = array('section' => 'Quant', 'units' => $quant_units);

    // ── VERBAL SECTION ──
    $verbal_units = array();

    // Verbal Unit 1
    $verbal_units[] = array(
        'title' => 'Unit 1',
        'learn' => array('intro_verbal', 'cr_lesson_1', 'cr_lesson_2', 'rc_lesson_1'),
        'practice' => array('cr_exercise_1', 'cr_exercise_2'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 2
    $verbal_units[] = array(
        'title' => 'Unit 2',
        'learn' => array('cr_lesson_3', 'cr_lesson_4', 'rc_lesson_2', 'rc_lesson_3'),
        'practice' => array('cr_exercise_3', 'rc_exercise_1'),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
    );

    // Verbal Unit 3
    $v3_suggest = '';
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_4', $ids)) {
        $v3_suggest = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $v3_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'quant_review_2', $ids)) {
        $v3_cross_links = array('quant_exercise_1', 'quant_review_2');
    } else {
        $v3_cross_links = array('quant_review_2');
    }
    $verbal_units[] = array(
        'title' => 'Unit 3',
        'learn' => array('cr_lesson_5'),
        'practice' => array('cr_exercise_4'),
        'review' => array('verbal_review_2'),
        'suggest' => $v3_suggest,
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v3_cross_links,
    );

    // Verbal Unit 4
    $v4_extra_review = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_4', $ids)) {
        $v4_extra_review[] = 'cr_lesson_5';
    }
    $v4_suggest = '';
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_5', $ids)) {
        $v4_suggest = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $v4_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'quant_review_3', $ids)) {
        $v4_cross_links = array('quant_exercise_2', 'quant_review_3');
    } else {
        $v4_cross_links = array('quant_review_3');
    }
    $verbal_units[] = array(
        'title' => 'Unit 4',
        'learn' => array('cr_lesson_6'),
        'practice' => array('cr_exercise_5'),
        'review' => array_merge($v4_extra_review, array('verbal_review_3')),
        'suggest' => $v4_suggest,
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v4_cross_links,
    );

    // Verbal Unit 5
    $v5_extra_review = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_5', $ids)) {
        $v5_extra_review[] = 'cr_lesson_6';
    }
    $v5_suggest = '';
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_6', $ids)) {
        $v5_suggest = 'You need to improve: identifying, extracting key info, targeting, eliminating';
    }
    $v5_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'quant_review_4', $ids)) {
        $v5_cross_links = array('quant_exercise_3', 'quant_review_4');
    } else {
        $v5_cross_links = array('quant_review_4');
    }
    $verbal_units[] = array(
        'title' => 'Unit 5',
        'learn' => array('cr_lesson_7'),
        'practice' => array('cr_exercise_6'),
        'review' => array_merge($v5_extra_review, array('verbal_review_4')),
        'suggest' => $v5_suggest,
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v5_cross_links,
    );

    // Verbal Unit 6
    $v6_extra_review = array();
    if (gmat_sp_is_attempted_not_complete($user_id, 'cr_exercise_6', $ids)) {
        $v6_extra_review[] = 'cr_lesson_7';
    }
    $v6_cross_links = array();
    if (!gmat_sp_is_complete($user_id, 'quant_review_5', $ids)) {
        $v6_cross_links = array('quant_exercise_4', 'quant_review_5');
    } else {
        $v6_cross_links = array('quant_review_5');
    }
    $verbal_units[] = array(
        'title' => 'Unit 6',
        'learn' => array('cr_lesson_8', 'cr_lesson_9'),
        'practice' => array('cr_exercise_7', 'cr_exercise_8'),
        'review' => array_merge($v6_extra_review, array('verbal_review_5')),
        'suggest' => '',
        'suggest_links' => array(),
        'suggest_redo' => array(),
        'cross_suggest' => '',
        'cross_suggest_links' => $v6_cross_links,
    );

    $plan[] = array('section' => 'Verbal', 'units' => $verbal_units);

    // ── DATA INSIGHTS SECTION ──
    $di_units = array();

    // DI Unit 1
    $di_units[] = array(
        'title' => 'Unit 1',
        'learn' => array('intro_di', 'di_lesson_1', 'di_lesson_2', 'di_lesson_3'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => array('quant_exercise_5'),
        'suggest_redo' => array(),
    );

    // DI Unit 2
    $di2_suggest_links = array();
    if (!gmat_sp_is_complete($user_id, 'cr_exercise_7', $ids)) {
        $di2_suggest_links = array('cr_lesson_8', 'cr_exercise_7');
    } else {
        $di2_suggest_links = array('cr_exercise_7');
    }
    $di_units[] = array(
        'title' => 'Unit 2',
        'learn' => array('di_lesson_4', 'di_lesson_5'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => $di2_suggest_links,
        'suggest_redo' => array(),
    );

    // DI Unit 3
    $di3_suggest_links = array();
    if (!gmat_sp_is_complete($user_id, 'cr_exercise_8', $ids)) {
        $di3_suggest_links = array('cr_lesson_9', 'cr_exercise_8');
    } else {
        $di3_suggest_links = array('cr_exercise_8');
    }
    $di_units[] = array(
        'title' => 'Unit 3',
        'learn' => array('di_lesson_6', 'di_lesson_7'),
        'practice' => array(),
        'review' => array(),
        'suggest' => '',
        'suggest_links' => $di3_suggest_links,
        'suggest_redo' => array(),
    );

    $plan[] = array('section' => 'Data Insights', 'units' => $di_units);

    return $plan;
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
                                            <?php foreach ($unit[$type] as $lk) :
                                                $status  = gmat_sp_get_status($user_id, $lk, $lesson_ids);
                                                $label   = isset($all_keys[$lk]) ? $all_keys[$lk]['label'] : $lk;
                                                $url     = gmat_sp_get_url($lk, $lesson_ids);
                                                $has_id  = isset($lesson_ids[$lk]) && intval($lesson_ids[$lk]) > 0;
                                                $topic   = $has_id ? gmat_sp_get_topic_name($lk, $lesson_ids) : '';
                                            ?>
                                                <div class="gmat-sp-lesson gmat-sp-lesson--<?php echo $status; ?>">
                                                    <div class="gmat-sp-lesson__number-col">
                                                        <span class="gmat-sp-lesson__number gmat-sp-lesson__number--<?php echo $status; ?>"><?php echo $lesson_num; ?></span>
                                                    </div>
                                                    <div class="gmat-sp-lesson__info">
                                                        <span class="gmat-sp-lesson__name"><?php echo esc_html($label); ?></span>
                                                        <?php if ($topic) : ?>
                                                            <span class="gmat-sp-lesson__topic">Topic: <?php echo esc_html($topic); ?></span>
                                                        <?php else : ?>
                                                            <span class="gmat-sp-lesson__topic">Topic</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="gmat-sp-lesson__actions">
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

    $user_id = get_current_user_id();
    $lesson_ids = gmat_sp_get_lesson_ids();

    // Force load xAPI data
    $xapi_map = gmat_sp_get_xapi_status_map($user_id);
    $slug_map = gmat_sp_get_slug_map($lesson_ids);

    $lesson_keys = gmat_sp_get_lesson_keys();

    ob_start();
    echo '<div style="background:#fff;border:2px solid #00409E;padding:20px;margin:20px;font-family:monospace;font-size:12px;max-width:1200px;">';
    echo '<h2 style="color:#00409E;">GMAT Study Plan &mdash; xAPI Tracking Debug</h2>';

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

    echo '<p style="margin-top:15px;color:#666;"><strong>Legend:</strong> Green = completed. Yellow = in-progress. White = not started. Red = no xAPI URL configured.</p>';
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
