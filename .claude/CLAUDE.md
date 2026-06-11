# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Role

You are a senior WordPress PHP full-stack developer, 20+ years experience. GrassBlade LRS expert, Lectora tool expert, expert in HTML, iframe, and xAPI modules.

### Role Rules
- Write clean, minimal, secure code only
- Never assume missing requirements — ask first
- Never make mistakes — verify logic before output
- Follow WordPress coding standards and best practices
- Always sanitize inputs, escape outputs, use nonces

### Security Protocol
- Self-audit every snippet before output
- Flag any vulnerability, then fix it inline
- No raw SQL — use `$wpdb` with `prepare()`
- No direct file access — check `ABSPATH`

### Response Format
- Strip filler words: no "the / is / am / are / basically / simply"
- Sentences: 3–6 words max
- No narration — show output, stop
- Code first, explanation after (if needed)
- No preamble, no summary

### Before Every Code Response
1. Re-read requirement
2. Check for edge cases
3. Security audit
4. Output final code only

## Project Overview

**Gurutor** — A GMAT test prep platform built as a **WordPress GeneratePress child theme**. No build tools, bundlers, or package managers. All PHP/JS/CSS is vanilla and served directly.

**Stack:** WordPress, GeneratePress (parent theme), Elementor (header/footer only), WooCommerce + Subscriptions, LearnDash LMS, GrassBlade xAPI LRS, jQuery, Dompdf 3.1.5 (vendored at `lib/dompdf/`, used only by the AI report PDF download)

**Staging:** https://stg-gurutor-test.kinsta.cloud/

## Development Workflow

- **No build step** — edit files directly, refresh browser
- **PHP 7.4+ compatible** — no PHP 8-only features
- **jQuery required** — all JS files depend on jQuery (enqueued via WordPress)
- **No automated tests** — manual QA on staging
- **CSS enqueue for intake/settings may be commented out** — check `wp_enqueue_style()` calls in PHP files before editing CSS; styles may load via Elementor
- **Cache busting:** Chatbox uses `filemtime()` for CSS/JS versioning; other modules use static theme version — clear browser cache after JS/CSS changes

## Architecture

### User Flow
```
Registration (WooCommerce) → Free Trial (courses 7472/9361)
                            → Paid Subscription (products 7008/7009)
                              → Intake Wizard (5-step onboarding, required)
                              → Dashboard + Study Plan (course 8112)
                              → AI Chatbox (floating widget on course pages)
```

### Key Access Control Function
```php
gurutor_user_has_active_paid_access($user_id = null)
// Defined in: inc/free-trial-grassblade-xapi.php
// Used everywhere to gate paid-only features
```

### Module Map

| Module | PHP | JS | CSS | Entry Point |
|--------|-----|----|----|-------------|
| **Intake Wizard** | `inc/gmat-intake-form.php` | `js/gmat-intake.js` | `css/gmat-intake.css` | Shortcode `[gmat_intake_form]` on `/gmat-intake/` |
| **Dashboard** | `inc/gmat-dashboard.php` | — | `css/gmat-dashboard.css` | Shortcode `[gmat_dashboard]` on `/gmat-dashboard/` |
| **Study Plan** | `inc/gmat-study-plan.php` | `js/gmat-study-plan.js` | `css/gmat-study-plan.css` | Overrides LearnDash course 8112 via `the_content` filter |
| **Study Plan Admin** | `inc/gmat-study-plan-admin.php` | — | — | WP Admin → Settings → GMAT Study Plan |
| **AI Chatbox** | `inc/gmat-chatbox.php` | `js/gmat-chatbox.js` | `css/gmat-chatbox.css` | `wp_footer` hook on course 8112 only |
| **GMAT Settings** | `inc/gmat-settings-account.php` | `js/gmat-settings.js` | `css/gmat-settings.css` | WooCommerce My Account endpoint `/my-account/gmat-settings/` |
| **Free Trial/xAPI** | `inc/free-trial-grassblade-xapi.php` | — | — | Various hooks |
| **Analyse with AI** | `inc/gmat-analyse-ai.php` (+ `inc/templates/pdf-analyse-ai.php` + `inc/templates/gurutor-logo.png`) | `js/gmat-analyse-ai.js` | `css/gmat-analyse-ai.css` | `wp_enqueue_scripts` hook on course 8112 lesson pages. Modal includes a **Download Report** CTA that streams a Dompdf-rendered PDF via the `gmat_analyse_ai_download_pdf` AJAX endpoint |
| **Next Lesson Button** | `inc/gmat-next-lesson.php` | `js/gmat-next-lesson.js` | `css/gmat-next-lesson.css` | `wp_footer` + `wp_enqueue_scripts` on lessons/topics of courses 7472, 9361, 8112 |
| **Course Preview (locked)** | `inc/gmat-course-preview.php` | — (reuses `js/gmat-study-plan.js`) | `css/gmat-course-preview.css` (+ `css/gmat-study-plan.css`) | Shortcode `[gmat_course_preview]` on `/packages/` page |
| **Checkout Coupon** | `inc/gmat-checkout-coupon.php` | — | `css/gmat-checkout-coupon.css` | `template_redirect` + `wp_enqueue_scripts` on checkout/cart; admin hint on coupon edit |
| **Lesson Loader** | `inc/gmat-lesson-loader.php` | `js/gmat-lesson-loader.js` | `css/gmat-lesson-loader.css` | `wp_footer` priority 50 — branded full-screen overlay on lesson navigation clicks + iframe load wait |

### Active Includes (functions.php)

All includes are in `functions.php` lines 21-34. Some may be commented out during development — always check the actual file before assuming a feature is active. Currently all modules are included (no lines commented out).

## Important Conventions

### CSS
- **Chatbox uses BEM:** `.gmat-cb__*` (block), `.gmat-cb__*--*` (modifier)
- **Older modules** use looser naming (`.gmat-intake-*`, `.gmat-settings-*`)
- **Elementor for header/footer** — NOT GeneratePress theme parts. To hide header/footer on a page, target `.elementor-location-header` and `data-elementor-type` selectors
- **Body class injection** required for page-specific CSS — use `body_class` filter (WordPress doesn't auto-add page slugs)

### PHP/AJAX
- All AJAX handlers: `wp_ajax_<feature>_<action>` (e.g., `gmat_intake_save_goal`, `gmat_chatbox_send`)
- All intake user meta keys prefixed: `_gmat_intake_*`
- Score sanitization helper: `gmat_intake_sanitize_scores($scores_array)`
- Score field name is `overall` (NOT `total`) — critical for cross-module consistency
- Chatbox response formatting: `gmat_chatbox_format_reply()` converts plain-text AI replies to HTML — handles bullet points (•/-/*), numbered lists, section headers (lines ending in `:` under 80 chars), **bold**, *italic*, `code`. Passes through untouched if response already contains HTML tags. All output sanitized via `wp_kses_post()`

### Study Plan Key Functions (v8)
- `gmat_sp_fetch_xapi_data($user_id)` — core function returning both `status_map` and `pass_fail` signals (static cached). Includes 4-layer smart quotes sanitization before `json_decode()` (BOM strip, curly→straight quotes, `mb_convert_encoding()`, regex fallback).
- `gmat_sp_get_pass_fail_map($user_id)` — returns `variable_name => "Pass"|"Fail"` from xAPI completed statement object names
- `gmat_sp_get_pass_fail_variable_map()` — maps ~65 xAPI variable names to lesson keys (CR exercises, verbal reviews, QLE_*, QRS_*)
- `gmat_sp_get_exercise_result($user_id, $lesson_key)` — returns `'fail'`|`'pass'`|`'none'` (no fallback)
- `gmat_sp_get_review_result($user_id, $review_key)` — returns `'fail'`|`'pass'`|`'none'` (multi-variable: fails if ANY fails)
- `gmat_sp_get_quant_exercise_failures($user_id, $exercise_num, $learn_keys, $ids)` — explicit QLE_* failures only (no fallback)
- `gmat_sp_get_learn_lesson_failures($user_id, $learn_keys)` — explicit learn lesson pass/fail failures
- `gmat_sp_get_qrs_lesson_map()` — maps QRS topic suffixes (ALG1, NP1, etc.) to lesson keys
- `gmat_sp_get_quant_review_failures($user_id, $review_num)` — returns lesson keys that failed within a specific QRS review
- `gmat_sp_build_suggest_html($args, $all_keys)` — builds orange "Suggested areas of focus" box HTML
- **Rule:** Only explicit Pass/Fail xAPI signals trigger suggestions. Never assume attempted/completed = pass/fail.
- `gmat_sp_format_description($desc)` — converts newline-separated text to HTML `<ul>` list
- Lesson descriptions stored as `'desc'` field, topic names stored as `'topic'` field in `gmat_sp_get_lesson_keys()`
- **Two suggest boxes per unit:** Practice suggest box (`suggest`/`suggest_redo`) renders ABOVE practice lessons. Review suggest box (`review_suggest`/`review_suggest_redo` + `cross_suggest_links`) renders ABOVE review lessons. Verbal units use `review_suggest` only; quant units use both.
- `gmat_sp_get_lesson_minutes()` (in `gmat-study-plan-admin.php`) — flat `lesson_key => int` map of estimated completion minutes sourced from the Gurutor Module Completion Times PDFs (Quant / Verbal / Data Insights, kept in repo root). Merged into each entry's `'minutes'` field at the end of `gmat_sp_get_lesson_keys()`. Renders as `<span class="gmat-sp-lesson__time">Est. N min</span>` under the topic line in both the paid plan (course 8112) and the locked `/packages/` preview. Lessons absent from the map render no time line.

### Study Plan PDF Resource Cards
Supplementary PDFs (Course Intro, Practice Tests, Quant Fundamentals, DI Exercises + Comprehensive DI Review) render inline alongside lessons but bypass LearnDash + xAPI tracking entirely.

- **Entry shape (in `gmat_sp_get_lesson_keys()`, section = `'Resources'`):**
  ```php
  'course_intro' => array(
    'label' => '...', 'section' => 'Resources',
    'type' => 'pdf', 'pdf_subtype' => 'intro',  // 'intro' | 'test' | 'qf'
    'pdf_path' => '2026/06/gurutor-course-intro-v9.pdf',  // relative to uploads baseurl
    'topic' => '...', 'desc' => '',  // 'desc' optional — when set, renders accordion
  )
  ```
- **URL strategy:** `gmat_sp_get_pdf_url($relative_path)` uses `wp_upload_dir()['baseurl']` so URLs resolve to the current site host (staging vs. live). Never hardcode `gurutor.co` — relies on PDFs existing at the same relative path on both environments.
- **Predicate:** `gmat_sp_is_pdf_resource($entry)` — branches the renderer + skips PDFs in progress counters (unit total, section total, overall %).
- **Render helpers:** `gmat_sp_render_pdf_card($lk, $all_keys, $args)` emits a single card. `gmat_sp_render_resource_cards($keys, $all_keys, $heading, $locked, $placement)` wraps multiple cards; pass `$placement = 'top'` for standalone above-section cards (Course Intro).
- **Accordion:** PDF cards render the expand chevron + `.gmat-sp-lesson__desc` block ONLY when `desc` is non-empty AND not locked. Existing JS handler (`.gmat-sp-lesson` click → `.gmat-sp-lesson__desc` toggle) auto-handles toggle; `a, button` clicks bypass the toggle so "Open PDF" still works. Locked preview cards stay row-only to match preview's existing lesson card pattern (no accordion). DI Exercises and Comprehensive DI Review use tabbed `desc` content rendered by `gmat_sp_format_description()` for the hierarchical Skills-Covered tree.
- **Plan structure:** sections carry optional `intro_resources` + `outro_resources` arrays of PDF lesson keys. Course Intro = `intro_resources` of first section (renders OUTSIDE `.gmat-sp-section__card`, above the `<h2>` heading). Practice Tests = `outro_resources` of each section (renders INSIDE the section card after the last unit).
- **PDF placements:**
  - Verbal-first plan: Verbal → PT1, Quant → PT2, DI → PT3. Verbal Unit reviews seeded with Quant Fundamentals PDFs (Group A in Units 1/3/5, Group B in Units 2/4/6). DI Units 1/2/3 practice subsections = `di_exercise_1/2/3`. DI Unit 3 review = `comprehensive_di_review`.
  - Quant-first plan: Quant → PT1, Verbal → PT2, DI → PT3. No QF PDFs in reviews. DI Units 1/2/3 practice subsections = `di_exercise_1/2/3`. DI Unit 1 review = `quant_review_6` (duplicates Verbal Unit 1 review — intentional per client doc). DI Unit 3 review = `comprehensive_di_review`.
- **CSS variants** (`gmat-study-plan.css`): base `.gmat-sp-lesson--pdf` uses CSS custom properties `--pdf-accent`/`--pdf-accent-bg`/`--pdf-accent-bd`. Subtype modifiers override them:
  - `--pdf-intro` → orange `#f68525` (Course Introduction)
  - `--pdf-test`  → navy `#00409E` (Practice Tests, brand primary blue)
  - `--pdf-qf`    → teal `#0d9488` (Quant Fundamentals + DI Exercises + Comprehensive DI Review)
  - Locked state (preview) overrides all subtypes to neutral grey.
- **Admin UI:** PDF entries are skipped in `gmat-study-plan-admin.php`'s lesson-ID mapping form (no LearnDash post ID to assign).
- **Topic line:** never repeat "(PDF)" suffix — the orange/navy/teal badge already labels the card. Removing the suffix from topic strings is the convention.

#### Quant-first plan customisations vs. doc default
- **Quant Unit 4 Review** in Quant-first does NOT inject the dynamic `review_suggest` box (Q3 Learn failures from Quant Exercise 2). Per client direction (strikethrough in `Quant_First.md`), the Unit 3 Quant Review Set renders without the "Before completing the Review Set..." prompt. Verbal-first's Quant Unit 4 still uses the dynamic suggest. Don't reintroduce `review_suggest`/`review_suggest_redo` on Quant-first Q4 unless the client reverses this.
- **Doc-reading rule for client strikethroughs:** Italicised notes between `Unit N - Review` and `Unit N+1 - Learn` describe what would otherwise happen IN THE NEXT UNIT's review (e.g. "Q3 Learn failures from Quant Exercise 2 → Unit 4 Review"). Strikethrough = remove that behaviour from the NEXT unit, not the one above the note. (Got this wrong once — restored from Q3 and moved removal to Q4.)

### Course Preview (Locked)
- File: `inc/gmat-course-preview.php`. Shortcode: `[gmat_course_preview]`. Default attrs: `preference="verbal"`, optional `heading`, `subheading`.
- Reuses `gmat_sp_build_verbal_first(0, $ids)` / `gmat_sp_build_quant_first(0, $ids)` — passing `$user_id = 0` short-circuits `gmat_sp_fetch_xapi_data()` (early return in `get_userdata()` check), so no LRS calls are made in preview mode.
- Renders locked accordion: units expandable, all lessons show "🔒 Locked" pill instead of action buttons. Progress cards + suggest boxes are suppressed.
- Enqueues `css/gmat-study-plan.css` + `css/gmat-course-preview.css` + `js/gmat-study-plan.js` only when `is_page('packages')` OR shortcode detected in post content.
- Shortcode is expected on `/packages/` page; drop into Elementor or the page content to render.

### External Next Lesson Button
- File: `inc/gmat-next-lesson.php` + `js/gmat-next-lesson.js` + `css/gmat-next-lesson.css`.
- Active on lessons/topics of courses **7472, 9361, 8112** (trial + paid). Injects a hidden button next to the existing "Back to Course" CTA (both sit after `.grassblade` iframe container).
- Polls LRS via `gmat_next_lesson_check` AJAX every 15s (max 40 polls = 10 min). Query: `agent_email` + `verb=completed` + `since=page_open_iso`. Any completed statement after page open reveals the button.
- On completion, calls `gmat_next_lesson_url` AJAX which flattens lessons + topics via `learndash_course_get_steps_by_type()` + `learndash_get_topic_list()` and returns the next step's permalink. Last step → returns course permalink with `is_last: true` and button label flips to "Back to Course".
- Button opens next lesson in a new tab (`target="_blank" rel="noopener noreferrer"`) — user-initiated click, so not blocked by popup blockers.
- Nonce: `gmat_next_lesson_nonce`. Both AJAX handlers require `is_user_logged_in()`.

### Lesson Loader (Branded Loading Overlay)
- File: `inc/gmat-lesson-loader.php` + `js/gmat-lesson-loader.js` + `css/gmat-lesson-loader.css`.
- Purpose: cover the blank window between clicking a lesson-navigation link and the GrassBlade iframe finishing load.
- Gate function `gmat_lesson_loader_page_type()` returns `'destination'`, `'source'`, or `false` (static-cached per request):
  - `'destination'` → lesson/topic of courses **7472, 9361, 8112**.
  - `'source'` → paid course view (8112), OR any singular page whose `post_content` / `_elementor_data` contains `[grassblade_study_plan_focus`, `[grassblade_study_plan_test2`, `[grassblade_study_plan`, OR the user-defined Elementor IDs `free-trial-test-1` / `personalized-gmatz-cta`.
- Hook priority: `wp_footer` priority **50** (mirrors chatbox — late enough that all footer markup is in place).
- Click triggers (delegated, all skipped when `href="#"`, `target=_blank`, or modifier-key/middle-click): `a.gmat-sp-lesson__btn`, `a.lesson-link`, `a.gurutor-back-to-course__link`, `a.gmat-next-lesson__link`, `#free-trial-test-1 a.elementor-button`, `#personalized-gmatz-cta a.elementor-button`.
- Destination behavior: overlay shows on DOM ready, dismisses on `.grassblade iframe.grassblade_iframe` `load` event. `MutationObserver` handles late iframe injection. Safety timeout 15 s (`GMAT_LESSON_LOADER_TIMEOUT_MS`) + a `window.load + 1500 ms` belt-and-braces.
- z-index `999999` (above chatbox 99997). `display: flex !important` on `--visible` state to defeat any third-party overrides.

### Analyse with AI — Modal States (Jun 2026)
- **No-report lessons:** `gmat_analyse_ai_no_report_lessons()` (in `inc/gmat-analyse-ai.php`) lists intro/theory keys with no analysable content: `intro_verbal`, `intro_quant`, `intro_di`, `cr_lesson_1`, `cr_lesson_2`, `rc_lesson_1`. Button still renders; click opens an informational modal (`buildNoReportHTML()` — blue `.gmat-aai-empty--info` variant) with NO API call. `noReport` flag localized to JS config; `send_data` + `download_pdf` AJAX handlers both reject these keys with 400 (defence-in-depth).
- **Loading UX:** clicking Analyse opens the modal IMMEDIATELY in loading state (`renderModal(null, 'loading')`) — large spinner + rotating status messages (`LOADING_STATUSES`, 12s interval via `startStatusCycle()`). Backdrop + `body.gmat-aai-locked` block the page during the 1–5 min generation. Footer shows **Cancel**: close/Esc/backdrop aborts the in-flight request (`activeXhr.abort()` in `closeModal()`; error callback early-returns on `textStatus === 'abort'`) and restores the button. AJAX errors swap the loading section for an in-modal error state (`showModalLoadError()`), Cancel label flips to Close.
- **`renderModal(report, mode)`** modes: `'report'` (default), `'loading'`, `'noreport'`. Download + Re-analyse buttons hidden in non-report modes.
- **Download button hover:** must set `color` + `border-color` explicitly (not just `background`) — theme's global `button:hover { color:#fff }` outranks the base class color and turns the label invisible. Rule uses `.gmat-aai-modal .gmat-aai-modal__download:hover:not(:disabled)` specificity.

### Analyse with AI — Download Report (PDF)
- Files: `inc/gmat-analyse-ai.php` (handler) + `inc/templates/pdf-analyse-ai.php` (HTML/CSS template) + `inc/templates/gurutor-logo.png` (pre-rendered logo, 600×135 transparent PNG with WHITE text — designed for the dark-blue header) + `lib/dompdf/` (vendored Dompdf 3.1.5, no Composer).
- AJAX handler: `wp_ajax_gmat_analyse_ai_download_pdf`. Nonce: `gmat_analyse_ai_nonce` (shared with `send_data`).
- **Data flow:** JS caches the latest `coaching_report_html` returned by `gmat_analyse_ai_send_data` and POSTs it back on Download click — no AI re-run, instant PDF. Server re-runs `wp_kses()` via `gmat_analyse_ai_allowed_html()` (defence-in-depth). Lesson label / student name / date are re-derived server-side from `post_id` + current user — never trusted from POST.
- **Storage:** none. PDF is generated in memory and streamed via `Dompdf::output()` + `Content-Disposition: attachment`. No file is ever written to `wp-content/uploads/` or the theme dir. `while (ob_get_level()) ob_end_clean();` before headers, `exit()` after stream.
- **Dompdf options:** `isRemoteEnabled=false`, `isHtml5ParserEnabled=true`, `defaultFont='DejaVu Sans'`, `chroot=$theme_path`.
- **Logo lookup priority:** `inc/templates/gurutor-logo.png` → `images/gurutor-logo.png` → `GURUTOR-logo (1).svg` → `GURUTOR-logo.svg`. PNG preferred — php-svg-lib (Dompdf's bundled SVG renderer) silently drops the brand SVG's text-glyph paths (the icon-arc renders but "URUTOR" letters disappear). The PNG was pre-rendered offline via `svglib + reportlab + pypdfium2` with white text + magenta-marker post-processing for transparency.
- **Template layout (Dompdf-safe):** No flexbox/grid. Header is a single `<table class="pdf-header">` (NOT a div with `width:100%; padding;` — that overflows the @page content area because Dompdf doesn't honour `box-sizing: border-box` reliably for div+padding combinations). Table cells carry padding instead.
- **Footer buttons:** Download Report (outline blue, matches `.gmat-aai-modal__regen` styling). Re-analyse is hidden via `.gmat-aai-modal__regen { display: none !important; }` — markup and click handler kept intact for easy revert.
- **Filename:** `sanitize_file_name('Gurutor-Coaching-Report-{lesson_key}-{Y-m-d}.pdf')`.
- **JS download trigger:** hidden `<form method="POST">` submission (not XHR) — browser handles the binary download natively via Content-Disposition. 5s safety timeout re-enables the button.

### Registration Form (Name + Phone)
- Hooked at `woocommerce_register_form_start` via `gurutor_add_name_phone_to_registration_form()` (`functions.php` ~line 1400). Appears on both `/my-account/` and `/my-account/?type_subs=free`.
- Fields: `billing_first_name`, `billing_last_name`, `billing_phone` — all required. Name regex: `/^[a-zA-Z\s\-]+$/`. Phone regex: `/^\+?[1-9][0-9]{6,14}$/` (E.164, 7–15 digits).
- Validation in `validate_terms_checkbox()` (extended). Persistence in `gurutor_save_registration_name_phone()` on `woocommerce_created_customer` — writes both WP core (`first_name`, `last_name`) and WC billing (`billing_first_name`, `billing_last_name`, `billing_phone`) so checkout auto-populates.

### JavaScript
- All JS wraps in `(function($) { 'use strict'; ... })(jQuery);` IIFE
- Chatbox shares `AI_AVATAR_SVG` constant across render functions
- Intake wizard uses `validateStep(step)` before each AJAX save
- Settings page uses `validate()` before AJAX save — mirrors intake validation rules

### Validation Rules (shared between intake & settings)
| Field | Range |
|-------|-------|
| Goal/Desired Score | 205–805 |
| Overall Score | 205–805 |
| Quant Score | 60–90 |
| Verbal Score | 60–90 |
| Data Insights Score | 60–90 |
| Test Date | Today or future |

## Key IDs & Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `FREE_TRIAL_PRODUCT_ID` | 7006 | WooCommerce free trial product |
| `FREE_TRIAL_COURSE_ID` | 7472 | LearnDash free trial course |
| `FREE_TRIAL_DAYS` | 5 | Free trial duration in days |
| Free trial course 2 | 9361 | Second free trial course |
| `GMAT_SP_COURSE_ID` | 8112 | Paid LearnDash course |
| `GMAT_INTAKE_PAGE_SLUG` | 'gmat-intake' | Intake wizard page slug |
| `GMAT_DASHBOARD_PAGE_SLUG` | 'gmat-dashboard' | Dashboard page slug |
| Paid product IDs | 7008, 7009 | WooCommerce subscription products |
| `GMAT_CHATBOX_API_URL` | wp-config.php | FastAPI backend URL (AWS EC2) |
| `GMAT_CHATBOX_API_KEY` | wp-config.php | Shared secret for API auth |
| `GMAT_CHATBOX_RATE_LIMIT` | 20 msg/60s | Per-user rate limit (transient key: `gmat_cb_rate_{user_id}`) |
| `GMAT_CHATBOX_API_TIMEOUT` | 30s | External API request timeout |
| `GMAT_CHATBOX_MAX_MSG_LENGTH` | 2000 chars | Max user message length |
| `GMAT_ANALYSE_AI_API_URL` | wp-config.php | External AI analysis endpoint — `https://dataapi.gurutor.co/report` (nginx → FastAPI on EC2; as of Jun 2026, replaced old ngrok tunnel) |
| `GMAT_ANALYSE_AI_API_KEY` | wp-config.php | Shared secret sent as `Authorization: Bearer` header |
| `GMAT_ANALYSE_AI_COURSE_ID` | 8112 | Course whose lessons show the Analyse button |
| `GMAT_ANALYSE_AI_API_TIMEOUT` | 600s | External AI API request timeout (report generation takes 1–5 min; JS ajax timeout sits at 610s). Upstream nginx `proxy_read_timeout` on the EC2 box must also exceed generation time |
| `GMAT_ANALYSE_AI_MAX_REPORT_BYTES` | 50 KB | Upstream cap on coaching_report markdown. PDF handler caps `report_html` at 2× this (100 KB) to allow HTML-tag overhead |
| `GMAT_ANALYSE_AI_META_PREFIX` | `_gmat_analyse_ai_report_` | Reserved prefix for any future per-user report meta keys (not currently used — caching disabled) |

## User Meta Keys Reference

| Key | Type | Purpose |
|-----|------|---------|
| `_gmat_intake_official_scores` | JSON string | Intake Step 1 official test scores |
| `_gmat_intake_practice_scores` | JSON string | Intake Step 1 practice test scores |
| `_gmat_intake_step1_done` | `'1'` | Step 1 skipped/saved flag |
| `_gmat_intake_goal_score` | int | Intake Step 2 goal score |
| `_gmat_intake_weekly_hours` | int | Intake Step 3 study hours |
| `_gmat_intake_next_test_date` | `'Y-m-d'` | Intake Step 4 test date |
| `_gmat_intake_section_preference` | `'quant'`\|`'verbal'` | Intake Step 5 section order |
| `_gmat_intake_completed` | `'1'` | Intake completion flag |
| `_gmat_intake_completed_at` | timestamp | Intake completion timestamp |

Score entry format (stored as JSON arrays):
```json
{ "date": "2025-01-15", "overall": 645, "quant": 78, "verbal": 72, "di": 75 }
```

## Gotchas

1. **Elementor, not GeneratePress** for header/footer — don't use GP theme part selectors
2. **CSS enqueue may be commented out** for intake and settings — verify in PHP before editing CSS
3. **`intval('')` returns 0** in PHP, and `if (0)` is falsy — the save handlers intentionally skip empty values to preserve existing data. Client-side validation must prevent empty submissions.
4. **HTML `<input type="number">` allows 'e'** (scientific notation) — all number inputs have keydown handlers blocking e/E/+/- characters
5. **Browser cache** — chatbox uses `filemtime()` for cache busting; other modules use static theme version which doesn't change on file edits
6. **Login URL** — use `wc_get_page_permalink('myaccount')`, NOT `wp_login_url()`
7. **Logout redirect loops** — home-to-dashboard redirect must guard against WooCommerce logout/my-account pages
8. **xAPI DI lessons** — many Data Insights lessons have empty `xapi_slug`; depends on admin config in Settings → GMAT Study Plan
9. **Dompdf + SVG** — Dompdf's bundled `php-svg-lib` does NOT reliably render the Gurutor brand SVG (text-glyph paths get dropped, only the icon-arc shows). Always use the pre-rendered PNG at `inc/templates/gurutor-logo.png` for PDF output. Do not switch the PDF template's `<img>` back to the SVG.
10. **Dompdf + `box-sizing: border-box`** — not reliably honoured on `<div>` with `width:100%; padding;` (header bleeds past the @page right margin). Use a `<table width="100%">` with cell padding instead. See the `pdf-header` markup in `inc/templates/pdf-analyse-ai.php`.
11. **PDF resource URLs** — always use `gmat_sp_get_pdf_url($relative_path)` (wraps `wp_upload_dir()['baseurl']`). Never hardcode `gurutor.co/wp-content/uploads/...` — staging would link to the live PDFs. Relies on PDFs existing at the same `2026/05/...` path on both environments.
12. **PDF cards skip progress counters** — every progress loop in `gmat-study-plan.php` + `gmat-course-preview.php` MUST guard with `if (gmat_sp_is_pdf_resource($all_keys[$lk])) continue;` BEFORE incrementing totals or calling `gmat_sp_get_status()`. PDFs have no LearnDash ID + no xAPI tracking → they would otherwise pin units at "never complete".

## Design Tokens

| Token | Value |
|-------|-------|
| Primary Blue | `#00409E` |
| Dark Navy | `#002b6b` |
| Orange | `#f68525` / `#FBB03B` |
| Accent Blue | `#4F80FF` |
| Light BG | `#eef3fb` |
| PDF Intro Orange | `#f68525` (bg `#fff7ed`, border `#fed7aa`) — Course Introduction card |
| PDF Test Navy | `#00409E` (bg `#eef3fb`, border `#c7d6ef`) — Practice Test cards |
| PDF QF Teal | `#0d9488` (bg `#f0fdfa`, border `#99f6e4`) — Quant Fundamentals cards |
| Font (intake) | `"Nunito Sans", sans-serif` |
| Font (chatbox) | `"Inter", sans-serif` |

## Additional Context

See `context.md` (in project root) for exhaustive feature documentation including: two-phase Step 1 flow, study plan section/unit/lesson structure, xAPI tracking states, pass/fail signal parsing, lesson accordion descriptions, dashboard readiness calculation formula, chatbox security layers, responsive breakpoints, and full session development history.


