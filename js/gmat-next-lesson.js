/**
 * GMAT Prev/Next Lesson buttons — client-side controller (free trial only).
 *
 * - Injects two hidden buttons next to the GrassBlade iframe.
 * - If the current lesson is already completed (server flag), resolves
 *   neighbor URLs immediately. Otherwise polls LRS for a `completed`
 *   statement since page open.
 * - Only sides with an in-plan neighbor are revealed.
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

    function injectButtons() {
        var $template = $('#gmat-next-lesson-template');
        if (!$template.length) return;

        var $iframe = $('.grassblade iframe.grassblade_iframe').first();
        if (!$iframe.length) return;

        var $container = $iframe.closest('.grassblade');
        if (!$container.length) return;

        $injected = $($template.html());
        $container.after($injected);
    }

    function getPrev() { return $injected ? $injected.find('.gmat-next-lesson__link--prev') : $(); }
    function getNext() { return $injected ? $injected.find('.gmat-next-lesson__link--next') : $(); }

    function hideButtons() {
        getPrev().addClass('gmat-next-lesson__link--hidden').attr('aria-disabled', 'true');
        getNext().addClass('gmat-next-lesson__link--hidden').attr('aria-disabled', 'true');
    }

    function showButtons(prevUrl, nextUrl) {
        if (prevUrl) {
            getPrev().attr('href', prevUrl)
                     .removeAttr('aria-disabled')
                     .removeClass('gmat-next-lesson__link--hidden');
        }
        if (nextUrl) {
            getNext().attr('href', nextUrl)
                     .removeAttr('aria-disabled')
                     .removeClass('gmat-next-lesson__link--hidden');
        }
    }

    function resolveNeighbors() {
        $.ajax({
            url: gmatNextLesson.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'gmat_next_lesson_url',
                nonce: gmatNextLesson.nonce,
                lesson_id: gmatNextLesson.lessonId
            }
        }).done(function (res) {
            if (res && res.success && res.data) {
                showButtons(res.data.prev_url || '', res.data.next_url || '');
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
                resolveNeighbors();
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
        injectButtons();
        if (!$injected) return;
        hideButtons();

        if (gmatNextLesson.alreadyCompleted) {
            resolved = true;
            resolveNeighbors();
            return;
        }

        pollTimer = setInterval(checkCompletion, gmatNextLesson.pollMs);
        setTimeout(checkCompletion, 4000);
    });
})(jQuery);
