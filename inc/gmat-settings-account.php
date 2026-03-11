<?php
/**
 * GMAT Settings — WooCommerce My Account Tab
 *
 * Adds a "GMAT Settings" tab to the WooCommerce My Account sidebar.
 * Allows users to edit their intake form data:
 * - Desired Score, Test Date, Weekly Study Schedule, Study Module
 * - GMAT Scores (test date, overall, quant, verbal, data insights)
 *
 * All fields are pre-filled with data from the intake form user meta.
 */

if (!defined('ABSPATH')) exit;


// ============================================================================
// 1. REGISTER ENDPOINT
// ============================================================================

/**
 * Register the "gmat-settings" endpoint so WooCommerce
 * recognises /my-account/gmat-settings/ as a valid page.
 */
function gmat_settings_add_endpoint() {
    add_rewrite_endpoint('gmat-settings', EP_ROOT | EP_PAGES);
}
add_action('init', 'gmat_settings_add_endpoint');

/**
 * Add "gmat-settings" to WooCommerce query vars so it can be parsed.
 */
function gmat_settings_query_vars($vars) {
    $vars[] = 'gmat-settings';
    return $vars;
}
add_filter('query_vars', 'gmat_settings_query_vars', 0);


// ============================================================================
// 2. ADD MENU ITEM
// ============================================================================

/**
 * Insert "GMAT Settings" into the My Account sidebar menu,
 * placed after "Account Details" (edit-account).
 */
function gmat_settings_menu_item($items) {
    $new_items = array();

    foreach ($items as $key => $label) {
        $new_items[$key] = $label;

        // Insert after "Account Details"
        if ($key === 'edit-account') {
            $new_items['gmat-settings'] = 'GMAT Settings';
        }
    }

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'gmat_settings_menu_item');


// ============================================================================
// 3. ENDPOINT CONTENT — Renders the settings page HTML
// ============================================================================

function gmat_settings_endpoint_content() {
    $user_id = get_current_user_id();
    if (!$user_id) return;

    // Load existing intake data
    $goal_score         = get_user_meta($user_id, '_gmat_intake_goal_score', true);
    $next_test_date     = get_user_meta($user_id, '_gmat_intake_next_test_date', true);
    $weekly_hours       = get_user_meta($user_id, '_gmat_intake_weekly_hours', true);
    $section_preference = get_user_meta($user_id, '_gmat_intake_section_preference', true);

    // Scores — stored as JSON strings
    $official_scores_raw = get_user_meta($user_id, '_gmat_intake_official_scores', true);
    $practice_scores_raw = get_user_meta($user_id, '_gmat_intake_practice_scores', true);

    $official_scores = $official_scores_raw ? json_decode($official_scores_raw, true) : array();
    $practice_scores = $practice_scores_raw ? json_decode($practice_scores_raw, true) : array();

    // Use the most recent score entry (official first, then practice) to pre-fill
    $latest_score = null;
    if (!empty($official_scores)) {
        $latest_score = end($official_scores);
    } elseif (!empty($practice_scores)) {
        $latest_score = end($practice_scores);
    }

    // Format the test date for display (MM/DD/YYYY)
    $test_date_display = '';
    if ($next_test_date) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $next_test_date);
        if ($date_obj) {
            $test_date_display = $date_obj->format('m/d/Y');
        }
    }

    // Format score date for display (MM/DD/YYYY)
    $score_date_display = '';
    if ($latest_score && !empty($latest_score['date'])) {
        $sd_obj = DateTime::createFromFormat('Y-m-d', $latest_score['date']);
        if ($sd_obj) {
            $score_date_display = $sd_obj->format('m/d/Y');
        } else {
            $score_date_display = $latest_score['date'];
        }
    }

    // Nonce and AJAX URL
    $nonce    = wp_create_nonce('gmat_settings_nonce');
    $ajax_url = admin_url('admin-ajax.php');

    ?>
    <script>
        var gmatSettingsData = {
            ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
            nonce: <?php echo wp_json_encode($nonce); ?>
        };
    </script>

    <style>
    .gmat-date-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .gmat-date-input-wrap input[type="text"] {
        padding-right: 38px;
        width: 100%;
    }
    .gmat-date-picker-hidden {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
        overflow: hidden;
    }
    .gmat-date-picker-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: #00409E;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .gmat-date-picker-btn:hover,
    .gmat-date-picker-btn:focus,
    .gmat-date-picker-btn:active {
        color: #002b6b;
        background: none;
        background-color: transparent;
        outline: none;
        box-shadow: none;
    }
    </style>

    <div id="gmat-settings-page">

        <!-- GMAT Settings Section -->
        <div class="gmat-settings-section">
            <h3 class="gmat-settings-section__title">GMAT Settings</h3>

            <div class="gmat-settings-row">
                <div class="gmat-settings-field">
                    <label for="gmat-s-desired-score">Desired Score</label>
                    <input type="number" id="gmat-s-desired-score" class="gmat-settings-input" min="205" max="805" step="10" value="<?php echo esc_attr($goal_score); ?>" placeholder="Enter score">
                </div>
                <div class="gmat-settings-field">
                    <label for="gmat-s-test-date">Test Date</label>
                    <div class="gmat-date-input-wrap">
                        <input type="text" id="gmat-s-test-date" class="gmat-settings-input" placeholder="MM/DD/YYYY" maxlength="10" autocomplete="off" value="<?php echo esc_attr($test_date_display); ?>">
                        <input type="date" class="gmat-date-picker-hidden" data-target="#gmat-s-test-date" min="<?php echo esc_attr(date('Y-m-d')); ?>" tabindex="-1" aria-hidden="true">
                        <button type="button" class="gmat-date-picker-btn" data-target="#gmat-s-test-date" title="Open calendar" aria-label="Open calendar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="gmat-settings-row">
                <div class="gmat-settings-field">
                    <label for="gmat-s-weekly-hours">Weekly Study Schedule</label>
                    <input type="text" id="gmat-s-weekly-hours" class="gmat-settings-input" value="<?php echo $weekly_hours ? esc_attr($weekly_hours . ' Hours') : ''; ?>" placeholder="e.g. 12 Hours" readonly>
                    <select id="gmat-s-weekly-hours-select" class="gmat-settings-select" style="display:none;">
                        <option value="">Select hours</option>
                        <?php for ($h = 5; $h <= 40; $h += 5) : ?>
                            <option value="<?php echo $h; ?>" <?php selected($weekly_hours, $h); ?>><?php echo $h; ?> Hours</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="gmat-settings-field">
                    <label for="gmat-s-study-module">Study Module</label>
                    <select id="gmat-s-study-module" class="gmat-settings-input gmat-settings-select-visible">
                        <option value="quant" <?php selected($section_preference, 'quant'); ?>>Quant</option>
                        <option value="verbal" <?php selected($section_preference, 'verbal'); ?>>Verbal</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- GMAT Scores Section -->
        <div class="gmat-settings-section">
            <h3 class="gmat-settings-section__title">GMAT Scores</h3>

            <div class="gmat-settings-row">
                <div class="gmat-settings-field">
                    <label for="gmat-s-score-date">Test Date</label>
                    <div class="gmat-date-input-wrap">
                        <input type="text" id="gmat-s-score-date" class="gmat-settings-input" placeholder="MM/DD/YYYY" maxlength="10" autocomplete="off" value="<?php echo esc_attr($score_date_display); ?>">
                        <input type="date" class="gmat-date-picker-hidden" data-target="#gmat-s-score-date" tabindex="-1" aria-hidden="true">
                        <button type="button" class="gmat-date-picker-btn" data-target="#gmat-s-score-date" title="Open calendar" aria-label="Open calendar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </button>
                    </div>
                </div>
                <div class="gmat-settings-field">
                    <label for="gmat-s-score-overall">Overall Score</label>
                    <input type="number" id="gmat-s-score-overall" class="gmat-settings-input" min="205" max="805" step="10" value="<?php echo $latest_score && !empty($latest_score['overall']) ? esc_attr($latest_score['overall']) : ''; ?>" placeholder="Enter score">
                </div>
            </div>

            <div class="gmat-settings-row">
                <div class="gmat-settings-field">
                    <label for="gmat-s-score-quant">Quant Score</label>
                    <input type="number" id="gmat-s-score-quant" class="gmat-settings-input" min="60" max="90" value="<?php echo $latest_score && !empty($latest_score['quant']) ? esc_attr($latest_score['quant']) : ''; ?>" placeholder="Enter score">
                </div>
                <div class="gmat-settings-field">
                    <label for="gmat-s-score-verbal">Verbal Score</label>
                    <input type="number" id="gmat-s-score-verbal" class="gmat-settings-input" min="60" max="90" value="<?php echo $latest_score && !empty($latest_score['verbal']) ? esc_attr($latest_score['verbal']) : ''; ?>" placeholder="Enter score">
                </div>
            </div>

            <div class="gmat-settings-row gmat-settings-row--half">
                <div class="gmat-settings-field">
                    <label for="gmat-s-score-di">Data Insights Score</label>
                    <input type="number" id="gmat-s-score-di" class="gmat-settings-input" min="60" max="90" value="<?php echo $latest_score && !empty($latest_score['di']) ? esc_attr($latest_score['di']) : ''; ?>" placeholder="Enter score">
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="gmat-settings-actions">
            <div class="gmat-settings-message" id="gmat-settings-message"></div>
            <button type="button" class="gmat-settings-btn-save" id="gmat-settings-save">Save Changes</button>
        </div>

    </div>
    <?php
}
add_action('woocommerce_account_gmat-settings_endpoint', 'gmat_settings_endpoint_content');


// ============================================================================
// 4. ENQUEUE CSS + JS only on the GMAT Settings endpoint
// ============================================================================

function gmat_settings_enqueue_assets() {
    // Only load on My Account → gmat-settings endpoint
    if (!is_account_page()) return;

    global $wp;
    if (!isset($wp->query_vars['gmat-settings'])) return;

    $theme_version = wp_get_theme()->get('Version');

    // wp_enqueue_style(
    //     'gmat-settings',
    //     get_stylesheet_directory_uri() . '/css/gmat-settings.css',
    //     array(),
    //     $theme_version
    // );

    wp_enqueue_script(
        'gmat-settings',
        get_stylesheet_directory_uri() . '/js/gmat-settings.js',
        array('jquery'),
        $theme_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'gmat_settings_enqueue_assets');


// ============================================================================
// 5. AJAX HANDLER — Save all GMAT settings at once
// ============================================================================

function gmat_settings_save_handler() {
    check_ajax_referer('gmat_settings_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in.'), 403);
    }

    $user_id = get_current_user_id();

    // --- GMAT Settings fields ---
    $desired_score      = isset($_POST['desired_score'])      ? intval($_POST['desired_score'])               : '';
    $test_date          = isset($_POST['test_date'])          ? sanitize_text_field($_POST['test_date'])       : '';
    $weekly_hours       = isset($_POST['weekly_hours'])       ? intval($_POST['weekly_hours'])                 : '';
    $study_module       = isset($_POST['study_module'])       ? sanitize_text_field($_POST['study_module'])    : '';

    // --- GMAT Scores fields ---
    $score_date    = isset($_POST['score_date'])    ? sanitize_text_field($_POST['score_date'])    : '';
    $score_overall_raw = isset($_POST['score_overall']) ? trim($_POST['score_overall']) : '';
    $score_overall = $score_overall_raw !== '' ? intval($score_overall_raw) : '';
    $score_quant_raw   = isset($_POST['score_quant'])   ? trim($_POST['score_quant'])   : '';
    $score_quant   = $score_quant_raw !== '' ? intval($score_quant_raw) : '';
    $score_verbal_raw  = isset($_POST['score_verbal'])  ? trim($_POST['score_verbal'])  : '';
    $score_verbal  = $score_verbal_raw !== '' ? intval($score_verbal_raw) : '';
    $score_di_raw      = isset($_POST['score_di'])      ? trim($_POST['score_di'])      : '';
    $score_di      = $score_di_raw !== '' ? intval($score_di_raw) : '';

    // Validate desired score (must be 205-805 in increments of 10)
    if ($desired_score && ($desired_score < 205 || $desired_score > 805 || ($desired_score - 205) % 10 !== 0)) {
        wp_send_json_error(array('message' => 'Desired score must be between 205 and 805 in increments of 10.'));
    }

    // Validate test date — accept MM/DD/YYYY, convert to Y-m-d
    if ($test_date) {
        $date_obj = DateTime::createFromFormat('m/d/Y', $test_date);
        if (!$date_obj || $date_obj->format('m/d/Y') !== $test_date) {
            // Fallback: try Y-m-d
            $date_obj = DateTime::createFromFormat('Y-m-d', $test_date);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $test_date) {
                wp_send_json_error(array('message' => 'Please enter a valid test date (MM/DD/YYYY).'));
            }
        }
        $test_date = $date_obj->format('Y-m-d');
    }

    // Validate weekly hours
    if ($weekly_hours && ($weekly_hours <= 0 || $weekly_hours > 100)) {
        wp_send_json_error(array('message' => 'Weekly hours must be between 1 and 100.'));
    }

    // Validate study module
    if ($study_module && !in_array($study_module, array('quant', 'verbal'), true)) {
        wp_send_json_error(array('message' => 'Study module must be Quant or Verbal.'));
    }

    // Validate section scores (overall must be in increments of 10)
    if ($score_overall !== '' && ($score_overall < 205 || $score_overall > 805 || ($score_overall - 205) % 10 !== 0)) {
        wp_send_json_error(array('message' => 'Overall score must be between 205 and 805 in increments of 10.'));
    }
    if ($score_quant !== '' && ($score_quant < 60 || $score_quant > 90)) {
        wp_send_json_error(array('message' => 'Quant score must be between 60 and 90.'));
    }
    if ($score_verbal !== '' && ($score_verbal < 60 || $score_verbal > 90)) {
        wp_send_json_error(array('message' => 'Verbal score must be between 60 and 90.'));
    }
    if ($score_di !== '' && ($score_di < 60 || $score_di > 90)) {
        wp_send_json_error(array('message' => 'Data Insights score must be between 60 and 90.'));
    }

    // --- Save settings ---
    if ($desired_score) {
        update_user_meta($user_id, '_gmat_intake_goal_score', $desired_score);
    }
    if ($test_date) {
        update_user_meta($user_id, '_gmat_intake_next_test_date', $test_date);
    }
    if ($weekly_hours) {
        update_user_meta($user_id, '_gmat_intake_weekly_hours', $weekly_hours);
    }
    if ($study_module) {
        update_user_meta($user_id, '_gmat_intake_section_preference', $study_module);
    }

    // --- Save score entry (replace or add to the official scores array) ---
    // Convert score_date from MM/DD/YYYY to Y-m-d for storage
    if ($score_date) {
        $sd_obj = DateTime::createFromFormat('m/d/Y', $score_date);
        if ($sd_obj) {
            $score_date = $sd_obj->format('Y-m-d');
        } else {
            // Try Y-m-d fallback
            $sd_obj = DateTime::createFromFormat('Y-m-d', $score_date);
            if ($sd_obj) {
                $score_date = $sd_obj->format('Y-m-d');
            }
        }
    }

    if ($score_date || $score_overall || $score_quant || $score_verbal || $score_di) {
        $new_score = array(
            'date'    => $score_date,
            'overall' => $score_overall ? $score_overall : 0,
            'quant'   => $score_quant   ? $score_quant   : 0,
            'verbal'  => $score_verbal  ? $score_verbal  : 0,
            'di'      => $score_di      ? $score_di      : 0,
        );

        // Sanitize using existing helper
        if (function_exists('gmat_intake_sanitize_scores')) {
            $sanitized = gmat_intake_sanitize_scores(array($new_score));
            if (!empty($sanitized)) {
                $new_score = $sanitized[0];
            }
        }

        // Load existing official scores
        $existing_raw = get_user_meta($user_id, '_gmat_intake_official_scores', true);
        $existing = $existing_raw ? json_decode($existing_raw, true) : array();
        if (!is_array($existing)) {
            $existing = array();
        }

        // Check if an entry with the same date exists — update it; otherwise append
        $found = false;
        foreach ($existing as $i => $entry) {
            if (isset($entry['date']) && $entry['date'] === $new_score['date']) {
                $existing[$i] = $new_score;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $existing[] = $new_score;
        }

        update_user_meta($user_id, '_gmat_intake_official_scores', wp_json_encode($existing));
    }

    wp_send_json_success(array('message' => 'Settings saved successfully.'));
}
add_action('wp_ajax_gmat_settings_save', 'gmat_settings_save_handler');


// ============================================================================
// 6. FLUSH REWRITE RULES ON THEME ACTIVATION
// ============================================================================

/**
 * Flush rewrite rules when the theme is activated or switched,
 * so the /my-account/gmat-settings/ endpoint is available immediately.
 */
function gmat_settings_flush_rewrite() {
    gmat_settings_add_endpoint();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'gmat_settings_flush_rewrite');
