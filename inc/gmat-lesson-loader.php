<?php
/**
 * GMAT Lesson Loader
 *
 * Branded full-screen overlay shown while a lesson module is loading.
 *
 * Triggers:
 *   - Click of Start Lesson / Continue / Review on paid study plan (course 8112)
 *   - Click of Start Lesson on free trial study plan shortcode pages
 *   - Click of "Back to Course" on lesson/topic pages
 *
 * On lesson/topic pages of courses 7472, 9361, 8112 the overlay shows on
 * page load and dismisses when `.grassblade iframe.grassblade_iframe` fires
 * its `load` event (15s safety timeout otherwise).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'GMAT_LESSON_LOADER_PAID_COURSE_ID' ) ) {
    define( 'GMAT_LESSON_LOADER_PAID_COURSE_ID', 8112 );
}
if ( ! defined( 'GMAT_LESSON_LOADER_DEST_COURSE_IDS' ) ) {
    define( 'GMAT_LESSON_LOADER_DEST_COURSE_IDS', '7472,9361,8112' );
}
if ( ! defined( 'GMAT_LESSON_LOADER_TIMEOUT_MS' ) ) {
    define( 'GMAT_LESSON_LOADER_TIMEOUT_MS', 15000 );
}

/**
 * Returns 'destination' on lesson/topic pages of target courses,
 * 'source' on study plan pages (paid course 8112 or any singular page
 * containing a free-trial study plan shortcode), or false otherwise.
 *
 * @return string|false
 */
function gmat_lesson_loader_page_type() {
    static $cached = null;
    if ( null !== $cached ) return $cached;

    // Destination: lesson/topic page within a target course.
    if ( is_singular( array( 'sfwd-lessons', 'sfwd-topic' ) ) ) {
        if ( ! function_exists( 'learndash_get_course_id' ) ) {
            return $cached = false;
        }
        $course_id = intval( learndash_get_course_id( get_the_ID() ) );
        $allowed   = array_map( 'intval', array_filter(
            explode( ',', GMAT_LESSON_LOADER_DEST_COURSE_IDS )
        ) );
        return $cached = ( $course_id && in_array( $course_id, $allowed, true ) )
            ? 'destination'
            : false;
    }

    // Source: paid study plan course view.
    if ( is_singular( 'sfwd-courses' )
        && intval( get_the_ID() ) === intval( GMAT_LESSON_LOADER_PAID_COURSE_ID ) ) {
        return $cached = 'source';
    }

    // Source: any singular page containing a free-trial study plan
    // shortcode — checks both post_content and Elementor data.
    if ( is_singular() ) {
        $post = get_post();
        if ( $post ) {
            $haystack = (string) $post->post_content;
            $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
            if ( is_string( $elementor_data ) && '' !== $elementor_data ) {
                $haystack .= ' ' . $elementor_data;
            }
            $shortcodes = array(
                'grassblade_study_plan_focus',
                'grassblade_study_plan_test2',
                'grassblade_study_plan',
            );
            foreach ( $shortcodes as $tag ) {
                if ( false !== strpos( $haystack, '[' . $tag ) ) {
                    return $cached = 'source';
                }
            }

            // Source: free-trial diagnostic CTAs (user-defined Elementor IDs).
            $cta_markers = array(
                'free-trial-test-1',
                'personalized-gmatz-cta',
            );
            foreach ( $cta_markers as $marker ) {
                if ( false !== strpos( $haystack, $marker ) ) {
                    return $cached = 'source';
                }
            }
        }
    }

    return $cached = false;
}

add_action( 'wp_enqueue_scripts', 'gmat_lesson_loader_enqueue_assets' );
function gmat_lesson_loader_enqueue_assets() {
    $page_type = gmat_lesson_loader_page_type();
    if ( ! $page_type ) return;

    $theme_dir  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();
    $css_rel    = '/css/gmat-lesson-loader.css';
    $js_rel     = '/js/gmat-lesson-loader.js';
    $css_ver    = file_exists( $theme_path . $css_rel ) ? filemtime( $theme_path . $css_rel ) : '1.0.0';
    $js_ver     = file_exists( $theme_path . $js_rel )  ? filemtime( $theme_path . $js_rel )  : '1.0.0';

    wp_enqueue_style( 'gmat-lesson-loader', $theme_dir . $css_rel, array(), $css_ver );
    wp_enqueue_script( 'gmat-lesson-loader', $theme_dir . $js_rel, array( 'jquery' ), $js_ver, true );

    wp_localize_script( 'gmat-lesson-loader', 'gmatLessonLoader', array(
        'pageType'          => $page_type,
        'fallbackTimeoutMs' => intval( GMAT_LESSON_LOADER_TIMEOUT_MS ),
    ) );
}

add_action( 'wp_footer', 'gmat_lesson_loader_render_overlay', 50 );
function gmat_lesson_loader_render_overlay() {
    if ( ! gmat_lesson_loader_page_type() ) return;
    ?>
    <div class="gmat-loader__overlay" id="gmat-loader-overlay" aria-hidden="true" role="status" aria-live="polite">
        <div class="gmat-loader__inner">
            <div class="gmat-loader__brand">GURUTOR</div>
            <div class="gmat-loader__spinner" aria-hidden="true"></div>
            <div class="gmat-loader__text">Loading module&hellip;</div>
        </div>
    </div>
    <?php
}
