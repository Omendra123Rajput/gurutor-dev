/**
 * GMAT Next Lesson button — client-side controller.
 *
 * - Injects the hidden button next to the GrassBlade iframe.
 * - Polls LRS (via AJAX) for the `completed` verb since page open.
 * - On completion, resolves the next lesson URL and reveals the button.
 */
(function ($) {
    'use strict';

    if (typeof gmatNextLesson === 'undefined') {
        return;
    }

    var $injected = null;
    var pollCount = 0;
    var pollTimer = null;
    var resolved  = false;

    function injectButton() {
        var $template = $('#gmat-next-lesson-template');
        if (!$template.length) return;

        var $iframe = $('.grassblade iframe.grassblade_iframe').first();
        if (!$iframe.length) return;

        var $container = $iframe.closest('.grassblade');
        if (!$container.length) return;

        $injected = $($template.html());
        $container.after($injected);
    }

    function getLinkEl() {
        return $injected ? $injected.find('.gmat-next-lesson__link') : $();
    }

    function hideButton() {
        getLinkEl().addClass('gmat-next-lesson__link--hidden').attr('aria-disabled', 'true');
    }

    function showButton(url, isLast) {
        // Last lesson: existing "Back to Course" CTA already handles this case.
        // Do not reveal a second button to avoid duplicate back-to-course buttons.
        if (isLast) {
            hideButton();
            return;
        }

        var $link = getLinkEl();
        if (!$link.length) return;

        $link.attr('href', url).removeAttr('aria-disabled')
             .removeClass('gmat-next-lesson__link--hidden');
    }

    function resolveNextUrl() {
        $.ajax({
            url: gmatNextLesson.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'gmat_next_lesson_url',
                nonce: gmatNextLesson.nonce,
                lesson_id: gmatNextLesson.lessonId,
                course_id: gmatNextLesson.courseId
            }
        }).done(function (res) {
            if (res && res.success && res.data && res.data.next_url) {
                showButton(res.data.next_url, !!res.data.is_last);
            }
        });
    }

    function checkCompletion() {
        if (resolved) return;
        pollCount++;

        $.ajax({
            url: gmatNextLesson.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'gmat_next_lesson_check',
                nonce: gmatNextLesson.nonce,
                since: gmatNextLesson.pageOpenIso
            }
        }).done(function (res) {
            if (res && res.success && res.data && res.data.completed) {
                resolved = true;
                clearInterval(pollTimer);
                resolveNextUrl();
            } else if (pollCount >= gmatNextLesson.maxPolls) {
                clearInterval(pollTimer);
            }
        }).fail(function () {
            if (pollCount >= gmatNextLesson.maxPolls) {
                clearInterval(pollTimer);
            }
        });
    }

    $(function () {
        injectButton();
        if (!$injected) return;
        hideButton();
        pollTimer = setInterval(checkCompletion, gmatNextLesson.pollMs);
        setTimeout(checkCompletion, 4000);
    });
})(jQuery);
