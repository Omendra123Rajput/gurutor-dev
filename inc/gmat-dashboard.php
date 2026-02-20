<?php
/**
 * GMAT Dashboard — Paid User Home Page
 *
 * Displays a personalized dashboard for paid users with:
 *   - Welcome banner with user's first name
 *   - GMAT Snapshot cards (goal score, test date, completion %, weekly hours, readiness)
 *   - Course Progress Breakdown (Verbal / Quant / DI progress bars)
 *   - Study Plan CTA card + motivational quote
 *   - Readiness & Planning Metrics (total content, practice tests, score improvement)
 *
 * The dashboard is rendered via shortcode [gmat_dashboard] placed on a WordPress page.
 * Paid users are redirected here after completing the intake form (instead of the course page).
 *
 * Dependencies:
 *   - inc/gmat-study-plan-admin.php    (lesson keys + IDs)
 *   - inc/gmat-study-plan.php          (xAPI tracking + plan building)
 *   - inc/gmat-intake-form.php         (user intake meta)
 *   - inc/free-trial-grassblade-xapi.php (paid access check)
 */

if (!defined('ABSPATH')) exit;

// Dashboard page slug
if (!defined('GMAT_DASHBOARD_PAGE_SLUG')) define('GMAT_DASHBOARD_PAGE_SLUG', 'gmat-dashboard');


// ============================================================================
// ENQUEUE ASSETS
// ============================================================================

function gmat_dash_enqueue_assets() {
    if (!is_page(GMAT_DASHBOARD_PAGE_SLUG)) return;
    if (!is_user_logged_in()) return;

    $v = wp_get_theme()->get('Version');
    // wp_enqueue_style('gmat-dashboard', get_stylesheet_directory_uri() . '/css/gmat-dashboard.css', array(), $v);
}
add_action('wp_enqueue_scripts', 'gmat_dash_enqueue_assets');


// ============================================================================
// SHORTCODE: [gmat_dashboard]
// ============================================================================

function gmat_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your dashboard.</p>';
    }

    // Only for paid users
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) {
        return '<p>This dashboard is available for paid subscribers only.</p>';
    }

    $user_id = get_current_user_id();
    $user    = get_userdata($user_id);

    // ── Get user intake data ──
    $first_name     = $user->first_name ? $user->first_name : $user->display_name;
    $goal_score     = get_user_meta($user_id, '_gmat_intake_goal_score', true);
    $weekly_hours   = get_user_meta($user_id, '_gmat_intake_weekly_hours', true);
    $test_date_raw  = get_user_meta($user_id, '_gmat_intake_next_test_date', true);
    $preference     = get_user_meta($user_id, '_gmat_intake_section_preference', true);

    // Format test date
    $test_date_display = 'Not set';
    if (!empty($test_date_raw)) {
        $ts = strtotime($test_date_raw);
        if ($ts) {
            $test_date_display = date('M d, Y', $ts);
        }
    }

    // Weekly hours display
    $weekly_hours_display = !empty($weekly_hours) ? intval($weekly_hours) . ' Hours' : 'Not set';

    // Goal score display
    $goal_score_display = !empty($goal_score) ? intval($goal_score) : 'Not set';

    // ── Calculate progress from study plan ──
    if (!in_array($preference, array('verbal', 'quant'))) {
        $preference = 'verbal';
    }

    $lesson_ids = function_exists('gmat_sp_get_lesson_ids') ? gmat_sp_get_lesson_ids() : array();

    $plan = array();
    if (function_exists('gmat_sp_build_verbal_first') && function_exists('gmat_sp_build_quant_first')) {
        $plan = ($preference === 'verbal')
            ? gmat_sp_build_verbal_first($user_id, $lesson_ids)
            : gmat_sp_build_quant_first($user_id, $lesson_ids);
    }

    // Calculate per-section and overall progress
    $section_stats   = array();
    $total_items     = 0;
    $completed_items = 0;

    foreach ($plan as $section) {
        $sec_total = 0;
        $sec_done  = 0;
        foreach ($section['units'] as $unit) {
            foreach (array('learn', 'practice', 'review') as $type) {
                foreach ($unit[$type] as $lk) {
                    $sec_total++;
                    $total_items++;
                    if (function_exists('gmat_sp_is_complete') && gmat_sp_is_complete($user_id, $lk, $lesson_ids)) {
                        $sec_done++;
                        $completed_items++;
                    }
                }
            }
        }
        $section_stats[$section['section']] = array('done' => $sec_done, 'total' => $sec_total);
    }

    $overall_pct = $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;

    // ── Get current score (used by readiness + score improvement) ──
    $official_scores_json = get_user_meta($user_id, '_gmat_intake_official_scores', true);
    $practice_scores_json = get_user_meta($user_id, '_gmat_intake_practice_scores', true);
    $current_score = 0;

    // Try official scores first, then practice scores
    if (!empty($official_scores_json)) {
        $scores = json_decode($official_scores_json, true);
        if (is_array($scores) && !empty($scores)) {
            $last = end($scores);
            if (isset($last['overall']) && intval($last['overall']) > 0) {
                $current_score = intval($last['overall']);
            }
        }
    }
    if ($current_score === 0 && !empty($practice_scores_json)) {
        $scores = json_decode($practice_scores_json, true);
        if (is_array($scores) && !empty($scores)) {
            $last = end($scores);
            if (isset($last['overall']) && intval($last['overall']) > 0) {
                $current_score = intval($last['overall']);
            }
        }
    }

    // If no previous score entered, use 555 (GMAT mean score) as baseline
    $starting_score = $current_score > 0 ? $current_score : 555;

    // ── Score Improvement Needed ──
    $score_improvement = 'N/A';
    $points_to_improve = 0;
    if (!empty($goal_score) && intval($goal_score) > $starting_score) {
        $points_to_improve = intval($goal_score) - $starting_score;
        $score_improvement = $points_to_improve;
    } elseif (!empty($goal_score) && intval($goal_score) <= $starting_score) {
        $score_improvement = 0;
    }

    // ── Total Course Content (estimated minutes) ──
    $total_course_minutes = $total_items * 40; // ~40 min per item

    // ── Required Practice Tests ──
    // Baseline: 3 practice tests
    // Add 1 practice test for every 50 points beyond the first 100 points of improvement
    $baseline_practice_tests = 3;
    $additional_practice_tests = 0;
    if ($points_to_improve > 100) {
        $additional_practice_tests = floor(($points_to_improve - 100) / 50);
    }
    $required_practice_tests = $baseline_practice_tests + $additional_practice_tests;

    // ── Estimated Readiness ──
    // Inputs: weekly hours, total course content minutes, required practice tests
    // Each additional practice test adds 2 extra weeks
    $estimated_readiness = 'N/A';
    if (!empty($weekly_hours) && $weekly_hours > 0 && $total_course_minutes > 0) {
        $weekly_minutes = intval($weekly_hours) * 60;

        // Weeks to complete remaining course content
        $remaining_minutes = ($total_items > 0 && $completed_items < $total_items)
            ? ($total_items - $completed_items) * 40
            : 0;

        if ($remaining_minutes > 0) {
            $weeks_for_content = ceil($remaining_minutes / $weekly_minutes);

            // Add 2 extra weeks for each additional practice test (beyond baseline 3)
            $extra_weeks_for_tests = $additional_practice_tests * 2;

            $total_weeks = $weeks_for_content + $extra_weeks_for_tests;
            $ready_ts = strtotime('+' . $total_weeks . ' weeks');
            $estimated_readiness = date('M d, Y', $ready_ts);
        } elseif ($remaining_minutes === 0 && $completed_items > 0) {
            $estimated_readiness = 'Ready!';
        }
    }

    // Study plan URL
    $study_plan_url = home_url('/courses/gurutors-recommended-gmat-program/');

    // ── Motivational quotes ──
    $quotes = array(
        array('text' => 'Education is the passport to the future, for tomorrow belongs to those who prepare for it today.', 'author' => 'Malcolm X.'),
        array('text' => 'The expert in anything was once a beginner.', 'author' => 'Helen Hayes'),
        array('text' => 'Success is the sum of small efforts, repeated day in and day out.', 'author' => 'Robert Collier'),
        array('text' => 'It does not matter how slowly you go as long as you do not stop.', 'author' => 'Confucius'),
        array('text' => 'The secret of getting ahead is getting started.', 'author' => 'Mark Twain'),
    );
    $quote = $quotes[array_rand($quotes)];

    // ── Render ──
    ob_start();
    ?>
    <div id="gmat-dashboard">

        <!-- ── Hero Banner ── -->
        <div class="gmat-dash-hero">
            <div class="gmat-dash-hero__inner">
                <h1 class="gmat-dash-hero__title">Hello <?php echo esc_html($first_name); ?>, welcome back!</h1>
                <p class="gmat-dash-hero__subtitle">Track your progress and stay on course to reach your goal</p>
            </div>
        </div>

        <div class="gmat-dash-body">

            <!-- ── GMAT Snapshot ── -->
            <div class="gmat-dash-snapshot">
                <h2 class="gmat-dash-section-title">GMAT Snapshot</h2>
                <div class="gmat-dash-snapshot__cards">
                    <div class="gmat-dash-snap-card">
                        <span class="gmat-dash-snap-card__label">Goal Score</span>
                        <span class="gmat-dash-snap-card__value"><?php echo esc_html($goal_score_display); ?></span>
                    </div>
                    <div class="gmat-dash-snap-card">
                        <span class="gmat-dash-snap-card__label">GMAT Test Date</span>
                        <span class="gmat-dash-snap-card__value"><?php echo esc_html($test_date_display); ?></span>
                    </div>
                    <div class="gmat-dash-snap-card">
                        <span class="gmat-dash-snap-card__label">Course Completion</span>
                        <span class="gmat-dash-snap-card__value"><?php echo intval($overall_pct); ?>%</span>
                    </div>
                    <div class="gmat-dash-snap-card">
                        <span class="gmat-dash-snap-card__label">Weekly Study Time</span>
                        <span class="gmat-dash-snap-card__value"><?php echo esc_html($weekly_hours_display); ?></span>
                    </div>
                    <div class="gmat-dash-snap-card">
                        <span class="gmat-dash-snap-card__label">Estimated Readiness</span>
                        <span class="gmat-dash-snap-card__value"><?php echo esc_html($estimated_readiness); ?></span>
                    </div>
                </div>
            </div>

            <!-- ── Course Progress Breakdown ── -->
            <div class="gmat-dash-progress">
                <h2 class="gmat-dash-section-title">Course Progress Breakdown</h2>
                <div class="gmat-dash-progress__cards">
                    <?php
                    $color_map = array(
                        'Verbal'        => '#22c55e',
                        'Quant'         => '#5b6abf',
                        'Data Insights' => '#3b82f6',
                    );
                    foreach ($plan as $section) :
                        $st      = isset($section_stats[$section['section']]) ? $section_stats[$section['section']] : array('done' => 0, 'total' => 0);
                        $sec_pct = $st['total'] > 0 ? round(($st['done'] / $st['total']) * 100) : 0;
                        $bar_color = isset($color_map[$section['section']]) ? $color_map[$section['section']] : '#5b6abf';
                    ?>
                        <div class="gmat-dash-progress-card">
                            <div class="gmat-dash-progress-card__top">
                                <span class="gmat-dash-progress-card__label"><?php echo esc_html($section['section']); ?> Modules Completed</span>
                                <span class="gmat-dash-progress-card__count"><?php echo intval($st['done']); ?>/<?php echo intval($st['total']); ?></span>
                            </div>
                            <div class="gmat-dash-progress-card__bar-wrap">
                                <div class="gmat-dash-progress-card__bar" style="width: <?php echo $sec_pct; ?>%; background: <?php echo $bar_color; ?>;"></div>
                            </div>
                            <div class="gmat-dash-progress-card__pct" style="color: <?php echo $bar_color; ?>;"><?php echo $sec_pct; ?>% Complete</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── Bottom 2-column layout ── -->
            <div class="gmat-dash-bottom">
                <div class="gmat-dash-bottom__left">
                    <!-- Study Plan CTA -->
                    <div class="gmat-dash-cta-card">
                        <h2 class="gmat-dash-section-title">GMAT Study Plan</h2>
                        <div class="gmat-dash-cta-card__inner">
                            <span class="gmat-dash-cta-card__text">Continue Your Learning</span>
                            <a href="<?php echo esc_url($study_plan_url); ?>" class="gmat-dash-cta-card__btn">Go to Study Plan</a>
                        </div>
                    </div>

                    <!-- Quote -->
                    <div class="gmat-dash-quote-card">
                        <p class="gmat-dash-quote-card__text">&ldquo;<?php echo esc_html($quote['text']); ?>&rdquo;</p>
                        <p class="gmat-dash-quote-card__author">- <?php echo esc_html($quote['author']); ?></p>
                    </div>
                </div>

                <div class="gmat-dash-bottom__right">
                    <h2 class="gmat-dash-section-title">Readiness &amp; Planning Metrics</h2>

                    <div class="gmat-dash-metric-card">
                        <span class="gmat-dash-metric-card__label">Total Course Content</span>
                        <span class="gmat-dash-metric-card__value"><?php echo number_format($total_course_minutes); ?> minutes</span>
                    </div>

                    <div class="gmat-dash-metric-card">
                        <span class="gmat-dash-metric-card__label">Required Practice Tests</span>
                        <span class="gmat-dash-metric-card__value"><?php echo intval($required_practice_tests); ?></span>
                    </div>

                    <div class="gmat-dash-metric-card">
                        <span class="gmat-dash-metric-card__label">Score Improvement Needed</span>
                        <span class="gmat-dash-metric-card__value"><?php
                            if ($score_improvement === 'N/A') {
                                echo 'N/A';
                            } elseif ($score_improvement === 0) {
                                echo 'Goal reached!';
                            } else {
                                echo intval($score_improvement);
                            }
                        ?></span>
                    </div>
                </div>
            </div>

        </div><!-- /.gmat-dash-body -->
    </div><!-- /#gmat-dashboard -->
    <?php
    return ob_get_clean();
}
add_shortcode('gmat_dashboard', 'gmat_dashboard_shortcode');


// ============================================================================
// REDIRECT: After intake form completion → Dashboard (instead of course page)
// This is handled by updating the AJAX response in gmat-intake-form.php
// ============================================================================

/**
 * Filter the intake form redirect URL to point to the dashboard.
 * This hooks into the AJAX response from gmat_intake_save_preference_handler.
 */
function gmat_dash_override_intake_redirect($response, $handler) {
    // Not used — we directly modify the intake form handler redirect URL.
    // See the filter below.
    return $response;
}

// ============================================================================
// NAVIGATION: Custom nav menu for paid users
// ============================================================================

/**
 * Replace the primary navigation menu items for paid users.
 * Paid users see: Home | Study Plan | Test 2 Module | Statistics | Study Material
 * "Home" links to the dashboard page.
 */
function gmat_dash_paid_user_nav($items, $args) {
    // Only apply to the primary/header navigation
    if (!isset($args->theme_location) || $args->theme_location !== 'primary') {
        return $items;
    }

    // Only for logged-in paid users who have completed intake
    if (!is_user_logged_in()) return $items;
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return $items;

    $user_id = get_current_user_id();
    if (!get_user_meta($user_id, '_gmat_intake_completed', true)) return $items;

    // Replace the "Home" menu item URL to point to dashboard
    foreach ($items as $item) {
        if (strtolower(trim($item->title)) === 'home') {
            $dashboard_page = get_page_by_path(GMAT_DASHBOARD_PAGE_SLUG, OBJECT, 'page');
            if ($dashboard_page) {
                $item->url = get_permalink($dashboard_page->ID);

                // Mark as current if we're on the dashboard page
                if (is_page(GMAT_DASHBOARD_PAGE_SLUG)) {
                    $item->classes[] = 'current-menu-item';
                    $item->classes[] = 'current_page_item';
                }
            }
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'gmat_dash_paid_user_nav', 5, 2);


// ============================================================================
// REDIRECT: Paid users with completed intake visiting site root → Dashboard
// ============================================================================

function gmat_dash_redirect_home_to_dashboard() {
    if (is_admin() || wp_doing_ajax()) return;
    if (!is_user_logged_in()) return;
    if (current_user_can('manage_options')) return;

    // Never interfere with WooCommerce logout / my-account endpoints
    if (is_page('my-account') || is_account_page()) return;
    if (isset($_GET['customer-logout']) || strpos($_SERVER['REQUEST_URI'], 'customer-logout') !== false) return;

    // Only redirect from the front page / home page
    if (!is_front_page() && !is_home()) return;

    // Only for paid users who completed intake
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return;

    $user_id = get_current_user_id();
    if (!get_user_meta($user_id, '_gmat_intake_completed', true)) return;

    // Don't redirect if already on dashboard
    $dashboard_page = get_page_by_path(GMAT_DASHBOARD_PAGE_SLUG, OBJECT, 'page');
    if ($dashboard_page && is_page($dashboard_page->ID)) return;

    // Redirect to dashboard
    if ($dashboard_page) {
        wp_redirect(get_permalink($dashboard_page->ID));
        exit;
    }
}
add_action('template_redirect', 'gmat_dash_redirect_home_to_dashboard', 5);


// ============================================================================
// LOGOUT: Ensure WooCommerce logout redirects to home page (not dashboard)
// ============================================================================

function gmat_dash_logout_redirect($redirect_to, $requested_redirect_to, $user) {
    // After logout, always go to the home page — never to the dashboard
    return home_url('/');
}
add_filter('logout_redirect', 'gmat_dash_logout_redirect', 99, 3);


// ============================================================================
// Hide GeneratePress page title on dashboard page
// ============================================================================

function gmat_dash_hide_page_title($title) {
    if (is_page(GMAT_DASHBOARD_PAGE_SLUG)) {
        return false;
    }
    return $title;
}
add_filter('generate_show_title', 'gmat_dash_hide_page_title');
