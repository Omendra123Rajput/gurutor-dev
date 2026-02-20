/**
 * GMAT Study Plan — Frontend Interactions (v4)
 *
 * Handles:
 *  - Unit accordion expand/collapse within each section card
 *  - Auto-open first incomplete unit on page load
 *  - Smooth scroll to active unit
 *  - Only one unit open per section at a time (optional, configurable)
 */
(function ($) {
    'use strict';

    var StudyPlan = {

        // Set to true to allow only one unit open per section
        singleOpenPerSection: false,

        init: function () {
            this.cacheDom();
            if (!this.$wrap.length) return;
            this.bindEvents();
            this.autoExpandFirst();
        },

        cacheDom: function () {
            this.$wrap     = $('#gmat-study-plan');
            this.$units    = this.$wrap.find('.gmat-sp-unit');
            this.$sections = this.$wrap.find('.gmat-sp-section');
        },

        bindEvents: function () {
            var self = this;

            // Unit accordion toggle — click on header
            this.$wrap.on('click', '.gmat-sp-unit__header', function (e) {
                // Don't toggle if user clicked a link inside header (edge case)
                if ($(e.target).closest('a').length) return;

                var $unit = $(this).closest('.gmat-sp-unit');
                self.toggleUnit($unit);
            });
        },

        // ── Unit Accordion Toggle ──
        toggleUnit: function ($unit) {
            var isOpen = $unit.hasClass('open');

            if (this.singleOpenPerSection) {
                // Close all other units in the same section
                var $section = $unit.closest('.gmat-sp-section__card');
                $section.find('.gmat-sp-unit.open').not($unit).removeClass('open');
            }

            if (isOpen) {
                $unit.removeClass('open');
            } else {
                $unit.addClass('open');

                // Smooth scroll so the unit header is fully visible
                setTimeout(function () {
                    var headerTop = $unit.offset().top - 20;
                    var scrollTop = $(window).scrollTop();
                    if (scrollTop > headerTop || headerTop > scrollTop + $(window).height() - 100) {
                        $('html, body').animate({ scrollTop: headerTop }, 350);
                    }
                }, 120);
            }
        },

        // ── Auto-expand first incomplete unit on page load ──
        autoExpandFirst: function () {
            var self = this;

            // For each section, find the first unit that is in-progress or not-started
            this.$sections.each(function () {
                var $sectionCard = $(this).find('.gmat-sp-section__card');
                var $sectionUnits = $sectionCard.find('.gmat-sp-unit');

                // First priority: in-progress unit
                var $target = $sectionUnits.filter('[data-unit-state="in-progress"]').first();

                // Second priority: first not-started unit
                if (!$target.length) {
                    $target = $sectionUnits.filter('[data-unit-state="not-started"]').first();
                }

                // If all completed, open the last unit
                if (!$target.length) {
                    $target = $sectionUnits.last();
                }

                if ($target.length) {
                    $target.addClass('open');
                }
            });

            // After expanding, scroll to the first in-progress unit globally
            setTimeout(function () {
                var $globalTarget = self.$units.filter('[data-unit-state="in-progress"]').first();
                if ($globalTarget.length && $globalTarget.hasClass('open')) {
                    var headerTop = $globalTarget.offset().top - 80;
                    if (headerTop > $(window).height() * 0.3) {
                        $('html, body').animate({ scrollTop: headerTop }, 500);
                    }
                }
            }, 300);
        }
    };

    // Boot when DOM is ready
    $(document).ready(function () {
        if ($('#gmat-study-plan').length) {
            StudyPlan.init();
        }
    });

})(jQuery);
