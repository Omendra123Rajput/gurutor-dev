<?php
/**
 * GMAT External Prev/Next Lesson Buttons (Free Trial)
 *
 * Injects "Previous Lesson" and "Next Lesson" buttons outside the GrassBlade
 * iframe on lesson / topic pages for the free-trial courses (7472, 9361).
 * Navigation is scoped to the user's personalized Study_Plan_Focus map
 * (`grassblade_get_focus_lesson_map()`), not LearnDash flat order.
 *
 * Flow:
 *   1) Buttons hidden on page load.
 *   2) If the current lesson is already marked completed in the LRS,
 *      neighbor URLs are resolved immediately. Otherwise JS polls the LRS
 *      for a `completed` xAPI statement emitted after page open.
 *   3) On completion, prev/next URLs are resolved against the user's focus
 *      plan; only sides with an in-plan neighbor are revealed.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
// Config
// ============================================================================

if ( ! defined( 'GMAT_NEXT_LESSON_TRIAL_COURSE_IDS' ) ) {
    define( 'GMAT_NEXT_LESSON_TRIAL_COURSE_IDS', '7472,9361' );
}
if ( ! defined( 'GMAT_NEXT_LESSON_PAID_COURSE_ID' ) ) {
    define( 'GMAT_NEXT_LESSON_PAID_COURSE_ID', 8112 );
}

// ============================================================================
// Gate: should the button load on this page?
// ============================================================================

function gmat_next_lesson_should_load() {
    if ( ! is_singular( array( 'sfwd-lessons', 'sfwd-topic' ) ) ) return false;
    if ( ! is_user_logged_in() ) return false;
    if ( ! function_exists( 'learndash_get_course_id' ) ) return false;

    $course_id = intval( learndash_get_course_id( get_the_ID() ) );
    if ( ! $course_id ) return false;

    $allowed = array_map( 'intval', array_filter(
        explode( ',', GMAT_NEXT_LESSON_TRIAL_COURSE_IDS )
    ) );

    return in_array( $course_id, $allowed, true );
}

// ============================================================================
// Enqueue assets
// ============================================================================

add_action( 'wp_enqueue_scripts', 'gmat_next_lesson_enqueue_assets' );
function gmat_next_lesson_enqueue_assets() {
    if ( ! gmat_next_lesson_should_load() ) return;

    $theme_dir  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();
    $css_file   = '/css/gmat-next-lesson.css';
    $js_file    = '/js/gmat-next-lesson.js';

    wp_enqueue_style(
        'gmat-next-lesson',
        $theme_dir . $css_file,
        array(),
        file_exists( $theme_path . $css_file ) ? filemtime( $theme_path . $css_file ) : '1.0.0'
    );

    wp_enqueue_script(
        'gmat-next-lesson',
        $theme_dir . $js_file,
        array( 'jquery' ),
        file_exists( $theme_path . $js_file ) ? filemtime( $theme_path . $js_file ) : '1.0.0',
        true
    );

    $already_completed = false;
    if ( function_exists( 'grassblade_get_free_trial_lesson_status' ) ) {
        $already_completed = ( 'completed' === grassblade_get_free_trial_lesson_status( get_current_user_id(), get_the_ID() ) );
    }

    wp_localize_script( 'gmat-next-lesson', 'gmatNextLesson', array(
        'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
        'nonce'            => wp_create_nonce( 'gmat_next_lesson_nonce' ),
        'lessonId'         => intval( get_the_ID() ),
        'courseId'         => intval( learndash_get_course_id( get_the_ID() ) ),
        'pageOpenIso'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
        'pollMs'           => 15000,
        'maxPolls'         => 40,
        'alreadyCompleted' => $already_completed,
    ) );
}

// ============================================================================
// AJAX: poll LRS for completed verb since page open
// ============================================================================

add_action( 'wp_ajax_gmat_next_lesson_check', 'gmat_next_lesson_check_completion' );
function gmat_next_lesson_check_completion() {
    check_ajax_referer( 'gmat_next_lesson_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not authenticated.' ), 403 );
    }

    if ( ! function_exists( 'grassblade_fetch_statements' ) ) {
        wp_send_json_error( array( 'message' => 'LRS unavailable.' ), 500 );
    }

    $since = isset( $_POST['since'] ) ? sanitize_text_field( wp_unslash( $_POST['since'] ) ) : '';
    if ( '' === $since || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $since ) ) {
        $since = gmdate( 'Y-m-d\TH:i:s\Z', time() - 3600 );
    }

    $user   = wp_get_current_user();
    $result = grassblade_fetch_statements( array(
        'agent_email' => $user->user_email,
        'verb'        => 'http://adlnet.gov/expapi/verbs/completed',
        'since'       => $since,
        'limit'       => 5,
    ) );

    $completed = false;
    if ( ! is_wp_error( $result ) && ! empty( $result['statements'] ) ) {
        $completed = true;
    }

    wp_send_json_success( array( 'completed' => $completed ) );
}

// ============================================================================
// AJAX: resolve next lesson URL within current course
// ============================================================================

add_action( 'wp_ajax_gmat_next_lesson_url', 'gmat_next_lesson_resolve_url' );
function gmat_next_lesson_resolve_url() {
    check_ajax_referer( 'gmat_next_lesson_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not authenticated.' ), 403 );
    }

    $lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
    if ( ! $lesson_id ) {
        wp_send_json_error( array( 'message' => 'Invalid parameters.' ), 400 );
    }

    $neighbors = gmat_next_lesson_resolve_neighbors( get_current_user_id(), $lesson_id );

    wp_send_json_success( array(
        'prev_url' => $neighbors['prev'] ? get_permalink( $neighbors['prev'] ) : '',
        'next_url' => $neighbors['next'] ? get_permalink( $neighbors['next'] ) : '',
    ) );
}

/**
 * Resolve prev/next lesson IDs based on the user's personalized
 * Study_Plan_Focus map (free trial). Returns { prev, next } as post IDs;
 * 0 means "no neighbor in plan" (off-plan, first/last item, or no focus yet).
 */
function gmat_next_lesson_resolve_neighbors( $user_id, $current_id ) {
    $user_id    = intval( $user_id );
    $current_id = intval( $current_id );
    $empty      = array( 'prev' => 0, 'next' => 0 );

    if ( ! $user_id || ! $current_id ) return $empty;
    if ( ! function_exists( 'grassblade_get_study_plan_focus' ) ) return $empty;
    if ( ! function_exists( 'grassblade_get_focus_lesson_map' ) ) return $empty;

    $user = get_userdata( $user_id );
    if ( ! $user || empty( $user->user_email ) ) return $empty;

    $focus = grassblade_get_study_plan_focus( $user->user_email );
    if ( empty( $focus ) ) return $empty;

    $map = grassblade_get_focus_lesson_map();
    $key = strtoupper( $focus );
    if ( ! isset( $map[ $key ]['lessons'] ) || ! is_array( $map[ $key ]['lessons'] ) ) return $empty;

    $ids = array();
    foreach ( $map[ $key ]['lessons'] as $row ) {
        if ( ! empty( $row['lesson_id'] ) ) $ids[] = intval( $row['lesson_id'] );
    }

    $idx = array_search( $current_id, $ids, true );
    if ( false === $idx ) return $empty;

    return array(
        'prev' => isset( $ids[ $idx - 1 ] ) ? intval( $ids[ $idx - 1 ] ) : 0,
        'next' => isset( $ids[ $idx + 1 ] ) ? intval( $ids[ $idx + 1 ] ) : 0,
    );
}

// ============================================================================
// Render hidden button next to iframe (JS injects after iframe container)
// ============================================================================

add_action( 'wp_footer', 'gmat_next_lesson_inject_button' );
function gmat_next_lesson_inject_button() {
    if ( ! gmat_next_lesson_should_load() ) return;
    ?>
    <script type="text/html" id="gmat-next-lesson-template">
        <div class="gmat-next-lesson-wrap">
            <a class="gmat-next-lesson__link gmat-next-lesson__link--prev gmat-next-lesson__link--hidden"
               href="#"
               aria-disabled="true"
               aria-label="<?php esc_attr_e( 'Previous lesson', 'gurutor' ); ?>">
                <span aria-hidden="true">&larr;</span>
                <?php esc_html_e( 'Previous Lesson', 'gurutor' ); ?>
            </a>
            <a class="gmat-next-lesson__link gmat-next-lesson__link--next gmat-next-lesson__link--hidden"
               href="#"
               aria-disabled="true"
               aria-label="<?php esc_attr_e( 'Next lesson', 'gurutor' ); ?>">
                <?php esc_html_e( 'Next Lesson', 'gurutor' ); ?>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </script>
    <?php
}
