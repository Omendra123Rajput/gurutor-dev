# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Gurutor** — A GMAT test prep platform built as a **WordPress GeneratePress child theme**. No build tools, bundlers, or package managers. All PHP/JS/CSS is vanilla and served directly.

**Stack:** WordPress, GeneratePress (parent theme), Elementor (header/footer only), WooCommerce + Subscriptions, LearnDash LMS, GrassBlade xAPI LRS, jQuery

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

### Active Includes (functions.php)

All includes are toggled in `functions.php` lines 21-30. Some may be commented out during development — always check before assuming a feature is active.

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
| Free trial course 2 | 9361 | Second free trial course |
| `GMAT_SP_COURSE_ID` | 8112 | Paid LearnDash course |
| Paid product IDs | 7008, 7009 | WooCommerce subscription products |
| `GMAT_CHATBOX_API_URL` | wp-config.php | FastAPI backend URL (AWS EC2) |
| `GMAT_CHATBOX_API_KEY` | wp-config.php | Shared secret for API auth |

## Gotchas

1. **Elementor, not GeneratePress** for header/footer — don't use GP theme part selectors
2. **CSS enqueue may be commented out** for intake and settings — verify in PHP before editing CSS
3. **`intval('')` returns 0** in PHP, and `if (0)` is falsy — the save handlers intentionally skip empty values to preserve existing data. Client-side validation must prevent empty submissions.
4. **HTML `<input type="number">` allows 'e'** (scientific notation) — all number inputs have keydown handlers blocking e/E/+/- characters
5. **Browser cache** — chatbox uses `filemtime()` for cache busting; other modules use static theme version which doesn't change on file edits
6. **Login URL** — use `wc_get_page_permalink('myaccount')`, NOT `wp_login_url()`
7. **Logout redirect loops** — home-to-dashboard redirect must guard against WooCommerce logout/my-account pages
8. **xAPI DI lessons** — many Data Insights lessons have empty `xapi_slug`; depends on admin config in Settings → GMAT Study Plan

## Design Tokens

| Token | Value |
|-------|-------|
| Primary Blue | `#00409E` |
| Dark Navy | `#002b6b` |
| Orange | `#f68525` / `#FBB03B` |
| Accent Blue | `#4F80FF` |
| Light BG | `#eef3fb` |
| Font (intake) | `"Nunito Sans", sans-serif` |
| Font (chatbox) | `"Inter", sans-serif` |

## Additional Context

See `context.md` (in project root) for exhaustive feature documentation including: two-phase Step 1 flow, study plan section/unit/lesson structure, xAPI tracking states, dashboard readiness calculation formula, chatbox security layers, responsive breakpoints, and full session development history.
