<?php
/**
 * GMAT External Next Lesson Button
 *
 * Injects a "Next Lesson" button outside the GrassBlade iframe on lesson /
 * topic pages for trial courses (7472, 9361) and paid course (8112).
 *
 * Flow:
 *   1) Button hidden on page load.
 *   2) JS polls LRS (via AJAX) for any `completed` xAPI statement from the
 *      current user emitted after the page was opened.
 *   3) Once detected, next-lesson URL is resolved via LearnDash and the
 *      button fades in. Clicking opens the next lesson in a new tab.
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

    $allowed = array_map( 'intval', array_filter( array_merge(
        explode( ',', GMAT_NEXT_LESSON_TRIAL_COURSE_IDS ),
        array( GMAT_NEXT_LESSON_PAID_COURSE_ID )
    ) ) );

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

    wp_localize_script( 'gmat-next-lesson', 'gmatNextLesson', array(
        'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'gmat_next_lesson_nonce' ),
        'lessonId'    => intval( get_the_ID() ),
        'courseId'    => intval( learndash_get_course_id( get_the_ID() ) ),
        'pageOpenIso' => gmdate( 'Y-m-d\TH:i:s\Z' ),
        'pollMs'      => 15000,
        'maxPolls'    => 40,
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
    $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

    if ( ! $lesson_id || ! $course_id ) {
        wp_send_json_error( array( 'message' => 'Invalid parameters.' ), 400 );
    }

    if ( ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
        wp_send_json_error( array( 'message' => 'LearnDash unavailable.' ), 500 );
    }

    $next_id = gmat_next_lesson_find_next_step( $course_id, $lesson_id );

    if ( ! $next_id ) {
        wp_send_json_success( array(
            'next_url' => get_permalink( $course_id ),
            'is_last'  => true,
        ) );
    }

    wp_send_json_success( array(
        'next_url' => get_permalink( $next_id ),
        'is_last'  => false,
    ) );
}

/**
 * Find next lesson or topic within the same course.
 * Flattens lessons + topics in LearnDash step order.
 *
 * @return int  next step post ID, or 0 if current is last.
 */
function gmat_next_lesson_find_next_step( $course_id, $current_id ) {
    $course_id  = intval( $course_id );
    $current_id = intval( $current_id );
    if ( ! $course_id || ! $current_id ) return 0;

    $lessons = function_exists( 'learndash_course_get_steps_by_type' )
        ? learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' )
        : array();

    $flat = array();
    if ( is_array( $lessons ) ) {
        foreach ( $lessons as $lesson_id ) {
            $flat[] = intval( $lesson_id );
            if ( function_exists( 'learndash_get_topic_list' ) ) {
                $topics = learndash_get_topic_list( $lesson_id, $course_id );
                if ( is_array( $topics ) ) {
                    foreach ( $topics as $topic ) {
                        $topic_id = is_object( $topic ) && isset( $topic->ID ) ? intval( $topic->ID ) : 0;
                        if ( $topic_id ) $flat[] = $topic_id;
                    }
                }
            }
        }
    }

    $flat = array_values( array_unique( array_filter( $flat ) ) );
    $idx  = array_search( $current_id, $flat, true );

    if ( false === $idx ) return 0;
    if ( ! isset( $flat[ $idx + 1 ] ) ) return 0;

    return intval( $flat[ $idx + 1 ] );
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
            <a class="gmat-next-lesson__link gmat-next-lesson__link--hidden"
               href="#"
               target="_blank"
               rel="noopener noreferrer"
               aria-disabled="true"
               aria-label="<?php esc_attr_e( 'Next lesson', 'gurutor' ); ?>">
                <?php esc_html_e( 'Next Lesson', 'gurutor' ); ?>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </script>
    <?php
}
