# Gurutor GMAT Platform — AI Context File

> **Last updated:** 2026-02-27
> **Purpose:** Share with any AI model to resume development without context loss.
> **Project root:** `C:\Users\orajp\Desktop\G9\Gurutor Staging\Revamp\generatepress-child\`
> **Staging URL:** `https://stg-gurutor-test.kinsta.cloud/`

---

## 1. Project Overview

Gurutor is a **GMAT test prep platform** built on WordPress. It uses a GeneratePress child theme (no build tools — vanilla PHP/JS/CSS/jQuery) with the following integrations:

| Tool | Purpose |
|---|---|
| **GeneratePress** | Parent theme |
| **Elementor** | Page builder — handles site header/footer (NOT GeneratePress theme parts) |
| **WooCommerce + Subscriptions** | Payments — product IDs 7008, 7009 |
| **LearnDash LMS** | Course delivery — Course 8112 (paid), 7472 & 9361 (free trial) |
| **GrassBlade xAPI LRS** | Lesson completion tracking via xAPI statements |

### Key Concept: Paid vs Free Users
- `gurutor_user_has_active_paid_access()` — checks WooCommerce Subscriptions
- Free trial users get courses 7472/9361; paid users get course 8112
- Paid users must complete an intake wizard before accessing course content

---

## 2. File Structure

```
generatepress-child/
├── functions.php                    # Includes array + theme enqueues
├── style.css                        # Theme metadata
├── inc/
│   ├── free-trial-grassblade-xapi.php   # xAPI + free trial logic
│   ├── gurutor-thankyou-shortcodes.php  # Thank-you page shortcodes
│   ├── gmat-intake-form.php             # ★ Intake wizard (5-step onboarding)
│   ├── gmat-settings-account.php        # GMAT settings in My Account
│   ├── gmat-chatbox.php                 # ★ AI Chatbox (floating assistant widget)
│   ├── gmat-study-plan-admin.php        # Admin: lesson ID + xAPI URL mapping
│   ├── gmat-study-plan.php              # Dynamic study plan (overrides course 8112)
│   └── gmat-dashboard.php               # Dashboard (paid user homepage)
├── js/
│   ├── gmat-intake.js                   # ★ Intake wizard JS (jQuery)
│   ├── gmat-chatbox.js                  # ★ AI Chatbox JS (jQuery)
│   ├── gmat-study-plan.js               # Study plan accordion/interactions
│   ├── gmat-settings.js                 # Settings page JS
│   ├── gurutor-custom.js                # Global custom JS
│   └── jquery-stickyNavigator.js        # Sticky nav plugin
├── css/
│   ├── gmat-intake.css                  # ★ Intake wizard styles
│   ├── gmat-chatbox.css                 # ★ AI Chatbox styles (BEM)
│   ├── gmat-dashboard.css               # Dashboard styles
│   ├── gmat-study-plan.css              # Study plan styles
│   ├── gmat-settings.css                # Settings page styles
│   ├── gurutor-custom.css               # Global custom styles
│   └── global.css                       # Global overrides
└── woocommerce/                         # WooCommerce template overrides
```

---

## 3. functions.php — Active Includes

```php
$custom_includes = array(
    'inc/free-trial-grassblade-xapi.php',
    'inc/gurutor-thankyou-shortcodes.php',
    'inc/gmat-intake-form.php',
    'inc/gmat-settings-account.php',
    'inc/gmat-chatbox.php',                     // ★ AI Chatbox widget
    // 'inc/gmat-study-plan-admin.php',         // COMMENTED OUT
    // 'inc/gmat-study-plan.php',               // COMMENTED OUT
    // 'inc/gmat-dashboard.php',                // COMMENTED OUT
);
```

**IMPORTANT:** Study plan, study plan admin, and dashboard are currently **commented out**. They get toggled on/off during development. The chatbox is active and loads on course 8112 only.

---

## 4. Feature Details

### 4A. Intake Wizard (`gmat-intake-form.php` + `gmat-intake.js` + `gmat-intake.css`)

**Shortcode:** `[gmat_intake_form]` on page `/gmat-intake/`

**5-step onboarding wizard:**

| Step | Field | User Meta Key | Validation |
|---|---|---|---|
| 1 | Previous scores (official + practice) | `_gmat_intake_official_scores`, `_gmat_intake_practice_scores`, `_gmat_intake_step1_done` | Optional (skippable) |
| 2 | Goal score | `_gmat_intake_goal_score` | 205-805, required |
| 3 | Weekly study hours | `_gmat_intake_weekly_hours` | Select 5-40, required |
| 4 | Next test date | `_gmat_intake_next_test_date` | Future date, required |
| 5 | Section preference | `_gmat_intake_section_preference` | 'quant' or 'verbal', required |

**Completion:** Sets `_gmat_intake_completed = 1` and `_gmat_intake_completed_at = timestamp`

**Step 1 — Two-Phase Flow:**
- **Phase 1** ("Tell Us About Your GMAT Experience"): Two cards — Official Test Scores / Practice Test Scores with "Add Score" buttons
- **Phase 2** ("Update Us On Your Most Recent GMAT Performance"): Score entry form with 5 fields (date, overall 205-805, quant 60-90, verbal 60-90, DI 60-90)
- Save in Phase 2 → commits score to array → returns to Phase 1
- Skip in Phase 1 → saves to server → advances to Step 2
- Previous in Phase 2 → returns to Phase 1 (not previous step)

**Score data format:** JSON arrays stored as user meta. Each entry:
```json
{ "date": "2025-01-15", "overall": 645, "quant": 78, "verbal": 72, "di": 75 }
```
> **Critical:** Score field is `overall` (NOT `total`)

**Step 5 — Section Preference:**
- Title: "Choose Your Starting Section"
- Description paragraphs about starting with comfortable section, free trial progress preservation, DI coming last
- Cards: "Quant Study Plan" (Quant ⟶ Verbal ⟶ Data Insights) and "Verbal Study Plan" (Verbal ⟶ Quant ⟶ Data Insights)

**AJAX endpoints:**
- `gmat_intake_save_scores` (Step 1)
- `gmat_intake_save_goal` (Step 2)
- `gmat_intake_save_hours` (Step 3)
- `gmat_intake_save_test_date` (Step 4)
- `gmat_intake_save_preference` (Step 5 — also sets completion)

**Redirect behavior:**
- Paid users who haven't completed intake → redirected from course pages to `/gmat-intake/`
- After completion → redirected to dashboard (if active) or course page
- Non-logged-in users → shown login link to WooCommerce My Account page

**Page customizations:**
- Header/footer hidden via CSS targeting Elementor classes (not GeneratePress)
- Minimal logo bar at top
- Body class `page-gmat-intake` injected via `body_class` filter
- Page title hidden via `generate_show_title` filter

**CSS enqueue status:** The `wp_enqueue_style()` for intake CSS is currently **COMMENTED OUT** in gmat-intake-form.php (lines 36-41). CSS may be loaded via Elementor or another method. Verify before making CSS changes.

**Key Elementor selectors for hiding header/footer:**
```css
.page-gmat-intake .elementor-location-header,
.page-gmat-intake .elementor-location-footer,
.page-gmat-intake header.elementor[data-elementor-type="header"],
.page-gmat-intake footer.elementor[data-elementor-type="footer"],
.page-gmat-intake .elementor-sticky__spacer { display: none !important; }
```

### 4B. Dashboard (`gmat-dashboard.php` + `gmat-dashboard.css`)

**Shortcode:** `[gmat_dashboard]` on page `/gmat-dashboard/`

**Sections:**
1. Hero banner with user's first name
2. GMAT Snapshot (5 cards): Goal Score, Test Date, Course Completion %, Weekly Study Time, Estimated Readiness
3. Course Progress Breakdown (3 progress bars): Verbal, Quant, Data Insights
4. Bottom 2-column: Study Plan CTA + Quote | Readiness & Planning Metrics

**Estimated Readiness Calculation:**
```
starting_score = user's latest score OR 555 (GMAT mean)
points_to_improve = goal_score - starting_score (if gap exists)
baseline_practice_tests = 3
additional_practice_tests = floor((points_to_improve - 100) / 50) if > 100pts
required_practice_tests = baseline + additional
weeks_for_content = ceil(remaining_minutes / (weekly_hours * 60))
extra_weeks = additional_practice_tests * 2
total_weeks = weeks_for_content + extra_weeks
estimated_readiness = today + total_weeks
```

**Navigation modifications:**
- "Home" nav item → points to dashboard for paid users with completed intake
- Home page (front_page) → redirects to dashboard for paid users

**Logout handling:**
- Guards in `gmat_dash_redirect_home_to_dashboard()` for WooCommerce logout/my-account pages
- `logout_redirect` filter at priority 99 → forces `home_url('/')` after logout

### 4C. Study Plan (`gmat-study-plan.php` + `gmat-study-plan-admin.php`)

**Overrides:** LearnDash course 8112 content with dynamic study plan

**Plan structure:** Sections → Units → Learn/Practice/Review arrays of lesson keys

**Two plan orders:**
- `gmat_sp_build_verbal_first()`: Verbal → Quant → Data Insights
- `gmat_sp_build_quant_first()`: Quant → Verbal → Data Insights

**xAPI tracking (3-state):**
- `completed` — xAPI verb: completed (with result.completion=true)
- `in-progress` — xAPI verb: attempted
- `not-started` — no xAPI statements found

**Performance:** Only 2 API calls per page load (batch fetch all completed + attempted), then static cache via `gmat_sp_fetch_xapi_data()` which returns both `status_map` and `pass_fail` signals

**Pass/fail signal parsing (v5):**
- xAPI completed statements may contain JSON-encoded pass/fail variables in the object name field
- Format: `{"CR_Exercise_4_Pass_or_Fail": "Fail"}` in `$stmt['object']['definition']['name']['en-US']`
- Parsed automatically during the completed statements fetch (no extra API calls)
- `gmat_sp_get_pass_fail_map($user_id)` returns `variable_name => "Pass"|"Fail"` map
- `gmat_sp_get_pass_fail_variable_map()` maps ~50 variable names to lesson keys (CR exercises, verbal reviews, quant lessons, granular QLE_* signals)
- Granular quant exercise failures: `QLE_N_TOPIC_Pass_or_Fail` maps to specific lesson keys (e.g., `QLE_1_ALG1_Pass_or_Fail` → `algebra_1`)

**Suggestion logic (v6 — no fallback heuristics, pass/fail signals only):**
- `gmat_sp_get_exercise_result($user_id, $lesson_key)` — returns `'fail'` | `'pass'` | `'none'` (3-state, no fallback)
- `gmat_sp_get_review_result($user_id, $review_key)` — returns `'fail'` (any variable failed) | `'pass'` (all passed) | `'none'` (no signals)
- `gmat_sp_get_quant_exercise_failures($user_id, $exercise_num, $learn_keys, $ids)` — returns only explicit QLE_* failures, no fallback
- **Rule:** Only explicit Pass/Fail xAPI signals trigger suggestions. "Attempted-not-complete" or "not-started" NEVER triggers suggestions.
- **Removed:** `gmat_sp_should_suggest_review()`, `gmat_sp_is_review_failed_or_incomplete()`, `gmat_sp_is_attempted_not_complete()` — all had fallback heuristics
- **Cross-suggest (3-state):** If review result is `'fail'` → show remediation links. If `'pass'` → show just review link. If `'none'` → no cross-suggest at all.
- Each unit has a `'description'` field rendered as a brief text at the top of the expanded unit body

**Smart/curly quotes sanitization (v7):**
- xAPI content authoring tools (e.g., Articulate Storyline) embed curly/smart quotes (`"` U+201C, `"` U+201D) instead of straight quotes in JSON object names
- `gmat_sp_fetch_xapi_data()` applies 4-layer sanitization before `json_decode()`: strip BOM, replace smart quotes → straight, `mb_convert_encoding()` fix, regex fallback extraction
- Without this fix, `json_decode()` fails with `JSON_ERROR_UTF8` (error code 4)

**Suggested lesson cards (v7):**
- Units have a `'suggested_lessons'` field — associative array mapping `lesson_key => suggestion_text`
- When an exercise fails (e.g., CR Exercise 4), the suggestion text moves to the NEXT unit's `suggested_lessons` (e.g., `cr_lesson_5` in Unit 4)
- The old orange "Suggested areas of focus" box (`'suggest'` field) is set to `''` for affected units
- Renderer checks `$unit['suggested_lessons'][$lk]` and applies:
  - `.gmat-sp-lesson--suggested` class — amber background (`#fffbeb`), amber border, orange left bar + number badge
  - "Suggested" orange pill badge (`.gmat-sp-lesson__suggested-badge`)
  - Suggestion text replaces normal lesson description in accordion, prefixed with "Areas to focus on:"
- CSS: amber/orange theme consistent with existing suggest box palette

**Lesson-level accordion (v5):**
- Each lesson card is expandable (click to expand/collapse) showing a description of what the lesson covers
- Descriptions stored as `'desc'` field in `gmat_sp_get_lesson_keys()` in `gmat-study-plan-admin.php`
- Formatted via `gmat_sp_format_description()` — multi-line text becomes an HTML `<ul>` list
- CSS: `.gmat-sp-lesson__desc` with max-height transition, `.gmat-sp-lesson__expand-icon` chevron
- JS: Click handler on `.gmat-sp-lesson` toggles `.open` class (ignores clicks on links/buttons)

**Debug page:** Add `?gmat_sp_debug_xapi=1` to course URL (admin only) — shows xAPI activity statuses, lesson key mappings, pass/fail signals, exercise results (3-state), review results (3-state), and QLE granular failures

**Lesson key format:** e.g., `cr_lesson_1`, `quant_exercise_3`, `verbal_review_2`
**xAPI activity ID format:** `http://www.uniqueurl.com/{xapi_slug}`
**DI lessons:** Many have empty xapi_slug — use admin-configurable URLs from Settings > GMAT Study Plan

### 4E. AI Chatbox (`gmat-chatbox.php` + `gmat-chatbox.js` + `gmat-chatbox.css`)

**Type:** Floating widget rendered via `wp_footer` hook on course 8112 only

**Architecture:** `Chat UI → WP AJAX (proxy) → FastAPI on AWS EC2 → AWS Bedrock (Claude) → Response → UI`

**Access control:** Only visible to logged-in paid users (`gurutor_user_has_active_paid_access()`) on LearnDash course 8112

**wp-config.php constants (admin must add):**
```php
define('GMAT_CHATBOX_API_URL', 'https://<ec2-domain>/api/v1/chat');
define('GMAT_CHATBOX_API_KEY', 'GURUTOR_SECRET_KEY');
```

**Internal constants:**
- `GMAT_CHATBOX_COURSE_ID` = 8112
- `GMAT_CHATBOX_RATE_LIMIT` = 20 msg/60s per user
- `GMAT_CHATBOX_API_TIMEOUT` = 30s
- `GMAT_CHATBOX_MAX_MSG_LENGTH` = 2000 chars

**AJAX endpoint:** `wp_ajax_gmat_chatbox_send`
- Nonce: `gmat_chatbox_nonce`
- Rate limiting: transient `gmat_cb_rate_{user_id}`
- Proxies to external API via `wp_remote_post()`
- Response sanitized with `wp_kses_post()`

**Request JSON (WP → FastAPI):**
```json
{ "user_id": "wp_user_123", "session_id": "gs_...", "message": "...", "key": "SECRET" }
```

**Response JSON (FastAPI → WP):**
```json
{ "reply": "...", "status": "success", "key": "SECRET", "timestamp": "ISO8601" }
```

**Session management:** `sessionStorage` (per-tab, clears on tab close)
- Session ID format: `gs_<base36_timestamp>_<random8>`
- Messages stored in-memory + sessionStorage (max 100)

**UI (Premium v2):** Floating FAB (bottom-right, 60px, pulse ring on load) → opens chat panel (400×600px desktop, full-width bottom drawer on mobile)
- BEM naming: `.gmat-cb__*`, Inter font
- Spring-eased panel animation (`cubic-bezier(0.16, 1, 0.3, 1)`)
- Header: gradient navy, frosted glass avatar ring, AI robot SVG, breathing green status dot, ghost action buttons
- Messages: user bubbles (blue gradient, right), assistant (white + border, left), welcome tags as inline chips
- Typing indicator: avatar + 3 dots + "AI is thinking…" text
- Input: auto-resize textarea, focus-within glow ring, send button with press state
- Mobile: full-width bottom drawer (85vh), overlay backdrop with blur, drag handle, safe-area padding
- Keyboard: Enter=send, Shift+Enter=newline, Escape=close
- 4 responsive breakpoints: 767px, 480px, 360px + `prefers-reduced-motion`

**Security layers:** Nonce, auth, paid access, rate limiting, input sanitization, output sanitization (`wp_kses_post`), API key isolation, XSS prevention (user=`.text()`, AI=`.html()`), SSL verification

### 4F. Admin Settings (`gmat-study-plan-admin.php`)

**WP Admin:** Settings > GMAT Study Plan

Two configuration areas:
1. **Lesson IDs:** Map lesson keys → LearnDash post IDs (defaults hardcoded, admin can override)
2. **xAPI URLs for DI:** Paste full xAPI activity URLs for DI lessons not yet available

---

## 5. Design System

| Token | Value |
|---|---|
| Primary Blue | `#00409E` |
| Dark Navy | `#002b6b` |
| Orange | `#f68525` / `#FBB03B` |
| Accent Blue | `#4F80FF` |
| Light BG | `#eef3fb` |
| Text Dark | `#222222` / `#1e293b` |
| Text Muted | `#54595F` / `#64748b` |
| Border | `#e4e8ee` / `#e2e8f0` |
| Font | `"Nunito Sans", Sans-serif` (intake), system stack (dashboard) |

**Intake wizard:** max-width 1140px, white step panels with 20px radius, progress bar with 56px circles (active = blue border, completed = dark navy), buttons rounded 30px

**Dashboard:** Full-width hero, 1100px body, 12px radius cards, grid-based layout

---

## 6. Responsive Breakpoints

**Intake wizard:**
| Breakpoint | Changes |
|---|---|
| ≤1160px | Wizard padding: 40px 20px |
| 768-1024px (tablet) | Progress items max-width 100px, circles 40x40, progress line margin-top 19px |
| ≤810px–768px | Score card desc height:44px |
| ≤767px (mobile) | Progress labels hidden, circles 36x36, items min-width:auto, score fields stack, buttons stack, score cards/preference cards column layout, wizard padding 20px 12px |

**Dashboard:**
| Breakpoint | Changes |
|---|---|
| ≤992px | Snapshot 3-col, bottom layout 1-col |
| ≤768px | Snapshot 2-col, progress 1-col, CTA stacks |
| ≤480px | Snapshot 1-col, smaller text sizes |

---

## 7. Known Issues & Gotchas

1. **CSS enqueue commented out:** `wp_enqueue_style('gmat-intake', ...)` in gmat-intake-form.php is commented out. CSS may load via Elementor custom CSS or separate enqueue.

2. **Includes toggling:** Dashboard, study plan, study plan admin, and settings includes in functions.php get commented/uncommented during development. Always check their active state.

3. **Elementor vs GeneratePress:** Site uses Elementor for header/footer templates, NOT GeneratePress theme elements. CSS selectors must target `elementor-location-header/footer` and `data-elementor-type` attributes. The `elementor-sticky__spacer` duplicate div also needs hiding.

4. **Body class injection:** WordPress doesn't auto-add `page-gmat-intake` or `page-gmat-dashboard` to body. Both files have explicit `body_class` filter hooks.

5. **Score field name:** Intake form saves as `overall` (not `total`). Dashboard reads `$last['overall']`.

6. **Login URL:** Non-logged-in users use `wc_get_page_permalink('myaccount')` (dynamic WooCommerce URL), NOT `wp_login_url()`.

7. **Logout redirect loop:** The home-to-dashboard redirect must guard against WooCommerce logout/my-account pages to prevent redirect loops.

8. **xAPI DI lessons:** Most Data Insights lessons have empty xapi_slug — tracking depends on admin entering URLs in WP Admin > Settings > GMAT Study Plan.

---

## 8. User Meta Keys Reference

| Key | Type | Source |
|---|---|---|
| `_gmat_intake_official_scores` | JSON string | Intake Step 1 |
| `_gmat_intake_practice_scores` | JSON string | Intake Step 1 |
| `_gmat_intake_step1_done` | '1' | Intake Step 1 |
| `_gmat_intake_goal_score` | int | Intake Step 2 |
| `_gmat_intake_weekly_hours` | int | Intake Step 3 |
| `_gmat_intake_next_test_date` | 'Y-m-d' | Intake Step 4 |
| `_gmat_intake_section_preference` | 'quant'\|'verbal' | Intake Step 5 |
| `_gmat_intake_completed` | '1' | Intake Step 5 |
| `_gmat_intake_completed_at` | timestamp | Intake Step 5 |

---

## 9. WordPress Constants & IDs

| Constant/ID | Value | Purpose |
|---|---|---|
| `FREE_TRIAL_PRODUCT_ID` | 7006 | WooCommerce product |
| `FREE_TRIAL_COURSE_ID` | 7472 | LearnDash free trial course |
| Free trial course 2 | 9361 | Another free trial course |
| `GMAT_SP_COURSE_ID` | 8112 | Paid LearnDash course |
| `GMAT_INTAKE_PAGE_SLUG` | 'gmat-intake' | Intake page |
| `GMAT_DASHBOARD_PAGE_SLUG` | 'gmat-dashboard' | Dashboard page |
| Paid product IDs | 7008, 7009 | WooCommerce subscription products |
| `GMAT_CHATBOX_COURSE_ID` | 8112 | Course where chatbox appears |
| `GMAT_CHATBOX_API_URL` | (wp-config.php) | FastAPI backend URL |
| `GMAT_CHATBOX_API_KEY` | (wp-config.php) | Shared secret key |

---

## 10. Development Workflow

- **No build tools** — edit PHP/JS/CSS directly
- **Kinsta staging** — changes uploaded to staging site for testing
- **Theme:** GeneratePress child theme
- **jQuery required** — intake JS uses jQuery (enqueued with `jquery` dependency)
- **PHP 7.4+ compatible** — no PHP 8-only features used
- **Testing:** Manual on staging site, no automated tests

---

## 11. Pending / Future Work

- **AI Chatbox:** Currently connected to ngrok endpoint for testing. Production `GMAT_CHATBOX_API_URL` and `GMAT_CHATBOX_API_KEY` must be updated in wp-config.php for live deployment.
- DI lessons xAPI URLs need to be configured by admin as content becomes available
- Study plan may need UI refinements based on user testing
- Dashboard metrics may need adjustment based on course content changes

---

## 12. Session History Summary

**Sessions 1-2:** Built GMAT intake form wizard (5 steps), GMAT Settings in WooCommerce, dynamic study plan with UI iterations.

**Session 3:** xAPI tracking implementation, slug mapping fixes, dynamic conditional reviews/suggestions, mobile responsive fixes.

**Session 4:** Fixed xAPI slug values with user-provided URLs, created admin fields for missing DI lesson URLs, implemented dashboard page, fixed logout redirect issue.

**Session 5:**
1. Uncommented includes in functions.php
2. Fixed logout redirect loop (WooCommerce guards + logout_redirect filter)
3. New estimated readiness calculation (practice tests based on score gap)
4. Redesigned intake Step 1 as two-phase flow
5. Hid header/footer on intake page (Elementor selectors + body_class filter)
6. Fixed login URL (wp_login_url → wc_get_page_permalink)
7. Updated Step 5 text ("Choose Your Starting Section" + card descriptions)
8. Fixed mobile progress bar (min-width:auto override)

**Session 6:**
1. Created context.md for AI model context sharing
2. Ran code review (identified 39 issues across all files)
3. Built AI Chatbox widget (3 new files: gmat-chatbox.php, gmat-chatbox.js, gmat-chatbox.css)
   - Floating FAB → chat panel with real-time messaging
   - WP AJAX proxy to external FastAPI on AWS EC2
   - Rate limiting, nonce, paid access, input/output sanitization
   - BEM CSS, responsive (mobile drawer), keyboard accessible
   - Session management via sessionStorage

**Session 7 (Current):**
1. Chatbox API connected: `GMAT_CHATBOX_API_URL` set to ngrok endpoint, chatbox working end-to-end
2. Premium UI/UX overhaul for chatbox (all 3 files rewritten):
   - **CSS:** Inter font, spring-eased animations (`cubic-bezier(0.16, 1, 0.3, 1)`), FAB single pulse ring on load, frosted glass header avatar, breathing green status dot, user bubbles with subtle gradient, welcome message topic tags (inline chips), improved shadow hierarchy, badge pop animation, mobile bottom drawer with drag handle, `env(safe-area-inset-bottom)` for iPhone home bar, `prefers-reduced-motion` support, Firefox scrollbar support, 4 responsive breakpoints (767/480/360px)
   - **PHP:** Updated typing indicator with avatar + content wrapper, AI robot SVG icon (consistent across header/messages/typing), cleaner FAB chat icon
   - **JS:** Shared `AI_AVATAR_SVG` constant for consistency, badge pop re-trigger on new messages (reflow hack), char count warn at 80% (was 90%)
3. Fixed chatbox UX issues:
   - **Input border:** Removed browser-default focus outline/border on textarea with `!important` overrides for theme conflicts
   - **Placeholder alignment:** Changed `align-items: flex-end` → `center` on input-wrap, increased textarea min-height to 36px with padding for vertical centering
   - **AI response formatting:** Added `gmat_chatbox_format_reply()` PHP function that converts plain-text AI responses to structured HTML — detects bullet points (•/-/*), numbered lists, section headers (lines ending with `:` under 80 chars), **bold**, *italic*, `code` markdown. If response already contains HTML tags, passes through untouched. All output runs through `wp_kses_post()` sanitization.

**Session 8 (Study Plan v5 — Feb 2026):**
1. **Lesson-level accordion:** Each lesson card now expands on click to show a description of what the lesson covers. Descriptions stored as `'desc'` field in `gmat_sp_get_lesson_keys()`. Formatted as `<ul>` lists via `gmat_sp_format_description()`. CSS max-height transition, chevron icon rotates on open.
2. **xAPI pass/fail signal parsing:** Refactored `gmat_sp_get_xapi_status_map()` into `gmat_sp_fetch_xapi_data()` which returns both status map and pass/fail signals. Pass/fail signals are JSON-encoded in xAPI completed statement object names (e.g., `{"CR_Exercise_4_Pass_or_Fail": "Fail"}`). ~50 variable-to-lesson-key mappings in `gmat_sp_get_pass_fail_variable_map()`.
3. **Dynamic suggestion logic:** Replaced old `gmat_sp_is_attempted_not_complete()` heuristic with `gmat_sp_should_suggest_review()` which checks actual pass/fail signals first, then falls back to the old heuristic. Granular quant exercise failures via `gmat_sp_get_quant_exercise_failures()` using `QLE_*` prefix variables. Verbal review cross-suggest via `gmat_sp_is_review_failed_or_incomplete()`.
4. **Unit descriptions:** Each unit in both verbal-first and quant-first builders has a `'description'` field rendered as brief text at the top of the expanded unit body.
5. **Debug page enhanced:** `?gmat_sp_debug_xapi=1` now shows pass/fail signals and suggestion logic results in addition to xAPI activity statuses.
6. **Files modified:** `inc/gmat-study-plan.php`, `inc/gmat-study-plan-admin.php`, `css/gmat-study-plan.css`, `js/gmat-study-plan.js`

**Session 9 (Study Plan v6 — Suggestion Logic Fix — Feb 2026):**
1. **Removed all fallback heuristics from suggestion logic.** User rule: "Do not assume attempted and completed as pass or failed." Only explicit Pass/Fail xAPI signals now trigger suggestions.
2. **New 3-state result functions:** `gmat_sp_get_exercise_result()` and `gmat_sp_get_review_result()` return `'fail'`|`'pass'`|`'none'`. Replaced `gmat_sp_should_suggest_review()`, `gmat_sp_is_review_failed_or_incomplete()`, and `gmat_sp_is_attempted_not_complete()`.
3. **Hardened pass/fail parsing:** Added `trim()` to pass/fail values (handles trailing whitespace). Fixed `result: {}` (empty object) edge case — now treated as valid completion (`|| empty($stmt['result'])`).
4. **Updated all suggestion logic in both verbal-first and quant-first builders** (Units 3-6 verbal CR exercises, Units 3-6 quant cross-suggests, Units 3-6 verbal cross-suggests in quant-first path).
5. **Cross-suggest now 3-state:** `'fail'` → full remediation links, `'pass'` → just the review link, `'none'` → no cross-suggest at all (previously "none" still showed cross-suggest).
6. **Removed DI suggestion logic** — DI modules no longer show "Suggested areas of focus" boxes (not needed per docs).
7. **Topic names in lesson cards:** Added `'topic'` field to all lesson keys in admin.php, rendered as `<span class="gmat-sp-lesson__topic">`. Improved CSS visibility (darker color, heavier font weight).
8. **Debug page enhanced:** Now shows exercise results (3-state), review results (3-state), and QLE granular failures instead of old suggestion logic output.
9. **Files modified:** `inc/gmat-study-plan.php`, `css/gmat-study-plan.css`

**Session 10 (Study Plan v7 — Smart Quotes Fix + Suggested Lesson Cards — Feb 2026):**
1. **Fixed curly/smart quotes breaking JSON parsing.** xAPI content authoring tools (Articulate Storyline) embed `"` (U+201C) and `"` (U+201D) instead of straight `"` in object name JSON. `json_decode()` failed with `JSON_ERROR_UTF8`. Fixed with 4-layer sanitization in `gmat_sp_fetch_xapi_data()`: strip BOM, replace smart quotes → straight, `mb_convert_encoding()`, regex fallback.
2. **Moved suggestion text into suggested lesson cards.** Replaced the separate orange "Suggested areas of focus" box with inline suggested lesson styling:
   - Each unit now has `'suggested_lessons'` field — associative array mapping `lesson_key => suggestion_text`
   - When exercise fails (e.g., CR Exercise 4), suggestion text moves to the NEXT unit's `suggested_lessons` (e.g., `cr_lesson_5` in Unit 4 Review)
   - Old orange box (`'suggest'` field) set to `''` for affected units
3. **Suggested lesson card styling:** Amber background (`#fffbeb`), amber border (`#fde68a`), orange left bar + number badge (`#f68525`), "SUGGESTED" orange pill badge, accordion shows "Areas to focus on:" + suggestion text. CSS class: `.gmat-sp-lesson--suggested`.
4. **Updated both verbal-first and quant-first builders** (Units 3-6) with `suggested_lessons` field.
5. **Removed temporary footer debug function** (`gmat_sp_debug_footer`) — was used to diagnose the smart quotes issue.
6. **Files modified:** `inc/gmat-study-plan.php`, `css/gmat-study-plan.css`
