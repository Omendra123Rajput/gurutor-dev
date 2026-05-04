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
            <div class="gmat-loader__logo">
                <svg class="gmat-loader__logo-svg" viewBox="0 0 448.375 100.734" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                    <circle class="gmat-loader__logo-dot" fill="#FBB03B" cx="80.878" cy="9.46" r="7.08"/>
                    <path fill="#4E80FF" d="M64.294,64.875c-11.446,0-22.423,5.803-29.137,15.088c-2.143,3.171-5.703,5.07-9.523,5.07c-6.328,0-11.476-5.146-11.476-11.474c0-3.287,1.082-6.106,2.967-7.747l0,0.001l7.129-6.13c5.334,3.75,11.825,5.961,18.827,5.961c18.098,0,32.821-14.724,32.821-32.822C75.902,14.725,61.179,0,43.081,0S10.26,14.725,10.26,32.823c0,5.98,1.615,11.586,4.421,16.42l-6.786,5.835l3.296,3.834l-3.307-3.827C2.874,59.415,0,66.15,0,73.563c0,14.135,11.499,25.634,25.634,25.634c8.454,0,16.33-4.151,21.123-11.111l0.008,0.006c4.061-5.615,10.613-9.058,17.529-9.058c11.918,0,21.614,9.521,21.614,21.521h14.158C100.066,80.555,84.019,64.875,64.294,64.875z M43.081,14.158c10.291,0,18.663,8.372,18.663,18.664c0,10.291-8.372,18.663-18.663,18.663s-18.663-8.372-18.663-18.663C24.418,22.531,32.79,14.158,43.081,14.158z"/>
                    <g class="gmat-loader__logo-word" fill="#00409E">
                        <path d="M162.843,30.249c4.017,3.037,6.517,7.146,7.497,12.333h-12.683c-0.841-1.774-2.126-3.188-3.854-4.239c-1.728-1.051-3.808-1.577-6.236-1.577c-3.783,0-6.82,1.273-9.108,3.819c-2.29,2.547-3.434,5.991-3.434,10.335c0,4.72,1.202,8.339,3.608,10.86c2.405,2.522,5.827,3.784,10.266,3.784c2.849,0,5.348-0.781,7.497-2.348c2.148-1.564,3.667-3.795,4.555-6.691h-15.135v-8.619h25.155v11.843c-0.936,2.896-2.43,5.583-4.484,8.059c-2.057,2.478-4.685,4.484-7.884,6.026c-3.2,1.541-6.855,2.313-10.966,2.313c-4.999,0-9.366-1.063-13.104-3.188c-3.737-2.125-6.621-5.104-8.653-8.936c-2.032-3.829-3.048-8.197-3.048-13.104c0-4.904,1.016-9.271,3.048-13.104c2.032-3.83,4.905-6.809,8.618-8.935c3.714-2.125,8.047-3.188,12.999-3.188C153.709,25.694,158.825,27.213,162.843,30.249z"/>
                        <path d="M189.146,26.186v28.589c0,3.271,0.736,5.771,2.209,7.497c1.471,1.729,3.607,2.593,6.41,2.593c2.803,0,4.941-0.862,6.412-2.593c1.471-1.728,2.207-4.227,2.207-7.497V26.186h11.982v28.589c0,4.672-0.91,8.607-2.732,11.808c-1.822,3.199-4.311,5.604-7.463,7.218c-3.154,1.611-6.717,2.417-10.686,2.417c-3.971,0-7.475-0.794-10.512-2.382c-3.037-1.588-5.42-3.994-7.146-7.218c-1.729-3.225-2.594-7.171-2.594-11.843V26.186H189.146z"/>
                        <path d="M252.105,75.726l-10.722-18.709h-2.731v18.709H226.67v-49.54h20.461c3.924,0,7.24,0.678,9.949,2.032c2.709,1.354,4.742,3.212,6.098,5.57c1.354,2.358,2.031,5.057,2.031,8.093c0,3.598-0.992,6.657-2.979,9.18c-1.987,2.523-4.869,4.251-8.651,5.187l11.771,19.479H252.105z M238.652,48.818h7.429c2.383,0,4.133-0.524,5.254-1.576s1.684-2.604,1.684-4.659c0-1.962-0.574-3.504-1.717-4.625c-1.146-1.121-2.887-1.683-5.221-1.683h-7.429V48.818z"/>
                        <path d="M282.641,26.186v28.589c0,3.271,0.736,5.771,2.209,7.497c1.471,1.729,3.607,2.593,6.41,2.593s4.941-0.862,6.412-2.593c1.471-1.728,2.207-4.227,2.207-7.497V26.186h11.981v28.589c0,4.672-0.909,8.607-2.731,11.808c-1.822,3.199-4.312,5.604-7.463,7.218c-3.154,1.611-6.717,2.417-10.687,2.417c-3.972,0-7.476-0.794-10.513-2.382c-3.036-1.588-5.42-3.994-7.145-7.218c-1.73-3.225-2.595-7.171-2.595-11.843V26.186H282.641z"/>
                        <path d="M354.484,26.186v9.529h-13.452v40.011h-12.054V35.715h-13.313v-9.529H354.484z"/>
                        <path d="M392.219,28.673c3.807,2.172,6.807,5.186,9.004,9.039c2.195,3.854,3.293,8.21,3.293,13.067c0,4.859-1.111,9.228-3.328,13.104c-2.221,3.878-5.232,6.901-9.039,9.074c-3.809,2.172-8.023,3.258-12.648,3.258s-8.842-1.086-12.647-3.258c-3.808-2.173-6.819-5.196-9.039-9.074c-2.22-3.877-3.328-8.244-3.328-13.104c0-4.857,1.108-9.215,3.328-13.067c2.22-3.854,5.231-6.867,9.039-9.039s8.022-3.259,12.647-3.259C384.17,25.414,388.411,26.501,392.219,28.673z M370.145,40.375c-2.311,2.546-3.467,6.015-3.467,10.404c0,4.346,1.156,7.803,3.467,10.37c2.313,2.57,5.432,3.854,9.355,3.854c3.877,0,6.981-1.284,9.319-3.854c2.335-2.567,3.503-6.024,3.503-10.37c0-4.345-1.156-7.801-3.47-10.369c-2.312-2.569-5.431-3.854-9.354-3.854S372.458,37.829,370.145,40.375z"/>
                        <path d="M435.131,75.726l-10.721-18.709h-2.732v18.709h-11.98v-49.54h20.461c3.924,0,7.24,0.678,9.949,2.032c2.709,1.354,4.74,3.212,6.098,5.57c1.354,2.358,2.031,5.057,2.031,8.093c0,3.598-0.992,6.657-2.98,9.18c-1.986,2.523-4.869,4.251-8.65,5.187l11.77,19.479H435.131z M421.678,48.818h7.43c2.383,0,4.133-0.524,5.254-1.576s1.684-2.604,1.684-4.659c0-1.962-0.574-3.504-1.717-4.625c-1.146-1.121-2.887-1.683-5.221-1.683h-7.43V48.818z"/>
                    </g>
                </svg>
            </div>
            <div class="gmat-loader__spinner" aria-hidden="true"></div>
            <div class="gmat-loader__text">Loading module&hellip;</div>
        </div>
    </div>
    <?php
}
