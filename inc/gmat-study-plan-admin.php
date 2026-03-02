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
        // topic = topic subtitle shown below the lesson label
        // desc = accordion description shown when user expands the lesson card
        'intro_verbal'   => array('label' => 'Intro to Verbal',          'section' => 'Introduction',     'xapi_slug' => 'intro-to-verbal',
            'topic' => 'GMAT Verbal Section Overview',
            'desc' => "Understand the structure of GMAT Verbal, how CR and RC are tested, and how scoring rewards reasoning over content knowledge"),
        'intro_quant'    => array('label' => 'Intro to Quant',           'section' => 'Introduction',     'xapi_slug' => 'intro-to-quant',
            'topic' => 'GMAT Quant Section Overview',
            'desc' => "Understand the structure of GMAT Quant and why decision-making is the key to quant success"),
        'intro_di'       => array('label' => 'Intro to Data Insights',   'section' => 'Introduction',     'xapi_slug' => 'intro-to-data-insights',
            'topic' => 'GMAT Data Insights Section Overview',
            'desc' => "Understand how Data Insights blends quantitative, verbal, and logical reasoning skills tested elsewhere on the GMAT"),

        // ── Critical Reasoning (CR) Lessons ──
        'cr_lesson_1'    => array('label' => 'CR Lesson 1',  'section' => 'CR Lessons',   'xapi_slug' => 'intro-to-cr',
            'topic' => 'CR Families & Question Types',
            'desc' => "How Critical Reasoning is tested on the GMAT\nThe major CR question families and why they exist\nHow to optimize your reading process for each CR question family"),
        'cr_lesson_2'    => array('label' => 'CR Lesson 2',  'section' => 'CR Lessons',   'xapi_slug' => 'deconstructing-arguments',
            'topic' => 'CR Passage Structure & Signal Words',
            'desc' => "How to break CR passages into their core components\nHow signal words reveal argument structure"),
        'cr_lesson_3'    => array('label' => 'CR Lesson 3',  'section' => 'CR Lessons',   'xapi_slug' => 'cr-argument-types-lesson',
            'topic' => 'CR Argument Types',
            'desc' => "Learn the three assumption-family argument types and why being able to recognize each will improve your CR score"),
        'cr_lesson_4'    => array('label' => 'CR Lesson 4',  'section' => 'CR Lessons',   'xapi_slug' => 'assumption-family-question-types-lesson',
            'topic' => 'The Four Key Assumption Family Question Types',
            'desc' => "Learn the four core assumption-family question types and the exact logical move each requires"),
        'cr_lesson_5'    => array('label' => 'CR Lesson 5',  'section' => 'CR Lessons',   'xapi_slug' => 'plan-arguments-lesson',
            'topic' => 'CR Plan Arguments',
            'desc' => "Learn how to identify plan arguments and evaluate whether proposed actions will achieve their intended goal"),
        'cr_lesson_6'    => array('label' => 'CR Lesson 6',  'section' => 'CR Lessons',   'xapi_slug' => 'regular-arguments-lesson',
            'topic' => 'CR Regular Arguments',
            'desc' => "Learn how to analyze regular CR arguments through the lens of the two most conceptually challenging assumption family question types: find the assumption and evaluate the argument"),
        'cr_lesson_7'    => array('label' => 'CR Lesson 7',  'section' => 'CR Lessons',   'xapi_slug' => 'explanation-arguments-lesson',
            'topic' => 'CR Explanation Arguments',
            'desc' => "Learn how to use the GMAT's thought patterns to avoid traps in CR questions with explanation arguments"),
        'cr_lesson_8'    => array('label' => 'CR Lesson 8',  'section' => 'CR Lessons',   'xapi_slug' => 'structure-family-lesson',
            'topic' => 'CR Describe the Role',
            'desc' => "Learn how to analyze the logical structure of CR arguments independent of content"),
        'cr_lesson_9'    => array('label' => 'CR Lesson 9',  'section' => 'CR Lessons',   'xapi_slug' => 'evidence-family-lesson',
            'topic' => 'CR Inference & Discrepancy',
            'desc' => "Learn how to distinguish evidence-family CR questions from assumption-family questions"),

        // ── CR Exercises ──
        'cr_exercise_1'  => array('label' => 'CR Exercise 1', 'section' => 'CR Exercises', 'xapi_slug' => 'id-cr-questions-learning-exercise',
            'topic' => 'Classifying CR Questions',
            'desc' => "Practice identifying CR question types and clarifying the task they ask us to complete"),
        'cr_exercise_2'  => array('label' => 'CR Exercise 2', 'section' => 'CR Exercises', 'xapi_slug' => 'deconstructing-arguments-lesson',
            'topic' => 'Deconstructing CR Passages',
            'desc' => "Practice breaking CR arguments into logical components and using signal words to guide analysis"),
        'cr_exercise_3'  => array('label' => 'CR Exercise 3', 'section' => 'CR Exercises', 'xapi_slug' => 'cr-argument-types-learning-exercise-fix',
            'topic' => 'Classifying CR Argument Types',
            'desc' => "Practice classifying CR arguments"),
        'cr_exercise_4'  => array('label' => 'CR Exercise 4', 'section' => 'CR Exercises', 'xapi_slug' => 'plan-arguments-learning-exercise',
            'topic' => 'CR Plan Arguments Guided Practice',
            'desc' => "Practice solving plan-argument CR questions by seeking out the thought patterns that their right answers always address"),
        'cr_exercise_5'  => array('label' => 'CR Exercise 5', 'section' => 'CR Exercises', 'xapi_slug' => 'regular-arguments-learning-exercise',
            'topic' => 'CR Regular Arguments Guided Practice',
            'desc' => "Practice solving regular-argument CR questions with the help of answer evaluation techniques like the negation test"),
        'cr_exercise_6'  => array('label' => 'CR Exercise 6', 'section' => 'CR Exercises', 'xapi_slug' => 'explanation-arguments-learning-exercise',
            'topic' => 'CR Explanation Arguments Guided Practice',
            'desc' => "Practice solving explanation-argument CR questions by actively seeking out the thought patterns the GMAT rewards"),
        'cr_exercise_7'  => array('label' => 'CR Exercise 7', 'section' => 'CR Exercises', 'xapi_slug' => 'structure-family-learning-exercise',
            'topic' => 'CR Describe the Role Guided Practice',
            'desc' => "Practice solving structure-family CR questions by mapping argument roles and logical flow"),
        'cr_exercise_8'  => array('label' => 'CR Exercise 8', 'section' => 'CR Exercises', 'xapi_slug' => 'evidence-family-learning-exercise',
            'topic' => 'CR Inference & Discrepancy Guided Practice',
            'desc' => "Practice solving evidence-family CR questions using the content of the passage alone"),

        // ── Reading Comprehension (RC) ──
        'rc_lesson_1'    => array('label' => 'RC Lesson 1',   'section' => 'RC Lessons',   'xapi_slug' => 'intro-to-rc',
            'topic' => 'RC Reading Best Practices',
            'desc' => "How Reading Comprehension is tested and scored on the GMAT\nHow to read RC passages for structure and purpose, not detail\nHow to avoid common over-reading and under-reading mistakes"),
        'rc_lesson_2'    => array('label' => 'RC Lesson 2',   'section' => 'RC Lessons',   'xapi_slug' => 'rc-question-types',
            'topic' => 'RC Question Types',
            'desc' => "Learn the major RC question types and how your strategy should change for each"),
        'rc_lesson_3'    => array('label' => 'RC Lesson 3',   'section' => 'RC Lessons',   'xapi_slug' => 'rc-language-patterns',
            'topic' => 'RC Answer Language Patterns',
            'desc' => "Learn how answer-choice language signals correctness, distortion, or extremeness in RC questions"),
        'rc_exercise_1'  => array('label' => 'RC Exercise 1', 'section' => 'RC Exercises', 'xapi_slug' => 'rc-learning-exercise-1-mixed-practice-elb',
            'topic' => 'RC Untimed Practice',
            'desc' => "Practice identifying common RC answer-choice language traps\nPractice applying question-specific strategies under tutor guidance"),

        // ── Problem Solving Strategies (PSS) ──
        'pss_lesson_1'   => array('label' => 'Problem Solving Strategies Lesson 1', 'section' => 'PSS Lessons', 'xapi_slug' => 'problem-solving-strategies-lesson-1-copy',
            'topic' => 'Smart Numbers & Working Backwards',
            'desc' => "Smart Numbers: Learn when and how to replace variables with smart numbers to simplify problems without changing the underlying logic\nWorking Backwards: Learn how to work backwards from the answer choices and reverse the problem to save time and avoid unnecessary algebra"),
        'pss_lesson_2'   => array('label' => 'Problem Solving Strategies Lesson 2', 'section' => 'PSS Lessons', 'xapi_slug' => 'problem-solving-strategies-lesson-2-copy',
            'topic' => 'Estimation',
            'desc' => "Estimation: Learn how to approximate intelligently to eliminate answer choices and save time without sacrificing accuracy"),

        // ── Algebra ──
        'algebra_1'      => array('label' => 'Algebra Lesson 1', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-1-copy',
            'topic' => 'Exponents & Roots and Linear Equations',
            'desc' => "Exponents and Roots: Learn how to rewrite expressions with common bases and factor exponential expressions efficiently\nLinear Equations: Learn how to leverage elimination to avoid GMAT computation traps and solve for expressions involving multiple variables"),
        'algebra_2'      => array('label' => 'Algebra Lesson 2', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-2-copy',
            'topic' => 'Exponents & Roots, Quadratics, Inequalities',
            'desc' => "Exponents & Roots: Learn how to compare radical expressions\nQuadratics: Learn how to factor, expand, and apply the difference of squares efficiently\nInequalities: Learn how solving inequalities differs from solving equations, including how sign changes affect solutions"),
        'algebra_3'      => array('label' => 'Algebra Lesson 3', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-3-copy',
            'topic' => 'Formulas, Functions, & Sequences',
            'desc' => "Formulas, Functions, & Sequences: Learn how to interpret and manipulate formulas, function notation, and sequences without overcomplicating the algebra"),
        'algebra_4'      => array('label' => 'Algebra Lesson 4', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-4-copy',
            'topic' => 'Inequalities & Quadratics',
            'desc' => "Inequalities: Learn how to use elimination to combine inequalities and identify valid solution ranges efficiently\nQuadratics: Learn how to eliminate variables in quadratic systems to solve for multi-variable expressions"),
        'algebra_5'      => array('label' => 'Algebra Lesson 5', 'section' => 'Algebra', 'xapi_slug' => 'algebra-lesson-5-copy',
            'topic' => 'Exponents & Roots',
            'desc' => "Exponents & Roots: Learn how to use conjugates to simplify radical expressions and eliminate irrational denominators"),

        // ── Word Problems ──
        'word_problems_1' => array('label' => 'Word Problems Lesson 1', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-1-copy',
            'topic' => 'Translations',
            'desc' => "Translations: Learn how to model money-based problems using clear algebraic structures\nLearn how to translate fuel relationships into solvable equations efficiently"),
        'word_problems_2' => array('label' => 'Word Problems Lesson 2', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-2-copy',
            'topic' => 'Combined Rates, Two Overlapping Sets',
            'desc' => "Combined Rates: Learn how to organize complex rate information using a rate table to simplify multi-step problems\nTwo Overlapping Sets: Learn how to use the double-set matrix to track overlapping quantities accurately"),
        'word_problems_3' => array('label' => 'Word Problems Lesson 3', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-3-copy',
            'topic' => 'Rates - Changing Workers, Average Speed, Combinatorics',
            'desc' => "Rates - Changing Workers: Learn how adding or removing workers affects total time and productivity\nRates - Average Speed: Learn how to correctly calculate average speed in multi-leg journeys\nCombinatorics: Learn how to determine whether the order of selections affects the total number of combinations"),
        'word_problems_4' => array('label' => 'Word Problems Lesson 4', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-4-copy',
            'topic' => 'Statistics - Average/Sum & Median, Evenly-Spaced Sets',
            'desc' => "Statistics - Average/Sum: Learn how averages relate to totals and how to manipulate one to solve for the other\nStatistics - Median: Learn how to reason about medians in ordered and partially defined data sets\nEvenly-Spaced Sets: Learn how to recognize evenly spaced number sets and use shortcut formulas"),
        'word_problems_5' => array('label' => 'Word Problems Lesson 5', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-5-copy',
            'topic' => 'Weighted Averages, Standard Deviation, Three Overlapping Sets',
            'desc' => "Weighted Averages: Learn how to use the tug-of-war diagram to solve mixture and weighted average problems intuitively\nStandard Deviation: Learn how to compare and reason about standard deviation using spread and symmetry\nThree Overlapping Sets: Learn how to use structured formulas to track three overlapping sets accurately"),
        'word_problems_6' => array('label' => 'Word Problems Lesson 6', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-6-copy',
            'topic' => 'Combinatorics, Probability, Unit Conversions',
            'desc' => "Combinatorics: Learn when to split problems into separate cases vs. stages\nProbability: Learn how to calculate probabilities involving multiple events while tracking dependencies\nUnit Conversions: Learn how to convert units directly and handle multi-step conversions systematically"),
        'word_problems_7' => array('label' => 'Word Problems Lesson 7', 'section' => 'Word Problems', 'xapi_slug' => 'word-problems-lesson-7-copy',
            'topic' => 'Probability, Combinatorics, Min/Max',
            'desc' => "Probability & Combinatorics: Learn how to count or calculate probability when success can occur through multiple valid outcomes and solve \"at least\" problems using complements\nStatistics - Min/Max: Learn how to determine feasible minimum and maximum values using constraints and logical bounds"),

        // ── Number Properties ──
        'number_props_1'  => array('label' => 'Number Properties Lesson 1', 'section' => 'Number Properties', 'xapi_slug' => 'number-properties-lesson-1-copy',
            'topic' => 'Divisibility & Primes - Prime Factoring',
            'desc' => "Divisibility & Primes: Learn how to break numbers into prime factors to analyze divisibility\nLearn how to recognize square and cube structures that simplify exponent, root, and divisibility problems\nLearn how the GMAT disguises straightforward number property questions using misleading wording"),
        'number_props_2'  => array('label' => 'Number Properties Lesson 2', 'section' => 'Number Properties', 'xapi_slug' => 'number-properties-lesson-2-copy',
            'topic' => 'Divisibility & Primes - Remainders',
            'desc' => "Divisibility & Primes (Remainders): Learn how remainders interact with divisibility in GMAT-style number property questions"),
        'number_props_3'  => array('label' => 'Number Properties Lesson 3', 'section' => 'Number Properties', 'xapi_slug' => 'number-properties-lesson-3-copy',
            'topic' => 'Digits & Decimals',
            'desc' => "Digits & Decimals: Learn how decimal shifts affect place value and magnitude\nLearn how to identify repeating units digit patterns to solve exponent and power problems quickly\nLearn how prime factor structure determines whether a decimal terminates or repeats\nLearn how to model digit reversal algebraically and recognize common GMAT digit traps"),

        // ── Fractions, Percents, Ratios (FPRs) ──
        'fprs_1'          => array('label' => 'FPRs Lesson 1', 'section' => 'FPRs', 'xapi_slug' => 'fractions-percents-and-ratios-lesson-1-copy',
            'topic' => 'Percent Translations',
            'desc' => "Percents: Learn how to translate percent language into equations quickly and accurately"),
        'fprs_2'          => array('label' => 'FPRs Lesson 2', 'section' => 'FPRs', 'xapi_slug' => 'fractions-percents-and-ratios-lesson-2-copy',
            'topic' => 'Fractions & Ratios',
            'desc' => "Fractions: Learn when cross multiplication is valid and how to apply advanced fraction manipulations\nRatios: Learn how to use the unknown multiplier method to translate ratios into solvable algebraic equations"),

        // ── Quant Exercises ──
        'quant_exercise_1' => array('label' => 'Quant Exercise 1', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-1-publish',
            'topic' => 'Unit 2 Quant Guided Practice',
            'desc' => "Apply Unit 2 Quant strategies under realistic time pressure, with personalized tutor support targeting your specific breakdowns\nSkills Covered: Algebra (Exponents & Roots, Linear Equations), Number Properties (Divisibility & Primes), Word Problems (Translations), Problem Solving Strategies (Smart Numbers, Working Backwards)"),
        'quant_exercise_2' => array('label' => 'Quant Exercise 2', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-2-publish',
            'topic' => 'Unit 3 Quant Guided Practice',
            'desc' => "Apply Unit 3 Quant strategies under realistic time pressure, with personalized tutor support targeting your specific breakdowns\nSkills Covered: Algebra (Exponents & Roots, Quadratics, Inequalities), Number Properties (Remainders), Word Problems (Combined Rates, Two Overlapping Sets), Problem Solving Strategies (Estimation)"),
        'quant_exercise_3' => array('label' => 'Quant Exercise 3', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-3-publish',
            'topic' => 'Unit 4 Quant Guided Practice',
            'desc' => "Apply Unit 4 Quant strategies under realistic time pressure, with personalized tutor support targeting your specific breakdowns\nSkills Covered: Algebra (Formulas, Functions, & Sequences), Word Problems (Rates, Statistics, Evenly-Spaced Sets, Combinatorics), FPRs (Fractions, Ratios)"),
        'quant_exercise_4' => array('label' => 'Quant Exercise 4', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-4-publish',
            'topic' => 'Unit 5 Quant Guided Practice',
            'desc' => "Apply Unit 5 Quant strategies under realistic time pressure, with personalized tutor support targeting your specific breakdowns\nSkills Covered: Algebra (Inequalities, Quadratics), Word Problems (Weighted Averages, Standard Deviation, Three Overlapping Sets, Combinatorics, Probability, Unit Conversions)"),
        'quant_exercise_5' => array('label' => 'Quant Exercise 5', 'section' => 'Quant Exercises', 'xapi_slug' => 'quant-learning-exercise-5-publish',
            'topic' => 'Unit 6 Quant Guided Practice',
            'desc' => "Apply Unit 6 Quant strategies under realistic time pressure, with personalized tutor support targeting your specific breakdowns\nSkills Covered: Algebra (Exponents & Roots), Number Properties (Digits & Decimals), Word Problems (Probability, Combinatorics, Statistics - Min/Max)"),

        // ── Verbal Review Sets ──
        'verbal_review_2' => array('label' => 'Unit 2 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-2',
            'topic' => 'Reading Comprehension',
            'desc' => "Apply Unit 2 verbal concepts under exam-like timing and difficulty to reinforce accuracy and decision-making"),
        'verbal_review_3' => array('label' => 'Unit 3 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-3-copy',
            'topic' => 'Reading Comprehension and CR Plan Arguments',
            'desc' => "Reinforce Unit 3 verbal skills under realistic test conditions"),
        'verbal_review_4' => array('label' => 'Unit 4 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-4',
            'topic' => 'Reading Comprehension & CR Regular Arguments',
            'desc' => "Reinforce Unit 4 verbal skills under realistic test conditions"),
        'verbal_review_5' => array('label' => 'Unit 5 Verbal Review Set', 'section' => 'Verbal Reviews', 'xapi_slug' => 'verbal-review-set-unit-5-copy',
            'topic' => 'Reading Comprehension & CR Explanation Arguments',
            'desc' => "Reinforce Unit 5 verbal skills under realistic test conditions"),

        // ── Quant Review Sets ──
        'quant_review_2'  => array('label' => 'Unit 2 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-2-copy',
            'topic' => 'Review Unit 2 Quant Concepts',
            'desc' => "Apply Unit 2 quant concepts under exam-like timing and difficulty to reinforce accuracy and decision-making\nSkills Covered: Algebra (Exponents & Roots, Linear Equations), Number Properties (Divisibility & Primes), Word Problems (Translations), Problem Solving Strategies (Smart Numbers, Working Backwards)"),
        'quant_review_3'  => array('label' => 'Unit 3 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-3-copy',
            'topic' => 'Review Unit 3 Quant Concepts',
            'desc' => "Apply Unit 3 quant concepts under exam-like timing and difficulty to reinforce accuracy and decision-making\nSkills Covered: Algebra (Exponents & Roots, Quadratics, Inequalities), Number Properties (Remainders), Word Problems (Combined Rates, Two Overlapping Sets), Problem Solving Strategies (Estimation)"),
        'quant_review_4'  => array('label' => 'Unit 4 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-4-copy',
            'topic' => 'Review Unit 4 Quant Concepts',
            'desc' => "Apply Unit 4 quant concepts under exam-like timing and difficulty to reinforce accuracy and decision-making\nSkills Covered: Algebra (Formulas, Functions, & Sequences), Word Problems (Rates, Statistics, Evenly-Spaced Sets, Combinatorics), FPRs (Fractions, Ratios)"),
        'quant_review_5'  => array('label' => 'Unit 5 Quant Review Set', 'section' => 'Quant Reviews', 'xapi_slug' => 'quant-practice-set-1-unit-5-copy',
            'topic' => 'Review Unit 5 Quant Concepts',
            'desc' => "Apply Unit 5 quant concepts under exam-like timing and difficulty to reinforce accuracy and decision-making\nSkills Covered: Algebra (Inequalities, Quadratics), Word Problems (Weighted Averages, Standard Deviation, Three Overlapping Sets, Combinatorics, Probability, Unit Conversions)"),

        // ── Data Insights (DI) Lessons ──
        // di_lesson_1 = DI Lesson 1 (DS Intro) — has xAPI URL
        // di_lesson_2 = DI Lesson 2 (DS Strategies 1) — NOT AVAILABLE, admin-configurable
        // di_lesson_3 = DI Lesson 3 (DS Strategies 2) — has xAPI URL
        // di_lesson_4 = DI Lesson 4 (Graphics Interpretation) — NOT AVAILABLE, admin-configurable
        // di_lesson_5 = DI Lesson 5 (Table Interpretation) — NOT AVAILABLE, admin-configurable
        // di_lesson_6 = DI Lesson 6 (Two-Part Analysis) — NOT AVAILABLE, admin-configurable
        // di_lesson_7 = DI Lesson 7 (Multi-Source Reasoning) — NOT AVAILABLE, admin-configurable
        'di_lesson_1'     => array('label' => 'DI Lesson 1: Data Sufficiency Methods', 'section' => 'DI Lessons', 'xapi_slug' => '',
            'topic' => 'DS Question Structure & Methodology',
            'desc' => "Learn the structure of Data Sufficiency questions, including how prompts, statements, and answer choices work together\nLearn a consistent step-by-step approach for evaluating sufficiency without solving more than necessary\nLearn how to distinguish value questions from yes/no questions and adjust your evaluation strategy accordingly\nLearn how to demonstrate insufficiency confidently instead of guessing or over-solving"),
        'di_lesson_2'     => array('label' => 'DI Lesson 2: DS Strategies 1',        'section' => 'DI Lessons', 'xapi_slug' => '',
            'topic' => 'Rephrasing and Counting Variables & Equations',
            'desc' => "Learn how to rephrase the question in a clearer form so you know exactly what information is required\nLearn how to count variables and equations to determine whether enough information exists to answer the question"),
        'di_lesson_3'     => array('label' => 'DI Lesson 3: DS Strategies 2',        'section' => 'DI Lessons', 'xapi_slug' => 'di-lesson-4-ds-strategies-2',
            'topic' => 'Testing Cases & Logical Evaluation',
            'desc' => "Learn how to test strategic cases to uncover hidden insufficiency or confirm consistency\nLearn when logical reasoning alone is sufficient to answer a DS question without calculations or case testing"),
        'di_lesson_4'     => array('label' => 'DI Lesson 4: Graphics Interpretation', 'section' => 'DI Lessons', 'xapi_slug' => '',
            'topic' => 'Practice Graphics Interpretation',
            'desc' => "Learn how to extract trends, relationships, and constraints from charts and graphs without getting lost in details"),
        'di_lesson_5'     => array('label' => 'DI Lesson 5: Table Analysis',          'section' => 'DI Lessons', 'xapi_slug' => '',
            'topic' => 'Practice Table Analysis',
            'desc' => "Learn how to use table filters and sorting efficiently to isolate relevant data\nLearn how to balance speed and precision when working with dense data tables"),
        'di_lesson_6'     => array('label' => 'DI Lesson 6: Two-Part Analysis',      'section' => 'DI Lessons', 'xapi_slug' => '',
            'topic' => 'Practice Two-Part Analysis',
            'desc' => "Learn how two-part analysis questions are built and how they borrow from the topics tested in the quant and verbal sections"),
        'di_lesson_7'     => array('label' => 'DI Lesson 7: Multi-Source Reasoning', 'section' => 'DI Lessons', 'xapi_slug' => '',
            'topic' => 'Practice Multi-Source Reasoning',
            'desc' => "Learn how to scan, prioritize, and cross-reference multiple information sources efficiently"),
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
        'di_lesson_1' => 'DI Lesson 1: Data Sufficiency Methods',
        'di_lesson_2' => 'DI Lesson 2: DS Strategies 1',
        'di_lesson_4' => 'DI Lesson 4: Graphics Interpretation',
        'di_lesson_5' => 'DI Lesson 5: Table Analysis',
        'di_lesson_6' => 'DI Lesson 6: Two-Part Analysis',
        'di_lesson_7' => 'DI Lesson 7: Multi-Source Reasoning',
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
