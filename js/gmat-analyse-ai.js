(function($) {
    'use strict';

    if (typeof gmatAnalyseAI === 'undefined') return;

    var config = gmatAnalyseAI;
    var pollTimer = null;
    var pollCount = 0;
    var MAX_POLLS = 20;
    var POLL_INTERVAL = 30000; // 30s

    var BTN_LABEL_DEFAULT = 'Analyze with AI';
    var BTN_LABEL_VIEW    = 'View AI Report';

    var $body  = null;
    var $modal = null;
    var escHandler = null;
    var latestReport = null;     // cached after each successful analysis — used by Download Report
    var latestSessionId = null;  // session_id of the cached report
    var downloadTimer = null;
    var activeXhr = null;        // in-flight send_data request — aborted on modal close
    var statusTimer = null;      // rotates loading status messages
    var retryTimer = null;       // pending auto-retry after a 202 "still generating" response
    var generatingRetries = 0;   // 202 retries used for the current analysis
    var MAX_GENERATING_RETRIES = 3;

    var LOADING_STATUSES = [
        'Collecting your attempt data…',
        'Analysing your responses with AI…',
        'Identifying strengths and focus areas…',
        'Writing your personalised coaching report…',
        'Almost there — finalising your report…'
    ];

    function generateSessionId() {
        return 'gs_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 8);
    }

    function init() {
        var iframe = document.querySelector('.grassblade iframe.grassblade_iframe');
        if (!iframe) return;

        var grassbladeContainer = iframe.closest('.grassblade');
        if (!grassbladeContainer) return;

        $body = $('body');

        var label = config.hasCachedReport ? BTN_LABEL_VIEW : BTN_LABEL_DEFAULT;

        var wrapper = document.createElement('div');
        wrapper.className = 'gmat-analyse-ai';
        wrapper.style.display = 'none';
        wrapper.innerHTML =
            '<button class="gmat-analyse-ai__btn" type="button">' +
                '<svg class="gmat-analyse-ai__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M12 2a4 4 0 0 1 4 4c0 1.95-1.4 3.58-3.25 3.93L12 22"/>' +
                    '<path d="M8 6a4 4 0 0 1 8 0"/>' +
                    '<path d="M5 10c0-1 .4-1.9 1-2.6"/>' +
                    '<path d="M18 10c0-1-.4-1.9-1-2.6"/>' +
                    '<circle cx="12" cy="6" r="1"/>' +
                '</svg>' +
                ' <span class="gmat-analyse-ai__label">' + label + '</span>' +
            '</button>';

        var backCta = grassbladeContainer.parentNode.querySelector('.gurutor-back-to-course');
        if (backCta) {
            backCta.parentNode.insertBefore(wrapper, backCta.nextSibling);
        } else {
            grassbladeContainer.parentNode.insertBefore(wrapper, grassbladeContainer.nextSibling);
        }

        $(wrapper).on('click', '.gmat-analyse-ai__btn', handleClick);

        if (config.hasCachedReport) {
            $(wrapper).show();
        } else {
            checkCompletion(wrapper);
        }
    }

    function checkCompletion(wrapper) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'gmat_analyse_ai_check_completion',
                nonce: config.nonce,
                post_id: config.postId
            },
            success: function(res) {
                if (res.success && res.data.completed) {
                    showButton(wrapper);
                } else {
                    startPolling(wrapper);
                }
            },
            error: function() {
                startPolling(wrapper);
            }
        });
    }

    function startPolling(wrapper) {
        if (pollTimer) return;

        pollTimer = setInterval(function() {
            pollCount++;
            if (pollCount >= MAX_POLLS) {
                clearInterval(pollTimer);
                pollTimer = null;
                return;
            }

            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'gmat_analyse_ai_check_completion',
                    nonce: config.nonce,
                    post_id: config.postId
                },
                success: function(res) {
                    if (res.success && res.data.completed) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                        showButton(wrapper);
                    }
                }
            });
        }, POLL_INTERVAL);
    }

    function showButton(wrapper) {
        $(wrapper).fadeIn(400);
    }

    // ------------------------------------------------------------------
    // Click flow
    // ------------------------------------------------------------------

    function handleClick() {
        var $btn   = $(this);
        var $label = $btn.find('.gmat-analyse-ai__label');

        if ($btn.hasClass('gmat-analyse-ai__btn--loading')) return;

        // Intro/theory lessons carry no performance data — the AI team does
        // not generate reports for them. Show informational modal, no API call.
        if (config.noReport) {
            renderModal(null, 'noreport');
            return;
        }

        setButtonLoading($btn, $label, true);

        // Open the modal immediately in loading state — backdrop + body lock
        // block the page while the report generates (can take 1–5 minutes).
        renderModal(null, 'loading');

        generatingRetries = 0;
        sendAnalyseRequest($btn, $label, generateSessionId());
    }

    function sendAnalyseRequest($btn, $label, sessionId) {
        activeXhr = $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'gmat_analyse_ai_send_data',
                nonce:      config.nonce,
                post_id:    config.postId,
                session_id: sessionId
            },
            timeout: 610000, // 10 min + 10s buffer — stays above PHP GMAT_ANALYSE_AI_API_TIMEOUT (600s)
            success: function(res) {
                activeXhr = null;
                // Upstream 202 — report still generating; retry same POST shortly.
                if (res && res.success && res.data && res.data.generating) {
                    scheduleGeneratingRetry($btn, $label, sessionId, res.data.retry_after);
                    return;
                }
                setButtonLoading($btn, $label, false);
                if (res && res.success && res.data && res.data.report) {
                    generatingRetries = 0;
                    latestReport = res.data.report;
                    latestSessionId = sessionId;
                    closeModal();
                    renderModal(res.data.report);
                } else {
                    showModalLoadError((res && res.data && res.data.message) || 'Could not load report.');
                }
            },
            error: function(_xhr, textStatus) {
                if (textStatus === 'abort') return; // user cancelled — closeModal() already cleaned up
                activeXhr = null;
                setButtonLoading($btn, $label, false);
                showModalLoadError(textStatus === 'timeout'
                    ? 'AI service is taking too long. Please try again.'
                    : 'Connection error. Please try again.');
            }
        });
    }

    // Keep the loading modal open and re-send the same POST after the
    // server-suggested delay (AI team contract: ~120s). Capped retries.
    function scheduleGeneratingRetry($btn, $label, sessionId, retryAfter) {
        if (!$modal || !$modal.length) {
            // Modal was closed while the request was in flight — just restore the button.
            generatingRetries = 0;
            setButtonLoading($btn, $label, false);
            return;
        }
        if (generatingRetries >= MAX_GENERATING_RETRIES) {
            generatingRetries = 0;
            setButtonLoading($btn, $label, false);
            showModalLoadError('The AI is still working on your report. Please close this window and try again in a few minutes.');
            return;
        }
        generatingRetries++;

        stopStatusCycle();
        $modal.find('.gmat-aai-loading__status')
            .text('Your report is taking longer than usual — retrying automatically, please keep this tab open…');

        var delay = (parseInt(retryAfter, 10) || 120) * 1000;
        retryTimer = setTimeout(function() {
            retryTimer = null;
            if (!$modal || !$modal.length) return;
            $modal.find('.gmat-aai-loading__status').text('Checking your report…');
            sendAnalyseRequest($btn, $label, sessionId);
        }, delay);
    }

    function regenerateReport() {
        if (!$modal) return;
        var $card  = $modal.find('.gmat-aai-modal__card');
        var $regen = $modal.find('.gmat-aai-modal__regen');

        $regen.prop('disabled', true).text('Re-analysing…');
        $card.addClass('gmat-aai-modal__card--busy');

        var sessionId = generateSessionId();

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action:     'gmat_analyse_ai_send_data',
                nonce:      config.nonce,
                post_id:    config.postId,
                session_id: sessionId
            },
            timeout: 610000, // 10 min + 10s buffer — stays above PHP GMAT_ANALYSE_AI_API_TIMEOUT (600s)
            success: function(res) {
                if (res && res.success && res.data && res.data.report) {
                    latestReport = res.data.report;
                    latestSessionId = sessionId;
                    closeModal();
                    renderModal(res.data.report);
                } else {
                    $regen.prop('disabled', false).text('Re-analyse');
                    $card.removeClass('gmat-aai-modal__card--busy');
                    var $err = $modal.find('.gmat-aai-modal__err');
                    var msg = (res && res.data && res.data.message) || 'Re-analyse failed.';
                    if ($err.length) {
                        $err.text(msg).show();
                    }
                }
            },
            error: function() {
                $regen.prop('disabled', false).text('Re-analyse');
                $card.removeClass('gmat-aai-modal__card--busy');
                var $err = $modal.find('.gmat-aai-modal__err');
                if ($err.length) $err.text('Connection error.').show();
            }
        });
    }

    // ------------------------------------------------------------------
    // Download report as PDF
    // ------------------------------------------------------------------

    function downloadReport() {
        if (!$modal || !latestReport || !latestReport.coaching_report_html) return;

        var $btn   = $modal.find('.gmat-aai-modal__download');
        var $label = $btn.find('.gmat-aai-modal__download-label');

        if ($btn.hasClass('gmat-aai-modal__download--loading')) return;

        $btn.addClass('gmat-aai-modal__download--loading').prop('disabled', true);
        $label.text('Generating PDF…');

        // Build a hidden form so the browser handles the binary response as a
        // file download (Content-Disposition: attachment). XHR cannot trigger
        // a native Save dialog cleanly across browsers.
        var $form = $('<form>', {
            method: 'POST',
            action: config.ajaxUrl,
            target: '_self',
            style: 'display:none;'
        });

        var fields = {
            action:      'gmat_analyse_ai_download_pdf',
            nonce:       config.nonce,
            post_id:     config.postId,
            session_id:  latestSessionId || '',
            report_html: latestReport.coaching_report_html
        };

        $.each(fields, function(name, value) {
            $('<input>', { type: 'hidden', name: name }).val(value).appendTo($form);
        });

        $form.appendTo($body);
        $form[0].submit();
        $form.remove();

        // Safety timeout — re-enable after a few seconds so the user can retry
        // if the response stalls (PDF render is normally <1s).
        if (downloadTimer) clearTimeout(downloadTimer);
        downloadTimer = setTimeout(function() {
            $btn.removeClass('gmat-aai-modal__download--loading').prop('disabled', false);
            $label.text('Download Report');
            downloadTimer = null;
        }, 5000);
    }

    function setButtonLoading($btn, $label, on) {
        if (on) {
            $btn.addClass('gmat-analyse-ai__btn--loading').prop('disabled', true);
            $btn.find('.gmat-analyse-ai__spinner').remove();
            $label.before('<span class="gmat-analyse-ai__spinner"></span> ');
            $label.text('Loading…');
        } else {
            $btn.removeClass('gmat-analyse-ai__btn--loading').prop('disabled', false);
            $btn.find('.gmat-analyse-ai__spinner').remove();
            $label.text(BTN_LABEL_DEFAULT);
        }
    }

    // ------------------------------------------------------------------
    // Modal
    // ------------------------------------------------------------------

    function renderModal(report, mode) {
        if ($modal && $modal.length) closeModal();

        mode = mode || 'report';

        var bodyHtml;
        if (mode === 'loading') {
            bodyHtml = buildLessonHeader(null) + buildLoadingHTML();
        } else if (mode === 'noreport') {
            bodyHtml = buildLessonHeader(null) + buildNoReportHTML();
        } else {
            bodyHtml = buildLessonHeader(report) + buildCoachingHTML(report);
        }

        var closeLabel = (mode === 'loading') ? 'Cancel' : 'Close';

        var html =
            '<div class="gmat-aai-modal" role="dialog" aria-modal="true" aria-labelledby="gmat-aai-title">' +
                '<div class="gmat-aai-modal__backdrop"></div>' +
                '<div class="gmat-aai-modal__card" tabindex="-1">' +
                    '<header class="gmat-aai-modal__header">' +
                        '<div class="gmat-aai-modal__title-wrap">' +
                            '<svg class="gmat-aai-modal__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                                '<path d="M12 2a4 4 0 0 1 4 4c0 1.95-1.4 3.58-3.25 3.93L12 22"/>' +
                                '<path d="M8 6a4 4 0 0 1 8 0"/>' +
                                '<circle cx="12" cy="6" r="1"/>' +
                            '</svg>' +
                            '<h2 id="gmat-aai-title">AI Coaching Report</h2>' +
                        '</div>' +
                        '<button type="button" class="gmat-aai-modal__close" aria-label="Close">&times;</button>' +
                    '</header>' +
                    '<div class="gmat-aai-modal__body">' +
                        bodyHtml +
                    '</div>' +
                    '<footer class="gmat-aai-modal__footer">' +
                        '<div class="gmat-aai-modal__err" role="alert" style="display:none;"></div>' +
                        '<button type="button" class="gmat-aai-modal__download">' +
                            '<span class="gmat-aai-modal__download-label">Download Report</span>' +
                        '</button>' +
                        '<button type="button" class="gmat-aai-modal__regen">Re-analyse</button>' +
                        '<button type="button" class="gmat-aai-modal__ok">' + closeLabel + '</button>' +
                    '</footer>' +
                '</div>' +
            '</div>';

        $modal = $(html).appendTo($body);

        // Inject sanitized HTML for coaching narrative (server-side wp_kses'd)
        if (mode === 'report' && report && report.coaching_report_html) {
            $modal.find('.gmat-aai-coaching__body').html(report.coaching_report_html);
        }

        $body.addClass('gmat-aai-locked');

        $modal.find('.gmat-aai-modal__close, .gmat-aai-modal__ok').on('click', closeModal);
        $modal.find('.gmat-aai-modal__backdrop').on('click', closeModal);
        $modal.find('.gmat-aai-modal__regen').on('click', regenerateReport);
        $modal.find('.gmat-aai-modal__download').on('click', downloadReport);

        // Hide Download button if there's no coaching HTML to print.
        if (mode !== 'report' || !report || !report.coaching_report_html) {
            $modal.find('.gmat-aai-modal__download').hide();
        }
        if (mode !== 'report') {
            $modal.find('.gmat-aai-modal__regen').hide();
        }

        if (mode === 'loading') {
            startStatusCycle();
        }

        escHandler = function(e) { if (e.key === 'Escape' || e.keyCode === 27) closeModal(); };
        $(document).on('keydown.gmatAai', escHandler);

        // Animate in next frame
        requestAnimationFrame(function() {
            $modal.addClass('gmat-aai-modal--open');
        });

        setTimeout(function() {
            var card = $modal && $modal.find('.gmat-aai-modal__card')[0];
            if (card && typeof card.focus === 'function') card.focus();
        }, 30);
    }

    function closeModal() {
        if (!$modal || !$modal.length) return;
        stopStatusCycle();
        // Cancel a pending 202 auto-retry — user closed the loading modal
        // during the wait window; no request is in flight at that point.
        if (retryTimer) {
            clearTimeout(retryTimer);
            retryTimer = null;
            generatingRetries = 0;
            resetAnalyseButton();
        }
        // Cancel an in-flight analysis — abort fires the error callback with
        // textStatus 'abort', which returns early; restore the button here.
        if (activeXhr) {
            activeXhr.abort();
            activeXhr = null;
            resetAnalyseButton();
        }
        var $m = $modal;
        $modal = null;
        $m.removeClass('gmat-aai-modal--open');
        $body.removeClass('gmat-aai-locked');
        $(document).off('keydown.gmatAai');
        escHandler = null;
        setTimeout(function() { $m.remove(); }, 200);
    }

    function resetAnalyseButton() {
        var $btn = $('.gmat-analyse-ai__btn');
        if (!$btn.length) return;
        setButtonLoading($btn, $btn.find('.gmat-analyse-ai__label'), false);
    }

    function startStatusCycle() {
        stopStatusCycle();
        var idx = 0;
        statusTimer = setInterval(function() {
            if (!$modal || !$modal.length) { stopStatusCycle(); return; }
            idx++;
            if (idx >= LOADING_STATUSES.length) { stopStatusCycle(); return; }
            var $status = $modal.find('.gmat-aai-loading__status');
            $status.fadeOut(180, function() {
                $(this).text(LOADING_STATUSES[idx]).fadeIn(180);
            });
        }, 12000);
    }

    function stopStatusCycle() {
        if (statusTimer) {
            clearInterval(statusTimer);
            statusTimer = null;
        }
    }

    // Swap the loading state for an error message inside the open modal.
    function showModalLoadError(msg) {
        if (!$modal || !$modal.length) return;
        stopStatusCycle();
        var $loading = $modal.find('.gmat-aai-loading');
        if (!$loading.length) return;
        $loading.replaceWith(
            '<section class="gmat-aai-coaching gmat-aai-coaching--empty">' +
                '<div class="gmat-aai-empty">' +
                    '<svg class="gmat-aai-empty__icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                        '<circle cx="12" cy="12" r="10"/>' +
                        '<path d="M12 8v4M12 16h.01"/>' +
                    '</svg>' +
                    '<h4 class="gmat-aai-empty__title">Could not generate your report</h4>' +
                    '<p class="gmat-aai-empty__msg">' + escapeHtml(msg) + '</p>' +
                '</div>' +
            '</section>'
        );
        $modal.find('.gmat-aai-modal__ok').text('Close');
    }

    // ------------------------------------------------------------------
    // Section builders
    // ------------------------------------------------------------------

    function buildLessonHeader(r) {
        var label   = (config.lessonLabel  || '').toString();
        var student = (config.studentName  || '').toString();
        var modKey  = (config.lessonKey    || '').toString();
        var date    = (config.reportDate   || '').toString();
        var typeLbl = (config.reportTypeLbl || 'Performance Report').toString();

        if (!label && !student && !modKey) return '';

        var html = '<div class="gmat-aai-report-head">';
        html += '<div class="gmat-aai-report-head__type">' + escapeHtml(typeLbl) + '</div>';
        if (label) {
            html += '<h3 class="gmat-aai-report-head__title">' + escapeHtml(label) + '</h3>';
        }
        html += '<div class="gmat-aai-report-head__meta">';
        if (student) html += '<span><strong>Student:</strong> ' + escapeHtml(student) + '</span>';
        if (modKey)  html += '<span><strong>Module:</strong> '  + escapeHtml(modKey)  + '</span>';
        if (date)    html += '<span><strong>Date:</strong> '    + escapeHtml(date)    + '</span>';
        html += '</div>';
        html += '</div>';
        return html;
    }

    function buildCoachingHTML(r) {
        if (r && r.coaching_report_html) {
            return '<section class="gmat-aai-coaching">' +
                        '<div class="gmat-aai-coaching__body"></div>' +
                    '</section>';
        }
        // Fallback: no coaching content returned by API
        return '<section class="gmat-aai-coaching gmat-aai-coaching--empty">' +
                    '<div class="gmat-aai-empty">' +
                        '<svg class="gmat-aai-empty__icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                            '<circle cx="12" cy="12" r="10"/>' +
                            '<path d="M12 8v4M12 16h.01"/>' +
                        '</svg>' +
                        '<h4 class="gmat-aai-empty__title">Coaching insights not yet available</h4>' +
                        '<p class="gmat-aai-empty__msg">The AI report did not return any coaching narrative for this attempt. Please close this window and click <strong>Analyze with AI</strong> to request a fresh report.</p>' +
                    '</div>' +
                '</section>';
    }

    function buildLoadingHTML() {
        return '<section class="gmat-aai-loading" role="status" aria-live="polite">' +
                    '<span class="gmat-aai-loading__spinner" aria-hidden="true"></span>' +
                    '<h4 class="gmat-aai-loading__title">Generating your coaching report</h4>' +
                    '<p class="gmat-aai-loading__status">' + LOADING_STATUSES[0] + '</p>' +
                    '<p class="gmat-aai-loading__hint">This usually takes a minute or two — please keep this tab open. You can cancel and come back later.</p>' +
                '</section>';
    }

    function buildNoReportHTML() {
        return '<section class="gmat-aai-coaching gmat-aai-coaching--noreport">' +
                    '<div class="gmat-aai-empty gmat-aai-empty--info">' +
                        '<svg class="gmat-aai-empty__icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                            '<circle cx="12" cy="12" r="10"/>' +
                            '<path d="M12 16v-4M12 8h.01"/>' +
                        '</svg>' +
                        '<h4 class="gmat-aai-empty__title">No analysis needed for this lesson</h4>' +
                        '<p class="gmat-aai-empty__msg">This introductory lesson has no practice questions, so there is no performance data to analyse. Your <strong>AI Coaching Reports</strong> become available on practice exercises and review modules.</p>' +
                    '</div>' +
                '</section>';
    }

    // ------------------------------------------------------------------
    // Utils
    // ------------------------------------------------------------------

    function num(v) {
        var n = parseInt(v, 10);
        return isNaN(n) ? 0 : n;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    $(document).ready(init);

    // Lesson name + ring-percent are set via .text() to keep XSS-free
    $(document).on('gmatAaiAfterRender', function() {});

})(jQuery);
