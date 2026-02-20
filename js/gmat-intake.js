/**
 * GMAT Intake Form - Multi-Step Wizard
 *
 * jQuery-based wizard with:
 * - Smooth step transitions (fade + slide)
 * - AJAX save per step with debounce protection
 * - Score entry management (add/remove)
 * - Client-side validation
 * - Resume from saved state
 * - Accessible keyboard navigation
 */
(function ($) {
    'use strict';

    var GmatIntake = {

        // State
        currentStep: 1,
        totalSteps: 5,
        ajaxUrl: '',
        nonce: '',
        courseUrl: '',
        isSaving: false,

        // Score data
        officialScores: [],
        practiceScores: [],

        // Score form state
        activeScoreType: null,

        // Step 1 phase: 'choose' (phase 1) or 'entry' (phase 2)
        step1Phase: 'choose',

        // Preference
        selectedPreference: null,

        // ====================================================================
        // INIT
        // ====================================================================

        init: function () {
            var data = window.gmatIntakeData;
            if (!data) {
                return;
            }

            this.ajaxUrl  = data.ajaxUrl;
            this.nonce    = data.nonce;
            this.currentStep = data.currentStep || 1;
            this.courseUrl = data.courseUrl || '/';

            // Restore saved data so users can resume
            if (data.existingData) {
                this.officialScores  = Array.isArray(data.existingData.officialScores)  ? data.existingData.officialScores  : [];
                this.practiceScores  = Array.isArray(data.existingData.practiceScores)  ? data.existingData.practiceScores  : [];
                this.selectedPreference = data.existingData.sectionPreference || null;
            }

            this.bindEvents();
            this.goToStep(this.currentStep, false); // no animation on first load
            this.renderScoreList('official');
            this.renderScoreList('practice');
            this.updateStep1Buttons(); // reflect saved scores on resume
        },

        // ====================================================================
        // EVENT BINDING
        // ====================================================================

        bindEvents: function () {
            var self = this;

            // Step navigation buttons
            $(document).on('click', '.gmat-btn-prev:not(:disabled)', function (e) {
                e.preventDefault();
                self.handlePrev($(this));
            });
            $(document).on('click', '.gmat-btn-skip:not(:disabled), .gmat-btn-skip-step1:not(:disabled)', function (e) {
                e.preventDefault();
                self.handleSkip($(this));
            });
            $(document).on('click', '.gmat-btn-save:not(:disabled)', function (e) {
                e.preventDefault();
                self.handleSave($(this));
            });
            $(document).on('click', '.gmat-btn-finish:not(:disabled)', function (e) {
                e.preventDefault();
                self.handleFinish($(this));
            });

            // Score entry — Phase transitions
            $(document).on('click', '.gmat-add-score', function (e) {
                e.preventDefault();
                self.openScoreForm($(this).data('type'));
            });
            $(document).on('click', '.gmat-score-remove', function (e) {
                e.preventDefault();
                self.removeScore($(this).data('type'), $(this).data('index'));
            });

            // Preference cards
            $(document).on('click', '.gmat-preference-card', function (e) {
                e.preventDefault();
                self.selectPreference($(this));
            });

            // Keyboard: Enter to submit current step
            $(document).on('keydown', '#gmat-intake-wizard input', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var $step = $(this).closest('.gmat-step');
                    var $saveBtn = $step.find('.gmat-btn-save, .gmat-btn-finish, .gmat-btn-skip, .gmat-btn-skip-step1');
                    if ($saveBtn.length && !$saveBtn.prop('disabled')) {
                        $saveBtn.first().trigger('click');
                    }
                }
            });
        },

        // ====================================================================
        // STEP NAVIGATION
        // ====================================================================

        /**
         * Navigate to a step with smooth animation
         * @param {number} step - Step number (1-5)
         * @param {boolean} animate - Whether to animate the transition (default true)
         */
        goToStep: function (step, animate) {
            var self = this;
            animate = animate !== false; // default true

            this.currentStep = step;
            this.clearErrors();

            var $allSteps = $('.gmat-step');
            var $target   = $('.gmat-step[data-step="' + step + '"]');

            if (animate) {
                // Fade out current visible step, then show new one
                var $visible = $allSteps.not('.gmat-step--hidden');
                $visible.css({ opacity: 1 }).animate({ opacity: 0 }, 200, function () {
                    $allSteps.addClass('gmat-step--hidden');
                    $target.removeClass('gmat-step--hidden').css({ opacity: 0 }).animate({ opacity: 1 }, 300);
                });
            } else {
                $allSteps.addClass('gmat-step--hidden');
                $target.removeClass('gmat-step--hidden');
            }

            // Update progress bar
            this.updateProgressBar();

            // Smooth scroll to wizard top
            var scrollTarget = $('#gmat-intake-wizard').offset().top - 30;
            if ($(window).scrollTop() > scrollTarget) {
                $('html, body').animate({ scrollTop: scrollTarget }, 350, 'swing');
            }
        },

        updateProgressBar: function () {
            var current = this.currentStep;

            // Update circles
            $('.gmat-progress-step').each(function () {
                var stepNum = parseInt($(this).data('step'), 10);
                $(this).removeClass('active completed');

                if (stepNum < current) {
                    $(this).addClass('completed');
                } else if (stepNum === current) {
                    $(this).addClass('active');
                }
            });

            // Update connecting lines
            $('.gmat-progress-line').each(function () {
                var afterStep = parseInt($(this).data('after'), 10);
                $(this).toggleClass('completed', afterStep < current);
            });
        },

        // ====================================================================
        // BUTTON HANDLERS
        // ====================================================================

        handlePrev: function ($btn) {
            var step = parseInt($btn.data('step'), 10);

            // Special handling for Step 1: if in phase 2 (entry), go back to phase 1 (choose)
            if (step === 1 && this.step1Phase === 'entry') {
                this.showStep1Phase('choose');
                return;
            }

            if (step > 1) {
                this.goToStep(step - 1);
            }
        },

        handleSkip: function ($btn) {
            var self = this;
            var step = parseInt($btn.data('step'), 10);

            if (self.isSaving) return;

            // Step 1 handling depends on current phase
            if (step === 1) {
                // Phase 2 (score entry): validate & save the score entry, then go back to phase 1
                if (self.step1Phase === 'entry') {
                    var entryError = self.validateScoreEntry();
                    if (entryError) {
                        self.showError(1, entryError);
                        self.shakeElement($btn);
                        return;
                    }
                    self.commitScoreEntry();
                    self.showStep1Phase('choose');
                    return;
                }

                // Phase 1 (choose): save all scores to server and advance to step 2
                self.isSaving = true;
                self.showLoading($btn);
                self.saveStep1(function (err) {
                    self.isSaving = false;
                    self.hideLoading($btn);
                    if (err) {
                        self.showError(1, err);
                        return;
                    }
                    self.goToStep(2);
                });
            }
        },

        handleSave: function ($btn) {
            var self = this;
            var step = parseInt($btn.data('step'), 10);

            if (self.isSaving) return;

            // Validate
            var error = self.validateStep(step);
            if (error) {
                self.showError(step, error);
                self.shakeElement($btn);
                return;
            }

            self.isSaving = true;
            self.showLoading($btn);

            var saveFn;
            switch (step) {
                case 1: saveFn = self.saveStep1.bind(self); break;
                case 2: saveFn = self.saveStep2.bind(self); break;
                case 3: saveFn = self.saveStep3.bind(self); break;
                case 4: saveFn = self.saveStep4.bind(self); break;
                default: return;
            }

            saveFn(function (err) {
                self.isSaving = false;
                self.hideLoading($btn);
                if (err) {
                    self.showError(step, err);
                    return;
                }
                if (step < self.totalSteps) {
                    self.goToStep(step + 1);
                }
            });
        },

        handleFinish: function ($btn) {
            var self = this;

            if (self.isSaving) return;

            var error = self.validateStep(5);
            if (error) {
                self.showError(5, error);
                self.shakeElement($btn);
                return;
            }

            self.isSaving = true;
            self.showLoading($btn);

            self.saveStep5(function (err, redirectUrl) {
                self.isSaving = false;
                self.hideLoading($btn);
                if (err) {
                    self.showError(5, err);
                    return;
                }
                // Show brief success state then redirect
                $btn.text('Saving...').addClass('loading');
                setTimeout(function () {
                    window.location.href = redirectUrl || self.courseUrl;
                }, 600);
            });
        },

        // ====================================================================
        // VALIDATION
        // ====================================================================

        validateStep: function (step) {
            switch (step) {
                case 1:
                    return null; // Optional — user can skip

                case 2:
                    var goalVal = $('#gmat-goal-score').val();
                    if (!goalVal || goalVal.trim() === '') return 'Please enter your desired GMAT score.';
                    var goal = parseInt(goalVal, 10);
                    if (isNaN(goal))          return 'Please enter a valid number.';
                    if (goal < 205 || goal > 805) return 'Score must be between 205 and 805.';
                    return null;

                case 3:
                    if (!$('#gmat-weekly-hours').val()) return 'Please select your weekly study hours.';
                    return null;

                case 4:
                    var dateVal = $('#gmat-next-test-date').val();
                    if (!dateVal) return 'Please select your next test date.';
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    var selected = new Date(dateVal + 'T00:00:00');
                    if (isNaN(selected.getTime())) return 'Please enter a valid date.';
                    if (selected < today) return 'Test date must be today or in the future.';
                    return null;

                case 5:
                    if (!this.selectedPreference) return 'Please select either Quantitative or Verbal.';
                    return null;
            }
            return null;
        },

        // ====================================================================
        // AJAX SAVE FUNCTIONS
        // ====================================================================

        _ajax: function (action, data, callback) {
            data.action = action;
            data.nonce  = this.nonce;

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 15000,
                success: function (response) {
                    if (response && response.success) {
                        callback(null, response.data);
                    } else {
                        var msg = (response && response.data && response.data.message)
                            ? response.data.message
                            : 'Something went wrong. Please try again.';
                        callback(msg);
                    }
                },
                error: function (xhr, status) {
                    if (status === 'timeout') {
                        callback('Request timed out. Please check your connection and try again.');
                    } else {
                        callback('Network error. Please try again.');
                    }
                }
            });
        },

        saveStep1: function (callback) {
            this._ajax('gmat_intake_save_scores', {
                official_scores: JSON.stringify(this.officialScores),
                practice_scores: JSON.stringify(this.practiceScores)
            }, function (err) { callback(err); });
        },

        saveStep2: function (callback) {
            this._ajax('gmat_intake_save_goal', {
                goal_score: parseInt($('#gmat-goal-score').val(), 10)
            }, function (err) { callback(err); });
        },

        saveStep3: function (callback) {
            this._ajax('gmat_intake_save_hours', {
                weekly_hours: parseInt($('#gmat-weekly-hours').val(), 10)
            }, function (err) { callback(err); });
        },

        saveStep4: function (callback) {
            this._ajax('gmat_intake_save_test_date', {
                test_date: $('#gmat-next-test-date').val()
            }, function (err) { callback(err); });
        },

        saveStep5: function (callback) {
            this._ajax('gmat_intake_save_preference', {
                preference: this.selectedPreference
            }, function (err, data) {
                if (err) {
                    callback(err);
                } else {
                    callback(null, data && data.redirect_url ? data.redirect_url : null);
                }
            });
        },

        // ====================================================================
        // SCORE ENTRY MANAGEMENT — Two-phase Step 1
        // ====================================================================

        /**
         * Switch between Phase 1 (choose score type) and Phase 2 (score entry).
         * @param {string} phase - 'choose' or 'entry'
         */
        showStep1Phase: function (phase) {
            var self = this;
            self.step1Phase = phase;
            self.clearErrors();

            var $phase1 = $('#gmat-step1-phase1');
            var $phase2 = $('#gmat-step1-phase2');
            var $prevBtn = $('.gmat-step1-prev-btn');
            var $skipBtn = $('.gmat-btn-skip-step1');

            if (phase === 'entry') {
                // Transition to Phase 2: score entry form
                $phase1.fadeOut(200, function () {
                    $phase2.fadeIn(300, function () {
                        $('#gmat-score-date').trigger('focus');
                    });
                });
                // Enable Previous button (goes back to phase 1)
                $prevBtn.prop('disabled', false);
                // Change Skip/Save button to "Save"
                $skipBtn.text('Save').removeClass('gmat-btn-skip').addClass('gmat-btn-primary');
            } else {
                // Transition to Phase 1: score type cards
                $phase2.fadeOut(200, function () {
                    $phase1.fadeIn(300);
                });
                // Disable Previous button on phase 1 (step 1 has no previous step)
                $prevBtn.prop('disabled', true);
                // Update button: show "Save" if scores exist, "Skip" if not
                self.updateStep1Buttons();
            }
        },

        /**
         * User clicked "Add Score" on a card — transition to Phase 2.
         */
        openScoreForm: function (type) {
            this.activeScoreType = type;
            this.clearErrors();

            // Set form title based on score type
            var title = type === 'official'
                ? 'Enter Your Official GMAT Scores'
                : 'Enter Your Practice GMAT Scores';
            $('#gmat-score-form-title').text(title);

            // Clear previous values
            $('#gmat-score-date, #gmat-score-overall, #gmat-score-quant, #gmat-score-verbal, #gmat-score-di').val('');

            // Transition to Phase 2
            this.showStep1Phase('entry');
        },

        /**
         * Validate score entry fields. Returns error string or null.
         */
        validateScoreEntry: function () {
            var date    = $.trim($('#gmat-score-date').val());
            var overall = parseInt($('#gmat-score-overall').val(), 10);
            var quant   = parseInt($('#gmat-score-quant').val(), 10);
            var verbal  = parseInt($('#gmat-score-verbal').val(), 10);
            var di      = parseInt($('#gmat-score-di').val(), 10);

            if (!date) return 'Please select a date.';
            if (!overall || overall < 205 || overall > 805) return 'Overall score must be between 205 and 805.';
            if (!quant || quant < 60 || quant > 90) return 'Quant score must be between 60 and 90.';
            if (!verbal || verbal < 60 || verbal > 90) return 'Verbal score must be between 60 and 90.';
            if (!di || di < 60 || di > 90) return 'Data Insights score must be between 60 and 90.';
            return null;
        },

        /**
         * Commit the score entry from Phase 2 fields into the scores array.
         * Called after validation passes.
         */
        commitScoreEntry: function () {
            var entry = {
                date:    $.trim($('#gmat-score-date').val()),
                overall: parseInt($('#gmat-score-overall').val(), 10),
                quant:   parseInt($('#gmat-score-quant').val(), 10),
                verbal:  parseInt($('#gmat-score-verbal').val(), 10),
                di:      parseInt($('#gmat-score-di').val(), 10)
            };

            if (this.activeScoreType === 'official') {
                this.officialScores.push(entry);
                this.renderScoreList('official');
            } else {
                this.practiceScores.push(entry);
                this.renderScoreList('practice');
            }

            this.clearErrors();
            this.activeScoreType = null;
        },

        removeScore: function (type, index) {
            if (type === 'official') {
                this.officialScores.splice(index, 1);
            } else {
                this.practiceScores.splice(index, 1);
            }
            this.renderScoreList(type);
            this.updateStep1Buttons();
        },

        renderScoreList: function (type) {
            var scores     = type === 'official' ? this.officialScores : this.practiceScores;
            var $container = type === 'official' ? $('#gmat-saved-official') : $('#gmat-saved-practice');
            var $list      = type === 'official' ? $('#gmat-official-score-list') : $('#gmat-practice-score-list');

            if (!scores || scores.length === 0) {
                $container.slideUp(200);
                return;
            }

            var html = '';
            for (var i = 0; i < scores.length; i++) {
                var s = scores[i];
                html += '<div class="gmat-score-list-item">'
                     +  '  <div class="gmat-score-list-item__info">'
                     +  '    <span><strong>Date:</strong> ' + this.escapeHtml(s.date) + '</span>'
                     +  '    <span><strong>Overall:</strong> ' + s.overall + '</span>'
                     +  '    <span><strong>Q:</strong> ' + s.quant + '</span>'
                     +  '    <span><strong>V:</strong> ' + s.verbal + '</span>'
                     +  '    <span><strong>DI:</strong> ' + s.di + '</span>'
                     +  '  </div>'
                     +  '  <button type="button" class="gmat-score-remove" data-type="' + type + '" data-index="' + i + '" title="Remove score">&times;</button>'
                     +  '</div>';
            }

            $list.html(html);
            $container.slideDown(200);
        },

        /**
         * When scores are added, show "Save" instead of "Skip" on step 1.
         * When no scores, revert to "Skip".
         */
        updateStep1Buttons: function () {
            var hasScores = this.officialScores.length > 0 || this.practiceScores.length > 0;
            var $skipBtn  = $('.gmat-btn-skip-step1');

            if (hasScores) {
                $skipBtn.text('Save').removeClass('gmat-btn-skip').addClass('gmat-btn-primary');
            } else {
                $skipBtn.text('Skip').addClass('gmat-btn-skip').removeClass('gmat-btn-primary');
            }
        },

        // ====================================================================
        // PREFERENCE SELECTION (Step 5)
        // ====================================================================

        selectPreference: function ($card) {
            $('.gmat-preference-card').removeClass('selected');
            $card.addClass('selected');
            this.selectedPreference = $card.data('preference');
            this.clearErrors();
        },

        // ====================================================================
        // UI HELPERS
        // ====================================================================

        showError: function (step, message) {
            var $el = $('#gmat-step' + step + '-error');
            $el.text(message).css({ opacity: 0 }).animate({ opacity: 1 }, 250);
        },

        clearErrors: function () {
            $('.gmat-step__error').text('').css({ opacity: 1 });
        },

        showLoading: function ($btn) {
            $btn.data('original-text', $btn.text());
            $btn.addClass('loading').prop('disabled', true);
        },

        hideLoading: function ($btn) {
            $btn.removeClass('loading').prop('disabled', false);
            var orig = $btn.data('original-text');
            if (orig) {
                $btn.text(orig);
            }
        },

        /**
         * Brief shake animation on a button for validation feedback
         */
        shakeElement: function ($el) {
            $el.css('position', 'relative');
            $el.animate({ left: -6 }, 60)
               .animate({ left: 6 }, 60)
               .animate({ left: -4 }, 60)
               .animate({ left: 4 }, 60)
               .animate({ left: 0 }, 60);
        },

        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    };

    // ========================================================================
    // DOCUMENT READY
    // ========================================================================

    $(document).ready(function () {
        if ($('#gmat-intake-wizard').length) {
            GmatIntake.init();
        }
    });

})(jQuery);
