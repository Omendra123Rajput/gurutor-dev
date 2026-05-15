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

        setButtonLoading($btn, $label, true);

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
            timeout: 310000,
            success: function(res) {
                setButtonLoading($btn, $label, false);
                if (res && res.success && res.data && res.data.report) {
                    latestReport = res.data.report;
                    latestSessionId = sessionId;
                    renderModal(res.data.report);
                } else {
                    showInlineError($btn, (res && res.data && res.data.message) || 'Could not load report.');
                }
            },
            error: function(_xhr, textStatus) {
                setButtonLoading($btn, $label, false);
                showInlineError($btn, textStatus === 'timeout'
                    ? 'AI service is taking too long. Try again.'
                    : 'Connection error. Please try again.');
            }
        });
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
            timeout: 310000,
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

    function showInlineError($btn, msg) {
        var $wrap = $btn.closest('.gmat-analyse-ai');
        var $msg  = $wrap.find('.gmat-analyse-ai__msg');
        if (!$msg.length) {
            $msg = $('<div class="gmat-analyse-ai__msg"></div>').appendTo($wrap);
        }
        $msg.removeClass('gmat-analyse-ai__msg--success')
            .addClass('gmat-analyse-ai__msg--error')
            .text(msg);
        setTimeout(function() {
            $msg.fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    }

    // ------------------------------------------------------------------
    // Modal
    // ------------------------------------------------------------------

    function renderModal(report) {
        if ($modal && $modal.length) closeModal();

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
                        buildLessonHeader(report) +
                        buildCoachingHTML(report) +
                    '</div>' +
                    '<footer class="gmat-aai-modal__footer">' +
                        '<div class="gmat-aai-modal__err" role="alert" style="display:none;"></div>' +
                        '<button type="button" class="gmat-aai-modal__download">' +
                            '<span class="gmat-aai-modal__download-label">Download Report</span>' +
                        '</button>' +
                        '<button type="button" class="gmat-aai-modal__regen">Re-analyse</button>' +
                        '<button type="button" class="gmat-aai-modal__ok">Close</button>' +
                    '</footer>' +
                '</div>' +
            '</div>';

        $modal = $(html).appendTo($body);

        // Inject sanitized HTML for coaching narrative (server-side wp_kses'd)
        if (report && report.coaching_report_html) {
            $modal.find('.gmat-aai-coaching__body').html(report.coaching_report_html);
        }

        $body.addClass('gmat-aai-locked');

        $modal.find('.gmat-aai-modal__close, .gmat-aai-modal__ok').on('click', closeModal);
        $modal.find('.gmat-aai-modal__backdrop').on('click', closeModal);
        $modal.find('.gmat-aai-modal__regen').on('click', regenerateReport);
        $modal.find('.gmat-aai-modal__download').on('click', downloadReport);

        // Hide Download button if there's no coaching HTML to print (rare edge case).
        if (!report || !report.coaching_report_html) {
            $modal.find('.gmat-aai-modal__download').hide();
        }

        escHandler = function(e) { if (e.key === 'Escape' || e.keyCode === 27) closeModal(); };
        $(document).on('keydown.gmatAai', escHandler);

        // Animate in next frame
        requestAnimationFrame(function() {
            $modal.addClass('gmat-aai-modal--open');
        });

        setTimeout(function() {
            var card = $modal.find('.gmat-aai-modal__card')[0];
            if (card && typeof card.focus === 'function') card.focus();
        }, 30);
    }

    function closeModal() {
        if (!$modal || !$modal.length) return;
        var $m = $modal;
        $modal = null;
        $m.removeClass('gmat-aai-modal--open');
        $body.removeClass('gmat-aai-locked');
        $(document).off('keydown.gmatAai');
        escHandler = null;
        setTimeout(function() { $m.remove(); }, 200);
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
                        '<p class="gmat-aai-empty__msg">The AI report did not return any coaching narrative for this attempt. Click <strong>Re-analyse</strong> below to request a fresh report.</p>' +
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
