<?php
/**
 * GMAT Intake Form
 *
 * Multi-step onboarding wizard for paid users to collect:
 * - Previous GMAT scores (official & practice)
 * - Goal score
 * - Weekly study hours
 * - Next test date
 * - Quant/Verbal preference (determines study plan order)
 *
 * Shortcode: [gmat_intake_form]
 * Page: /gmat-intake/
 */

if (!defined('ABSPATH')) exit;


// ============================================================================
// CONSTANTS
// ============================================================================

if (!defined('GMAT_INTAKE_PAGE_SLUG')) define('GMAT_INTAKE_PAGE_SLUG', 'gmat-intake');


// ============================================================================
// ASSET ENQUEUE (only on intake page)
// ============================================================================

function gmat_intake_enqueue_assets() {
    $intake_page = get_page_by_path(GMAT_INTAKE_PAGE_SLUG, OBJECT, 'page');
    if (!$intake_page || !is_page($intake_page->ID)) return;

    $theme_version = wp_get_theme()->get('Version');

    // wp_enqueue_style(
    //     'gmat-intake',
    //     get_stylesheet_directory_uri() . '/css/gmat-intake.css',
    //     array(),
    //     $theme_version
    // );

    wp_enqueue_script(
        'gmat-intake',
        get_stylesheet_directory_uri() . '/js/gmat-intake.js',
        array('jquery'),
        $theme_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'gmat_intake_enqueue_assets');


// ============================================================================
// REDIRECT: Send paid users to intake form if not completed
// ============================================================================

function gmat_intake_redirect_if_not_completed() {
    if (is_admin() || wp_doing_ajax()) return;
    if (!is_user_logged_in()) return;

    // Skip admins
    if (current_user_can('manage_options')) return;

    // Only for users with active paid access
    if (!function_exists('gurutor_user_has_active_paid_access') || !gurutor_user_has_active_paid_access()) return;

    $user_id = get_current_user_id();

    // Skip if intake already completed
    if (get_user_meta($user_id, '_gmat_intake_completed', true)) return;

    // Skip if already on the intake page (prevent redirect loop)
    $intake_page = get_page_by_path(GMAT_INTAKE_PAGE_SLUG, OBJECT, 'page');
    if ($intake_page && is_page($intake_page->ID)) return;

    // Free trial course IDs to exclude
    $free_trial_course_ids = array(7472, 9361);

    $should_redirect = false;

    // Check: is this a paid course page?
    if (is_singular('sfwd-courses')) {
        $course_id = get_the_ID();
        if (!in_array($course_id, $free_trial_course_ids)) {
            $should_redirect = true;
        }
    }

    // Check: is this a lesson/topic belonging to a paid course?
    if (is_singular(array('sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'))) {
        if (function_exists('learndash_get_course_id')) {
            $course_id = learndash_get_course_id(get_the_ID());
            if ($course_id && !in_array($course_id, $free_trial_course_ids)) {
                $should_redirect = true;
            }
        }
    }

    if ($should_redirect) {
        wp_redirect(home_url('/' . GMAT_INTAKE_PAGE_SLUG . '/'));
        exit;
    }
}
add_action('template_redirect', 'gmat_intake_redirect_if_not_completed', 6);


// ============================================================================
// HELPER: Sanitize score entries
// ============================================================================

function gmat_intake_sanitize_scores($scores) {
    if (!is_array($scores)) return array();

    $sanitized = array();
    foreach ($scores as $score) {
        if (!is_array($score)) continue;
        $sanitized[] = array(
            'date'    => sanitize_text_field(isset($score['date']) ? $score['date'] : ''),
            'overall' => max(205, min(805, intval(isset($score['overall']) ? $score['overall'] : 0))),
            'quant'   => max(60, min(90, intval(isset($score['quant']) ? $score['quant'] : 0))),
            'verbal'  => max(60, min(90, intval(isset($score['verbal']) ? $score['verbal'] : 0))),
            'di'      => max(60, min(90, intval(isset($score['di']) ? $score['di'] : 0))),
        );
    }
    return $sanitized;
}


// ============================================================================
// HELPER: Get GMAT score-to-percentile table
// ============================================================================

function gmat_intake_get_percentile_table() {
    return array(
        805 => 100, 795 => 100, 785 => 99, 775 => 99, 765 => 98,
        755 => 97,  745 => 96,  735 => 94, 725 => 92, 715 => 90,
        705 => 87,  695 => 84,  685 => 80, 675 => 77, 665 => 73,
        655 => 69,  645 => 65,  635 => 61, 625 => 57, 615 => 53,
        605 => 49,  595 => 44,  585 => 40, 575 => 36, 565 => 32,
        555 => 29,  545 => 25,  535 => 22, 525 => 19, 515 => 16,
        505 => 14,  495 => 11,  485 => 9,  475 => 8,  465 => 6,
        455 => 5,   445 => 4,   435 => 3,  425 => 2,  415 => 2,
        405 => 1,   395 => 1,   385 => 1,  375 => 0,  365 => 0,
        355 => 0,   345 => 0,   335 => 0,  325 => 0,  315 => 0,
        305 => 0,   295 => 0,   285 => 0,  275 => 0,  265 => 0,
        255 => 0,   245 => 0,   235 => 0,  225 => 0,  215 => 0,
        205 => 0,
    );
}


// ============================================================================
// HELPER: Determine which step the user should resume from
// ============================================================================

function gmat_intake_get_current_step($user_id) {
    // Check if step 1 data exists (scores or skipped)
    $official = get_user_meta($user_id, '_gmat_intake_official_scores', true);
    $practice = get_user_meta($user_id, '_gmat_intake_practice_scores', true);
    $step1_done = get_user_meta($user_id, '_gmat_intake_step1_done', true);

    if (!$step1_done && !$official && !$practice) return 1;

    // Check step 2
    $goal = get_user_meta($user_id, '_gmat_intake_goal_score', true);
    if (!$goal) return 2;

    // Check step 3
    $hours = get_user_meta($user_id, '_gmat_intake_weekly_hours', true);
    if (!$hours) return 3;

    // Check step 4
    $date = get_user_meta($user_id, '_gmat_intake_next_test_date', true);
    if (!$date) return 4;

    // Check step 5
    $pref = get_user_meta($user_id, '_gmat_intake_section_preference', true);
    if (!$pref) return 5;

    return 5;
}


// ============================================================================
// SHORTCODE: [gmat_intake_form]
// ============================================================================

function gmat_intake_form_shortcode($atts) {
    if (!is_user_logged_in()) {
        $my_account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        return '<div class="gmat-intake-login-msg"><p>Please <a href="' . esc_url($my_account_url) . '">log in</a> to access the onboarding form.</p></div>';
    }

    $user_id = get_current_user_id();

    // If already completed, show message
    if (get_user_meta($user_id, '_gmat_intake_completed', true)) {
        $course_url = home_url('/courses/gurutors-recommended-gmat-program/');
        return '<div class="gmat-intake-completed-msg">
            <h2>You have already completed the onboarding form.</h2>
            <p><a href="' . esc_url($course_url) . '" class="gmat-btn gmat-btn-primary">Go to Course</a></p>
        </div>';
    }

    $nonce = wp_create_nonce('gmat_intake_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    $current_step = gmat_intake_get_current_step($user_id);

    // Load existing data for resume
    $official_scores = get_user_meta($user_id, '_gmat_intake_official_scores', true);
    $practice_scores = get_user_meta($user_id, '_gmat_intake_practice_scores', true);
    $goal_score = get_user_meta($user_id, '_gmat_intake_goal_score', true);
    $weekly_hours = get_user_meta($user_id, '_gmat_intake_weekly_hours', true);
    $next_test_date = get_user_meta($user_id, '_gmat_intake_next_test_date', true);
    $section_preference = get_user_meta($user_id, '_gmat_intake_section_preference', true);

    $percentile_table = gmat_intake_get_percentile_table();

    ob_start();
    ?>
    <script>
        var gmatIntakeData = {
            ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
            nonce: <?php echo wp_json_encode($nonce); ?>,
            currentStep: <?php echo intval($current_step); ?>,
            courseUrl: <?php echo wp_json_encode(home_url('/courses/gurutors-recommended-gmat-program/')); ?>,
            existingData: {
                officialScores: <?php echo $official_scores ? $official_scores : '[]'; ?>,
                practiceScores: <?php echo $practice_scores ? $practice_scores : '[]'; ?>,
                goalScore: <?php echo $goal_score ? intval($goal_score) : 'null'; ?>,
                weeklyHours: <?php echo $weekly_hours ? intval($weekly_hours) : 'null'; ?>,
                nextTestDate: <?php echo $next_test_date ? wp_json_encode($next_test_date) : 'null'; ?>,
                sectionPreference: <?php echo $section_preference ? wp_json_encode($section_preference) : 'null'; ?>
            }
        };
    </script>

    <!-- Minimal logo bar (header/footer hidden on intake page) -->
    <div class="gmat-intake-logo-bar">
        <a href="<?php echo esc_url(home_url('/gmat-intake/')); ?>">
            <?php
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                echo wp_get_attachment_image($custom_logo_id, 'full', false, array('alt' => get_bloginfo('name')));
            } else {
                echo '<span style="font-size:24px;font-weight:800;color:#00409E;">' . esc_html(get_bloginfo('name')) . '</span>';
            }
            ?>
        </a>
    </div>

    <div id="gmat-intake-wizard">
        <!-- Progress Bar -->
        <div class="gmat-intake-progress-bar">
            <div class="gmat-progress-item">
                <div class="gmat-progress-step active" data-step="1">
                    <span class="gmat-progress-number">1</span>
                    <span class="gmat-progress-check"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6L2.7 8.3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <span class="gmat-progress-label">Previous Score</span>
            </div>
            <div class="gmat-progress-line" data-after="1"></div>

            <div class="gmat-progress-item">
                <div class="gmat-progress-step" data-step="2">
                    <span class="gmat-progress-number">2</span>
                    <span class="gmat-progress-check"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6L2.7 8.3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <span class="gmat-progress-label">Goal Score</span>
            </div>
            <div class="gmat-progress-line" data-after="2"></div>

            <div class="gmat-progress-item">
                <div class="gmat-progress-step" data-step="3">
                    <span class="gmat-progress-number">3</span>
                    <span class="gmat-progress-check"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6L2.7 8.3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <span class="gmat-progress-label">Weekly Study Schedule</span>
            </div>
            <div class="gmat-progress-line" data-after="3"></div>

            <div class="gmat-progress-item">
                <div class="gmat-progress-step" data-step="4">
                    <span class="gmat-progress-number">4</span>
                    <span class="gmat-progress-check"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6L2.7 8.3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <span class="gmat-progress-label">Next Test Date</span>
            </div>
            <div class="gmat-progress-line" data-after="4"></div>

            <div class="gmat-progress-item">
                <div class="gmat-progress-step" data-step="5">
                    <span class="gmat-progress-number">5</span>
                    <span class="gmat-progress-check"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6L2.7 8.3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <span class="gmat-progress-label">Finish</span>
            </div>
        </div>

        <!-- Step 1: Previous Scores — Two-phase flow -->
        <div class="gmat-step" data-step="1">
            <div class="gmat-step__content">
                <!-- Phase 1: "Tell us about your GMAT experience" — score type selection -->
                <div id="gmat-step1-phase1">
                    <h2 class="gmat-step__title">Tell Us About Your GMAT Experience</h2>
                    <p class="gmat-step__subtitle">Have you taken the GMAT before? Your answer helps us personalize your study plan.</p>

                    <div class="gmat-score-cards row">
                        <!-- Official Test Scores Card -->
                        <div class="col-md-6">
                            <div class="gmat-score-card" id="gmat-card-official">
                                <h3 class="gmat-score-card__title">Official Test Scores</h3>
                                <p class="gmat-score-card__desc">Enter your official GMAT score.</p>
                                <button type="button" class="gmat-btn gmat-btn-primary gmat-add-score" data-type="official">Add Score</button>
                            </div>
                        </div>

                        <!-- Practice Test Scores Card -->
                        <div class="col-md-6">
                            <div class="gmat-score-card" id="gmat-card-practice">
                                <h3 class="gmat-score-card__title">Practice Test Scores</h3>
                                <p class="gmat-score-card__desc">Enter any previous GMAT test scores.</p>
                                <button type="button" class="gmat-btn gmat-btn-primary gmat-add-score" data-type="practice">Add Score</button>
                            </div>
                        </div>
                    </div>

                    <!-- Saved Scores Lists (shown after scores are added, back on phase 1) -->
                    <div class="gmat-saved-scores" id="gmat-saved-official" style="display:none;">
                        <h4>Official Scores</h4>
                        <div class="gmat-score-list" id="gmat-official-score-list"></div>
                    </div>
                    <div class="gmat-saved-scores" id="gmat-saved-practice" style="display:none;">
                        <h4>Practice Scores</h4>
                        <div class="gmat-score-list" id="gmat-practice-score-list"></div>
                    </div>
                </div>

                <!-- Phase 2: "Update us on your most recent performance" — score entry form -->
                <div id="gmat-step1-phase2" style="display:none;">
                    <h2 class="gmat-step__title">Update Us On Your Most Recent GMAT Performance</h2>
                    <p class="gmat-step__subtitle">Enter your scores below so we can identify where you'll gain the most points.</p>

                    <div class="gmat-score-form-wrapper" id="gmat-score-form-wrapper">
                        <div class="gmat-score-form">
                            <h3 class="gmat-score-form__title" id="gmat-score-form-title">Enter Your Practice GMAT Scores</h3>
                            <div class="gmat-score-form__fields row">
                                <div class="col-md-2">
                                    <label for="gmat-score-date">Date</label>
                                    <input type="date" id="gmat-score-date" placeholder="Select Date">
                                </div>
                                <div class="col-md-2">
                                    <label for="gmat-score-overall">Overall Score</label>
                                    <input type="number" id="gmat-score-overall" placeholder="Enter Overall Score" min="205" max="805" step="5">
                                </div>
                                <div class="col-md-2">
                                    <label for="gmat-score-quant">Quant Score</label>
                                    <input type="number" id="gmat-score-quant" placeholder="Enter Quant Score" min="60" max="90">
                                </div>
                                <div class="col-md-2">
                                    <label for="gmat-score-verbal">Verbal Score</label>
                                    <input type="number" id="gmat-score-verbal" placeholder="Enter Verbal Score" min="60" max="90">
                                </div>
                                <div class="col-md-2">
                                    <label for="gmat-score-di">Data Insights Score</label>
                                    <input type="number" id="gmat-score-di" placeholder="Enter Insights Score" min="60" max="90">
                                </div>
                            </div>
                            <p class="gmat-score-form__note">Don't worry — you can update this anytime.</p>
                        </div>
                    </div>
                </div>

                <div class="gmat-step__error" id="gmat-step1-error"></div>
            </div>

            <div class="gmat-step__actions gmat-step__actions--end">
                <button type="button" class="gmat-btn gmat-btn-outline gmat-btn-prev gmat-step1-prev-btn" data-step="1" disabled>Previous</button>
                <button type="button" class="gmat-btn gmat-btn-skip gmat-btn-skip-step1" data-step="1">Skip</button>
            </div>
        </div>

        <!-- Step 2: Goal Score -->
        <div class="gmat-step gmat-step--hidden" data-step="2">
            <div class="gmat-step__content">
                <h2 class="gmat-step__title">Set Your Target GMAT Score</h2>
                <p class="gmat-step__subtitle">Choose the score you’re aiming for. We’ll build your plan to close the gap between your current score and your goal.</p>

                <div class="row gmat-step2-desired-goal-score">
                    <div class="col-md-5">
                        <div class="gmat-goal-card">
                            <h3 class="gmat-goal-card__title">Desired Goal Score</h3>
                            <label for="gmat-goal-score" class="gmat-field-label">Desired Score</label>
                            <input type="number" id="gmat-goal-score" class="gmat-input" placeholder="Enter Your Desired Score here" min="205" max="805" step="5" value="<?php echo $goal_score ? esc_attr($goal_score) : ''; ?>">
                            <p class="gmat-goal-card__note">You can change your GMAT score later from your Settings.</p>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="gmat-percentile-card">
                            <table class="gmat-percentile-table">
                                <thead>
                                    <tr>
                                        <th>Total Score</th>
                                        <th>Estimated Percentile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($percentile_table as $score => $percentile) : ?>
                                        <tr>
                                            <td><?php echo esc_html($score); ?></td>
                                            <td><?php echo esc_html($percentile); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="gmat-percentile-scroll-note">Scroll for more scores</p>
                        </div>
                    </div>
                </div>

                <div class="gmat-step__error" id="gmat-step2-error"></div>
            </div>

            <div class="gmat-step__actions">
                <button type="button" class="gmat-btn gmat-btn-outline gmat-btn-prev" data-step="2">Previous</button>
                <button type="button" class="gmat-btn gmat-btn-primary gmat-btn-save" data-step="2">Save</button>
            </div>
        </div>

        <!-- Step 3: Weekly Study Schedule -->
        <div class="gmat-step gmat-step--hidden" data-step="3">
            <div class="gmat-step__content">
                <h2 class="gmat-step__title">How much can you study each week?</h2>
                <p class="gmat-step__subtitle">Be realistic. Consistency beats cramming — and we'll structure your plan around your availability.</p>

                <div class="gmat-hours-card">
                    <h3 class="gmat-hours-card__title">Weekly Study Hours</h3>
                    <label for="gmat-weekly-hours" class="gmat-field-label">Hours Per Week</label>
                    <select id="gmat-weekly-hours" class="gmat-input gmat-select">
                        <option value="">Select hours per week</option>
                        <?php for ($h = 5; $h <= 40; $h += 5) : ?>
                            <option value="<?php echo $h; ?>" <?php selected($weekly_hours, $h); ?>><?php echo $h; ?> hours</option>
                        <?php endfor; ?>
                    </select>
                    <p class="gmat-hours-card__note">You can adjust this later from your Settings.</p>
                </div>

                <div class="gmat-step__error" id="gmat-step3-error"></div>
            </div>

            <div class="gmat-step__actions">
                <button type="button" class="gmat-btn gmat-btn-outline gmat-btn-prev" data-step="3">Previous</button>
                <button type="button" class="gmat-btn gmat-btn-primary gmat-btn-save" data-step="3">Save</button>
            </div>
        </div>

        <!-- Step 4: Next Test Date -->
        <div class="gmat-step gmat-step--hidden" data-step="4">
            <div class="gmat-step__content">
                <h2 class="gmat-step__title">When Is Your Next GMAT Test?</h2>
                <p class="gmat-step__subtitle">Your exam date allows us to pace your preparation and set weekly milestones.</p>

                <div class="gmat-date-card">
                    <h3 class="gmat-date-card__title">Next Test Date</h3>
                    <label for="gmat-next-test-date" class="gmat-field-label">Enter Date</label>
                    <input type="date" id="gmat-next-test-date" class="gmat-input" min="<?php echo esc_attr(date('Y-m-d')); ?>" value="<?php echo $next_test_date ? esc_attr($next_test_date) : ''; ?>">
                    <p class="gmat-date-card__note">You can adjust this later if your plans change.</p>
                </div>

                <div class="gmat-step__error" id="gmat-step4-error"></div>
            </div>

            <div class="gmat-step__actions">
                <button type="button" class="gmat-btn gmat-btn-outline gmat-btn-prev" data-step="4">Previous</button>
                <button type="button" class="gmat-btn gmat-btn-primary gmat-btn-save" data-step="4">Save</button>
            </div>
        </div>

        <!-- Step 5: Section Preference -->
        <div class="gmat-step gmat-step--hidden" data-step="5">
            <div class="gmat-step__content">
                <h2 class="gmat-step__title">Choose Your Starting Section</h2>
                <div class="gmat-step5-description">
                <p class="gmat-step__subtitle">We recommend starting with the section you feel most comfortable with.</p>
                <p class="gmat-step__subtitle">Already worked on Quant during the free trial? Your personalized Quant study plan will be saved if you decide to start with Verbal—you'll pick up right where you left off.</p>
                <p class="gmat-step__subtitle">Data Insights will always come last, since it draws on both Quant and Verbal skills.</p>
                <p class="gmat-step__subtitle" style="margin-bottom: 30px;">We recommend following your study plan as designed,<br>but you're free to complete lessons in any order.</p>
                </div>            
                <div class="gmat-preference-cards row">
                    <div class="col-md-6">
                        <div class="gmat-preference-card <?php echo $section_preference === 'quant' ? 'selected' : ''; ?>" data-preference="quant">
                            <div class="gmat-preference-card__icon">
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="48" height="48" rx="12" fill="#EBF0F9"/>
                                    <path d="M16 32V20M24 32V16M32 32V24" stroke="#00409E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3 class="gmat-preference-card__title">Quant Study Plan</h3>
                            <p class="gmat-preference-card__desc">Quant &nbsp;&#10230;&nbsp; Verbal &nbsp;&#10230;&nbsp; Data Insights</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="gmat-preference-card <?php echo $section_preference === 'verbal' ? 'selected' : ''; ?>" data-preference="verbal">
                            <div class="gmat-preference-card__icon">
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="48" height="48" rx="12" fill="#EBF0F9"/>
                                    <path d="M14 18H34M14 24H28M14 30H22" stroke="#00409E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3 class="gmat-preference-card__title">Verbal Study Plan</h3>
                            <p class="gmat-preference-card__desc">Verbal &nbsp;&#10230;&nbsp; Quant &nbsp;&#10230;&nbsp; Data Insights</p>
                        </div>
                    </div>
                </div>

                <div class="gmat-step__error" id="gmat-step5-error"></div>
            </div>

            <div class="gmat-step__actions">
                <button type="button" class="gmat-btn gmat-btn-outline gmat-btn-prev" data-step="5">Previous</button>
                <button type="button" class="gmat-btn gmat-btn-primary gmat-btn-finish">Finish</button>
            </div>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('gmat_intake_form', 'gmat_intake_form_shortcode');


// ============================================================================
// AJAX HANDLER: Step 1 - Save Scores
// ============================================================================

function gmat_intake_save_scores_handler() {
    check_ajax_referer('gmat_intake_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'), 403);
    }

    $user_id = get_current_user_id();

    $official_raw = isset($_POST['official_scores']) ? stripslashes($_POST['official_scores']) : '[]';
    $practice_raw = isset($_POST['practice_scores']) ? stripslashes($_POST['practice_scores']) : '[]';

    $official = json_decode($official_raw, true);
    $practice = json_decode($practice_raw, true);

    $official = gmat_intake_sanitize_scores(is_array($official) ? $official : array());
    $practice = gmat_intake_sanitize_scores(is_array($practice) ? $practice : array());

    update_user_meta($user_id, '_gmat_intake_official_scores', wp_json_encode($official));
    update_user_meta($user_id, '_gmat_intake_practice_scores', wp_json_encode($practice));
    update_user_meta($user_id, '_gmat_intake_step1_done', '1');

    wp_send_json_success(array('message' => 'Scores saved'));
}
add_action('wp_ajax_gmat_intake_save_scores', 'gmat_intake_save_scores_handler');


// ============================================================================
// AJAX HANDLER: Step 2 - Save Goal Score
// ============================================================================

function gmat_intake_save_goal_handler() {
    check_ajax_referer('gmat_intake_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'), 403);
    }

    $user_id = get_current_user_id();
    $goal_score = intval($_POST['goal_score']);

    if ($goal_score < 205 || $goal_score > 805) {
        wp_send_json_error(array('message' => 'Score must be between 205 and 805.'));
    }

    update_user_meta($user_id, '_gmat_intake_goal_score', $goal_score);

    wp_send_json_success(array('message' => 'Goal score saved'));
}
add_action('wp_ajax_gmat_intake_save_goal', 'gmat_intake_save_goal_handler');


// ============================================================================
// AJAX HANDLER: Step 3 - Save Weekly Hours
// ============================================================================

function gmat_intake_save_hours_handler() {
    check_ajax_referer('gmat_intake_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'), 403);
    }

    $user_id = get_current_user_id();
    $weekly_hours = intval($_POST['weekly_hours']);

    if ($weekly_hours <= 0 || $weekly_hours > 100) {
        wp_send_json_error(array('message' => 'Please select valid study hours.'));
    }

    update_user_meta($user_id, '_gmat_intake_weekly_hours', $weekly_hours);

    wp_send_json_success(array('message' => 'Weekly hours saved'));
}
add_action('wp_ajax_gmat_intake_save_hours', 'gmat_intake_save_hours_handler');


// ============================================================================
// AJAX HANDLER: Step 4 - Save Next Test Date
// ============================================================================

function gmat_intake_save_test_date_handler() {
    check_ajax_referer('gmat_intake_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'), 403);
    }

    $user_id = get_current_user_id();
    $test_date = sanitize_text_field($_POST['test_date']);

    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $test_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $test_date) {
        wp_send_json_error(array('message' => 'Please enter a valid date.'));
    }

    // Validate date is not in the past
    $today = new DateTime('today');
    if ($date_obj < $today) {
        wp_send_json_error(array('message' => 'Test date must be today or in the future.'));
    }

    update_user_meta($user_id, '_gmat_intake_next_test_date', $test_date);

    wp_send_json_success(array('message' => 'Test date saved'));
}
add_action('wp_ajax_gmat_intake_save_test_date', 'gmat_intake_save_test_date_handler');


// ============================================================================
// AJAX HANDLER: Step 5 - Save Preference & Complete Intake
// ============================================================================

function gmat_intake_save_preference_handler() {
    check_ajax_referer('gmat_intake_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'), 403);
    }

    $user_id = get_current_user_id();
    $preference = sanitize_text_field($_POST['preference']);

    if (!in_array($preference, array('quant', 'verbal'), true)) {
        wp_send_json_error(array('message' => 'Please select either Quantitative or Verbal.'));
    }

    update_user_meta($user_id, '_gmat_intake_section_preference', $preference);
    update_user_meta($user_id, '_gmat_intake_completed', '1');
    update_user_meta($user_id, '_gmat_intake_completed_at', time());

    // Redirect to dashboard after intake completion
    $dash_slug      = defined('GMAT_DASHBOARD_PAGE_SLUG') ? GMAT_DASHBOARD_PAGE_SLUG : 'gmat-dashboard';
    $dashboard_page = get_page_by_path($dash_slug, OBJECT, 'page');
    $redirect_url   = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/courses/gurutors-recommended-gmat-program/');

    wp_send_json_success(array(
        'message'     => 'Intake completed',
        'redirect_url' => $redirect_url,
    ));
}
add_action('wp_ajax_gmat_intake_save_preference', 'gmat_intake_save_preference_handler');


// ============================================================================
// HIDE PAGE TITLE on intake page
// ============================================================================

function gmat_intake_hide_page_title($title) {
    if (is_page(GMAT_INTAKE_PAGE_SLUG)) {
        return false;
    }
    return $title;
}
add_filter('generate_show_title', 'gmat_intake_hide_page_title');


// ============================================================================
// ADD BODY CLASS on intake page — ensures .page-gmat-intake is always present
// ============================================================================

function gmat_intake_body_class($classes) {
    if (is_page(GMAT_INTAKE_PAGE_SLUG)) {
        $classes[] = 'page-gmat-intake';
    }
    return $classes;
}
add_filter('body_class', 'gmat_intake_body_class');
