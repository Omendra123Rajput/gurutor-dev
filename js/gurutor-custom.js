/**
 * Custom Theme JavaScript
 * Place this file in: /wp-content/themes/your-theme/js/custom.js
 */

(function($) {
    'use strict';
    // ========================================================================
    // TABLE OF CONTENTS (TOC)
    // ========================================================================

    function initTableOfContents() {
        if (!$('#toc').length) return;

        // Initialize sticky navigator
        $('#toc').stickyNavigator({
            wrapselector: '#wrapper',
            targetselector: "h2,h3"
        });

        // Toggle TOC content
        $(".table_of_content .title").on('click', function () {
            $(".table_of_content .toc_content").toggleClass('show');
        });

        // Handle TOC links and slug generation
        $(document).ready(function () {
            let currentURL = window.location.href;

            function convertStringToSlug(originalString) {
                var lowercaseString = originalString.toLowerCase();
                return lowercaseString.replace(/[.\s–]+/g, '-');
            }

            // Add click handlers for history API
            document.querySelectorAll('#toc a').forEach((elem) => {
                elem.addEventListener("click", e => {
                    const newUrl = e.target.href;
                    window.history.pushState({ path: newUrl }, '', newUrl);
                });
            });

            var parentElement = document.getElementById('wrapper');
            var tocA = document.querySelectorAll('#toc a');
            
            if (parentElement) {
                var childElements = parentElement.getElementsByTagName("*");
                let toc_counter = 0;

                for (var i = 0; i < childElements.length; i++) {
                    let element = childElements[i];
    
                    if (element.tagName === "H2" || element.tagName === "H3") {
                        let headingText = element.innerText;
    
                        // Remove leading numbers
                        let c = 0;
                        while (!isNaN(headingText[c])) {
                            headingText = headingText.slice(0, c) + headingText.slice(c + 1);
                        }
    
                        // Remove leading spaces and dots
                        while (headingText[0] === ' ' || headingText[0] === '.') {
                            headingText = headingText.slice(1);
                        }
    
                        const slug = convertStringToSlug(headingText);
                        if (tocA[toc_counter]) {
                            tocA[toc_counter].href = currentURL + '#' + slug;
                            element.id = slug;
                            toc_counter++;
                        }
                    }
                }
    
                // Handle anchor in URL
                let slug = window.location.href;
                const lastSlug = slug.slice(slug.lastIndexOf("/") + 1);
    
                if (lastSlug[0] === '#') {
                    var link = document.createElement("a");
                    link.setAttribute("href", window.location.href);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        });
    }



    // ========================================================================
    // TESTIMONIALS READ MORE
    // ========================================================================

    function initTestimonialsReadMore() {
        $('.read-more-toggle').on('click', function () {
            const parent = $(this).closest('.tt_content');
            const excerpt = parent.find('.excerpt-content');
            const full = parent.find('.full-content');

            if (full.is(':visible')) {
                full.hide();
                excerpt.show();
                $(this).text('Read More');
            } else {
                full.show();
                excerpt.hide();
                $(this).text('Read Less');
            }
        });
    }

    // ========================================================================
    // TESTIMONIALS SWIPER INITIALIZATION
    // ========================================================================

    function initTestimonialsSwiper() {
        if (typeof Swiper !== 'undefined' && $('.testimonial-swiper').length) {
            new Swiper('.testimonial-swiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: true,
                loopAdditionalSlides: 1,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    768: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    }
                }
            });
        }
    }



    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    $(document).ready(function() {
        // handleFreeTrialAccess();
        // modifyAccountPageForFreeTrial();
        initTableOfContents();
        // initFaqAccordion();
        initTestimonialsReadMore();
        initTestimonialsSwiper();
        // handleFreeTrialLessonsLayout();
    });

})(jQuery);