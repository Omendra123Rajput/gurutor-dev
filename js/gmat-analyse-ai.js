(function($) {
    'use strict';

    if (typeof gmatAnalyseAI === 'undefined') return;

    var config = gmatAnalyseAI;
    var pollTimer = null;
    var pollCount = 0;
    var MAX_POLLS = 20;
    var POLL_INTERVAL = 30000; // 30s

    function init() {
        var iframe = document.querySelector('.grassblade iframe.grassblade_iframe');
        if (!iframe) return;

        var grassbladeContainer = iframe.closest('.grassblade');
        if (!grassbladeContainer) return;

        // Create hidden button
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
                ' Analyse with AI' +
            '</button>' +
            '<div class="gmat-analyse-ai__msg"></div>';

        // Insert after back-to-course CTA if exists, else after grassblade container
        var backCta = grassbladeContainer.parentNode.querySelector('.gurutor-back-to-course');
        if (backCta) {
            backCta.parentNode.insertBefore(wrapper, backCta.nextSibling);
        } else {
            grassbladeContainer.parentNode.insertBefore(wrapper, grassbladeContainer.nextSibling);
        }

        // Bind click
        $(wrapper).on('click', '.gmat-analyse-ai__btn', handleClick);

        // Initial completion check
        checkCompletion(wrapper);
    }

    function checkCompletion(wrapper) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'gmat_analyse_ai_check_completion',
                nonce: config.nonce,
                post_id: config.postId,
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
                    post_id: config.postId,
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

    function handleClick() {
        var $btn = $(this);
        var $msg = $btn.closest('.gmat-analyse-ai').find('.gmat-analyse-ai__msg');

        if ($btn.hasClass('gmat-analyse-ai__btn--loading') || $btn.hasClass('gmat-analyse-ai__btn--sent')) return;

        $btn.addClass('gmat-analyse-ai__btn--loading');
        $btn.prop('disabled', true);
        $btn.html(
            '<span class="gmat-analyse-ai__spinner"></span> Sending...'
        );
        $msg.text('').removeClass('gmat-analyse-ai__msg--error gmat-analyse-ai__msg--success');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'gmat_analyse_ai_send_data',
                nonce: config.nonce,
                post_id: config.postId,
            },
            timeout: 60000,
            success: function(res) {
                $btn.removeClass('gmat-analyse-ai__btn--loading');
                if (res.success) {
                    $btn.addClass('gmat-analyse-ai__btn--sent');
                    $btn.html('&#10003; Sent');
                    $msg.addClass('gmat-analyse-ai__msg--success').text(res.data.message);
                } else {
                    $btn.prop('disabled', false);
                    $btn.html('Analyse with AI');
                    $msg.addClass('gmat-analyse-ai__msg--error').text(res.data.message || 'Something went wrong.');
                }
            },
            error: function() {
                $btn.removeClass('gmat-analyse-ai__btn--loading');
                $btn.prop('disabled', false);
                $btn.html('Analyse with AI');
                $msg.addClass('gmat-analyse-ai__msg--error').text('Connection error. Please try again.');
            }
        });
    }

    $(document).ready(init);

})(jQuery);
