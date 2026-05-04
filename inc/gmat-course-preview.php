<?php
/**
 * GMAT Course Preview (Locked)
 *
 * Renders the paid-course (8112) study plan structure as a read-only,
 * locked accordion for placement on the Packages / pricing page.
 *
 * Usage: drop the shortcode [gmat_course_preview] onto the /packages/ page.
 *
 * Dependencies:
 *   - inc/gmat-study-plan.php          (reuses plan builder + lesson key helpers)
 *   - inc/gmat-study-plan-admin.php    (lesson ID mapping defaults)
 *   - css/gmat-study-plan.css          (base accordion/lesson styles)
 *   - css/gmat-course-preview.css      (locked-state additions)
 *   - js/gmat-study-plan.js            (shared accordion toggle logic)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
// Enqueue assets (only when the shortcode renders or on /packages/)
// ============================================================================

add_action( 'wp_enqueue_scripts', 'gmat_course_preview_enqueue_assets' );
function gmat_course_preview_enqueue_assets() {
    if ( ! gmat_course_preview_should_enqueue() ) return;

    $theme_dir  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();
    $v          = wp_get_theme()->get( 'Version' );

    // Base study plan CSS (shared look)
    if ( file_exists( $theme_path . '/css/gmat-study-plan.css' ) ) {
        wp_enqueue_style(
            'gmat-study-plan',
            $theme_dir . '/css/gmat-study-plan.css',
            array(),
            $v
        );
    }

    // Locked-state overrides
    if ( file_exists( $theme_path . '/css/gmat-course-preview.css' ) ) {
        wp_enqueue_style(
            'gmat-course-preview',
            $theme_dir . '/css/gmat-course-preview.css',
            array( 'gmat-study-plan' ),
            filemtime( $theme_path . '/css/gmat-course-preview.css' )
        );
    }

    // Reuse existing accordion JS
    if ( file_exists( $theme_path . '/js/gmat-study-plan.js' ) ) {
        wp_enqueue_script(
            'gmat-study-plan',
            $theme_dir . '/js/gmat-study-plan.js',
            array( 'jquery' ),
            $v,
            true
        );
    }
}

function gmat_course_preview_should_enqueue() {
    if ( is_page( 'packages' ) ) return true;

    // Also load when the shortcode is explicitly present on any page
    global $post;
    if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'gmat_course_preview' ) ) {
        return true;
    }
    return false;
}

// ============================================================================
// Shortcode: [gmat_course_preview]
// ============================================================================

add_shortcode( 'gmat_course_preview', 'gmat_course_preview_shortcode' );
function gmat_course_preview_shortcode( $atts = array() ) {
    $atts = shortcode_atts( array(
        'preference' => 'verbal', // 'verbal' | 'quant'
        'heading'    => '',
        'subheading' => '',
    ), $atts, 'gmat_course_preview' );

    if ( ! function_exists( 'gmat_sp_get_lesson_ids' )
        || ! function_exists( 'gmat_sp_build_verbal_first' )
        || ! function_exists( 'gmat_sp_build_quant_first' )
        || ! function_exists( 'gmat_sp_get_lesson_keys' ) ) {
        return '';
    }

    $lesson_ids = gmat_sp_get_lesson_ids();

    // Build plan for guest (user_id=0) — xAPI calls short-circuit for invalid user.
    $plan = ( 'quant' === $atts['preference'] )
        ? gmat_sp_build_quant_first( 0, $lesson_ids )
        : gmat_sp_build_verbal_first( 0, $lesson_ids );

    ob_start();
    gmat_course_preview_render( $plan, $lesson_ids, $atts );
    return ob_get_clean();
}

// ============================================================================
// Shortcode: [gmat_preview_scroll_cue] — inline CTA that smooth-scrolls to
// the study plan preview. Drop into Elementor wherever needed.
//
// Attributes:
//   label  — button text (default: "Preview Study Plan")
//   target — element ID to scroll to (default: "gmat-study-plan")
// ============================================================================

add_shortcode( 'gmat_preview_scroll_cue', 'gmat_preview_scroll_cue_shortcode' );
function gmat_preview_scroll_cue_shortcode( $atts = array() ) {
    static $printed_script = false;

    $atts = shortcode_atts( array(
        'label'  => __( 'Preview Study Plan', 'gurutor' ),
        'target' => 'gmat-study-plan',
    ), $atts, 'gmat_preview_scroll_cue' );

    $target = sanitize_html_class( $atts['target'] );
    if ( '' === $target ) {
        $target = 'gmat-study-plan';
    }

    ob_start();
    ?>
    <div class="gmat-sp-scroll-cue">
        <a href="#<?php echo esc_attr( $target ); ?>" class="gmat-sp-scroll-cue__link" data-gmat-scroll>
            <span class="gmat-sp-scroll-cue__label"><?php echo esc_html( $atts['label'] ); ?></span>
            <span class="gmat-sp-scroll-cue__arrow" aria-hidden="true">&darr;</span>
        </a>
    </div>
    <?php

    if ( ! $printed_script ) {
        $printed_script = true;
        ?>
        <script>
        (function () {
            document.addEventListener('click', function (e) {
                var link = e.target.closest && e.target.closest('[data-gmat-scroll]');
                if (!link) return;
                var hash = link.getAttribute('href') || '';
                if (hash.charAt(0) !== '#') return;
                var target = document.getElementById(hash.slice(1));
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        })();
        </script>
        <?php
    }

    return ob_get_clean();
}


// ============================================================================
// Render: locked preview accordion
// ============================================================================

function gmat_course_preview_render( $plan, $lesson_ids, $atts ) {
    $all_keys = gmat_sp_get_lesson_keys();

    $heading    = ! empty( $atts['heading'] )
        ? $atts['heading']
        : __( 'Preview: Paid Course Study Plan', 'gurutor' );
    $subheading = ! empty( $atts['subheading'] )
        ? $atts['subheading']
        : __( 'Below is the complete study plan you unlock with a paid subscription. All lessons are locked here — subscribe to start learning.', 'gurutor' );
    ?>
    <div id="gmat-study-plan" class="gmat-study-plan gmat-study-plan--preview">

        <div class="gmat-sp-preview-intro">
            <span class="gmat-sp-preview-intro__eyebrow"><?php esc_html_e( 'Course Preview', 'gurutor' ); ?></span>
            <h2 class="gmat-sp-preview-intro__title"><?php echo esc_html( $heading ); ?></h2>
            <p class="gmat-sp-preview-intro__sub"><?php echo esc_html( $subheading ); ?></p>
        </div>

        <?php foreach ( $plan as $si => $section ) :
            $sec_label_map = array(
                'Verbal'        => 'Verbal Modules',
                'Quant'         => 'Quant Modules',
                'Data Insights' => 'Data Insights Modules',
            );
            $sec_title = isset( $sec_label_map[ $section['section'] ] )
                ? $sec_label_map[ $section['section'] ]
                : $section['section'] . ' Modules';
        ?>
            <div class="gmat-sp-section" id="sp-preview-section-<?php echo esc_attr( sanitize_title( $section['section'] ) ); ?>">
                <h2 class="gmat-sp-section__title"><?php echo esc_html( $sec_title ); ?></h2>
                <div class="gmat-sp-section__card">
                    <?php foreach ( $section['units'] as $ui => $unit ) :
                        $unit_total = 0;
                        foreach ( array( 'learn', 'practice', 'review' ) as $t ) {
                            if ( ! empty( $unit[ $t ] ) && is_array( $unit[ $t ] ) ) {
                                $unit_total += count( $unit[ $t ] );
                            }
                        }
                    ?>
                        <div class="gmat-sp-unit gmat-sp-unit--not-started gmat-sp-unit--locked" data-unit-state="not-started">

                            <div class="gmat-sp-unit__header">
                                <div class="gmat-sp-unit__header-left">
                                    <span class="gmat-sp-unit__state-icon gmat-sp-unit__state-icon--not-started">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="9" stroke="#cbd5e1" stroke-width="1.5"/></svg>
                                    </span>
                                    <span class="gmat-sp-unit__title"><?php echo esc_html( $unit['title'] ); ?></span>
                                    <span class="gmat-sp-unit__progress-text"><?php echo intval( $unit_total ); ?> Lessons</span>
                                </div>
                                <div class="gmat-sp-unit__header-right">
                                    <span class="gmat-sp-unit__chevron">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </div>
                            </div>

                            <div class="gmat-sp-unit__body">
                              <div class="gmat-sp-unit__body-inner">
                                <?php if ( ! empty( $unit['description'] ) ) : ?>
                                    <p class="gmat-sp-unit__desc"><?php echo esc_html( $unit['description'] ); ?></p>
                                <?php endif; ?>

                                <?php
                                $sub_sections = array(
                                    'learn'    => 'Learn',
                                    'practice' => 'Practice',
                                    'review'   => 'Review',
                                );
                                foreach ( $sub_sections as $type => $type_label ) :
                                    if ( empty( $unit[ $type ] ) || ! is_array( $unit[ $type ] ) ) continue;
                                    $lesson_num = 1;
                                ?>
                                    <div class="gmat-sp-subsection">
                                        <div class="gmat-sp-subsection__badge gmat-sp-subsection__badge--<?php echo esc_attr( $type ); ?>">
                                            UNIT <?php echo intval( $ui + 1 ); ?> - <?php echo esc_html( strtoupper( $type_label ) ); ?>
                                        </div>

                                        <div class="gmat-sp-lesson-list">
                                            <?php foreach ( $unit[ $type ] as $lk ) :
                                                $label = isset( $all_keys[ $lk ]['label'] ) ? $all_keys[ $lk ]['label'] : $lk;
                                                $topic = isset( $all_keys[ $lk ]['topic'] ) ? $all_keys[ $lk ]['topic'] : '';
                                            ?>
                                                <div class="gmat-sp-lesson gmat-sp-lesson--not-started gmat-sp-lesson--locked" aria-disabled="true">
                                                    <div class="gmat-sp-lesson__top-row">
                                                        <div class="gmat-sp-lesson__number-col">
                                                            <span class="gmat-sp-lesson__number gmat-sp-lesson__number--not-started"><?php echo intval( $lesson_num ); ?></span>
                                                        </div>
                                                        <div class="gmat-sp-lesson__info">
                                                            <span class="gmat-sp-lesson__name"><?php echo esc_html( $label ); ?></span>
                                                            <?php if ( $topic ) : ?>
                                                                <span class="gmat-sp-lesson__topic">Topic: <?php echo esc_html( $topic ); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="gmat-sp-lesson__actions">
                                                            <span class="gmat-sp-lesson__btn gmat-sp-lesson__btn--locked" aria-hidden="true">
                                                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M4 6V4a3 3 0 016 0v2M3 6h8v6H3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                Locked
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                                $lesson_num++;
                                            endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                              </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
