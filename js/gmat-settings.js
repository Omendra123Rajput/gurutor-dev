/**
 * GMAT Settings — My Account Tab JS
 *
 * Handles:
 * - "Weekly Study Schedule" field click → toggle to <select>
 * - Save all fields via single AJAX call
 * - Success/error feedback
 */
(function ($) {
    'use strict';

    var GmatSettings = {

        isSaving: false,

        init: function () {
            if (!$('#gmat-settings-page').length) return;

            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            // Block non-numeric characters (e, E, +, -) in number inputs
            // HTML type="number" allows 'e' for scientific notation — we don't want that
            $('#gmat-settings-page').on('keydown', 'input[type="number"]', function (e) {
                if (e.key === 'e' || e.key === 'E' || e.key === '+' || e.key === '-') {
                    e.preventDefault();
                }
            });

            // Also sanitize on paste — strip non-numeric characters
            $('#gmat-settings-page').on('paste', 'input[type="number"]', function (e) {
                var input = this;
                setTimeout(function () {
                    var cleaned = input.value.replace(/[^\d]/g, '');
                    if (input.value !== cleaned) {
                        input.value = cleaned;
                    }
                }, 0);
            });

            // Weekly hours: click on the readonly text input → show the hidden <select>
            $('#gmat-s-weekly-hours').on('click focus', function () {
                $(this).hide();
                $('#gmat-s-weekly-hours-select').show().trigger('focus');
            });

            // When the <select> changes, write the value back to the text field
            $('#gmat-s-weekly-hours-select').on('change', function () {
                var val = $(this).val();
                if (val) {
                    $('#gmat-s-weekly-hours').val(val + ' Hours');
                }
                $(this).hide();
                $('#gmat-s-weekly-hours').show();
            });

            // If the select loses focus without choosing, revert
            $('#gmat-s-weekly-hours-select').on('blur', function () {
                var self_el = this;
                // Small delay so "change" can fire first
                setTimeout(function () {
                    $(self_el).hide();
                    $('#gmat-s-weekly-hours').show();
                }, 150);
            });

            // Save button
            $('#gmat-settings-save').on('click', function (e) {
                e.preventDefault();
                self.handleSave($(this));
            });

            // Enter key in any input triggers save
            $('#gmat-settings-page').on('keydown', 'input', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#gmat-settings-save').trigger('click');
                }
            });
        },

        handleSave: function ($btn) {
            var self = this;
            if (self.isSaving) return;

            self.clearMessage();
            self.isSaving = true;
            $btn.addClass('loading').prop('disabled', true);

            // Gather all field values
            var weeklyRaw = $('#gmat-s-weekly-hours-select').val() || '';
            // Fallback: parse from display text "12 Hours"
            if (!weeklyRaw) {
                var displayVal = $('#gmat-s-weekly-hours').val();
                if (displayVal) {
                    weeklyRaw = parseInt(displayVal, 10);
                    if (isNaN(weeklyRaw)) weeklyRaw = '';
                }
            }

            var data = {
                action:         'gmat_settings_save',
                nonce:          window.gmatSettingsData.nonce,
                desired_score:  $('#gmat-s-desired-score').val(),
                test_date:      $('#gmat-s-test-date').val(),
                weekly_hours:   weeklyRaw,
                study_module:   $('#gmat-s-study-module').val(),
                score_date:     $('#gmat-s-score-date').val(),
                score_overall:  $('#gmat-s-score-overall').val(),
                score_quant:    $('#gmat-s-score-quant').val(),
                score_verbal:   $('#gmat-s-score-verbal').val(),
                score_di:       $('#gmat-s-score-di').val()
            };

            $.ajax({
                url: window.gmatSettingsData.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 15000,
                success: function (response) {
                    self.isSaving = false;
                    $btn.removeClass('loading').prop('disabled', false);

                    if (response && response.success) {
                        self.showMessage('Settings saved successfully.', 'success');
                    } else {
                        var msg = (response && response.data && response.data.message)
                            ? response.data.message
                            : 'Failed to save. Please try again.';
                        self.showMessage(msg, 'error');
                    }
                },
                error: function (xhr, status) {
                    self.isSaving = false;
                    $btn.removeClass('loading').prop('disabled', false);

                    if (status === 'timeout') {
                        self.showMessage('Request timed out. Please try again.', 'error');
                    } else {
                        self.showMessage('Network error. Please try again.', 'error');
                    }
                }
            });
        },

        showMessage: function (text, type) {
            var $msg = $('#gmat-settings-message');
            $msg.text(text).removeClass('success error').addClass(type)
                .css({ opacity: 0 }).animate({ opacity: 1 }, 250);

            // Auto-clear success messages after 4 seconds
            if (type === 'success') {
                setTimeout(function () {
                    $msg.animate({ opacity: 0 }, 300, function () {
                        $msg.text('').removeClass('success');
                    });
                }, 4000);
            }
        },

        clearMessage: function () {
            $('#gmat-settings-message').text('').removeClass('success error');
        }
    };

    $(document).ready(function () {
        GmatSettings.init();
    });

})(jQuery);
