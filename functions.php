<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// ============================================================================
// CONSTANTS & CONFIGURATION
// ============================================================================

// Fallback constants if not defined in wp-config.php
if (!defined('FREE_TRIAL_PRODUCT_ID')) define('FREE_TRIAL_PRODUCT_ID', 7006);
if (!defined('FREE_TRIAL_COURSE_ID')) define('FREE_TRIAL_COURSE_ID', 7472);
if (!defined('FREE_TRIAL_DAYS')) define('FREE_TRIAL_DAYS', 5);


// ============================================================================
// INCLUDE CUSTOM FUNCTIONALITY FILES
// ============================================================================

// Include all custom functionality from separate files
$custom_includes = array(
    'inc/free-trial-grassblade-xapi.php',           // GrassBlade xAPI integration and New Free Trial Study Plan
    'inc/gurutor-thankyou-shortcodes.php',
    'inc/gmat-intake-form.php',                     // GMAT Intake onboarding wizard
    'inc/gmat-settings-account.php',                // GMAT Settings in My Account
    'inc/gmat-chatbox.php',                         // GMAT AI Chatbox — Floating assistant widget
    'inc/gmat-study-plan-admin.php',                // GMAT Study Plan — Admin lesson ID config
    'inc/gmat-study-plan.php',                      // GMAT Study Plan — Dynamic course page
    'inc/gmat-dashboard.php',                       // GMAT Dashboard — Paid user home page
);

foreach ($custom_includes as $file) {
    $filepath = get_stylesheet_directory() . '/' . $file;
    if (file_exists($filepath)) {
        require_once $filepath;
    } else {
        error_log("Missing include file: {$filepath}");
    }
}


/**
 * Enqueue theme scripts and styles
 */
function gurutor_enqueue_scripts() {
    $theme_version = wp_get_theme()->get('Version');
    
    // Enqueue main theme stylesheet
    if (is_rtl() && file_exists(get_template_directory() . '/rtl.css')) {
        wp_enqueue_style('parent-rtl', get_template_directory_uri() . '/rtl.css', array(), $theme_version);
    }
    
    // Enqueue custom CSS
    wp_enqueue_style(
        'gurutor-custom',
        get_stylesheet_directory_uri() . '/css/gurutor-custom.css',
        array(),
        $theme_version
    );
    
    // Enqueue custom JS
    wp_enqueue_script(
        'gurutor-custom',
        get_stylesheet_directory_uri() . '/js/gurutor-custom.js',
        array('jquery'),
        $theme_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'gurutor_enqueue_scripts');


// ============================================================================
// WOOCOMMERCE SUBSCRIPTION ROLE MANAGEMENT
// ============================================================================

add_action('init', function () {

    // Run only if WooCommerce is active
    if (!class_exists('WooCommerce')) return;

    /**
     * Helper: Set the user's role safely (skip admins)
     */
    function csr_set_role_if_not_admin($user_id, $role) {
        if (!$user_id) return;
        if (user_can($user_id, 'manage_options')) return; // skip admins
        $user = new WP_User($user_id);
        $user->set_role($role); // ensures only one role remains
    }

    /**
     * Helper: Check if order has a subscription-type product
     */
    function csr_order_has_subscription_product($order) {
        if (!$order instanceof WC_Order) return false;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            if ($product->is_type('subscription') || $product->is_type('variable-subscription')) {
                return true;
            }
        }
        return false;
    }

    // ----------------------------------------------------------
    // (1) Everyone starts as SUBSCRIBER
    // ----------------------------------------------------------

    // Force default WooCommerce registration role to 'subscriber'
    add_filter('woocommerce_registration_default_role', function () {
        return 'subscriber';
    });

    // When a user registers (via login page, trial form, or checkout)
    add_action('user_register', function ($user_id) {
        csr_set_role_if_not_admin($user_id, 'subscriber');
    });

    // Also enforce subscriber role when created via WooCommerce checkout
    add_action('woocommerce_created_customer', function ($customer_id) {
        csr_set_role_if_not_admin($customer_id, 'subscriber');
    }, 9999);

    // Handle first paid renewal (after a free trial)
    add_action('woocommerce_subscription_renewal_payment_complete', function ($renewal_order) {
    if (!$renewal_order instanceof WC_Order) return;
    if ((float)$renewal_order->get_total() <= 0) return;
    
        $user_id = $renewal_order->get_user_id();
        if (!$user_id || user_can($user_id, 'manage_options')) return;
    
        // Make them ONLY 'customer' on the first paid renewal as well
        (new WP_User($user_id))->set_role('customer');
    }, 9999, 1);

    // ----------------------------------------------------------
    // (3) If subscription ends or is cancelled → back to SUBSCRIBER
    // ----------------------------------------------------------

    function csr_downgrade_on_subscription_stop($subscription) {
        if (!is_object($subscription) || !method_exists($subscription, 'get_user_id')) return;
        $user_id = $subscription->get_user_id();
        if ($user_id) {
            csr_set_role_if_not_admin($user_id, 'subscriber');
        }
    }

    add_action('woocommerce_subscription_status_cancelled', 'csr_downgrade_on_subscription_stop');
    add_action('woocommerce_subscription_status_expired', 'csr_downgrade_on_subscription_stop');

    // ----------------------------------------------------------
    // (4) Ensure active subscriptions are 'customer'
    // ----------------------------------------------------------

    add_action('woocommerce_subscription_status_active', function ($subscription) {
        if (!is_object($subscription) || !method_exists($subscription, 'get_user_id')) return;
        $user_id = $subscription->get_user_id();
        if ($user_id) {
            csr_set_role_if_not_admin($user_id, 'customer');
        }
    }, 9999);
    
    
    
    /**
     * Ensure correct user role when a subscription is manually created or activated by an admin.
     */
       add_action('woocommerce_subscription_updated', function ($subscription) {
            if (!is_object($subscription) || !method_exists($subscription, 'get_user_id')) return;
        
            $user_id = $subscription->get_user_id();
            if (!$user_id || user_can($user_id, 'manage_options')) return;
        
            $total = (float) $subscription->get_total();
        
            if ($total <= 0) {
                (new WP_User($user_id))->set_role('subscriber');
            } else {
                (new WP_User($user_id))->set_role('customer');
            }
        }, 9999, 1);
        
    
});


// functions.php
add_action('wp_head', function () {
    ?>
    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get("free_trial") === "access") {
                const css = `
                    #free_trial_access { display: block !important; }
                    #page_free_trial, #page_free_trial2 { display: none !important; }
                `;
                const style = document.createElement("style");
                style.appendChild(document.createTextNode(css));
                document.head.appendChild(style);
                
                  document.addEventListener('DOMContentLoaded', function () {
                  const container = document.getElementById('free_header_dynamic');
                  if (!container) {
                    console.warn('Element with id "free_header_dynamic" not found.');
                    return;
                  }
                
                  const heading = container.querySelector('h4, h1, h2, h3, h5, h6');
                  if (heading) {
                    heading.textContent = 'Thank you';
                  } else {
                    console.warn('Heading element not found inside #free_header_dynamic.');
                  }
                  
                  
                   const txtContainer = document.querySelector('#free_txt_dynamic .elementor-widget-container');
                  if (txtContainer) {
                      txtContainer.style.textAlign = "center";
                    txtContainer.innerHTML = `Thank you! We’re excited for you to try out our free resources. Click the button to access our free trial.`;
                  } else {
                    console.warn('Element #free_txt_dynamic not found.');
                  }
                });
         
            }
        })();
    </script>
    <?php
});



/**
 * Proven Quant Stats → window.provenQuant (pure data, no HTML)
 * Paste into functions.php or a mu-plugin.
 */
add_action('wp_head', 'pp_build_quant_stats_full');
function pp_build_quant_stats_full() {
    if ( ! is_user_logged_in() ) { return; }

    $current_user = wp_get_current_user();
    $url = "https://proven2h7.gblrs.com/xAPI/statements";
    $params = array(
        'verb'   => 'http://adlnet.gov/expapi/verbs/answered',
        'agent'  => '{"mbox":"mailto:' . $current_user->user_email . '"}',
        'limit'  => '10000',
        'format' => 'exact',
    );
    $url = $url . '?' . http_build_query($params);

    // fetch GMAT api auth 
    $auth = defined('GMAT_API_AUTH') ? GMAT_API_AUTH : '';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Basic ' . GMAT_API_AUTH
    ));

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch));
        $result = json_encode(['statements' => []]); // fallback
    }
    curl_close($ch);
    ?>
    <script>
    // =========================
    // CONFIG
    // =========================
    const SUBTOPIC_LABELS = {
      // Algebra
      ER:'Exponents & Roots', QD:'Quadratics', INEQ:'Inequalities', FFS:'Formulas, Functions & Sequences', LE:'Linear Equations',
      // Number Properties
      DP:'Divisibility & Primes', DD:'Digits & Decimals',
      // Word Problems
      TRN:'Translations', RTW:'Rates & Work', OS:'Overlapping Sets', STS:'Statistics', ESS:'Evenly-Spaced Sets', PRB:'Probability', CMB:'Combinatorics',
      // Fractions, Percents, Ratios
      FR:'Fractions', PCT:'Percentages', RA:'Ratios', FPRC:'FPR Connections'
    };
    const SUBTOPIC_SET = new Set(Object.keys(SUBTOPIC_LABELS));

    // TEMP correctness heuristic (replace with real answer-key logic when available)
    function resolveCorrect(varName, value) {
      if (value == null) return false;
      const s = String(value).trim();
      if (!s || /^VAR\(/i.test(s)) return false; // treat placeholders as not answered
      return true; // assume answered => correct (placeholder until you wire real check)
    }
    const isPlaceholder = (v) => v == null || String(v).trim() === '' || /^VAR\(/i.test(String(v));

    // =========================
    // RAW INGEST
    // =========================
    const fixSmart = s => s.replaceAll('“','"').replaceAll('”','"').replaceAll('MainIdea: "', 'MainIdea": "');
    let json = <?php echo wp_json_encode( json_decode($result) ); ?>;

    const flatPairs = [];
    (json?.statements ?? []).forEach(st => {
      try {
        const raw = fixSmart(st?.object?.definition?.name?.['en-US'] ?? '');
        if (!raw) return;
        const obj = JSON.parse(raw);
        Object.entries(obj).forEach(([k,v]) => flatPairs.push([k,v]));
      } catch (_) { /* skip malformed */ }
    });

    // =========================
    // NAME PARSER (robust)
    // =========================
    function parseName(name) {
      // Drop leading "UnitX_" if present
      let n = name.replace(/^Unit\s*\d+/i, '').replace(/^_+/, '');

      // Ignore assist/guess variables outright
      if (/_(Assist|Guess)_/i.test(n)) return null;

      // Identify time suffix: StepX_Time | Exact_Time | generic _Time
      let step = null, timeType = null;
      if (/_Step(?:1|2A|2B|3)([A-Z]+)?_Time$/i.test(n)) {
        const m = n.match(/_Step(1|2A|2B|3)([A-Z]+)?_Time$/i);
        step = ('STEP' + m[1].toUpperCase());
        timeType = 'STEP';
        n = n.replace(/_Step(?:1|2A|2B|3)([A-Z]+)?_Time$/i, '');
      } else if (/_Exact_Time$/i.test(n)) {
        timeType = 'EXACT';
        n = n.replace(/_Exact_Time$/i, '');
      } else if (/_Time$/i.test(n)) {
        timeType = 'GENERIC';
        n = n.replace(/_Time$/i, '');
      }

      // Detect step without time (e.g., _Step1ALG, _Step2B, _Step3)
      if (/_Step(?:1|2A|2B|3)([A-Z]+)?$/i.test(n)) {
        const m = n.match(/_Step(1|2A|2B|3)([A-Z]+)?$/i);
        step = ('STEP' + m[1].toUpperCase());
        n = n.replace(/_Step(?:1|2A|2B|3)([A-Z]+)?$/i, '');
      }

      // Tokenize and find subtopic anywhere
      const toks = n.split(/[_-]+/).filter(Boolean);
      let subtopic = null;
      for (const t of toks) {
        const up = t.toUpperCase();
        if (SUBTOPIC_SET.has(up)) { subtopic = up; break; }
      }
      if (!subtopic) return null;

      return { subtopic, step, timeType };
    }

    function baseKey(name) {
      return name
        .replace(/_Step(?:1|2A|2B|3)([A-Z]+)?(_Time)?$/i, '')
        .replace(/_Exact_Time$/i, '')
        .replace(/_Time$/i, '');
    }

    // =========================
    // REGISTRY (collect ALL time fields)
    // =========================
    const registry = new Map();
    function upsert(key, data) {
      if (!registry.has(key)) {
        registry.set(key, {
          baseKey: key,
          subtopic: null,
          step: null,             // last seen step for answer-type entries
          answered: false,
          correct: false,
          times: { STEP1: undefined, STEP2A: undefined, STEP2B: undefined, STEP3: undefined, EXACT: undefined }
        });
      }
      const obj = registry.get(key);
      Object.assign(obj, data);
      registry.set(key, obj);
    }

    flatPairs.forEach(([k,v]) => {
      const meta = parseName(k);
      if (!meta) return;

      const key = baseKey(k);

      // TIME ENTRIES
      if (meta.timeType) {
        const t = Number(v);
        if (Number.isFinite(t) && t >= 0) {
          const prevTimes = (registry.get(key)?.times) || {};
          let times = { ...prevTimes };
          if (meta.timeType === 'STEP' && meta.step) {
            times[meta.step] = t; // STEP1/STEP2A/STEP2B/STEP3
          } else if (meta.timeType === 'EXACT') {
            times.EXACT = t;
          } else { // GENERIC fallback
            times.EXACT = (times.EXACT ?? t);
          }
          upsert(key, { subtopic: meta.subtopic, times });
        } else {
          upsert(key, { subtopic: meta.subtopic });
        }
        return;
      }

      // ANSWER-LIKE ENTRIES (ignore empty/VAR)
      if (!isPlaceholder(v)) {
        const correct = resolveCorrect(k, v);
        upsert(key, { subtopic: meta.subtopic, step: meta.step ?? null, answered: true, correct });
      } else {
        upsert(key, { subtopic: meta.subtopic, step: meta.step ?? null });
      }
    });

    const items = Array.from(registry.values());

    // Total time for an item: prefer EXACT, else sum step times
    function totalTimeSecFor(item) {
      const t = item.times || {};
      if (Number.isFinite(t.EXACT)) return t.EXACT;
      const parts = [t.STEP1, t.STEP2A, t.STEP2B, t.STEP3].filter(x => Number.isFinite(x));
      return parts.length ? parts.reduce((a,b)=>a+b,0) : undefined;
    }

    // =========================
    // AGGREGATIONS
    // =========================
    const pct = (n,d) => d>0 ? Math.round((n/d)*100) : 0;

    // Overview (tile): QLE Step3 + QPS (no step)
    function overviewFor(code) {
      const rows = items.filter(r => r.subtopic === code && (r.step === 'STEP3' || !r.step));
      const den = rows.length;
      const num = rows.filter(r => r.correct).length;
      return { correctPct: pct(num, den), count: den };
    }

    function detailFor(code) {
      const all = items.filter(r => r.subtopic === code);

      // Overall = Step3 (QLE) + no step (QPS)
      const overallRows     = all.filter(r => r.step === 'STEP3' || !r.step);
      const withSupportRows = all.filter(r => r.step === 'STEP3'); // QLE Step3 only
      const noSupportRows   = all.filter(r => !r.step);            // QPS only

      const overall   = { correctPct: pct(overallRows.filter(r=>r.correct).length,     overallRows.length),     count: overallRows.length };
      const withSupp  = { correctPct: pct(withSupportRows.filter(r=>r.correct).length, withSupportRows.length), count: withSupportRows.length };
      const noSupp    = { correctPct: pct(noSupportRows.filter(r=>r.correct).length,   noSupportRows.length),   count: noSupportRows.length };

      // Average total time across items in this subtopic
      const totals = all.map(totalTimeSecFor).filter(x => Number.isFinite(x) && x >= 0);
      const avgTimeSec = totals.length ? Math.round(totals.reduce((a,b)=>a+b,0) / totals.length) : 0;

      const stepPct = (label) => {
        const rows = all.filter(r => (r.step||'').toUpperCase() === label);
        return { correctPct: pct(rows.filter(r=>r.correct).length, rows.length), count: rows.length };
      };

      return {
        overall:     overall,
        withSupport: withSupp,
        noSupport:   noSupp,
        avgTimeSec,
        steps: {
          Step1:  stepPct('STEP1'),
          Step2A: stepPct('STEP2A'),
          Step2B: stepPct('STEP2B')
        }
      };
    }

    // =========================
    // FINAL OUTPUT (pure data)
    // =========================
    const overview = {};
    const details  = {};

    for (const code of SUBTOPIC_SET) {
      const hasAny = items.some(r => r.subtopic === code);
      if (hasAny) {
        overview[code] = { label: SUBTOPIC_LABELS[code], ...overviewFor(code) };
        details[code]  = detailFor(code);
      } else {
        overview[code] = { label: SUBTOPIC_LABELS[code], correctPct: 0, count: 0 };
        details[code]  = {
          overall:{correctPct:0,count:0},
          withSupport:{correctPct:0,count:0},
          noSupport:{correctPct:0,count:0},
          avgTimeSec:0,
          steps:{
            Step1:{correctPct:0,count:0},
            Step2A:{correctPct:0,count:0},
            Step2B:{correctPct:0,count:0}
          }
        };
      }
    }
    
    
     function setSubtopicToHTML(code, idPct, detailCorrectOverAll, detailCorrectWhenR, detailCorrectWhenNotR, detailCorrectStep1, detailCorrectStep2, detailCorrectStep3, detailAvgTime) {
     
      
      window.addEventListener("DOMContentLoaded", () => {
            if (!window.provenQuant || !window.provenQuant.overview) {
                console.warn('provenQuant data not loaded yet');
                return;
            }
            
            const subOverview = window.provenQuant.overview[code];
            if (!subOverview) {
                console.warn(`Subtopic ${code} not found`);
                return;
            }
            
            
            const detailsData = window.provenQuant.details[code];
            if (!detailsData) {
                console.warn(`detailsData ${code} not found`);
                return;
            }
            // console.log(detailsData);
            
            
            const el = document.getElementById(idPct);
            const el_txt = document.getElementById(idPct + "_txt");
            
            if (el) {
                el.setAttribute("data-percentage", subOverview.correctPct);
                el_txt.textContent = subOverview.correctPct + "%"; // instead of document.write
            }
            
            // ------
            
            const elCorrectOAll = document.getElementById(detailCorrectOverAll);
			const elCorrectOAll_txt = document.getElementById(detailCorrectOverAll + "_txt");
			
			const elCorrectOAlldetailCorrectWhenR = document.getElementById(detailCorrectWhenR);
			const elCorrectOAll_txtdetailCorrectWhenR = document.getElementById(detailCorrectWhenR + "_txt");
			
			const eldetailCorrectWhenNotR = document.getElementById(detailCorrectWhenNotR);
			const eldetailCorrectWhenNotR_txt = document.getElementById(detailCorrectWhenNotR + "_txt");
            
            if (elCorrectOAll) {
                elCorrectOAll.setAttribute("data-percentage", detailsData.overall.correctPct);
                elCorrectOAll_txt.textContent = detailsData.overall.correctPct + "%"; // instead of document.write
            }
            
            if (elCorrectOAlldetailCorrectWhenR) {
                elCorrectOAlldetailCorrectWhenR.setAttribute("data-percentage", detailsData.withSupport.correctPct);
                elCorrectOAll_txtdetailCorrectWhenR.textContent = detailsData.withSupport.correctPct + "%"; // instead of document.write
            }
            
            if (eldetailCorrectWhenNotR) {
                eldetailCorrectWhenNotR.setAttribute("data-percentage", detailsData.noSupport.correctPct);
                eldetailCorrectWhenNotR_txt.textContent = detailsData.noSupport.correctPct + "%"; // instead of document.write
            }
		
		    // ------------
		    
		    const eldetailCorrectStep1 = document.getElementById(detailCorrectStep1);
			const eldetailCorrectStep1_txt = document.getElementById(detailCorrectStep1 + "_txt");
			
			if (eldetailCorrectStep1) {
                eldetailCorrectStep1.setAttribute("data-percentage", detailsData.steps.Step1.correctPct);
                eldetailCorrectStep1_txt.textContent = detailsData.steps.Step1.correctPct + "%"; // instead of document.write
            }
            
            const eldetailCorrectStep2 = document.getElementById(detailCorrectStep2);
			const eldetailCorrectStep2_txt = document.getElementById(detailCorrectStep2 + "_txt");
			
			if (eldetailCorrectStep2) {
                eldetailCorrectStep2.setAttribute("data-percentage", detailsData.steps.Step2A.correctPct);
                eldetailCorrectStep2_txt.textContent = detailsData.steps.Step2A.correctPct + "%"; // instead of document.write
            }
            
            const eldetailCorrectStep3 = document.getElementById(detailCorrectStep3);
			const eldetailCorrectStep3_txt = document.getElementById(detailCorrectStep3 + "_txt");
			
			if (eldetailCorrectStep3) {
                eldetailCorrectStep3.setAttribute("data-percentage", detailsData.steps.Step2B.correctPct);
                eldetailCorrectStep3_txt.textContent = detailsData.steps.Step2B.correctPct + "%"; // instead of document.write
            }
            
            // ------------
            
            const el_time_txt = document.getElementById('bc_timer-count-1_' + detailAvgTime);
            
            if (el_time_txt) {
             const avgMin = detailsData.avgTimeSec / 60;
			 el_time_txt.textContent = avgMin.toFixed(2) + " min";
		    }
    		
	   });
    
    }
    
    
    setSubtopicToHTML("ER", "ER_overview", "detail_correct_overall_cr_algebra", "detail_correct_when_receiving_cr_algebra", "detail_correct_with_no_cr_algebra", "detail_correct_step1_cr_algebra", "detail_correct_step2a_cr_algebra", "detail_correct_step2b_cr_algebra", "cr_algebra");
    setSubtopicToHTML("QD", "QD_overview", "detail_correct_overall_cr_quadratics", "detail_correct_when_receiving_cr_quadratics", "detail_correct_with_no_cr_quadratics", "detail_correct_step1_cr_quadratics", "detail_correct_step2a_cr_quadratics", "detail_correct_step2b_cr_quadratics", "cr_quadratics");
    
    
    setSubtopicToHTML(
      "LE",
      "LE_overview",
      "detail_correct_overall_cr_linear",
      "detail_correct_when_receiving_cr_linear",
      "detail_correct_with_no_cr_linear",
      "detail_correct_step1_cr_linear",
      "detail_correct_step2a_cr_linear",
      "detail_correct_step2b_cr_linear",
      "cr_linear"
    );
    
    setSubtopicToHTML(
      "INEQ",
      "INEQ_overview",
      "detail_correct_overall_cr_main_quadratics",
      "detail_correct_when_receiving_cr_main_quadratics",
      "detail_correct_with_no_cr_main_quadratics",
      "detail_correct_step1_cr_main_quadratics",
      "detail_correct_step2a_cr_main_quadratics",
      "detail_correct_step2b_cr_main_quadratics",
      "cr_main_quadratics"
    );
    
    setSubtopicToHTML(
      "FFS",
      "FFS_overview",
      "detail_correct_overall_cr_ffs",
      "detail_correct_when_receiving_cr_ffs",
      "detail_correct_with_no_cr_ffs",
      "detail_correct_step1_cr_ffs",
      "detail_correct_step2a_cr_ffs",
      "detail_correct_step2b_cr_ffs",
      "cr_ffs"
    );
    
    setSubtopicToHTML(
      "DD",
      "DD_overview",
      "detail_correct_overall_cr_digits_decimals",
      "detail_correct_when_receiving_cr_digits_decimals",
      "detail_correct_with_no_cr_digits_decimals",
      "detail_correct_step1_cr_digits_decimals",
      "detail_correct_step2a_cr_digits_decimals",
      "detail_correct_step2b_cr_digits_decimals",
      "cr_digits_decimals"
    );
    
    setSubtopicToHTML(
      "DP",
      "DP_overview",
      "detail_correct_overall_cr_divisibility_primes",
      "detail_correct_when_receiving_cr_divisibility_primes",
      "detail_correct_with_no_cr_divisibility_primes",
      "detail_correct_step1_cr_divisibility_primes",
      "detail_correct_step2a_cr_divisibility_primes",
      "detail_correct_step2b_cr_divisibility_primes",
      "cr_divisibility_primes"
    );
    
    setSubtopicToHTML(
      "TRN",
      "TRN_overview",
      "detail_correct_overall_cr_translations",
      "detail_correct_when_receiving_cr_translations",
      "detail_correct_with_no_cr_translations",
      "detail_correct_step1_cr_translations",
      "detail_correct_step2a_cr_translations",
      "detail_correct_step2b_cr_translations",
      "cr_translations"
    );
    
    setSubtopicToHTML(
      "RTW",
      "RTW_overview",
      "detail_correct_overall_cr_rates",
      "detail_correct_when_receiving_cr_rates",
      "detail_correct_with_no_cr_rates",
      "detail_correct_step1_cr_rates",
      "detail_correct_step2a_cr_rates",
      "detail_correct_step2b_cr_rates",
      "cr_rates"
    );
    
    setSubtopicToHTML(
      "OS",
      "OS_overview",
      "detail_correct_overall_cr_overlapping",
      "detail_correct_when_receiving_cr_overlapping",
      "detail_correct_with_no_cr_overlapping",
      "detail_correct_step1_cr_overlapping",
      "detail_correct_step2a_cr_overlapping",
      "detail_correct_step2b_cr_overlapping",
      "cr_overlapping"
    );
    
    setSubtopicToHTML(
      "STS",
      "STS_overview",
      "detail_correct_overall_cr_statistics",
      "detail_correct_when_receiving_cr_statistics",
      "detail_correct_with_no_cr_statistics",
      "detail_correct_step1_cr_statistics",
      "detail_correct_step2a_cr_statistics",
      "detail_correct_step2b_cr_statistics",
      "cr_statistics"
    );
    
    setSubtopicToHTML(
      "ESS",
      "ESS_overview",
      "detail_correct_overall_cr_evenly",
      "detail_correct_when_receiving_cr_evenly",
      "detail_correct_with_no_cr_evenly",
      "detail_correct_step1_cr_evenly",
      "detail_correct_step2a_cr_evenly",
      "detail_correct_step2b_cr_evenly",
      "cr_evenly"
    );
    
    setSubtopicToHTML(
      "PRB",
      "PRB_overview",
      "detail_correct_overall_cr_probability",
      "detail_correct_when_receiving_cr_probability",
      "detail_correct_with_no_cr_probability",
      "detail_correct_step1_cr_probability",
      "detail_correct_step2a_cr_probability",
      "detail_correct_step2b_cr_probability",
      "cr_probability"
    );
    
    setSubtopicToHTML(
      "CMB",
      "CMB_overview",
      "detail_correct_overall_cr_combinatorics",
      "detail_correct_when_receiving_cr_combinatorics",
      "detail_correct_with_no_cr_combinatorics",
      "detail_correct_step1_cr_combinatorics",
      "detail_correct_step2a_cr_combinatorics",
      "detail_correct_step2b_cr_combinatorics",
      "cr_combinatorics"
    );
    
    setSubtopicToHTML(
      "FR",
      "FR_overview",
      "detail_correct_overall_cr_fractions",
      "detail_correct_when_receiving_cr_fractions",
      "detail_correct_with_no_cr_fractions",
      "detail_correct_step1_cr_fractions",
      "detail_correct_step2a_cr_fractions",
      "detail_correct_step2b_cr_fractions",
      "cr_fractions"
    );
    
    setSubtopicToHTML(
      "PCT",
      "PCT_overview",
      "detail_correct_overall_cr_percentages",
      "detail_correct_when_receiving_cr_percentages",
      "detail_correct_with_no_cr_percentages",
      "detail_correct_step1_cr_percentages",
      "detail_correct_step2a_cr_percentages",
      "detail_correct_step2b_cr_percentages",
      "cr_percentages"
    );
    
    setSubtopicToHTML(
      "RA",
      "RA_overview",
      "detail_correct_overall_cr_ratios",
      "detail_correct_when_receiving_cr_ratios",
      "detail_correct_with_no_cr_ratios",
      "detail_correct_step1_cr_ratios",
      "detail_correct_step2a_cr_ratios",
      "detail_correct_step2b_cr_ratios",
      "cr_ratios"
    );
    
    setSubtopicToHTML(
      "FPRC",
      "FPRC_overview",
      "detail_correct_overall_cr_fpr",
      "detail_correct_when_receiving_cr_fpr",
      "detail_correct_with_no_cr_fpr",
      "detail_correct_step1_cr_fpr",
      "detail_correct_step2a_cr_fpr",
      "detail_correct_step2b_cr_fpr",
      "cr_fpr"
    );


    window.provenQuant = { overview, details };

    // Single, clean log for your app to consume (no HTML).
    console.log('provenQuant', window.provenQuant);
    </script>
    <?php
}



// Add to functions.php or Code Snippets plugin
add_action('wp_head', 'my_account_free_trial_head_css');
function my_account_free_trial_head_css() {
    $is_account = ( function_exists('is_account_page') && is_account_page() ) || is_page('my-account');
    
    
    
    if ( ! $is_account ) return;

    if ( isset($_GET['type_subs']) && $_GET['type_subs'] === 'free' ) {
        ?>
        <style id="my-account-free-trial-style">
            /* Hide first column immediately */
            #customer_login > div:first-child { display: none !important; }
			
			.woocommerce-form.woocommerce-form-login.login,
            .woocommerce-form.woocommerce-form-register.register {
                width: 60% !important;
            }
            
            @media (max-width: 768px) {
              #customer_login .u-column2 h2 {
                font-size: 1.3rem !important;
                line-height: 2rem !important;
              }
              
               .woocommerce-form.woocommerce-form-login.login,
                .woocommerce-form.woocommerce-form-register.register {
                    width: 100% !important;
                }
            }
        </style>
        <script>
        (function() {
            // Run as early as possible to prevent flicker
            var interval = setInterval(function() {
                var container = document.querySelector("#customer_login > div:nth-child(2)");
				
                if (container) {
					
                    // 1️⃣ Change H2 text
                    var h2 = container.querySelector("h2");
                    if (h2) {
                        h2.textContent = "Get access to Gurutor's Adaptive GMAT Prep Here. No Credit Card Required!";
                    }

                    // 2️⃣ Add paragraph after submit button
                    var submitBtn = container.querySelector("#customer_login > div:nth-child(2) form .woocommerce-form-row.form-row button");
                    if (submitBtn && !submitBtn.dataset.inserted) {
                        var p = document.createElement("p");
						p.style.marginTop = "20px";
                        p.className = "woocommerce-LostPassword lost_password";
                        p.innerHTML = '<a href="https://gurutor.co/my-account/">Already have an account? Click here to log in.</a>';

                        submitBtn.parentNode.insertBefore(p, submitBtn.nextSibling);
                        submitBtn.dataset.inserted = "true"; // prevent duplicate insertion
                    }

                    clearInterval(interval); // stop checking once done
                }
            }, 1);
        })();
        </script>
        <?php
    }
}

function add_free_trial_toUserq(){
	
		// ✅ Redirect if user is not logged in
		if ( !is_user_logged_in() ) {
			$redirect_url = add_query_arg( 'type_subs', 'free', home_url('/my-account/') );
			wp_redirect( $redirect_url );
			exit;
		}

        $user = wp_get_current_user();
        $free_trial_url = home_url('/courses/gurutor-free-trial');
        $free_trial_url_access = home_url('/courses/gurutor-free-trial/?free_trial=access');
        $redirect_url = '';
    
        // ✅ If product_id=7006 (Free Trial)
       
        try {
            $user_id = $user->ID;
            $product_id = 7006;

            // 🔍 Check if user already has this subscription
            $has_subscription = false;
            $subscriptions = wcs_get_users_subscriptions($user_id);
            foreach ($subscriptions as $subscription) {
                if (!in_array($subscription->get_status(), ['active', 'pending', 'on-hold'])) continue;
                foreach ($subscription->get_items() as $item) {
                    $subscribed_product = $item->get_product();
                    if ($subscribed_product && intval($subscribed_product->get_id()) === $product_id) {
                        log_to_browser_console("⚠️ User already has subscription to product_id $product_id");
                        $has_subscription = true;
                        break 2;
                    }
                }
            }

            if ($has_subscription) {
                $redirect_url = $free_trial_url;
            } else {
                // ✅ Create the subscription
                if (!function_exists('wcs_create_subscription')) {
                    log_to_browser_console('❌ WooCommerce Subscriptions plugin not active');
                    return;
                }

                $product = wc_get_product($product_id);
                if (!$product) return;

                $product_type = $product->get_type();
                if (!in_array($product_type, ['subscription', 'variable-subscription'])) return;

                $billing_period   = get_post_meta($product_id, '_subscription_period', true);
                $billing_interval = get_post_meta($product_id, '_subscription_period_interval', true);

                $subscription = wcs_create_subscription([
                    'customer_id'      => $user_id,
                    'billing_period'   => $billing_period,
                    'billing_interval' => $billing_interval,
                    'start_date'       => gmdate('Y-m-d H:i:s'),
                ]);

                if (is_wp_error($subscription)) {
                    log_to_browser_console('❌ Subscription creation failed: ' . $subscription->get_error_message());
                    return;
                }

                $subscription->add_product($product, 1);
                $subscription->set_billing_address([
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->user_email,
                ]);
                $subscription->calculate_totals();
                $subscription->update_status('active');
                $subscription->save();
                
                /** Keep user as ONLY 'subscriber' on free trial creation (no payment yet) */
                if ( ! user_can( $user_id, 'manage_options' ) ) {
                    (new WP_User($user_id))->set_role('subscriber'); // single role
                }

                log_to_browser_console('✅ Subscription created and activated');
                $redirect_url = $free_trial_url_access;
            }
        } catch (Throwable $e) {
            log_to_browser_console('💥 Exception: ' . $e->getMessage());
            return;
        }
        
        
        wp_redirect(home_url($redirect_url));
        exit; // Make sure to stop further processing
}

// Register "Access Code Bundle" post type.
add_action('init', function () {
    register_post_type('access-code-bundle', [
        'labels' => [
            'name'          => 'Access Code Bundles',
            'singular_name' => 'Access Code Bundle',
            'menu_name'     => 'Access Code Bundles',
            'add_new_item'  => 'Add New Bundle',
            'edit_item'     => 'Edit Bundle',
        ],
        'public'       => true,     // not publicly queryable
        'show_ui'      => true,      // show in admin
        'show_in_menu' => true,
        'supports'     => ['title'], // ACF fields for the rest
        'has_archive'  => false,
        'menu_position'=> 25,
    ]);
});

// Helpful log to confirm it's registered.
add_action('admin_init', function () {
    error_log( post_type_exists('access_code_bundle')
        ? '✅ access_code_bundle registered'
        : '❌ access_code_bundle NOT registered'
    );
});


function redirect_after_registration_based_on_url($user_id) {
    // Check if the URL contains 'type_subs=free'
    if (isset($_GET['type_subs']) && $_GET['type_subs'] === 'free') {
        wp_set_current_user($user_id); // Set current user context
        wp_set_auth_cookie($user_id); // Set authentication cookie
        
        add_free_trial_toUserq();
    }
}
// add_action('user_register', 'redirect_after_registration_based_on_url', 999);

/**
 * =============================================================================
 * COMPLETE FREE TRIAL REGISTRATION FIX
 * =============================================================================
 */

/**
 * STEP 1: Force subscriber role
 */
add_filter('woocommerce_new_customer_data', 'force_subscriber_role_registration', 1, 1);
function force_subscriber_role_registration($data) {
    $data['role'] = 'subscriber';
    return $data;
}

/**
 * STEP 2: Disable WP Fusion's broken WooCommerce registration hook
 */
add_action('plugins_loaded', 'disable_wpf_woocommerce_registration', 20);
function disable_wpf_woocommerce_registration() {
    if (class_exists('WPF_WooCommerce')) {
        // Remove WPF's problematic WooCommerce integration hook
        remove_action('woocommerce_created_customer', array(WPF_WooCommerce::get_instance(), 'created_customer'), 10);
    }
}

/**
 * STEP 3: Create HubSpot contact using correct WP Fusion methods
 */
add_action('woocommerce_created_customer', 'manual_wpf_sync_for_registration', 5, 3);
function manual_wpf_sync_for_registration($customer_id, $new_customer_data, $password_generated) {
    $user = get_userdata($customer_id);
    
    if (!$user || !$user->user_email) {
        return;
    }
    
    
    // Try WP Fusion with correct method names
    if (function_exists('wp_fusion')) {
        $wpf = wp_fusion();
        
        if ($wpf && isset($wpf->crm)) {
            
            // Get user meta for sync
            $contact_data = array(
                'user_email' => $user->user_email,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
            );
            
            // Try different method names that WP Fusion uses
            $methods_to_try = array(
                'add_update_contact',  // Most common
                'update_contact',       // Alternative
                'sync_contact',         // Another alternative
            );
            
            $contact_created = false;
            
            foreach ($methods_to_try as $method) {
                if (method_exists($wpf->crm, $method)) {
                    
                    try {
                        
                        // Call the method
                        $result = call_user_func(array($wpf->crm, $method), $user->user_email, $contact_data);
                        
                        if (is_wp_error($result)) {
                            error_log("❌ $method returned error: " . $result->get_error_message());
                        } elseif ($result && !empty($result)) {
                            error_log("✅ Contact created/updated via $method: ID = $result");
                            
                            // Store the contact ID
                            update_user_meta($customer_id, $wpf->crm->slug . '_contact_id', $result);
                            $contact_created = true;
                            
                            // Apply registration tags
                            if (isset($wpf->user) && method_exists($wpf->user, 'apply_tags')) {
                                $wpf_options = get_option('wpf_options', array());
                                $apply_tags = isset($wpf_options['woo_apply_tags_registration']) ? $wpf_options['woo_apply_tags_registration'] : array();
                                
                                if (!empty($apply_tags)) {
                                    $wpf->user->apply_tags($apply_tags, $customer_id);
                                }
                            }
                            
                            break; // Success, exit loop
                        }
                    } catch (Exception $e) {
                        error_log("❌ Exception calling $method: " . $e->getMessage());
                    }
                }
            }
            
            if ($contact_created) {
                return; // Success!
            }
            
            error_log("⚠️ No working methods found, trying direct API approach...");
        }
    }
    
    // Fallback: Direct HubSpot API call with correct key retrieval
    error_log("🚀 Attempting direct HubSpot API call for: " . $user->user_email);
    
    // HubSpot stores keys differently - let's find it
    $hubspot_key = false;
    
    // Method 1: Check WP Fusion options
    $wpf_options = get_option('wpf_options', array());
    
    // Try different possible key names
    $possible_keys = array(
        'hubspot_key',
        'hubspot_token',
        'access_token',
        'hubspot_access_token',
        'crm_token'
    );
    
    foreach ($possible_keys as $key_name) {
        if (!empty($wpf_options[$key_name])) {
            $hubspot_key = $wpf_options[$key_name];
            break;
        }
    }
    
    // Method 2: Check if stored separately
    if (!$hubspot_key) {
        $hubspot_key = get_option('wpf_hubspot_token', '');
        if ($hubspot_key) {
            error_log("✅ Found API key in wpf_hubspot_token");
        }
    }
    
    // Method 3: Try to get from WP Fusion's CRM params
    if (!$hubspot_key && function_exists('wp_fusion')) {
        $wpf = wp_fusion();
        if (isset($wpf->crm->params) && !empty($wpf->crm->params['access_token'])) {
            $hubspot_key = $wpf->crm->params['access_token'];
            error_log("✅ Found API key in CRM params");
        }
    }
    
    if (empty($hubspot_key)) {
        
        // Last resort: Use WP Fusion's internal sync
        if (function_exists('wpf_sync_user')) {
            error_log("🔄 Falling back to wpf_sync_user()");
            wpf_sync_user($customer_id);
        }
        return;
    }
    
    
    // Make direct HubSpot API call
    $api_url = 'https://api.hubapi.com/contacts/v1/contact/';
    
    $contact_data = array(
        'properties' => array(
            array(
                'property' => 'email',
                'value' => $user->user_email
            ),
            array(
                'property' => 'firstname',
                'value' => $user->first_name ?: ''
            ),
            array(
                'property' => 'lastname',
                'value' => $user->last_name ?: ''
            )
        )
    );
    
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $hubspot_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($contact_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        error_log("❌ HubSpot API error: " . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
        
    if ($response_code == 200 || $response_code == 201) {
        $data = json_decode($response_body, true);
        
        if (isset($data['vid'])) {
            $contact_id = $data['vid'];
            update_user_meta($customer_id, 'hubspot_contact_id', $contact_id);
        }
    } elseif ($response_code == 409) {
        $existing_data = json_decode($response_body, true);
        
        if (isset($existing_data['identityProfile']['vid'])) {
            $contact_id = $existing_data['identityProfile']['vid'];
            update_user_meta($customer_id, 'hubspot_contact_id', $contact_id);
        }
    } else {
        error_log("❌ HubSpot API error code: " . $response_code);
    }
}

/**
 * Helper function to apply tags directly to HubSpot
 */
function direct_hubspot_apply_tags($contact_id, $tag_ids, $api_key) {
    if (empty($tag_ids) || !is_array($tag_ids)) {
        return;
    }
    
    error_log("🏷️ Applying tags to contact $contact_id: " . implode(', ', $tag_ids));
    
    foreach ($tag_ids as $tag_id) {
        $api_url = "https://api.hubapi.com/contacts/v1/contact/vid/$contact_id/profile";
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'properties' => array(
                    array(
                        'property' => 'hs_lead_status',
                        'value' => $tag_id
                    )
                )
            )),
            'timeout' => 15
        ));
        
        if (!is_wp_error($response)) {
            error_log("✅ Tag applied: $tag_id");
        }
    }
}

/**
 * STEP 4: Handle free trial registration WITHOUT wp_redirect
 */
add_action('woocommerce_created_customer', 'handle_free_trial_registration_immediate', 10, 3);
function handle_free_trial_registration_immediate($customer_id, $new_customer_data, $password_generated) {
    
    // Only for free trial registrations
    if (!isset($_GET['type_subs']) || $_GET['type_subs'] !== 'free') {
        return;
    }
    
    $user = get_userdata($customer_id);
    if (!$user) {
        return;
    }
    
    error_log("🎯 Free trial registration for: " . $user->user_email);
    
    // Set session flag to create subscription on next page load
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['pending_free_trial_user_id'] = $customer_id;
    $_SESSION['pending_free_trial_email'] = $user->user_email;
}

/**
 * STEP 5: Create subscription on wp_loaded (before headers sent)
 */
add_action('wp_loaded', 'create_pending_free_trial_subscription', 1);
function create_pending_free_trial_subscription() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if we have a pending free trial
    if (empty($_SESSION['pending_free_trial_user_id'])) {
        return;
    }
    
    $user_id = $_SESSION['pending_free_trial_user_id'];
    $user_email = $_SESSION['pending_free_trial_email'];
    
    // Clear session
    unset($_SESSION['pending_free_trial_user_id']);
    unset($_SESSION['pending_free_trial_email']);
    
    error_log("⏳ Creating free trial subscription for user: $user_email");
    
    $product_id = 7006;
    $free_trial_url = home_url('/courses/gurutor-free-trial/?free_trial=access');
    
    try {
        // Check if subscription already exists
        if (function_exists('wcs_get_users_subscriptions')) {
            $subscriptions = wcs_get_users_subscriptions($user_id);
            foreach ($subscriptions as $subscription) {
                if (in_array($subscription->get_status(), ['active', 'pending', 'on-hold'])) {
                    foreach ($subscription->get_items() as $item) {
                        if ((int)$item->get_product_id() === $product_id) {
                            error_log("⚠️ User already has free trial");
                            wp_safe_redirect($free_trial_url);
                            exit;
                        }
                    }
                }
            }
        }
        
        // Create subscription
        if (!function_exists('wcs_create_subscription')) {
            error_log('❌ WooCommerce Subscriptions not active');
            wp_safe_redirect(home_url('/my-account/'));
            exit;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('❌ Product not found: ' . $product_id);
            wp_safe_redirect(home_url('/my-account/'));
            exit;
        }
        
        $user = get_userdata($user_id);
        
        $billing_period = get_post_meta($product_id, '_subscription_period', true);
        $billing_interval = get_post_meta($product_id, '_subscription_period_interval', true);
        
        $subscription = wcs_create_subscription([
            'customer_id' => $user_id,
            'billing_period' => $billing_period,
            'billing_interval' => $billing_interval,
            'start_date' => gmdate('Y-m-d H:i:s'),
        ]);
        
        if (is_wp_error($subscription)) {
            error_log('❌ Subscription creation failed: ' . $subscription->get_error_message());
            wp_safe_redirect(home_url('/my-account/'));
            exit;
        }
        
        $subscription->add_product($product, 1);
        $subscription->set_billing_address([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
        ]);
        $subscription->calculate_totals();
        $subscription->update_status('active');
        $subscription->save();
        
        // Keep as subscriber
        if (!user_can($user_id, 'manage_options')) {
            wp_update_user([
                'ID' => $user_id,
                'role' => 'subscriber'
            ]);
        }
        
        error_log('✅ Free trial created for: ' . $user->user_email);
        
        // Set authentication
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Apply WP Fusion tags for free trial
        if (function_exists('wp_fusion')) {
            // You can add specific tags here for free trial users
            // wp_fusion()->user->apply_tags(array(123), $user_id); // Replace 123 with your tag ID
            
            // Force a sync with HubSpot
            wp_schedule_single_event(time() + 5, 'delayed_wpf_sync', array($user_id));
        }
        
        // Redirect to free trial course
        wp_safe_redirect($free_trial_url);
        exit;
        
    } catch (Throwable $e) {
        error_log('💥 Exception in free trial creation: ' . $e->getMessage());
        wp_safe_redirect(home_url('/my-account/'));
        exit;
    }
}

/**
 * Fix End
 */

// 1. Add Terms & Conditions checkbox to registration form
add_action('woocommerce_register_form', 'add_terms_to_registration_form',20);
function add_terms_to_registration_form() {
    ?>
    <p class="form-row form-row-wide">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
            <input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" 
                   name="terms" type="checkbox" id="terms" />
            <span>
                I have read and accept the 
                <a href="/terms-and-conditions" target="_blank" style="text-decoration: underline;">
                    Terms & Conditions
                </a>
            </span>
        </label>
    </p>
    <?php
}

// 2. Validate checkbox is checked
add_action('woocommerce_register_post', 'validate_terms_checkbox', 10, 3);
function validate_terms_checkbox($username, $email, $validation_errors) {
    if (!isset($_POST['terms'])) {
        $validation_errors->add('terms_error', __('You must accept the Terms & Conditions to register.', 'woocommerce'));
    }
    return $validation_errors;
}

function custom_elementor_layout_css_only() {
    if ( strpos($_SERVER['REQUEST_URI'], '/courses/gurutor-free-trial/lessons') !== false ) {
        ?>
        <style>
            /* Target the first section inside .site-content and its .elementor-container */
            div.site-content section .elementor-container > *:first-child {
                display: none !important;
            }

            div.site-content section .elementor-container > *:nth-child(2) {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'custom_elementor_layout_css_only'); // Use wp_head to apply CSS earlier


// For auto renew subscription free trial. makes user's role subscriber to customer
add_action( 'woocommerce_subscription_renewal_payment_complete', 'change_user_role_on_subscription_renewal', 10, 2 );

function change_user_role_on_subscription_renewal( $subscription, $order ) {
    // Get the user ID from the subscription
    $user_id = $subscription->get_user_id();

    // Get the WP_User object
    $user = get_userdata( $user_id );

    if (in_array('subscriber', $user->roles)) {
        $user->set_role('customer');
        update_user_meta($user->ID, '_redirect_to_course', true);
        echo "<script>console.log('✅ Role changed to customer');</script>";
    }
}


add_action( 'woocommerce_payment_complete', 'my_project_after_payment', 10, 1 );

/**
 * Fires when Woo marks an order as paid (via gateway/webhook).
 * Great place to grant access, change roles, set meta, etc.
 */
function my_project_after_payment( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Prevent double-running (idempotency)
    if ( $order->get_meta( '_my_after_payment_done' ) ) {
        return;
    }

    $user_id = $order->get_user_id();
    if ( $user_id ) {
        $user = get_userdata( $user_id );

        // Example: subscriber -> customer
        if ( $user && in_array( 'subscriber', (array) $user->roles, true ) ) {
            // $user->add_role( 'customer' );
            // $user->remove_role( 'subscriber' );
            
            $user->set_role('customer');
            update_user_meta($user->ID, '_redirect_to_course', true);
        }

        // Example: flag to redirect this user to a course page once
        // update_user_meta( $user_id, '_redirect_to_course', 1 );
    }

    // …your other post-payment work here (enroll to course/LMS, send custom email, etc.)

    $order->add_order_note( 'Custom after-payment tasks ran.' );
    $order->update_meta_data( '_my_after_payment_done', time() );
    $order->save();
}



// For click on "My courses" button. If there is user exists "Free trial" it redirects to free trial lessons.
add_action('template_redirect', 'handle_units_overview_redirect');


function handle_units_overview_redirect() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Only run if ?type=check_course is present
    if ( ! isset($_GET['type']) || $_GET['type'] !== 'check_course' ) {
        return;
    }

    $user = wp_get_current_user();

    $free_trial_product_id = 7006;
    $paid_course_url  = home_url('/courses/gurutors-recommended-gmat-program/');
    $free_trial_url   = home_url('/courses/gurutor-free-trial/');
    $subscribe_url    = home_url('/?product_id=7006');

    $roles = (array) $user->roles;

    // Admins go straight to paid course
    if ( in_array('administrator', $roles, true) ) {
        wp_redirect($paid_course_url);
        exit;
    }

    // Customers go to paid course
    if ( in_array('customer', $roles, true) ) {
        wp_redirect($paid_course_url);
        exit;
    }

    // Subscribers get extra logic
    if ( in_array('subscriber', $roles, true) ) {

        // Check WooCommerce Subscriptions
        if ( function_exists('wcs_get_users_subscriptions') ) {
            $subscriptions = wcs_get_users_subscriptions($user->ID);

            foreach ( $subscriptions as $subscription ) {
                if ( in_array($subscription->get_status(), array('active', 'on-hold'), true) ) {
                    foreach ( $subscription->get_items() as $item ) {
                        if ( (int) $item->get_product_id() === $free_trial_product_id ) {
                            wp_redirect($free_trial_url);
                            exit;
                        }
                    }
                }
            }
        }

        // Check WooCommerce Orders
        $customer_orders = wc_get_orders(array(
            'customer_id' => $user->ID,
            'post_status' => array('wc-completed', 'wc-processing'),
            'limit'       => -1,
        ));

        foreach ( $customer_orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                if ( (int) $item->get_product_id() === $free_trial_product_id ) {
                    wp_redirect($free_trial_url);
                    exit;
                }
            }
        }

        // No free trial found → send to subscription page
        wp_redirect($subscribe_url);
        exit;
    }

    // Fallback: unknown role
    wp_redirect($subscribe_url);
    exit;
}

function log_to_browser_console($message) {
    static $logs = [];
    $logs[] = json_encode("🪵 $message");

    add_action('wp_footer', function () use (&$logs) {
        if (!empty($logs)) {
            echo "<script>\n";
            foreach ($logs as $log) {
                echo "console.log($log);\n";
            }
            echo "</script>";
        }
    }, 1000);
}

function hasFreeTrial(){
    $user = wp_get_current_user();
    
    $user_id = $user->ID;
    $product_id = 7006;

    // 🔍 Check if user already has this subscription
    $has_subscription = false;
    $subscriptions = wcs_get_users_subscriptions($user_id);
    foreach ($subscriptions as $subscription) {
        if (!in_array($subscription->get_status(), ['active', 'pending', 'on-hold'])) continue;
        foreach ($subscription->get_items() as $item) {
            $subscribed_product = $item->get_product();
            if ($subscribed_product && intval($subscribed_product->get_id()) === $product_id) {
                log_to_browser_console("⚠️ User already has subscription to product_id $product_id");
                $has_subscription = true;
                break 2;
            }
        }
    }
    
    return $has_subscription;
}


add_action('template_redirect', 'handle_has_free_trial_add_prod');
function handle_has_free_trial_add_prod() {
    $free_trial_url_has = home_url('/courses/gurutor-free-trial');
    if ( ! is_user_logged_in() ) {
        return;
    }

    if (isset($_GET['product_id']) && intval($_GET['product_id']) === 7006) {
    
        if(is_user_logged_in()){
            $has_course_free = hasFreeTrial();
            if($has_course_free){
                wp_redirect($free_trial_url_has);
                exit;
            }
        }
        
    }
    
}

add_action('template_redirect', 'redirect_free_trial_account_page');

function redirect_free_trial_account_page() {

    // Only for logged-in users
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Target URL
    $free_trial_course_url = home_url('/courses/gurutor-free-trial');

    // Check query parameter
    if ( isset($_GET['type_subs']) && $_GET['type_subs'] === 'free' ) {

       

            // Check if user has free trial
            if ( function_exists('hasFreeTrial') && hasFreeTrial() ) {
                wp_redirect($free_trial_course_url);
                exit;
            }

        
    }
}

function add_subscription() {
    $free_trial_url = home_url('/courses/gurutor-free-trial/?free_trial=access');
    $free_trial_url_has = home_url('/courses/gurutor-free-trial');
    

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }

    // 🔁 Prevent infinite loop: already redirected this session
    if (!empty($_SESSION['already_redirected'])) return;
    
    $redirect_url = '';
  
	 // ✅ If product_id=7006 (Free Trial)
    if (isset($_GET['product_id']) && intval($_GET['product_id']) === 7006) {
        if (!is_user_logged_in()) {
            if (empty($_SESSION['already_redirected_login'])) {
                $_SESSION['already_redirected_login'] = true;
                echo "<script>
                    console.log('🔒 User not logged in — redirecting to login');
                    setTimeout(function() {
                        window.location.replace('" . esc_url(home_url('/my-account/?type_subs=free')) . "');
                    }, 100);
                </script>";
            } else {
                echo "<script>console.log('🛑 Already redirected to login this session');</script>";
            }
            return;
        }
        
    
        
    
        // Debug info
        echo "<script>console.log('🔥 FUNCTION FIRED');</script>";
        echo "<script>console.log('Request URI: " . esc_js($_SERVER['REQUEST_URI']) . "');</script>";
        echo "<script>console.log('Query string: " . esc_js($_SERVER['QUERY_STRING']) . "');</script>";
        echo "<script>console.log('GET params: " . esc_js(json_encode($_GET)) . "');</script>";

        $user = wp_get_current_user();
        
        $user_id = $user->ID;
        $product_id = 7006;
        
        if($user){
            try {
                
                // ✅ Create the subscription
                if (!function_exists('wcs_create_subscription')) {
                    log_to_browser_console('❌ WooCommerce Subscriptions plugin not active');
                    return;
                }
                
                echo "<script>console.log('START create subscription: " . esc_js(json_encode($_GET)) . "');</script>";
        
                $product = wc_get_product($product_id);
                if (!$product) return;
        
                $product_type = $product->get_type();
                if (!in_array($product_type, ['subscription', 'variable-subscription'])) return;
        
                $billing_period   = get_post_meta($product_id, '_subscription_period', true);
                $billing_interval = get_post_meta($product_id, '_subscription_period_interval', true);
        
                $subscription = wcs_create_subscription([
                    'customer_id'      => $user_id,
                    'billing_period'   => $billing_period,
                    'billing_interval' => $billing_interval,
                    'start_date'       => gmdate('Y-m-d H:i:s'),
                ]);
        
                if (is_wp_error($subscription)) {
                    log_to_browser_console('❌ Subscription creation failed: ' . $subscription->get_error_message());
                    return;
                }
        
                $subscription->add_product($product, 1);
                $subscription->set_billing_address([
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->user_email,
                ]);
                $subscription->calculate_totals();
                $subscription->update_status('active');
                $subscription->save();
                
                /** Keep user as ONLY 'subscriber' on free trial creation (no payment yet) */
                if ( ! user_can( $user_id, 'manage_options' ) ) {
                    (new WP_User($user_id))->set_role('subscriber'); // single role
                }
        
                log_to_browser_console('✅ Subscription created and activated');
                $redirect_url = $free_trial_url;
            
                
            } catch (Throwable $e) {
                log_to_browser_console('💥 Exception: ' . $e->getMessage());
                return;
            }
        }
     
       // ✅ If no `product_id`, fallback to order-based redirect
        if (empty($redirect_url) && !isset($_GET['product_id'])) {
            function get_course_redirect_url_from_order($order) {
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if (!$product) continue;
                    $course_ids = get_post_meta($product->get_id(), '_related_course', true);
                    if (!empty($course_ids)) {
                        $first_course_id = is_array($course_ids) ? $course_ids[0] : $course_ids;
                        $course = get_post($first_course_id);
                        if ($course && $course->post_type === 'sfwd-courses') {
                            return home_url('/courses/' . $course->post_name . '/');
                        }
                    }
                }
                return '';
            }
    
            $orders = wc_get_orders([
                'customer_id' => $user->ID,
                'limit'       => 1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'status'      => ['completed', 'processing']
            ]);
    
            if (!empty($orders)) {
                $order = $orders[0];
                $redirect_url = get_course_redirect_url_from_order($order);
            }
        }
    
        // ✅ Final redirect
         echo "<script>console.log('⏳ Redirect target:_2_check " . esc_js($redirect_url) . "');</script>";

        if (!empty($redirect_url)) {
            $current_url = untrailingslashit(home_url($_SERVER['REQUEST_URI']));
            $normalized_redirect = untrailingslashit($redirect_url);
    
            echo "<script>console.log('⏳ Redirect target: " . esc_js($normalized_redirect) . "');</script>";
            echo "<script>console.log('📍 Current URL: " . esc_js($current_url) . "');</script>";
    
            if ($normalized_redirect === $current_url) {
                echo "<script>console.log('❌ Prevented self-redirect to same page');</script>";
                return;
            }
    
            $_SESSION['already_redirected'] = true;
    
            echo "<script>
                setTimeout(() => {
                    window.location.replace('" . esc_url($redirect_url) . "');
                }, 3000);
            </script>";
    
            // echo "<p>✅ Redirecting to your course in 3 seconds...</p>";
        } else {
            echo "<script>console.log('❌ No redirect URL available');</script>";
        }   
    }
}
add_action('wp_footer', 'add_subscription');


// For redirecting after login. 

add_action('wp_login', 'redirect_user_based_on_role_and_product', 20, 2);

function redirect_user_based_on_role_and_product($user_login, $user) {
    
    if (isset($_GET['type_subs']) && $_GET['type_subs'] === 'free') {
        // Redirect the user to the subscription page
        add_free_trial_toUserq();
    }
    
    wp_set_current_user($user->ID);

    $free_trial_product_id = 7006;
    //$paid_course_url = home_url('/units-overview/');
    $paid_course_url = home_url('/courses/gurutors-recommended-gmat-program/');
    $free_trial_url = home_url('/courses/gurutor-free-trial/');

    $roles = (array) $user->roles;

    log_to_console('User Login: ' . $user_login);
    log_to_console('User Roles: ' . print_r($roles, true));

    if (in_array('administrator', $roles)) {
        log_to_console('Redirecting administrator to paid course');
        redirect_with_console_log($paid_course_url);
    }

    if (in_array('customer', $roles)) {
        log_to_console('Redirecting customer to paid course');
        redirect_with_console_log($paid_course_url);
    }

    if (in_array('subscriber', $roles)) {
        log_to_console('Processing subscriber redirect logic');

        if (function_exists('wcs_get_users_subscriptions')) {
            $subscriptions = wcs_get_users_subscriptions($user->ID);
            foreach ($subscriptions as $subscription) {
                if (in_array($subscription->get_status(), array('active', 'on-hold'))) {
                    foreach ($subscription->get_items() as $item) {
                        if ((int)$item->get_product_id() === $free_trial_product_id) {
                            log_to_console('subscriber has free trial subscription');
                            redirect_with_console_log($free_trial_url);
                        }
                    }
                }
            }
        }

        $customer_orders = wc_get_orders(array(
            'customer_id' => $user->ID,
            'post_status' => array('wc-completed', 'wc-processing'),
            'limit' => -1,
        ));

        foreach ($customer_orders as $order) {
            foreach ($order->get_items() as $item) {
                if ((int)$item->get_product_id() === $free_trial_product_id) {
                    log_to_console('customer has purchased free trial product');
                    redirect_with_console_log($free_trial_url);
                }
            }
        }

        log_to_console('Redirecting subscriber to my account (no matching purchase)');
        redirect_with_console_log(home_url('/?product_id=7006'));
    }

    log_to_console('Redirecting unknown role to my account');
    redirect_with_console_log(home_url('/?product_id=7006'));
}

// Helper function to log to browser console
function log_to_console($message) {
    echo "<script>console.log(" . json_encode($message) . ");</script>";
}

// Helper function to redirect after logging
function redirect_with_console_log($url) {
    echo "<script>console.log('Redirecting to: " . esc_url($url) . "'); window.location.href = '" . esc_url($url) . "';</script>";
    exit;
}



// For after make payment manually.

add_action('woocommerce_thankyou', 'redirect_after_purchase_of_specific_product');

function redirect_after_purchase_of_specific_product($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $product_id_to_check = 7006;

    foreach ($order->get_items() as $item) {
        if ((int)$item->get_product_id() === $product_id_to_check) {

            // Only redirect for the current user, on thank you page
            if (!is_admin() && is_user_logged_in()) {
                wp_redirect(home_url('/courses/gurutor-free-trial/'));
                exit;
            }
        }
    }
}


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

// END ENQUEUE PARENT ACTION

function catch_urierror_and_prevent_crash() {
    ?>
    <script>
    window.onerror = function (message, source, lineno, colno, error) {
        if (message && message.includes('URI malformed')) {
            console.warn('decodeURIComponent hatası yakalandı ve bastırıldı');
            return true; // hatayı yuttuk, devam et
        }
        return false; // diğer hatalar normale devam etsin
    };
    </script>
    <?php
}
add_action('wp_footer', 'catch_urierror_and_prevent_crash', 100);



function reading_comprehension_statistics_function() { return get_template_part( 'template-parts/statistics/reading-comprehension' ); }
add_shortcode( 'reading_comprehension_statistics', 'reading_comprehension_statistics_function' );
function critical_reasoning_statistics_function() { return get_template_part( 'template-parts/statistics/critical-reasoning' ); }
add_shortcode( 'critical_reasoning_statistics', 'critical_reasoning_statistics_function' );

function critical_reasoning_statistics_function_demo() { return get_template_part( 'template-parts/statistics/critical-reasoning_demo' ); }
add_shortcode( 'critical_reasoning_statistics_demo', 'critical_reasoning_statistics_function_demo' );

/* WP Footer Function */
add_action('wp_enqueue_scripts', 'enqueue_sticky_toc_script');
function enqueue_sticky_toc_script() {
    if (is_single()) {
        wp_enqueue_script(
            'toc-js',
            get_stylesheet_directory_uri() . '/js/jquery-stickyNavigator.js',
            array('jquery'),
            '11',
            true
        );
    }
}

add_action('wp_footer', 'add_sticky_toc_inline_script', 100);
function add_sticky_toc_inline_script() {
    if (is_single()) {
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $current_url .= "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
        ?>
        <script type="text/javascript">
            jQuery('#toc').stickyNavigator({
                wrapselector: '#wrapper',
                targetselector: "h2,h3"
            });
    
            jQuery(".table_of_content .title").click(function () {
                jQuery(".table_of_content .toc_content").toggleClass('show');
            });
    
            jQuery(document).ready(function () {
                let currentURL = '<?= esc_js($current_url) ?>';
    
                function convertStringToSlug(originalString) {
                    var lowercaseString = originalString.toLowerCase();
                    return lowercaseString.replace(/[.\s–]+/g, '-');
                }
    
                document.querySelectorAll('#toc a').forEach((elem) => {
                    elem.addEventListener("click", e => {
                        const newUrl = e.target.href;
                        window.history.pushState({ path: newUrl }, '', newUrl);
                    });
                });
    
                // var parentElement = document.getElementById('wrapper');
                // var childElements = parentElement.getElementsByTagName("*");
                
                var parentElement = document.getElementById('wrapper');
                var tocA = document.querySelectorAll('#toc a');
                
                if (parentElement) {
                    var childElements = parentElement.getElementsByTagName("*");
    
                    let toc_counter = 0;
                    for (var i = 0; i < childElements.length; i++) {
                        let element = childElements[i];
        
                        if (element.tagName === "H2" || element.tagName === "H3") {
                            let headingText = element.innerText;
        
                            let c = 0;
                            while (!isNaN(headingText[c])) {
                                headingText = headingText.slice(0, c) + headingText.slice(c + 1);
                            }
        
                            while (headingText[0] === ' ' || headingText[0] === '.') {
                                headingText = headingText.slice(1);
                            }
        
                            const slug = convertStringToSlug(headingText);
                            if (tocA[toc_counter]) {
                                tocA[toc_counter].href = currentURL + '#' + slug;
                                element.id = slug;
                                toc_counter++;
                            }
                        }
                    }
        
                    let slug = window.location.href;
                    const lastSlug = slug.slice(slug.lastIndexOf("/") + 1);
        
                    if (lastSlug[0] === '#') {
                        var link = document.createElement("a");
                        link.setAttribute("href", window.location.href);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                }
            });
        </script>
        <?php
    }
}

/* Custom Post Type: Testimonials */
function register_testimonials_cpt() {
    $labels = array(
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'new_item'           => 'New Testimonial',
        'view_item'          => 'View Testimonial',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found',
        'not_found_in_trash' => 'No testimonials found in Trash',
        'menu_name'          => 'Testimonials',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'exclude_from_search'=> true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('testimonials', $args);
}
add_action('init', 'register_testimonials_cpt');


/* Custom Taxonomy: Testimonial Categories */
function register_testimonial_category_taxonomy() {
    $labels = array(
        'name'              => 'Testimonial Categories',
        'singular_name'     => 'Testimonial Category',
        'search_items'      => 'Search Categories',
        'all_items'         => 'All Categories',
        'edit_item'         => 'Edit Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
        'menu_name'         => 'Categories',
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => false,
        'publicly_queryable'=> false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => false, // No URL
    );

    register_taxonomy('testimonial_category', array('testimonials'), $args);
}
add_action('init', 'register_testimonial_category_taxonomy');

/* All Testimonials */
function display_testimonials_shortcode($atts) {
    ob_start();
    ?>
    <style>
        .testimonail_box {
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 7px;
			background-image: linear-gradient(180deg, #fff, #eeeeed);
        }
		.testimonail_box:nth-last-child(1) {
			margin-bottom: 0px;
		}
        .testimonail_box .rating {
            margin-bottom: 15px;
        }
        .testimonail_box .rating i {
            color: #f0ad4e;
            margin: 0 2px;
        }
        .testimonail_box .tt_content {
            position: relative;
            font-style: italic;
            line-height: 1.7;
            margin-bottom: 20px;
            font-size: 18px;
            font-family: "Nunito Sans", sans-serif;
        }
        .testimonail_box img {
            width: 50px;
            height: 50px;
            border-radius: 100%;
            border: 1px solid #ddd;
        }
        .testimonail_box .name {
            font-family: "Nunito Sans", sans-serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-top: 4px;
        }
        .tt_content .full-content {
            display: none;
        }
        .tt_content button {
            padding: 0;
            background: none;
            color: #0073aa;
            font-size: 15px;
            border: none;
            cursor: pointer;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.read-more-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const parent = btn.closest('.tt_content');
                    const excerpt = parent.querySelector('.excerpt-content');
                    const full = parent.querySelector('.full-content');

                    if (full.style.display === 'none') {
                        full.style.display = 'inline';
                        excerpt.style.display = 'none';
                        btn.textContent = 'Read Less';
                    } else {
                        full.style.display = 'none';
                        excerpt.style.display = 'inline';
                        btn.textContent = 'Read More';
                    }
                });
            });
        });
    </script>
    <?php

    $args = array(
        'post_type'      => 'testimonials',
        'posts_per_page' => -1,
    );
    $testimonials = new WP_Query($args);

    if ($testimonials->have_posts()) :
        echo '<div class="testimonials-wrapper">';
        while ($testimonials->have_posts()) : $testimonials->the_post();
            $rating = get_field('rating'); // ACF field for rating
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            $content = get_the_content();
            $clean_content = wp_strip_all_tags($content);
            $word_count = str_word_count($clean_content);

            // Create excerpt
            $words = explode(' ', $clean_content);
            $excerpt = implode(' ', array_slice($words, 0, 200));
            ?>
            <div class="testimonail_box">
                <div class="rating">
                    <?php
                    if ($rating) {
                        for ($i = 1; $i <= 5; $i++) {
                            echo ($i <= $rating) ? '<i class="fa fa-star"></i>' : '<i class="fa fa-star-o"></i>';
                        }
                    }
                    ?>
                </div>

                <?php
                // NEW ACF FIELD OUTPUT (ALL CAPS + BOLD)
                $student_result = get_field('student_result');
                if ($student_result) {
                    echo '<div class="student-result" style="font-weight:bold; margin-bottom:10px;">' . strtoupper($student_result) . '</div>';
                }
                ?>
                
                <div class="tt_content">
                    <?php if ($word_count > 250): ?>
                        <span class="excerpt-content"><?php echo esc_html($excerpt); ?>...</span>
                        <span class="full-content"><?php echo esc_html($clean_content); ?></span>
                        <button type="button" class="read-more-toggle">Read More</button>
                    <?php else: ?>
                        <span class="excerpt-content"><?php echo esc_html($clean_content); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                <?php endif; ?>
                <div class="name"><?php the_title(); ?></div>
            </div>
            <?php
        endwhile;
        echo '</div>';
        wp_reset_postdata();
    endif;

    return ob_get_clean();
}
add_shortcode('testimonials-shortcode', 'display_testimonials_shortcode');

function display_testimonials_shortcode_allpage($atts) {
    ob_start();
    // Enqueue Swiper
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js', array(), '9.0', true);
    $atts = shortcode_atts(array(
        'category' => '',
    ), $atts);

    $tax_query = array();
    if (!empty($atts['category'])) {
        $tax_query[] = array(
            'taxonomy' => 'testimonial_category',
            'field'    => 'name',
            'terms'    => $atts['category'],
        );
    }

    $args = array(
        'post_type'      => 'testimonials',
        'posts_per_page' => -1,
        'tax_query'      => $tax_query,
    );

    $testimonials = new WP_Query($args);
    ?>

    <div class="swiper testimonial-swiper">
        <div class="swiper-wrapper">
            <?php if ($testimonials->have_posts()) :
                while ($testimonials->have_posts()) : $testimonials->the_post();
                    $rating = get_field('rating');
                    $student_result = get_field('student_result'); // NEW FIELD
                    $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                    $content = get_the_content();
                    $clean_content = wp_strip_all_tags($content);
                    $word_count = str_word_count($clean_content);
                    $words = explode(' ', $clean_content);
                    $excerpt = implode(' ', array_slice($words, 0, 100));

                    $schema_content = wp_strip_all_tags(get_the_content());
                    $schema_author = get_the_title();
                    ?>
                    <div class="swiper-slide">
                        <div class="testimonail_box">
                            <div class="rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $rating) ? '<i class="fa fa-star"></i>' : '<i class="fa fa-star-o"></i>';
                                }
                                ?>
                            </div>
                            
                            <!-- STUDENT RESULT (NEW) -->

                            <?php if ($student_result): ?>

                                <div class="student-result" style="font-weight:bold; margin-bottom:10px;">

                                    <?php echo strtoupper($student_result); ?>

                                </div>

                            <?php endif; ?>

                            <div class="tt_content">
                                <?php if ($word_count > 100): ?>
                                    <span class="excerpt-content"><?php echo esc_html($excerpt); ?>...</span>
                                    <span class="full-content"><?php echo esc_html($clean_content); ?></span>
                                    <button type="button" class="read-more-toggle">Read More</button>
                                <?php else: ?>
                                    <span class="excerpt-content"><?php echo esc_html($clean_content); ?></span>
                                <?php endif; ?>
                            </div>
                            
                              <!-- NAME BELOW RATING -->

                            <div class="name"><?php the_title(); ?></div>
                        </div>
                    </div>
                <?php endwhile;
                wp_reset_postdata();
            endif; ?>
        </div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>


    <?php
    return ob_get_clean();
}
add_shortcode('All-testimonials', 'display_testimonials_shortcode_allpage');

function assign_gmat_codes_on_registration($user_id) {
    $user = get_userdata($user_id);

    if (!in_array('customer', $user->roles)) {
        error_log("❌ Not a customer. Skipping assignment");
        return;
    }

    error_log("✅ assign_gmat_codes_on_registration called for user ID: $user_id");
    error_log("✅ User is a customer: {$user->user_email}");

    // Get 1 unassigned bundle
    $args = [
        'post_type'      => 'access-code-bundle',
        'posts_per_page' => 1,
    	'post_status' => ['publish', 'private'],
        'meta_query'     => [
            [
                'key'     => 'assigned',
                'value'   => '0',
                'compare' => '=',
            ],
        ],
    ];
    $bundles = get_posts($args);

    if (empty($bundles)) {
        error_log("❌ No unassigned access code bundles found");
        return;
    }

    $bundle = $bundles[0];
    $bundle_id = $bundle->ID;

    // Retrieve codes
    $code_1 = get_field('code_1', $bundle_id);
    $code_2 = get_field('code_2', $bundle_id);
    $code_3 = get_field('code_3', $bundle_id);
    $code_4 = get_field('code_4', $bundle_id);

    // Update user
    update_field('code_1', $code_1, 'user_' . $user_id);
    update_field('code_2', $code_2, 'user_' . $user_id);
    update_field('code_3', $code_3, 'user_' . $user_id);
    update_field('code_4', $code_4, 'user_' . $user_id);

    // Update bundle
    update_field('assigned', 1, $bundle_id);
    update_field('assigned_to_email', $user->user_email, $bundle_id);

    error_log("✅ Bundle marked assigned and user updated with codes");
}
add_action('user_register', 'assign_gmat_codes_on_registration');

add_action( 'wpf_webhook_create_gmat_user', function( $data ) {

    // Extract data from webhook payload
    $email      = sanitize_email( $data['user_email'] ?? '' );
    $first_name = sanitize_text_field( $data['first_name'] ?? '' );
    $last_name  = sanitize_text_field( $data['last_name'] ?? '' );

    // Check if email is valid
    if ( empty( $email ) || ! is_email( $email ) ) {
        error_log( 'Webhook error: Invalid or missing email.' );
        return;
    }

    // Check if user already exists
    if ( email_exists( $email ) ) {
        error_log( 'Webhook notice: User already exists for email ' . $email );
        return;
    }

    // Create user
    $random_password = wp_generate_password( 12, false );
    $user_id = wp_create_user( $email, $random_password, $email );

    if ( is_wp_error( $user_id ) ) {
        error_log( 'Webhook error: User creation failed for email ' . $email . '. Error: ' . $user_id->get_error_message() );
        return;
    }

    // Set first and last name
    wp_update_user([
        'ID'           => $user_id,
        'first_name'   => $first_name,
        'last_name'    => $last_name
    ]);

    // Optional: log success
    error_log( 'Webhook success: User created for email ' . $email );
} );

add_action('wp_footer', function () {
    if (is_page('my-account') && isset($_GET['user_login'])) {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const emailField = document.querySelector('form.woocommerce-ResetPassword input#user_login');
                if (emailField) {
                    emailField.value = decodeURIComponent("<?php echo esc_js($_GET['user_login']); ?>");
                }
            });
        </script>
        <?php
    }
});

add_action('woocommerce_checkout_create_order', 'add_trial_metadata_if_trial_product', 20, 2);

function add_trial_metadata_if_trial_product($order, $data) {
    $is_trial = false;
    $trial_product_id = 7006; // 🔁 Replace this with the actual Free Trial product I

    foreach (WC()->cart->get_cart() as $cart_item) {
        if ($cart_item['product_id'] == $trial_product_id) {
            $is_trial = true;
            break;
        }
    }

    if ($is_trial) {
        $order->update_meta_data('trial', 'true');
    }
}

// Start output buffering before comment_form
add_action('comment_form_before', 'start_comment_form_buffer');
function start_comment_form_buffer() {
    ob_start();
}

// Replace <h3> with <div> after comment_form output
add_action('comment_form_after', 'replace_comment_form_title_tag');
function replace_comment_form_title_tag() {
    $html = ob_get_clean();
    $html = str_replace('<h3 id="reply-title" class="comment-reply-title">', '<div id="reply-title" class="comment-reply-title">', $html);
    $html = str_replace('</h3>', '</div>', $html);
    echo $html;
}

/* === FAQ Schema === */
function faq_schema() {
	ob_start();
	?>

	<div itemscope itemtype="https://schema.org/FAQPage" class="mb_25">
		<?php
		if( have_rows('schema') ):
		$i = 1; 
		$j = 1;
		while ( have_rows('schema') ) : the_row();
		if( get_row_layout() == 'faq' ): 
		$faq_heading = get_sub_field('faq_heading');
		$faq_answer = get_sub_field('faq_answer');
		?>
		<div class="faq_box" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
			<h3 itemprop="name" class="faq__title" for="rd<?php echo $j++; ?>"><?php echo $faq_heading; ?> <i class="ti-angle-down"></i></h3>
			<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="faq_content">
				<div itemprop="text"><?php echo $faq_answer; ?></div>
			</div>
		</div>
		<?php 
		endif;
		// End loop.
		endwhile;
		// No value.
		else :
		// Do something...
		endif;
		?>
	</div>
<?php 
	wp_reset_postdata();
	$myvariable = ob_get_clean();
	return $myvariable;
}
add_shortcode('faq-schema','faq_schema');

// Disable WordPress default sitemap
add_filter( 'wp_sitemaps_enabled', '__return_false' );

/**
 * Send 5-day trial email exactly when LearnDash enrolls the user into the trial course.
 * - Listens to LearnDash course-access update action(s)
 * - Fallback: on WooCommerce user creation, send immediately if user already has access
 *
 * IMPORTANT: set $trial_course_id to your course ID.
 */

// === CONFIG ===
add_action( 'init', function() {
    // nothing to schedule here; just a placeholder so plugin loads early
}, 1 );

$MY_TRIAL_COURSE_ID = FREE_TRIAL_COURSE_ID; // <- change this to your trial course ID
$MY_TRIAL_DAYS      = FREE_TRIAL_DAYS;

/**
 * Send trial email using WooCommerce email template (header/footer/styles).
 * Replaces previous my_send_trial_email_to_user function.
 *
 * Call: my_send_trial_email_to_user( $user_id, $trial_course_id, $trial_days );
 */
function my_send_trial_email_to_user( $user_id, $trial_course_id = 0, $trial_days = 5 ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return false;
    }

    $user_email = $user->user_email;
    if ( ! is_email( $user_email ) ) {
        return false;
    }

    // Avoid duplicate sending
    if ( get_user_meta( $user_id, '_my_trial_email_sent', true ) ) {
        return false;
    }

    // Prepare data
    $first_name = get_user_meta( $user_id, 'first_name', true );
    if ( empty( $first_name ) ) {
        $first_name = $user->display_name ?: $user->user_login;
    }

    $site_name    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $course_title = $trial_course_id ? get_post_field( 'post_title', $trial_course_id ) : '';
    if ( empty( $course_title ) ) {
        $course_title = 'your free trial course';
    }

    $expiry_timestamp      = current_time( 'timestamp' ) + ( intval( $trial_days ) * DAY_IN_SECONDS );
    $expiry_date_formatted = date_i18n( 'F j, Y', $expiry_timestamp );

    // Build course and account URLs
    $course_link  = $trial_course_id ? get_permalink( $trial_course_id ) : '';
    $account_link = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : site_url( '/my-account/' );

    // If we have a course URL, send users to My Account with a redirect param so after login they'll be taken to the course.
    // Use both 'redirect' (WooCommerce login handling) and 'redirect_to' (WP login handling) to maximize compatibility.
    if ( $course_link ) {
        $action_link = add_query_arg(
            array(
                'redirect'    => rawurlencode( $course_link ),
                'redirect_to' => rawurlencode( $course_link ),
            ),
            $account_link
        );
    } else {
        // No course permalink available — just point to My Account
        $action_link = $account_link;
    }

    // CTA label — clearer wording
    $cta_label = 'My Account';

    $message_body = '
    <p style="margin:0 0 12px;">Hi ' . esc_html( $first_name ) . ',</p>

    <p style="margin:0 0 12px;">
        Welcome — your <strong>' . intval( $trial_days ) . '-day free trial</strong> for <strong>' . esc_html( $course_title ) . '</strong> has started.
    </p>

    <p style="margin:0 0 12px;">
        Your trial will expire on <strong>' . esc_html( $expiry_date_formatted ) . '</strong>.
    </p>

    <p style="margin:18px 0;">
        <a href="' . esc_url( $action_link ) . '" style="
            display:inline-block;
            padding:12px 20px;
            text-decoration:none;
            border-radius:6px;
            border:1px solid #e1e1e1;
            font-weight:600;
            background-color:#4F80FF;
            color:#ffffff;
        ">' . esc_html( $cta_label ) . '</a>
    </p>

    <p style="margin:0 0 12px;">
        If you didn’t sign up for this, please contact us immediately.
    </p>

    <p style="margin:12px 0 0;">— ' . esc_html( $site_name ) . ' Team</p>
    ';



    // Email heading/subject
    $heading = sprintf( '%d-day free trial started: %s', intval( $trial_days ), $course_title );
    $subject = $heading;

    // If WooCommerce mailer is available, use it so the email uses the WooCommerce templates.
    if ( function_exists( 'WC' ) && method_exists( WC(), 'mailer' ) ) {
        $mailer = WC()->mailer();

        // Wrap message in WooCommerce email template header/footer/styles
        $wrapped = $mailer->wrap_message( $heading, $message_body );

        // Let WooCommerce handle headers and sending
        try {
            $mailer->send( $user_email, $subject, $wrapped );
            update_user_meta( $user_id, '_my_trial_email_sent', time() );
            return true;
        } catch ( Exception $e ) {
            // If something goes wrong with WC mailer, fall back to wp_mail
            error_log( 'WC mailer failed to send trial email for user ' . $user_id . ': ' . $e->getMessage() );
        }
    }

    // Fallback: simple wp_mail (still HTML)
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $fallback_message = '<h2>' . esc_html( $heading ) . '</h2>' . $message_body;
    $sent = wp_mail( $user_email, $subject, $fallback_message, $headers );
    if ( $sent ) {
        update_user_meta( $user_id, '_my_trial_email_sent', time() );
        return true;
    }

    return false;
}


/**
 * LearnDash hook callback:
 * - learndash_update_course_access is fired after a user's course list is updated (LearnDash-dev docs).
 *   Signature varies across versions; we accept multiple args and detect $user_id/$course_id.
 *   Docs reference: learndash_update_course_access action. :contentReference[oaicite:1]{index=1}
 */
function my_learndash_course_access_listener() {
    // grab all passed args to be robust across LD versions
    $args = func_get_args();

    // Typical signature: ( $user_id, $course_id, $course_access_list = null, $remove = false )
    $user_id   = isset( $args[0] ) ? intval( $args[0] ) : 0;
    $course_id = isset( $args[1] ) ? intval( $args[1] ) : 0;
    $remove    = isset( $args[3] ) ? (bool) $args[3] : false;

    // ensure we have the intended course and this is an "add" (not a remove)
    global $MY_TRIAL_COURSE_ID, $MY_TRIAL_DAYS;
    if ( ! $user_id || ! $course_id ) {
        return;
    }

    if ( $course_id !== intval( $MY_TRIAL_COURSE_ID ) ) {
        return;
    }

    if ( $remove ) {
        // access removed — do nothing
        return;
    }

    // Send email if not sent yet
    if ( ! get_user_meta( $user_id, '_my_trial_email_sent', true ) ) {
        my_send_trial_email_to_user( $user_id, $course_id, $MY_TRIAL_DAYS );
    }
}
// Attach to both common LearnDash actions (some installs expose both)
add_action( 'learndash_update_course_access', 'my_learndash_course_access_listener', 10, 4 );
add_action( 'ld_update_course_access',       'my_learndash_course_access_listener', 10, 4 ); // defensive fallback

/**
 * Fallback: when a WooCommerce user is created, send email immediately if user already has access.
 * (This covers flows where enrollment happened before or at registration time.)
 */
function my_wc_created_customer_trial_check( $customer_id, $new_customer_data = array(), $password_generated = false ) {
    global $MY_TRIAL_COURSE_ID, $MY_TRIAL_DAYS;

    if ( ! $customer_id || get_user_meta( $customer_id, '_my_trial_email_sent', true ) ) {
        return;
    }

    // If LearnDash is available and user already has access, send immediately.
    if ( function_exists( 'sfwd_lms_has_access' ) && intval( $MY_TRIAL_COURSE_ID ) ) {
        if ( sfwd_lms_has_access( $customer_id, intval( $MY_TRIAL_COURSE_ID ) ) ) {
            my_send_trial_email_to_user( $customer_id, intval( $MY_TRIAL_COURSE_ID ), $MY_TRIAL_DAYS );
        }
    }
}
add_action( 'woocommerce_created_customer', 'my_wc_created_customer_trial_check', 20, 3 );



/**send email after 5 day free trial expires */

/**
 * Send expiration email using WooCommerce template.
 */
function my_send_trial_expired_email_to_user( $user_id, $course_id = 0, $trial_days = 5 ) {
    if ( ! $user_id || ! $course_id ) {
        return false;
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return false;
    }

    $user_email = $user->user_email;
    if ( ! is_email( $user_email ) ) {
        return false;
    }

    // Avoid duplicate sends
    if ( get_user_meta( $user_id, '_my_trial_expired_email_sent', true ) ) {
        return false;
    }

    // Prepare values
    $first_name = get_user_meta( $user_id, 'first_name', true ) ?: ( $user->display_name ?: $user->user_login );
    $site_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $course_title = get_post_field( 'post_title', $course_id ) ?: 'your free trial course';

    $subject = sprintf( 'Your %d-day free trial for "%s" has ended', intval( $trial_days ), $course_title );

    // Build Packages URL dynamically: try to find a page with slug 'packages', fallback to home_url('/packages/')
    $packages_page = get_page_by_path( 'packages' );
    if ( $packages_page && isset( $packages_page->ID ) ) {
        $packages_url = get_permalink( $packages_page->ID );
    } else {
        $packages_url = home_url( '/packages/' );
    }

    // Message body (core content). WooCommerce will wrap it.
    $message_body = '
      <p style="margin:0 0 12px;">Hi ' . esc_html( $first_name ) . ',</p>

      <p style="margin:0 0 12px;">
        Your <strong>' . intval( $trial_days ) . '-day free trial</strong> for <strong>' . esc_html( $course_title ) . '</strong> has now ended.
      </p>

      <p style="margin:0 0 12px;">
        To continue learning without interruption, you can upgrade to one of our packages.
      </p>

      <p style="margin:18px 0;">
        <a href="' . esc_url( $packages_url ) . '" style="
            display:inline-block;
            padding:12px 20px;
            text-decoration:none;
            border-radius:6px;
            border:1px solid #e1e1e1;
            font-weight:600;
        ">View packages & upgrade</a>
      </p>

      <p style="margin:0 0 12px;">
        If you think this is an error or need help, please contact us and we’ll assist.
      </p>

      <p style="margin:12px 0 0;">— ' . esc_html( $site_name ) . ' Team</p>
    ';

    // If WooCommerce mailer exists, use it (keeps consistent look)
    if ( function_exists( 'WC' ) && method_exists( WC(), 'mailer' ) ) {
        $mailer = WC()->mailer();
        $wrapped = $mailer->wrap_message( $subject, $message_body );
        try {
            $mailer->send( $user_email, $subject, $wrapped );
            update_user_meta( $user_id, '_my_trial_expired_email_sent', time() );
            return true;
        } catch ( Exception $e ) {
            error_log( 'Failed to send trial-expired email via WC mailer for user ' . $user_id . ': ' . $e->getMessage() );
            // fall through to wp_mail fallback
        }
    }

    // Fallback to wp_mail if WC isn't present or failed
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $fallback_message = '<h2>' . esc_html( $subject ) . '</h2>' . $message_body;
    $sent = wp_mail( $user_email, $subject, $fallback_message, $headers );
    if ( $sent ) {
        update_user_meta( $user_id, '_my_trial_expired_email_sent', time() );
        return true;
    }

    return false;
}


/**
 * LearnDash listener that watches for access removals.
 * It is defensive about arguments and triggers expiration email only when:
 *  - course id matches the trial course
 *  - the call is a "remove" OR the user no longer has access
 *  - AND the user previously received the trial-start email (_my_trial_email_sent meta)
 */
function my_learndash_course_access_expire_listener() {
    $args = func_get_args();

    // Typical signature: ( $user_id, $course_id, $course_access_list = null, $remove = false )
    $user_id   = isset( $args[0] ) ? intval( $args[0] ) : 0;
    $course_id = isset( $args[1] ) ? intval( $args[1] ) : 0;
    $remove    = isset( $args[3] ) ? (bool) $args[3] : null;

    global $MY_TRIAL_COURSE_ID, $MY_TRIAL_DAYS;

    if ( ! $user_id || ! $course_id ) {
        return;
    }

    if ( $course_id !== intval( $MY_TRIAL_COURSE_ID ) ) {
        return;
    }

    // Only proceed if the user previously got the trial-start email (prevents sending for unrelated manual removals).
    if ( ! get_user_meta( $user_id, '_my_trial_email_sent', true ) ) {
        return;
    }

    // If remove flag present and true => treat as removal/expiry
    if ( $remove === true ) {
        // Only send if we haven't already sent expired email
        if ( ! get_user_meta( $user_id, '_my_trial_expired_email_sent', true ) ) {
            my_send_trial_expired_email_to_user( $user_id, $course_id, $MY_TRIAL_DAYS );
        }
        return;
    }

    // If remove flag explicitly false => user was added/re-enrolled: clear expired flag so future expirations can notify again.
    if ( $remove === false ) {
        delete_user_meta( $user_id, '_my_trial_expired_email_sent' );
        return;
    }

    // If $remove is null (hook variant didn't pass it), we do a fallback check:
    // If user currently DOES NOT have access and we haven't sent expired email, consider it removal.
    if ( function_exists( 'sfwd_lms_has_access' ) ) {
        $has_access_now = sfwd_lms_has_access( $user_id, $course_id );
        if ( ! $has_access_now && ! get_user_meta( $user_id, '_my_trial_expired_email_sent', true ) ) {
            my_send_trial_expired_email_to_user( $user_id, $course_id, $MY_TRIAL_DAYS );
        }
    }
}

// Attach the listener to common LearnDash hooks (defensive)
add_action( 'learndash_update_course_access', 'my_learndash_course_access_expire_listener', 10, 4 );
add_action( 'ld_update_course_access',       'my_learndash_course_access_expire_listener', 10, 4 );




/**
 * 1) Scheduler: add 5-minute cron schedule and schedule a recurring event.
 * 2) Cron callback: check for expired trial accesses, call ld_update_course_access(..., true)
 *    so LearnDash removes access (that triggers your existing listener to send the expiry email).
 * 3) AJAX endpoint: when the frontend countdown hits zero, AJAX calls this to force-check expiry
 *    and remove access if still present, then returns JSON => JS reloads the page.
 *
 * Paste this below your existing trial-start / expired-email functions.
 */

/* -----------------------
 * CONFIG - change only if needed
 * ---------------------- */
add_action( 'init', function() {
    // nothing here — file loaded early
}, 1 );

global $MY_TRIAL_COURSE_ID;
if ( ! isset( $MY_TRIAL_COURSE_ID ) ) {
    // Keep same course ID you used earlier (fallback to 7472 if not set)
    $MY_TRIAL_COURSE_ID = 7472;
}
/* ----------------------- */


/**
 * 1) Add custom cron schedule (every 5 minutes).
 */
add_filter( 'cron_schedules', 'my_add_five_min_cron_schedule' );
function my_add_five_min_cron_schedule( $schedules ) {
    if ( ! isset( $schedules['every_five_minutes'] ) ) {
        $schedules['every_five_minutes'] = array(
            'interval' => 300, // 300 seconds = 5 minutes
            'display'  => __( 'Every 5 Minutes' ),
        );
    }
    return $schedules;
}

/**
 * 2) Schedule recurring event (only once).
 */
add_action( 'wp', 'my_schedule_trial_expiry_cron' );
function my_schedule_trial_expiry_cron() {
    if ( ! wp_next_scheduled( 'my_check_trial_expirations' ) ) {
        wp_schedule_event( time(), 'every_five_minutes', 'my_check_trial_expirations' );
    }
}

/**
 * 3) Cron callback: finds users who were on trial and not yet marked expired,
 *    checks expiry via ld_course_access_expires_on(), and removes access for
 *    those that passed. The LearnDash removal will trigger your existing listener
 *    which sends the WooCommerce-styled expiry email.
 */
add_action( 'my_check_trial_expirations', 'my_cron_check_trial_expirations' );
function my_cron_check_trial_expirations() {
    global $MY_TRIAL_COURSE_ID;

    if ( empty( $MY_TRIAL_COURSE_ID ) ) {
        return;
    }

    // Only proceed if LearnDash is available
    if ( ! function_exists( 'ld_course_access_expires_on' ) ) {
        return;
    }

    // Query users who were sent the trial-start email AND who haven't yet been sent the expired email.
    // This avoids scanning all users.
    $args = array(
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key'     => '_my_trial_email_sent',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => '_my_trial_expired_email_sent',
                'compare' => 'NOT EXISTS',
            ),
        ),
        'fields' => 'ID',
        'number' => 200, // batch size - tune as needed
    );

    $users = get_users( $args );
    if ( empty( $users ) ) {
        return;
    }

    $now_ts = current_time( 'timestamp' );

    foreach ( $users as $user_id ) {
        $expiry_ts = intval( ld_course_access_expires_on( $MY_TRIAL_COURSE_ID, $user_id ) );

        // If expiry timestamp exists and has passed, remove access. LearnDash listener will handle emailing.
        if ( $expiry_ts && $expiry_ts <= $now_ts ) {
            // Call LearnDash function to remove access. Signature: ld_update_course_access( $user_id, $course_id, $remove = false );
            try {
                // Removing access should trigger learndash_update_course_access / ld_update_course_access actions
                // and your listener will send the expired email (and mark _my_trial_expired_email_sent).
                ld_update_course_access( $user_id, $MY_TRIAL_COURSE_ID, true );
            } catch ( Exception $e ) {
                // log and continue - avoid fatal errors
                error_log( 'Error removing LearnDash course access for user ' . intval( $user_id ) . ': ' . $e->getMessage() );
            }
        }
    }
}

/**
 * 4) AJAX endpoint that the countdown JS calls when the timer hits 0.
 *    - Checks expiry server-side, removes access immediately if expired, and returns JSON.
 *
 *    Frontend JS (below in shortcode) calls admin-ajax.php?action=my_check_user_course_status&course_id=###
 */
add_action( 'wp_ajax_my_check_user_course_status', 'my_check_user_course_status_ajax' );
function my_check_user_course_status_ajax() {
    // Only for logged-in users
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ), 403 );
    }

    $user_id = get_current_user_id();
    $course_id = isset( $_REQUEST['course_id'] ) ? intval( $_REQUEST['course_id'] ) : 0;
    if ( ! $course_id ) {
        wp_send_json_error( array( 'message' => 'Missing course_id' ), 400 );
    }

    // Ensure LearnDash exists
    if ( ! function_exists( 'ld_course_access_expires_on' ) ) {
        wp_send_json_error( array( 'message' => 'LearnDash not available' ), 500 );
    }

    $expiry_ts = intval( ld_course_access_expires_on( $course_id, $user_id ) );
    $now_ts = current_time( 'timestamp' );

    // If no expiry_ts but user previously had access and now does not -> treat as expired
    $access_from = function_exists( 'ld_course_access_from' ) ? ld_course_access_from( $course_id, $user_id ) : false;
    $user_has_access = function_exists( 'sfwd_lms_has_access' ) ? sfwd_lms_has_access( $course_id, $user_id ) : ( ( $expiry_ts > $now_ts ) || ( ! empty( $access_from ) && $expiry_ts === 0 ) );

    if ( $expiry_ts && $expiry_ts <= $now_ts ) {
        // expired -> remove access (if still present) and report expired
        if ( $user_has_access ) {
            // remove access; your LD listener will send email (if not already)
            ld_update_course_access( $user_id, $course_id, true );
        }
        wp_send_json_success( array( 'expired' => true ) );
    }

    // edge: expiry_ts empty but user had earlier access_from and no current access -> treat expired
    if ( ! $expiry_ts && ! $user_has_access && ! empty( $access_from ) ) {
        // ensure expired meta is set via your listener; attempt to remove access again for safety
        ld_update_course_access( $user_id, $course_id, true );
        wp_send_json_success( array( 'expired' => true ) );
    }

    // Not expired yet
    wp_send_json_success( array( 'expired' => false ) );
}

/**
 * OPTIONAL: Unschedule our cron on plugin/theme deactivation.
 * If you paste this into a plugin, add the equivalent unregister on plugin deactivation.
 * (If functions.php usage is preferred, you can omit deactivation cleanup.)
 */
function my_unschedule_trial_expiry_cron() {
    $timestamp = wp_next_scheduled( 'my_check_trial_expirations' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'my_check_trial_expirations' );
    }
}
// Note: If you keep code in functions.php, don't call unschedule on theme switch automatically.
// If you later convert this to a plugin, call my_unschedule_trial_expiry_cron() in plugin deactivation hook.

/**
 * Improved LearnDash countdown shortcode with updated visual design.
 *
 * Usage: [ld_course_countdown] on a course page (or pass course_id).
 * Shows live countdown, and when expired shows "Free Trial Time Expired" + Upgrade button.
 * Reloads the page via AJAX when clock hits zero to reflect removed access immediately.
 */

add_shortcode( 'ld_course_countdown', 'ld_course_countdown_shortcode' );

function ld_course_countdown_shortcode( $atts = array() ) {
    // Shortcode attributes
    $atts = shortcode_atts( array(
        'course_id'      => 0,
        'label'          => 'Time remaining',
        'expired_text'   => 'Your access to this course has expired.',
        'hide_if_no_exp' => false,
    ), $atts, 'ld_course_countdown' );

    // Only for logged-in users
    if ( ! is_user_logged_in() ) {
        return '';
    }

    // Resolve course id
    $course_id = intval( $atts['course_id'] );
    if ( ! $course_id ) {
        $course_id = get_the_ID();
    }
    if ( ! $course_id ) {
        return '';
    }

    $user_id = get_current_user_id();

    // Ensure LearnDash helper functions exist
    if ( ! function_exists( 'ld_course_access_expires_on' ) || ! function_exists( 'ld_course_access_from' ) ) {
        return '';
    }

    // Get timestamps
    $expiry_ts   = intval( ld_course_access_expires_on( $course_id, $user_id ) );
    $access_from = ld_course_access_from( $course_id, $user_id );

    // Helper: detect whether user currently has access
    $user_has_access = false;
    if ( function_exists( 'sfwd_lms_has_access' ) ) {
        $user_has_access = (bool) sfwd_lms_has_access( $course_id, $user_id );
    } else {
        $user_has_access = ( $expiry_ts > current_time( 'timestamp' ) ) || ( ! empty( $access_from ) && $expiry_ts === 0 );
    }

    // If no expiry timestamp at all
    if ( ! $expiry_ts ) {
        if ( $atts['hide_if_no_exp'] ) {
            return '';
        }

        // CASE B: user had enrollment but doesn't currently have access
        if ( ! $user_has_access ) {
            $configured_days = false;

            if ( function_exists( 'learndash_get_course_meta_setting' ) ) {
                $all_settings = learndash_get_course_meta_setting( $course_id );
                if ( ! empty( $all_settings ) && is_array( $all_settings ) ) {
                    $possible_keys = array(
                        'access_expiration', 'course_access_expiration', 'course_access_expire',
                        'course_expiration', 'access_days', 'access_period', 'expires', 'expire_days'
                    );
                    foreach ( $possible_keys as $k ) {
                        if ( isset( $all_settings[ $k ] ) && is_numeric( $all_settings[ $k ] ) ) {
                            $configured_days = intval( $all_settings[ $k ] );
                            break;
                        }
                    }

                    if ( ! $configured_days ) {
                        foreach ( $all_settings as $k => $v ) {
                            if ( is_numeric( $v ) && intval( $v ) > 0 && intval( $v ) < 3650 ) {
                                $configured_days = intval( $v );
                                break;
                            }
                        }
                    }
                }
            }

            if ( ! $configured_days && function_exists( 'learndash_get_setting' ) ) {
                $maybe = learndash_get_setting( $course_id, 'course_access_period' );
                if ( is_numeric( $maybe ) ) {
                    $configured_days = intval( $maybe );
                }
            }

            if ( ! $configured_days && $access_from && $expiry_ts ) {
                $configured_days = (int) ceil( ( $expiry_ts - intval( $access_from ) ) / DAY_IN_SECONDS );
            }

            if ( ! $configured_days ) {
                $configured_days = 5;
            }

            $site_url    = get_site_url();
            $package_link = '';
            $page = get_page_by_path( 'packages' );
            if ( $page && isset( $page->ID ) ) {
                $package_link = get_permalink( $page->ID );
            } else {
                $package_link = trailingslashit( $site_url ) . 'packages/';
            }

            ob_start();
            ?>
            <div class="ld-countdown-expired-container heading" id="ld-countdown-expired-<?php echo esc_attr( $course_id . '-' . $user_id ); ?>">
              <h2 class="ld-expired-title">Free Trial Time Expired</h2>
              <p class="ld-expired-message">Your free trial time of <?php echo intval( $configured_days ); ?> days has now expired. Upgrade to continue learning lessons.</p>
              <a class="ld-upgrade-button" href="<?php echo esc_url( $package_link ); ?>">Upgrade Now</a>
            </div>
  <style>
       .ld-countdown-expired-container {
              text-align: center;
              padding: 0px 20px;
              max-width: 517px;
              margin: 0 auto;
            }
            .ld-expired-title {
              /* font-size: 32px;
              font-weight: 700;
              color: #0051A8; */
              margin: 0 0 13px 0;
              /* line-height: 1.2; */
            }
            .ld-expired-message { 
                font-weight: 400;
                color: #222222;
                font-size: 18px;     
                line-height: 27px;          
                text-align: center; 
                margin-bottom:50px;  
            }
            .ld-upgrade-button {
              display: inline-block;
              background: #FBB03B;
              color: #ffffff;
              font-size: 16px;
              font-weight: 700;
              line-height:24px;
              padding: 12px 96px;
              border-radius: 50px;
              text-decoration: none;
              transition: all .3s;
             
            }
            .ld-upgrade-button:hover {
             background-color: #4F80FF;  
             color:#ffffff;
            }
            @media (max-width: 600px) {
              
              .ld-expired-message { margin-bottom:26px; }
             .ld-upgrade-button { 
width: 100%;
             }
            }
        </style>
            <?php
            return ob_get_clean();
        }

        return '<div class="ld-countdown no-expiry">No expiry set for this course.</div>';
    }

    $now_ts = current_time( 'timestamp' );

    // If already expired
    if ( $expiry_ts <= $now_ts ) {
        $site_url = get_site_url();
        $page = get_page_by_path( 'packages' );
        if ( $page && isset( $page->ID ) ) {
            $package_link = get_permalink( $page->ID );
        } else {
            $package_link = trailingslashit( $site_url ) . 'packages/';
        }

        $configured_days = null;
        if ( $access_from ) {
            $configured_days = (int) ceil( ( $expiry_ts - intval( $access_from ) ) / DAY_IN_SECONDS );
        }
        if ( ! $configured_days ) {
            if ( function_exists( 'learndash_get_course_meta_setting' ) ) {
                $all_settings = learndash_get_course_meta_setting( $course_id );
                if ( ! empty( $all_settings ) && is_array( $all_settings ) ) {
                    foreach ( $all_settings as $k => $v ) {
                        if ( is_numeric( $v ) && intval( $v ) > 0 && intval( $v ) < 3650 ) {
                            $configured_days = intval( $v );
                            break;
                        }
                    }
                }
            }
        }
        if ( ! $configured_days ) {
            $configured_days = 5;
        }

        ob_start();
        ?>
        <div class="ld-countdown-expired-container heading" id="ld-countdown-expired-<?php echo esc_attr( $course_id . '-' . $user_id ); ?>">
          <h2 class="ld-expired-title">Free Trial Time Expired</h2>
          <p class="ld-expired-message">Your free trial time of <?php echo intval( $configured_days ); ?> days has now expired. Upgrade to continue learning lessons and take diagnostic tests.</p>
          <a class="ld-upgrade-button" href="<?php echo esc_url( $package_link ); ?>">Upgrade Now</a>
        </div>
        <style>
       .ld-countdown-expired-container {
              text-align: center;
              padding: 0px 20px;
              max-width: 517px;
              margin: 0 auto;
            }
            .ld-expired-title {
              /* font-size: 32px;
              font-weight: 700;
              color: #0051A8; */
              margin: 0 0 13px 0;
              /* line-height: 1.2; */
            }
            .ld-expired-message { 
                font-weight: 400;
                color: #222222;
                font-size: 18px;     
                line-height: 27px;          
                text-align: center; 
                margin-bottom:50px;  
            }
            .ld-upgrade-button {
              display: inline-block;
              background: #FBB03B;
              color: #ffffff;
              font-size: 16px;
              font-weight: 700;
              line-height:24px;
              padding: 12px 96px;
              border-radius: 50px;
              text-decoration: none;
              transition: all .3s;
             
            }
            .ld-upgrade-button:hover {
             background-color: #4F80FF;  
             color:#ffffff;
            }
            @media (max-width: 600px) {
              
              .ld-expired-message { margin-bottom:26px; }
             .ld-upgrade-button { 
width: 100%;
             }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    // If we reach here, expiry_ts > now -> show countdown
    static $ld_count = 0;
    $ld_count++;
    $uniq_id = 'ld-countdown-' . intval( $course_id ) . '-' . intval( $user_id ) . '-' . $ld_count;

    $expiry_date = date_i18n( get_option( 'date_format' ), $expiry_ts );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $uniq_id ); ?>" class="ld-countdown-container heading">
      <h2 class="ld-countdown-title">Free Trial Time Remaining</h2>
      <p class="ld-countdown-subtitle">Complete your free diagnostic before the trial ends to unlock your custom study roadmap.</p>
      
      <div class="ld-countdown-timer-wrapper" data-expiry="<?php echo esc_attr( intval( $expiry_ts * 1000 ) ); ?>">
        <div class="ld-time-box">
          <div class="ld-time-value ld-countdown-days">0</div>
          <div class="ld-time-label">Days</div>
        </div>
        <div class="ld-time-separator">:</div>
        <div class="ld-time-box">
          <div class="ld-time-value ld-countdown-hours">00</div>
          <div class="ld-time-label">Hours</div>
        </div>
        <div class="ld-time-separator">:</div>
        <div class="ld-time-box">
          <div class="ld-time-value ld-countdown-mins">00</div>
          <div class="ld-time-label">Minutes</div>
        </div>
        <div class="ld-time-separator">:</div>
        <div class="ld-time-box">
          <div class="ld-time-value ld-countdown-secs">00</div>
          <div class="ld-time-label">Seconds</div>
        </div>
      </div>
      
      <div class="ld-countdown-expiry-date">Expires on: <?php echo esc_html( $expiry_date ); ?></div>
    </div>

    <style>
    #<?php echo esc_attr( $uniq_id ); ?>.ld-countdown-container {
      text-align: center;
      /* padding: 00px 20px; */
      /* font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; */
      max-width: 560px;
      margin: 0 auto;
    
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-title {
      /* font-size: 32px; */
      /* font-weight: 700;
      color: #0051A8; */
      margin: 0 0 13px 0;
      /* line-height: 1.2; */
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-subtitle {
      font-size: 18px;
      color: #222222;
      line-height: 27px;
      margin: 0 0 50px 0;
      max-width: 517px;
      margin-left: auto;
      margin-right: auto;
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-timer-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
      margin-bottom: 27px;
      flex-wrap: wrap;
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-time-box {
      /* background: #fff;
      border: 2px solid #E5E5E5;
      border-radius: 12px;
      padding: 16px 20px;
      min-width: 90px; */
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-time-value {
font-size: 32px;
    font-weight: 700;
    color: #00409E;
    line-height: 1;
    margin-bottom: 12px;
    background: #fff;
    border: 1.29px solid #D5D8DC;
    /* border: 2px solid #E5E5E5; */
    border-radius: 10px;
    padding: 30px;
    min-width: 98px;
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-time-label {
      font-size: 20px;
      color: #222222;
     line-height:100%;
      font-weight: 400;
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-time-separator {
        font-size: 32px;
    font-weight: 700;
   color: #00409E;

    position: relative;
    margin: 0 4px;
    top: -18px;
    }
    #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-expiry-date {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    color: #222222;
    text-align: center;
    padding-bottom:80px;
    }
   @media (max-width: 1024px) { 
    #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-timer-wrapper { margin-bottom:24px; }
   }
    @media (max-width: 600px) {
      #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-title { font-size: 24px; }
      #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-subtitle { font-size: 18px;  }
      #<?php echo esc_attr( $uniq_id ); ?> .ld-time-box { 
        /* padding: 12px 16px; 
        min-width: 70px; */
      }
      #<?php echo esc_attr( $uniq_id ); ?> .ld-time-value { font-size: 20px; line-height:100%;padding:19px;min-width: 62px; margin-bottom:10px;  }
      #<?php echo esc_attr( $uniq_id ); ?> .ld-time-label { font-size: 14px; }
      #<?php echo esc_attr( $uniq_id ); ?> .ld-time-separator { font-size: 24px; margin:0 2px; }
      #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-timer-wrapper { gap: 8px; margin-bottom:17px; }
        #<?php echo esc_attr( $uniq_id ); ?> .ld-countdown-expiry-date { font-size:12px; padding-bottom:30px; }
    }
    </style>

    <script>
    (function(){
      var wrap = document.getElementById('<?php echo esc_js( $uniq_id ); ?>');
      if (!wrap) return;
      var timerEl = wrap.querySelector('.ld-countdown-timer-wrapper');
      if (!timerEl) return;
      var expiryMs = parseInt(timerEl.getAttribute('data-expiry'), 10);
      if (!expiryMs || isNaN(expiryMs)) return;

      function pad(n){ return (n<10? '0' : '') + n; }

      function checkServerExpired(courseId, cb) {
        var xhr = new XMLHttpRequest();
        var params = 'action=my_check_user_course_status&course_id=' + encodeURIComponent(courseId);
        xhr.open('POST', '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onreadystatechange = function() {
          if (xhr.readyState !== 4) return;
          try {
            var res = JSON.parse(xhr.responseText);
            if (res && res.success && res.data && typeof res.data.expired !== 'undefined') {
              cb(null, res.data);
            } else {
              cb(new Error('Unexpected response'));
            }
          } catch (e) {
            cb(e);
          }
        };
        xhr.send(params);
      }

      function update(){
        var now = new Date().getTime();
        var diff = expiryMs - now;
        if (diff <= 0) {
          checkServerExpired(<?php echo intval( $course_id ); ?>, function(err, data) {
            if ( ! err && data && data.expired ) {
              window.location.reload(true);
            } else {
              timerEl.innerHTML = '<span class="ld-countdown-expired">Access expired</span>';
            }
          });
          clearInterval(interval);
          return;
        }
        var seconds = Math.floor(diff/1000);
        var days = Math.floor(seconds / (24*3600));
        seconds = seconds % (24*3600);
        var hours = Math.floor(seconds / 3600);
        seconds = seconds % 3600;
        var mins = Math.floor(seconds / 60);
        var secs = seconds % 60;

        var daysEl = timerEl.querySelector('.ld-countdown-days');
        var hoursEl = timerEl.querySelector('.ld-countdown-hours');
        var minsEl = timerEl.querySelector('.ld-countdown-mins');
        var secsEl = timerEl.querySelector('.ld-countdown-secs');
        if (daysEl) daysEl.textContent = days;
        if (hoursEl) hoursEl.textContent = pad(hours);
        if (minsEl) minsEl.textContent = pad(mins);
        if (secsEl) secsEl.textContent = pad(secs);
      }

      update();
      var interval = setInterval(update, 1000);
    })();
    </script>
    <?php

    return ob_get_clean();
}


/**Code Moved from Snippet Plugin*/

//skip cart and go straight to checkout
add_filter( 'woocommerce_add_to_cart_redirect', 'skip_wc_cart' ); 
function skip_wc_cart() {
   return wc_get_checkout_url();
}

//remove the "has been added to your cart" message
add_filter( 'wc_add_to_cart_message_html', '__return_false' );

/**Add product short description in Cart on Checkout page */
function excerpt_in_cart($cart_item_html, $product_data) {
global $_product;

$excerpt = get_the_excerpt($product_data['product_id']);
$excerpt = substr($excerpt, 0, 300);

echo $cart_item_html . '<br><p class="shortDescription">' . $excerpt . '' . '</p>';
}

add_filter('woocommerce_cart_item_name', 'excerpt_in_cart', 40, 2);

/**Direct customer log out */

function skip_logout_confirmation() {
global $wp;
if ( isset( $wp->query_vars['customer-logout'] ) ) {
    wp_redirect( str_replace( '&amp;', '&', wp_logout_url( home_url() ) ) );
    exit;
  }
}
add_action( 'template_redirect', 'skip_logout_confirmation' );

/**Empty cart with multiple products */

function gl_main_empty_cart_when_adding_new_products() {
	wc_empty_cart();

	return TRUE;
}
add_action( 'woocommerce_add_to_cart_validation', 'gl_main_empty_cart_when_adding_new_products');

/**
 * Redirect WooCommerce to a custom page after checkout
 */
add_action( 'woocommerce_thankyou', 'custom_redirect_woo_checkout');
function custom_redirect_woo_checkout( $order_id ){
    $order = wc_get_order( $order_id );

    $thank_you_url = home_url( '/thank-you-page/' ); // auto picks domain

    if ( ! $order->has_status( 'failed' ) ) {
        wp_safe_redirect( $thank_you_url );
        exit;
    }
}

/**Redirect to Packages page after registration */

add_filter( 'woocommerce_registration_redirect', 'custom_redirection_after_registration', 10, 1 );
function custom_redirection_after_registration( $redirection_url ){
    // Change the redirection Url
    $redirection_url = home_url( '/packages/' );

    return $redirection_url; // Always return something
}

/**Redirect after login for paid and unpaid customers separately */
function ts_redirect_login( $redirect) {
    if( isset($_POST['username']) && !empty($_POST['username']) ){
        $user_data = get_user_by(is_email( $_POST['username'] ) ? 'email' : 'login', $_POST['username']);
        $user_id = $user_data->data->ID;
        $customer_orders = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id,
            'post_type'   => 'shop_order',
            'post_status' => array('wc-completed','wc-processing')
        ) );
        if($customer_orders){
            // Purchased user
            $redirect = get_permalink(2005); // replace with your page link
        }else{
            // Non purchased user
            $redirect = get_permalink(1719); // replace with your page link
        }
    }
    return $redirect;
}
add_filter( 'woocommerce_login_redirect', 'ts_redirect_login' );

/**Browse product redirect */

add_filter( 'woocommerce_return_to_shop_redirect', 'redirect_browse_product' );

function redirect_browse_product( $redirection_url ) {
    $redirection_url = ( '/packages/' );

    return $redirection_url; // Always return something
}

/**Adding confirm password field to My account page */
add_action( 'woocommerce_register_form', 'wc_register_form_password_repeat' );
function wc_register_form_password_repeat() {
    ?>
    <p class="form-row form-row-wide">
        <label for="reg_password2"><?php _e( 'Confirm password', 'woocommerce' ); ?> <span class="required">*</span></label>
        <input type="password" class="input-text" name="password2" id="reg_password2" value="<?php if ( ! empty( $_POST['password2'] ) ) echo esc_attr( $_POST['password2'] ); ?>" />
    </p>
    <?php
}

/**Confirm password error message if field values are different */

function woocommerce_registration_errors_validation($reg_errors, $sanitized_user_login, $user_email) {
	global $woocommerce;
	extract( $_POST );
	if ( strcmp( $password, $password2 ) !== 0 ) {
		return new WP_Error( 'registration-error', __( 'Passwords do not match.', 'woocommerce' ) );
	}
	return $reg_errors;
}
add_filter('woocommerce_registration_errors', 'woocommerce_registration_errors_validation', 10, 3);

/**Hide menu items for unpaid customers - My course */

function hide_menu_conditional($items, $args) {
        // Check if the USer is logged in
        $user_id = get_current_user_id();
        $customer_orders = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id,
            'post_type'   => 'shop_order',
            'post_status' => array('wc-completed','wc-processing')
        ) );
        if($customer_orders){
					return $items;
		}
		else{
			foreach ($items as $key => $item) {
                if ($item->title == 'My course') {
                    // Remove the menu item
                    unset($items[$key]);
                    break;
                }
            }
		}
        return $items;

}
add_filter('wp_nav_menu_objects', 'hide_menu_conditional', 10, 2);

/**Hide menu items for unpaid customers - My profile */

function hide_menu_conditional_2($items, $args) {
        // Check if the USer is logged in
        $user_id = get_current_user_id();
        $customer_orders = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id,
            'post_type'   => 'shop_order',
            'post_status' => array('wc-completed','wc-processing')
        ) );
        if($customer_orders){
					return $items;
		}
		else{
			foreach ($items as $key => $item) {
                if ($item->title == 'My profile') {
                    // Remove the menu item
                    unset($items[$key]);
                    break;
                }
            }
		}
        return $items;

}
add_filter('wp_nav_menu_objects', 'hide_menu_conditional_2', 10, 2);

/**LMS go back to course page after lesson completion */

add_filter(
    'learndash_completion_redirect',
    function( $link, $post_id ) {
 
        // We only want to do this for Lessons (sfwd-lessons).
        if ( get_post_type( $post_id ) == 'sfwd-lessons' ) {
			$course_id = learndash_get_course_id();
            $link = get_permalink( $course_id );
        }
 
        // Always return $link
        return $link;
    },
    20,
    2
);

/**JS for phone numbers only at Checkout */

add_action('wp_footer', 'restrict_phone_field_to_numbers');

function restrict_phone_field_to_numbers() {
    if (is_checkout()) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#billing_phone, #shipping_phone').on('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });
        </script>
    <?php }
}

/**Validate Phone Field at Checkout */

add_action('woocommerce_after_checkout_validation', 'validate_phone_number', 10, 2);

function validate_phone_number($data, $errors) {
    if (!preg_match('/^[0-9]+$/', $data['billing_phone'])) {
        $errors->add('validation', __('Please enter a valid phone number with digits only.', 'woocommerce'));
    }
}

/**
 * Letters only for name at Checkout JS
 */

add_action('wp_footer', 'restrict_name_fields_to_letters');

function restrict_name_fields_to_letters() {
    if (is_checkout()) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#billing_first_name, #billing_last_name, #shipping_first_name, #shipping_last_name').on('input', function() {
                    this.value = this.value.replace(/[^a-zA-Z\s-]/g, '');
                });
            });
        </script>
    <?php }
}

/**
 * Letters only for name at Checkout PHP
 */

add_action('woocommerce_after_checkout_validation', 'validate_name_fields', 10, 2);

function validate_name_fields($data, $errors) {
    if (!preg_match('/^[a-zA-Z\s-]+$/', $data['billing_first_name'])) {
        $errors->add('validation', __('First name can only contain letters, spaces, and hyphens.', 'woocommerce'));
    }
    if (!preg_match('/^[a-zA-Z\s-]+$/', $data['billing_last_name'])) {
        $errors->add('validation', __('Last name can only contain letters, spaces, and hyphens.', 'woocommerce'));
    }
}

/**Validate phone field on My profile editing / updates */

add_action('woocommerce_save_account_details_errors', 'validate_phone_number_my_account', 10, 2);

function validate_phone_number_my_account($errors, $user) {
    if (!preg_match('/^[0-9]+$/', $_POST['billing_phone'])) {
        $errors->add('validation', __('Please enter a valid phone number with digits only.', 'woocommerce'));
    }
}

/**JS for phone numbers only on My profile */

add_action('wp_footer', 'restrict_phone_field_to_numbers_my_account');

function restrict_phone_field_to_numbers_my_account() {
    if (is_account_page()) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#billing_phone').on('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });
        </script>
    <?php }
}

/**
 * Letters only for name My profile page PHP
 */

add_action('woocommerce_save_account_details_errors', 'validate_name_fields_my_account', 10, 2);

function validate_name_fields_my_account($errors, $user) {
    if (!preg_match('/^[a-zA-Z\s-]+$/', $_POST['billing_first_name'])) {
        $errors->add('validation', __('First name can only contain letters, spaces, and hyphens.', 'woocommerce'));
    }
    if (!preg_match('/^[a-zA-Z\s-]+$/', $_POST['billing_last_name'])) {
        $errors->add('validation', __('Last name can only contain letters, spaces, and hyphens.', 'woocommerce'));
    }
}

/**Letters only for name My profile page JS */

add_action('wp_footer', 'restrict_name_fields_to_letters_my_account');

function restrict_name_fields_to_letters_my_account() {
    if (is_account_page() && is_wc_endpoint_url('edit-account')) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Restrict first and last name fields in "Edit Account"
                $('#billing_first_name, #billing_last_name').on('keydown', function(e) {
                    var key = e.keyCode || e.which;
                    var keyChar = String.fromCharCode(key).toLowerCase();

                    // Allow only letters (a-z), spaces, and hyphens
                    if (!/[a-z\s-]/.test(keyChar) && key !== 8) {
                        e.preventDefault(); // Block non-allowed characters
                    }
                });

                // Fallback for 'input' event in case 'keydown' doesn't work
                $('#billing_first_name, #billing_last_name').on('input', function() {
                    this.value = this.value.replace(/[^a-zA-Z\s-]/g, ''); // Remove non-letter characters (no digits)
                });
            });
        </script>
    <?php }
}

/**
 * Custom logout query and endpoint rule
 */

// Register the custom query var
function gurutor_add_query_vars( $vars ) {
    $vars[] = 'customer-logout';
    return $vars;
}
add_filter( 'query_vars', 'gurutor_add_query_vars' );

// Add a rewrite rule for the pretty logout URL
function gurutor_add_logout_rewrite_rule() {
    add_rewrite_rule( '^my-account/customer-logout/?$', 'index.php?customer-logout=1', 'top' );
}
add_action( 'init', 'gurutor_add_logout_rewrite_rule' );


/**
 * Redirect non-logged-in users from Free Trial course to My Account page
 */
add_action( 'template_redirect', 'redirect_non_logged_users_from_free_trial' );

function redirect_non_logged_users_from_free_trial() {
    
    // Check if user is NOT logged in
    if ( is_user_logged_in() ) {
        return;
    }
    
    // Get the My Account page URL dynamically
    $my_account_url = get_permalink( wc_get_page_id( 'myaccount' ) );
    
    // Fallback if WooCommerce function doesn't exist or returns empty
    if ( ! $my_account_url ) {
        $my_account_url = home_url( '/my-account/' );
    }
    
    // Add the query parameter
    $redirect_url = add_query_arg( 'type_subs', 'free', $my_account_url );
    
    // Check if we're on the Free Trial course page (ID: 7472)
    if ( is_singular( 'sfwd-courses' ) && get_the_ID() === 7472 ) {
        // Redirect to my account page with type_subs parameter
        wp_redirect( $redirect_url );
        exit;
    }
    
    // Also check for lessons/topics that belong to the Free Trial course
    if ( is_singular( array( 'sfwd-lessons', 'sfwd-topic' ) ) ) {
        
        $current_id = get_the_ID();

        // Check if accessing specific lesson IDs directly

        if ( in_array( $current_id, array( 9349, 9412 ), true ) ) {

            wp_redirect( $redirect_url );

            exit;

        }
        
        $course_id = learndash_get_course_id( $current_id );
        
        // If this lesson/topic belongs to Free Trial course (ID: 7472), redirect
        if ( $course_id === 7472 ) {
            wp_redirect( $redirect_url );
            exit;
        }

        // If this lesson/topic belongs Free Trial Study Plan Course 9361, redirect to the same URL
        if ( $course_id === 9361 ) {
            wp_redirect( $redirect_url );
            exit;
        }

    }
}


add_filter('woocommerce_add_error', 'decode_html_in_checkout_errors');
function decode_html_in_checkout_errors($error) {
    // Decode HTML entities in error messages
    return html_entity_decode($error, ENT_QUOTES, 'UTF-8');
}

add_filter('wp_nav_menu_items', 'allow_html_in_menu_label', 10, 2);
function allow_html_in_menu_label($items, $args) {
    return $items;
}
