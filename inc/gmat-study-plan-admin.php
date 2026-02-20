<?php
/**
 * GMAT Study Plan — Admin Configuration
 *
 * Provides a settings page under the WordPress admin menu where admins
 * can map each study-plan lesson/topic name to its LearnDash post ID.
 * IDs are stored as a single option: gmat_study_plan_lesson_ids (serialised array).
 *
 * Default IDs are hardcoded from the provided mapping; the admin page
 * allows overrides/corrections.
 *
 * Menu location: Settings > GMAT Study Plan
 */

if (!defined('ABSPATH')) exit;


// ============================================================================
// REGISTER ADMIN PAGE
// ============================================================================

function gmat_sp_admin_menu() {
    add_options_page(
        'GMAT Study Plan — Lesson IDs',
        'GMAT Study Plan',
        'manage_options',
        'gmat-study-plan',
        'gmat_sp_admin_page_render'
    );
}
add_action('admin_menu', 'gmat_sp_admin_menu');


// ============================================================================
// REGISTER SETTING
// ============================================================================

function gmat_sp_admin_init() {
    register_setting('gmat_study_plan_group', 'gmat_study_plan_lesson_ids', array(
        'type'              => 'array',
        'sanitize_callback' => 'gmat_sp_sanitize_ids',
    ));
    register_setting('gmat_study_plan_group', 'gmat_study_plan_xapi_urls', array(
        'type'              => 'array',
        'sanitize_callback' => 'gmat_sp_sanitize_xapi_urls',
    ));
}
add_action('admin_init', 'gmat_sp_admin_init');

function gmat_sp_sanitize_ids($input) {
    if (!is_array($input)) return array();
    $clean = array();
    foreach ($input as $key => $val) {
        $clean[sanitize_key($key)] = absint($val);
    }
    return $clean;
}

function gmat_sp_sanitize_xapi_urls($input) {
    if (!is_array($input)) return array();
    $clean = array();
    foreach ($input as $key => $val) {
        $val = trim($val);
        if (!empty($val)) {
            $clean[sanitize_key($key)] = esc_url_raw($val);
        }
    }
    return $clean;
}

/**
 * Get admin-saved xAPI URLs for DI lessons (that don't have hardcoded slugs).
 * Returns array: lesson_key => full xAPI activity URL
 */
function gmat_sp_get_saved_xapi_urls() {
    $saved = get_option('gmat_study_plan_xapi_urls', array());
    if (!is_array($saved)) return array();
    return $saved;
}


// ============================================================================
// DEFAULT LESSON IDS — Hardcoded from the provided mapping
// ============================================================================

function gmat_sp_get_default_ids() {
    return array(
        // Introductions
        'intro_verbal'      => 8151,
        'intro_quant'       => 8123,
        'intro_di'          => 8152,

        // CR Lessons
        'cr_lesson_1'       => 8153,
        'cr_lesson_2'       => 8154,
        'cr_lesson_3'       => 8163,
        'cr_lesson_4'       => 8164,
        'cr_lesson_5'       => 8177,
        'cr_lesson_6'       => 8190,
        'cr_lesson_7'       => 8204,
        'cr_lesson_8'       => 8218,
        'cr_lesson_9'       => 8219,

        // CR Exercises
        'cr_exercise_1'     => 8156,
        'cr_exercise_2'     => 8157,
        'cr_exercise_3'     => 8169,
        'cr_exercise_4'     => 8181,
        'cr_exercise_5'     => 8196,
        'cr_exercise_6'     => 8209,
        'cr_exercise_7'     => 8224,
        'cr_exercise_8'     => 8225,

        // RC
        'rc_lesson_1'       => 8155,
        'rc_lesson_2'       => 8165,
        'rc_lesson_3'       => 8166,
        'rc_exercise_1'     => 8170,

        // PSS
        'pss_lesson_1'      => 8159,
        'pss_lesson_2'      => 8172,

        // Algebra
        'algebra_1'         => 8160,
        'algebra_2'         => 8174,
        'algebra_3'         => 8187,
        'algebra_4'         => 8201,
        'algebra_5'         => 8216,

        // Word Problems
        'word_problems_1'   => 8161,
        'word_problems_2'   => 8188,
        'word_problems_3'   => 8189,
        'word_problems_4'   => 8669,
        'word_problems_5'   => 8202,
        'word_problems_6'   => 8203,
        'word_problems_7'   => 8215,

        // Number Properties
        'number_props_1'    => 8162,
        'number_props_2'    => 8173,
        'number_props_3'    => 8214,

        // FPRs
        'fprs_1'            => 8175,
        'fprs_2'            => 8186,

        // Quant Exercises
        'quant_exercise_1'  => 8168,
        'quant_exercise_2'  => 8180,
        'quant_exercise_3'  => 8195,
        'quant_exercise_4'  => 8208,
        'quant_exercise_5'  => 8223,

        // Verbal Review Sets
        'verbal_review_2'   => 8184,
        'verbal_review_3'   => 8199,
        'verbal_review_4'   => 8212,
        'verbal_review_5'   => 8228,

        // Quant Review Sets
        'quant_review_2'    => 8183,
        'quant_review_3'    => 8198,
        'quant_review_4'    => 8211,
        'quant_review_5'    => 8227,

        // DI Lessons
        'di_lesson_1'       => 8191,
        'di_lesson_2'       => 8192,
        'di_lesson_3'       => 8193,
        'di_lesson_4'       => 8205,
        'di_lesson_5'       => 8206,
        'di_lesson_6'       => 8220,
        'di_lesson_7'       => 8221,
    );
}


// ============================================================================
// LESSON KEY DEFINITIONS — Every lesson/topic referenced in the study plans
// ============================================================================

function gmat_sp_get_lesson_keys() {
    return array(
        // ── Introductions ──
        // xapi_slug = the slug portion of the xAPI activity ID: http://www.uniqueurl.com/{xapi_slug}
        // These must match exactly what GrassBlade sends in xAPI statements.
        'intro_verbal'   => array('label' => 'Intro to Verbal',          'section' => 'Introduction',     'xapi_slug' => 'intro-to-verbal'),
        'intro_quant'    => array('label' => 'Intro to Quant',           'section' => 'Introduction',     'xapi_slug' => 'intro-to-quant'),
        'intro_di'       => array('label' => 'Intro to Data Insights',   'section' => 'Introduction',     'xapi_slug' => 'intro-to-data-insights'),

        // ── Critical Reasoning (CR) Lessons ──
        'cr_lesson_1'    => array('label' => 'CR Lesson 1',  'section' => 'CR Lessons',   'xapi_slug' => 'intro-to-cr'),
        'cr_lesson_2'    => array('label' => 'CR Lesson 2',  'section' => 'CR Lessons',   'xapi_slug' => 'deconstructing-arguments'),
        'cr_lesson_3'    => array('label' => 'CR Lesson 3',  'section' => 'CR Lessons',   'xapi_slug' => 'cr-argument-types-lesson'),
        'cr_lesson_4'    => array('label' => 'CR Lesson 4',  'section' => 'CR Lessons',   'xapi_slug' => 'assumption-family-question-types-lesson'),
        'cr_lesson_5'    => array('label' => 'CR Lesson 5',  'section' => 'CR Lessons',   'xapi_slug' => 'plan-arguments-lesson'),
        'cr_lesson_6'    => array('label' => 'CR Lesson 6',  'section' => 'CR Lessons',   'xapi_slug' => 'regular-arguments-lesson'),
        'cr_lesson_7'    => array('label' => 'CR Lesson 7',  'section' => 'CR Lessons',   'xapi_slug' => 'explanation-arguments-lesson'),
        'cr_lesson_8'    => array('label' => 'CR Lesson 8',  'section' => 'CR Lessons',   'xapi_slug' => 'structure-family-lesson'),
        'cr_lesson_9'    => array('label' => 'CR Lesson 9',  'section' => 'CR Lessons',   'xapi_slug' => 'evidence-family-lesson'),

        // ── CR Exercises ──
        'cr_exercise_1'  => array('label' => 'CR Exercise 1', 'section' => 'CR Exercises', 'xapi_slug' => 'id-cr-questions-learning-exercise'),
        'cr_exercise_2'  => array('label' => 'CR Exercise 2', 'section' => 'CR Exercises', 'xapi_slug' => 'deconstructing-arguments-lesson'),
        'cr_exercise_3'  => array('label' => 'CR Exercise 3', 'section' => 'CR Exercises', 'xapi_slug' => 'cr-argument-types-learning-exercise-fix'),
        'cr_exercise_4'  => array('label' => 'CR Exercise 4', 'section' => 'CR Exercises', 'xapi_slug' => 'plan-arguments-learning-exercise'),
        'cr_exercise_5'  => array('label' => 'CR Exercise 5', 'section' => 'CR Exercises', 'xapi_slug' => 'regular-arguments-learning-exercise'),
        'cr_exercise_6'  => array('label' => 'CR Exercise 6', 'section' => 'CR Exercises', 'xapi_slug' => 'explanation-arguments-learning-exercise'),
        'cr_exercise_7'  => array('label' => 'CR Exercise 7', 'section' => 'CR Exercises', 'xapi_slug' => 'structure-family-learning-exercise'),
        'cr_exercise_8'  => array('label' => 'CR Exercise 8', 'section' => 'CR Exercises', 'xapi_slug' => 'evidence-family-learning-exercise'),

        // ── Reading Comprehension (RC) ──
        'rc_lesson_1'    => array('label' => 'RC Lesson 1',   'section' => 'RC Lessons',   'xapi_slug' => 'intro-to-rc'),
        'rc_lesson_2'    => array('label' => 'RC Lesson 2',   'section' => 'RC Lessons',   'xapi_slug' => 'rc-question-types'),
        'rc_lesson_3'    => array('label' => 'RC Lesson 3',   'section' => 'RC Lessons',   'xapi_slug' => 'rc-language-patterns'),
        'rc_exercise_1'  => array('label' => 'RC Exercise 1', 'section' => 'RC Exercises', 'xapi_slug' => 'rc-learning-exercise-1-mixed-practice-elb'),

        // ── Problem Solving Strategies (PSS) ──
        'pss_lesson_1'   => array('label' => 'PSS Lesson 1', 'section' => 'PSS Lessons', 'xapi_slug' => 'problem-solving-strategies-lesson-1-copy'),
        'pss_lesson_2'   => array('label' => 'PSS Lesson 2', 'section' => 'PSS Lessons', 'xapi_slug' => 'problem-solving-strategies-lesson-2-copy'),

        // ── Algebra ──
        'algebra_1'      => array('label' => 'Algebra Lesson 1', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-1-copy'),
        'algebra_2'      => array('label' => 'Algebra Lesson 2', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-2-copy'),
        'algebra_3'      => array('label' => 'Algebra Lesson 3', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-3-copy'),
        'algebra_4'      => array('label' => 'Algebra Lesson 4', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-4-copy'),
        'algebra_5'      => array('label' => 'Algebra Lesson 5', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-5-copy'),

        // ── Word Problems ──
        'word_problems_1' => array('label' => 'Word Problems Lesson 1', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-1-copy'),
        'word_problems_2' => array('label' => 'Word Problems Lesson 2', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-2-copy'),
        'word_problems_3' => array('label' => 'Word Problems Lesson 3', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-3-copy'),
        'word_problems_4' => array('label' => 'Word Problems Lesson 4', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-4-copy'),
        'word_problems_5' => array('label' => 'Word Problems Lesson 5', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-5-copy'),
        'word_problems_6' => array('label' => 'Word Problems Lesson 6', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-6-copy'),
        'word_problems_7' => array('label' => 'Word Problems Lesson 7', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-7-copy'),

        // ── Number Properties ──
        'number_props_1'  => array('label' => 'Number Properties Lesson 1', 'section' => 'Number Properties', 'xapi_slug' => 'number-properties-lesson-1-copy'),
        'number_props_2'  => array('label' => 'Number Properties Lesson 2', 'section' => 'Number Properties', 'xapi_slug' => 'number-properties-lesson-2-copy'),
        'number_props_3'  => array('label' => 'Number Properties Lesson 3', 'section' => 'Number Properties', 'xapi_slug' => 'number-properties-lesson-3-copy'),

        // ── Fractions, Percents, Ratios (FPRs) ──
        'fprs_1'          => array('label' => 'FPRs Lesson 1', 'section' => 'FPRs', 'xapi_slug' => 'fractions-percents-and-ratios-lesson-1-copy'),
        'fprs_2'          => array('label' => 'FPRs Lesson 2', 'section' => 'FPRs', 'xapi_slug' => 'fractions-percents-and-ratios-lesson-2-copy'),

        // ── Quant Exercises ──
        'quant_exercise_1' => array('label' => 'Quant Exercise 1', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-1-publish'),
        'quant_exercise_2' => array('label' => 'Quant Exercise 2', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-2-publish'),
        'quant_exercise_3' => array('label' => 'Quant Exercise 3', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-3-publish'),
        'quant_exercise_4' => array('label' => 'Quant Exercise 4', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-4-publish'),
        'quant_exercise_5' => array('label' => 'Quant Exercise 5', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-5-publish'),

        // ── Verbal Review Sets ──
        'verbal_review_2' => array('label' => 'Unit 2 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-2'),
        'verbal_review_3' => array('label' => 'Unit 3 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-3-copy'),
        'verbal_review_4' => array('label' => 'Unit 4 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-4'),
        'verbal_review_5' => array('label' => 'Unit 5 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-5-copy'),

        // ── Quant Review Sets ──
        'quant_review_2'  => array('label' => 'Unit 2 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-2-copy'),
        'quant_review_3'  => array('label' => 'Unit 3 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-3-copy'),
        'quant_review_4'  => array('label' => 'Unit 4 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-4-copy'),
        'quant_review_5'  => array('label' => 'Unit 5 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-5-copy'),

        // ── Data Insights (DI) Lessons ──
        // di_lesson_1 = DI Lesson 1 (DS Intro) — has xAPI URL
        // di_lesson_2 = DI Lesson 2 (DS Strategies 1) — NOT AVAILABLE, admin-configurable
        // di_lesson_3 = DI Lesson 3 (DS Strategies 2) — has xAPI URL
        // di_lesson_4 = DI Lesson 4 (Graphics Interpretation) — NOT AVAILABLE, admin-configurable
        // di_lesson_5 = DI Lesson 5 (Table Interpretation) — NOT AVAILABLE, admin-configurable
        // di_lesson_6 = DI Lesson 6 (Two-Part Analysis) — NOT AVAILABLE, admin-configurable
        // di_lesson_7 = DI Lesson 7 (Multi-Source Reasoning) — NOT AVAILABLE, admin-configurable
        'di_lesson_1'     => array('label' => 'DI Lesson 1 (DS Intro)',              'section' => 'DI Lessons', 'xapi_slug' => ''),
        'di_lesson_2'     => array('label' => 'DI Lesson 2 (DS Strategies 1)',       'section' => 'DI Lessons', 'xapi_slug' => ''),
        'di_lesson_3'     => array('label' => 'DI Lesson 3 (DS Strategies 2)',       'section' => 'DI Lessons', 'xapi_slug' => 'di-lesson-4-ds-strategies-2'),
        'di_lesson_4'     => array('label' => 'DI Lesson 4 (Graphics Interp.)',      'section' => 'DI Lessons', 'xapi_slug' => ''),
        'di_lesson_5'     => array('label' => 'DI Lesson 5 (Table Interp.)',         'section' => 'DI Lessons', 'xapi_slug' => ''),
        'di_lesson_6'     => array('label' => 'DI Lesson 6 (Two-Part Analysis)',     'section' => 'DI Lessons', 'xapi_slug' => ''),
        'di_lesson_7'     => array('label' => 'DI Lesson 7 (Multi-Source Reasoning)','section' => 'DI Lessons', 'xapi_slug' => ''),
    );
}


/**
 * Get merged lesson IDs: defaults + admin overrides.
 * Admin-saved values overwrite defaults; missing keys fall back to defaults.
 */
function gmat_sp_get_lesson_ids() {
    $defaults = gmat_sp_get_default_ids();
    $saved    = get_option('gmat_study_plan_lesson_ids', array());

    if (!is_array($saved) || empty($saved)) {
        return $defaults;
    }

    // Merge: saved values override defaults
    return array_merge($defaults, array_filter($saved));
}


// ============================================================================
// RENDER ADMIN PAGE
// ============================================================================

/**
 * Lesson keys that need admin-configurable xAPI URLs.
 * These are DI lessons where the xAPI activity URL is not yet available.
 */
function gmat_sp_get_xapi_url_fields() {
    return array(
        'di_lesson_1' => 'DI Lesson 1 (DS Intro)',
        'di_lesson_2' => 'DI Lesson 2 (DS Strategies 1)',
        'di_lesson_4' => 'DI Lesson 4 (Graphics Interp.)',
        'di_lesson_5' => 'DI Lesson 5 (Table Interp.)',
        'di_lesson_6' => 'DI Lesson 6 (Two-Part Analysis)',
        'di_lesson_7' => 'DI Lesson 7 (Multi-Source Reasoning)',
    );
}

function gmat_sp_admin_page_render() {
    if (!current_user_can('manage_options')) return;

    $lesson_keys  = gmat_sp_get_lesson_keys();
    $defaults     = gmat_sp_get_default_ids();
    $saved_ids    = get_option('gmat_study_plan_lesson_ids', array());
    $saved_xapi   = get_option('gmat_study_plan_xapi_urls', array());
    $xapi_fields  = gmat_sp_get_xapi_url_fields();

    // Group by section
    $sections = array();
    foreach ($lesson_keys as $key => $meta) {
        $sections[$meta['section']][$key] = $meta;
    }
    ?>
    <div class="wrap">
        <h1>GMAT Study Plan &mdash; Settings</h1>
        <p>Map each study-plan item to its LearnDash Lesson / Topic / Quiz post ID.<br>
           Default IDs are pre-filled. You can override any ID below.<br>
           You can find the post ID by editing a lesson in LearnDash and checking the URL (<code>post=12345</code>).</p>

        <form method="post" action="options.php">
            <?php settings_fields('gmat_study_plan_group'); ?>

            <?php foreach ($sections as $section_name => $items) : ?>
                <h2 style="margin-top:30px;border-bottom:1px solid #ccd0d4;padding-bottom:8px;"><?php echo esc_html($section_name); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                    <?php foreach ($items as $key => $meta) :
                        $current_val = isset($saved_ids[$key]) && $saved_ids[$key] ? $saved_ids[$key] : (isset($defaults[$key]) ? $defaults[$key] : '');
                    ?>
                        <tr>
                            <th scope="row">
                                <label for="gmat_sp_<?php echo esc_attr($key); ?>"><?php echo esc_html($meta['label']); ?></label>
                            </th>
                            <td>
                                <input type="number" min="0" step="1"
                                       id="gmat_sp_<?php echo esc_attr($key); ?>"
                                       name="gmat_study_plan_lesson_ids[<?php echo esc_attr($key); ?>]"
                                       value="<?php echo esc_attr($current_val); ?>"
                                       class="regular-text"
                                       placeholder="LearnDash Post ID">
                                <?php if (isset($defaults[$key])) : ?>
                                    <span class="description" style="margin-left:8px;color:#666;">Default: <?php echo intval($defaults[$key]); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

            <!-- ── xAPI Activity URLs for DI Lessons (not yet available) ── -->
            <h2 style="margin-top:40px;border-bottom:2px solid #00409E;padding-bottom:8px;color:#00409E;">xAPI Activity URLs — Data Insights</h2>
            <p>These DI lessons do not have xAPI tracking URLs yet. When available, paste the full
               <code>http://www.uniqueurl.com/...</code> URL for each lesson below.<br>
               The tracking will automatically start working once the URL is saved.</p>
            <table class="form-table" role="presentation">
                <tbody>
                <?php foreach ($xapi_fields as $key => $label) :
                    $current_url = isset($saved_xapi[$key]) ? $saved_xapi[$key] : '';
                ?>
                    <tr>
                        <th scope="row">
                            <label for="gmat_sp_xapi_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="gmat_sp_xapi_<?php echo esc_attr($key); ?>"
                                   name="gmat_study_plan_xapi_urls[<?php echo esc_attr($key); ?>]"
                                   value="<?php echo esc_attr($current_url); ?>"
                                   class="large-text"
                                   placeholder="http://www.uniqueurl.com/slug-here"
                                   style="max-width:600px;">
                            <?php if (empty($current_url)) : ?>
                                <span class="description" style="margin-left:8px;color:#c00;">Not available yet</span>
                            <?php else : ?>
                                <span class="description" style="margin-left:8px;color:#080;">&#10003; URL set</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}
