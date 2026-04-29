/**
 * GMAT Lesson Loader
 *
 * Source pages (study plan): show overlay on click of lesson buttons,
 * allow browser navigation to proceed.
 *
 * Destination pages (lesson/topic): show overlay on load, dismiss on
 * GrassBlade iframe `load` event or after a safety timeout.
 */
(function ($) {
    'use strict';

    var cfg = window.gmatLessonLoader || {};
    var pageType = cfg.pageType || '';
    var fallbackMs = parseInt(cfg.fallbackTimeoutMs, 10) || 15000;

    var TRIGGER_SELECTOR = [
        'a.gmat-sp-lesson__btn',
        'a.lesson-link',
        'a.gurutor-back-to-course__link',
        'a.gmat-next-lesson__link',
        '#free-trial-test-1 a.elementor-button',
        '#personalized-gmatz-cta a.elementor-button'
    ].join(', ');

    var $overlay = null;
    var dismissed = false;

    function getOverlay() {
        if (!$overlay || !$overlay.length) {
            $overlay = $('#gmat-loader-overlay');
        }
        return $overlay;
    }

    function show() {
        var $o = getOverlay();
        if (!$o.length) return;
        $o.removeClass('gmat-loader__overlay--leaving')
          .addClass('gmat-loader__overlay--visible')
          .attr('aria-hidden', 'false');
    }

    function hide() {
        if (dismissed) return;
        dismissed = true;
        var $o = getOverlay();
        if (!$o.length) return;
        $o.addClass('gmat-loader__overlay--leaving')
          .attr('aria-hidden', 'true');
        setTimeout(function () {
            $o.removeClass('gmat-loader__overlay--visible gmat-loader__overlay--leaving');
        }, 250);
    }

    // Source-side: bind clicks on lesson navigation anchors. Modifier-key
    // and middle clicks open in a new tab — overlay would be left orphaned,
    // so skip them.
    $(document).on('click', TRIGGER_SELECTOR, function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (typeof e.button === 'number' && e.button !== 0) return;

        var href = $(this).attr('href');
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;

        var target = $(this).attr('target');
        if (target && target !== '_self') return;

        show();
    });

    // Destination-side: lesson/topic page — show on load, wait for iframe.
    if (pageType === 'destination') {
        $(function () {
            show();

            var IFRAME_SELECTOR = '.grassblade iframe.grassblade_iframe';

            function attachLoad(iframe) {
                if (!iframe) return;
                try {
                    var doc = iframe.contentDocument;
                    if (doc && doc.readyState === 'complete') {
                        hide();
                        return;
                    }
                } catch (err) { /* cross-origin: rely on load event */ }

                if (iframe.addEventListener) {
                    iframe.addEventListener('load', hide, { once: true });
                } else {
                    $(iframe).one('load', hide);
                }
            }

            var existing = document.querySelector(IFRAME_SELECTOR);
            if (existing) {
                attachLoad(existing);
            } else if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function () {
                    var found = document.querySelector(IFRAME_SELECTOR);
                    if (found) {
                        observer.disconnect();
                        attachLoad(found);
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }

            setTimeout(hide, fallbackMs);
        });

        // Final safety net: if everything stalls, full window load = hide.
        $(window).on('load', function () {
            setTimeout(hide, 1500);
        });
    }
})(jQuery);
