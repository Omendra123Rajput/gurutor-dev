<?php
/**
 * PDF template — AI Coaching Report
 *
 * Rendered by Dompdf. Receives the following from the calling handler:
 *   @var string $logo_svg           Raw <svg>...</svg> markup or empty if logo missing
 *   @var string $student_name       Sanitized student display name
 *   @var string $lesson_label       Sanitized lesson title
 *   @var string $lesson_key         Sanitized lesson key (module identifier)
 *   @var string $report_date        Formatted report date
 *   @var string $coaching_html      wp_kses'd coaching narrative HTML
 *
 * Dompdf supports CSS 2.1 + a subset of CSS 3 (no flexbox/grid).
 * Layout uses block + table for column alignment.
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo esc_html($lesson_label); ?> — AI Coaching Report</title>
<style>
    @page { margin: 28pt 32pt 32pt 32pt; }

    body {
        font-family: "DejaVu Sans", "Helvetica", sans-serif;
        font-size: 10.5pt;
        line-height: 1.55;
        color: #1c2940;
        margin: 0;
        padding: 0;
    }

    /* ---------- Top header band ---------- */
    .pdf-header {
        background-color: #00409E;
        color: #ffffff;
        padding: 10pt 18pt;
        width: 100%;
    }
    .pdf-header__table { width: 100%; border-collapse: collapse; }
    .pdf-header__logo-cell { width: 165pt; vertical-align: middle; padding: 0; }
    .pdf-header__title-cell { vertical-align: middle; text-align: right; padding: 0; }
    .pdf-header__title {
        margin: 0;
        font-size: 16pt;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: 0.3pt;
    }
    .pdf-header__logo {
        display: block;
        width: 150pt;
        height: 34pt;
    }
    .pdf-header__logo svg {
        width: 150pt;
        height: 34pt;
        display: block;
    }
    .pdf-header__logo-fallback {
        font-size: 18pt;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: 1.5pt;
    }

    /* ---------- Performance Report header block ---------- */
    .pdf-report-head {
        padding: 18pt 4pt 14pt 4pt;
        border-bottom: 0.6pt solid #e5ecf6;
        margin-bottom: 14pt;
    }
    .pdf-report-head__type {
        font-size: 8pt;
        font-weight: 700;
        color: #5a6b85;
        letter-spacing: 1.2pt;
        text-transform: uppercase;
        margin: 0 0 6pt 0;
    }
    .pdf-report-head__title {
        margin: 0 0 8pt 0;
        font-size: 18pt;
        font-weight: 800;
        line-height: 1.25;
        color: #002b6b;
    }
    .pdf-report-head__meta {
        font-size: 9pt;
        color: #3d4b66;
    }
    .pdf-report-head__meta strong {
        color: #002b6b;
        font-weight: 700;
    }
    .pdf-report-head__meta-sep {
        display: inline-block;
        width: 14pt;
    }

    /* ---------- Hero PASS / FAIL ---------- */
    .gmat-aai-hero {
        padding: 12pt 14pt;
        background-color: #fdf2ef;
        border-left: 3pt solid #d8442f;
        margin: 0 0 16pt 0;
    }
    .gmat-aai-hero--pass {
        background-color: #eef9f1;
        border-left-color: #1f8a52;
    }
    .gmat-aai-hero__pill {
        display: inline-block;
        background-color: #d8442f;
        color: #ffffff;
        font-size: 8pt;
        font-weight: 700;
        letter-spacing: 1pt;
        padding: 3pt 8pt;
        margin-right: 8pt;
        vertical-align: middle;
    }
    .gmat-aai-hero--pass .gmat-aai-hero__pill {
        background-color: #1f8a52;
    }
    .gmat-aai-hero__msg {
        display: inline;
        margin: 0;
        font-size: 10.5pt;
        line-height: 1.45;
        color: #2c3a52;
        vertical-align: middle;
    }
    .gmat-aai-hero__score-wrap {
        margin-top: 8pt;
        font-size: 9pt;
    }
    .gmat-aai-hero__score {
        font-size: 12pt;
        font-weight: 800;
        color: #d8442f;
        margin-right: 12pt;
    }
    .gmat-aai-hero--pass .gmat-aai-hero__score {
        color: #1f8a52;
    }
    .gmat-aai-hero__threshold {
        font-size: 8.5pt;
        color: #5a6b85;
    }

    /* ---------- Section headers (## numbered) ---------- */
    h3.gmat-aai-section {
        margin: 18pt 0 8pt 0;
        padding: 0 0 5pt 0;
        border-bottom: 0.6pt solid #d8dfeb;
        font-size: 9pt;
        font-weight: 700;
        color: #5a6b85;
        text-transform: uppercase;
        letter-spacing: 1.4pt;
        line-height: 1.3;
    }
    h3.gmat-aai-section .gmat-aai-section__num {
        color: #00409E;
        font-weight: 800;
        margin-right: 8pt;
    }
    h3.gmat-aai-section .gmat-aai-section__title {
        color: #5a6b85;
    }
    h3.gmat-aai-section--instructor {
        border-bottom: none;
        padding: 0;
    }
    h3.gmat-aai-section--instructor .gmat-aai-section__title {
        display: inline-block;
        background-color: #0d1b30;
        color: #ffffff;
        padding: 3pt 8pt;
        font-size: 8.5pt;
        letter-spacing: 1pt;
    }

    /* ---------- Sub-heading (### h4) ---------- */
    h4.gmat-aai-subsection {
        margin: 12pt 0 5pt 0;
        font-size: 11pt;
        font-weight: 800;
        color: #002b6b;
        line-height: 1.3;
    }

    h5 {
        margin: 10pt 0 4pt 0;
        font-size: 10pt;
        font-weight: 700;
        color: #002b6b;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }

    /* ---------- Paragraphs ---------- */
    p {
        margin: 0 0 8pt 0;
        font-size: 10.5pt;
        line-height: 1.55;
        color: #1c2940;
    }
    strong { color: #c25c0d; font-weight: 800; }
    em     { color: #00409E; font-style: italic; }

    /* ---------- Lists ---------- */
    ul, ol {
        margin: 4pt 0 10pt 16pt;
        padding: 0;
        font-size: 10.5pt;
        line-height: 1.55;
    }
    li {
        margin: 0 0 3pt 0;
        padding: 0;
    }

    /* ---------- Tables ---------- */
    .gmat-aai-table-wrap {
        margin: 8pt 0 14pt 0;
        border: 0.6pt solid #e5ecf6;
    }
    table.gmat-aai-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt;
        line-height: 1.4;
        background-color: #ffffff;
    }
    table.gmat-aai-table thead th {
        font-size: 8pt;
        font-weight: 700;
        color: #5a6b85;
        background-color: #f5f8fc;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        padding: 7pt 9pt;
        text-align: left;
        border-bottom: 0.6pt solid #d8dfeb;
    }
    table.gmat-aai-table tbody td {
        padding: 7pt 9pt;
        border-bottom: 0.4pt solid #eef2f8;
        color: #1c2940;
        vertical-align: top;
    }
    table.gmat-aai-table tbody tr.gmat-aai-tr--pass td {
        background-color: #f4faf6;
    }
    .gmat-aai-mark { font-weight: 700; }
    .gmat-aai-mark--ok   { color: #1f8a52; }
    .gmat-aai-mark--fail { color: #d8442f; }

    /* ---------- Footer band ---------- */
    .pdf-footer {
        margin-top: 18pt;
        padding-top: 10pt;
        border-top: 0.6pt solid #e5ecf6;
        font-size: 8pt;
        color: #8492aa;
        text-align: center;
    }
</style>
</head>
<body>

<div class="pdf-header">
    <table class="pdf-header__table">
        <tr>
            <td class="pdf-header__logo-cell">
                <?php if (!empty($logo_svg)) : ?>
                    <div class="pdf-header__logo"><?php echo $logo_svg; // raw SVG — read from theme file, not user input ?></div>
                <?php else : ?>
                    <span class="pdf-header__logo-fallback">GURUTOR</span>
                <?php endif; ?>
            </td>
            <td class="pdf-header__title-cell">
                <h1 class="pdf-header__title">AI Coaching Report</h1>
            </td>
        </tr>
    </table>
</div>

<div class="pdf-report-head">
    <p class="pdf-report-head__type">Performance Report</p>
    <h2 class="pdf-report-head__title"><?php echo esc_html($lesson_label); ?></h2>
    <div class="pdf-report-head__meta">
        <strong>Student:</strong> <?php echo esc_html($student_name); ?>
        <span class="pdf-report-head__meta-sep"></span>
        <strong>Module:</strong> <?php echo esc_html($lesson_key); ?>
        <span class="pdf-report-head__meta-sep"></span>
        <strong>Date:</strong> <?php echo esc_html($report_date); ?>
    </div>
</div>

<div class="pdf-coaching">
    <?php
    // Coaching HTML is already wp_kses'd server-side by gmat_analyse_ai_format_markdown()
    // and re-sanitised in the download handler before reaching this template.
    echo $coaching_html;
    ?>
</div>

<div class="pdf-footer">
    Generated by Gurutor &middot; <?php echo esc_html($report_date); ?>
</div>

</body>
</html>
