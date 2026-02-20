/**
 * GMAT AI Chatbox — Premium Frontend Widget
 *
 * jQuery-based floating chatbox with:
 *   - Session management via sessionStorage
 *   - Real-time message sending via WP AJAX proxy
 *   - Typing indicator with animated dots + avatar
 *   - Auto-resizing textarea
 *   - Mobile responsive (bottom drawer)
 *   - Keyboard accessible (Enter, Shift+Enter, Escape)
 *   - Unread badge with pop animation when minimized
 *   - Welcome message with fade-up entrance delay
 *
 * Depends on: jQuery, gmatChatbox (wp_localize_script)
 */
(function ($) {
    'use strict';

    /* GMAT AI Avatar SVG — Graduation cap with AI sparkle dots */
    var AI_AVATAR_SVG =
        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>' +
        '<circle cx="20" cy="4" r="1.5" opacity="0.85"/>' +
        '<circle cx="22" cy="6.5" r="0.8" opacity="0.6"/>' +
        '</svg>';

    var GmatChat = {

        // ====================================================================
        // CONFIGURATION & STATE
        // ====================================================================

        config: {
            ajaxUrl: '',
            nonce: '',
            userId: '',
            userName: '',
            maxMsgLength: 2000,
        },

        // UI state
        isOpen: false,
        isSending: false,
        sessionId: '',
        messages: [],
        unreadCount: 0,

        // DOM cache
        $fab: null,
        $panel: null,
        $overlay: null,
        $messages: null,
        $input: null,
        $send: null,
        $typing: null,
        $badge: null,
        $charcount: null,
        $charcountNum: null,
        $status: null,

        // ====================================================================
        // INIT
        // ====================================================================

        init: function () {
            this.loadConfig();
            this.cacheDom();
            this.bindEvents();
            this.initSession();
        },

        loadConfig: function () {
            var data = window.gmatChatbox;
            if (!data) return;

            this.config.ajaxUrl      = data.ajaxUrl || '';
            this.config.nonce        = data.nonce || '';
            this.config.userId       = data.userId || '';
            this.config.userName     = data.userName || '';
            this.config.maxMsgLength = parseInt(data.maxMsgLength, 10) || 2000;
        },

        cacheDom: function () {
            this.$fab          = $('#gmat-cb-fab');
            this.$panel        = $('#gmat-cb');
            this.$overlay      = $('#gmat-cb-overlay');
            this.$messages     = $('#gmat-cb-messages');
            this.$input        = $('#gmat-cb-input');
            this.$send         = $('#gmat-cb-send');
            this.$typing       = $('#gmat-cb-typing');
            this.$badge        = $('#gmat-cb-badge');
            this.$charcount    = $('#gmat-cb-charcount');
            this.$charcountNum = $('#gmat-cb-charcount-num');
            this.$status       = $('#gmat-cb-status');
        },

        // ====================================================================
        // EVENT BINDING
        // ====================================================================

        bindEvents: function () {
            var self = this;

            // FAB click — toggle chat
            this.$fab.on('click', function () {
                self.toggleChat();
            });

            // Close button
            $('#gmat-cb-close').on('click', function () {
                self.closeChat();
            });

            // Overlay click (mobile) — close
            this.$overlay.on('click', function () {
                self.closeChat();
            });

            // New conversation button
            $('#gmat-cb-new').on('click', function () {
                self.newConversation();
            });

            // Send button
            this.$send.on('click', function () {
                self.sendMessage();
            });

            // Input: Enter to send, Shift+Enter for newline
            this.$input.on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Input: auto-resize, char count, send button state
            this.$input.on('input', function () {
                self.autoResizeInput();
                self.updateCharCount();
                self.updateSendButton();
            });

            // Escape key closes chat
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.closeChat();
                }
            });
        },

        // ====================================================================
        // SESSION MANAGEMENT (sessionStorage — per-tab)
        // ====================================================================

        initSession: function () {
            var stored = sessionStorage.getItem('gmat_chatbox_session');

            if (stored) {
                try {
                    var data = JSON.parse(stored);
                    if (data.sessionId && Array.isArray(data.messages)) {
                        this.sessionId = data.sessionId;
                        this.messages  = data.messages;
                        this.restoreMessages();
                        return;
                    }
                } catch (e) {
                    // Corrupted data — start fresh
                }
            }

            // New session
            this.sessionId = this.generateSessionId();
            this.messages  = [];
            this.showWelcome();
            this.saveSession();
        },

        generateSessionId: function () {
            return 'gs_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 8);
        },

        saveSession: function () {
            try {
                // Keep max 100 messages to prevent sessionStorage overflow
                var msgs = this.messages.length > 100
                    ? this.messages.slice(this.messages.length - 100)
                    : this.messages;

                sessionStorage.setItem('gmat_chatbox_session', JSON.stringify({
                    sessionId: this.sessionId,
                    messages:  msgs,
                }));
            } catch (e) {
                // sessionStorage full or unavailable — fail silently
            }
        },

        newConversation: function () {
            if (this.isSending) return;

            this.sessionId = this.generateSessionId();
            this.messages  = [];
            this.$messages.empty();
            this.showWelcome();
            this.saveSession();
            this.$input.val('').trigger('input');
            this.$input.focus();
        },

        // ====================================================================
        // OPEN / CLOSE
        // ====================================================================

        toggleChat: function () {
            if (this.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        },

        openChat: function () {
            this.isOpen = true;

            // Show panel
            this.$panel
                .addClass('gmat-cb--open')
                .attr('aria-hidden', 'false');

            // Update FAB
            this.$fab
                .addClass('gmat-cb__fab--open')
                .attr('aria-expanded', 'true');

            // Show overlay on mobile
            if (window.innerWidth <= 767) {
                this.$overlay.addClass('gmat-cb__overlay--visible').attr('aria-hidden', 'false');
            }

            // Clear unread
            this.unreadCount = 0;
            this.$badge.hide().attr('aria-hidden', 'true');

            // Scroll to bottom and focus input
            this.scrollToBottom();
            setTimeout(function () {
                this.$input.focus();
            }.bind(this), 300);
        },

        closeChat: function () {
            this.isOpen = false;

            // Hide panel
            this.$panel
                .removeClass('gmat-cb--open')
                .attr('aria-hidden', 'true');

            // Update FAB
            this.$fab
                .removeClass('gmat-cb__fab--open')
                .attr('aria-expanded', 'false');

            // Hide overlay
            this.$overlay.removeClass('gmat-cb__overlay--visible').attr('aria-hidden', 'true');

            // Return focus to FAB
            this.$fab.focus();
        },

        // ====================================================================
        // SEND MESSAGE
        // ====================================================================

        sendMessage: function () {
            var text = $.trim(this.$input.val());

            if (!text || this.isSending) return;
            if (text.length > this.config.maxMsgLength) return;

            this.isSending = true;

            // 1. Append user message to UI immediately
            this.appendMessage('user', text, new Date().toISOString());

            // 2. Clear input and reset
            this.$input.val('').trigger('input');
            this.updateSendButton();

            // 3. Show typing indicator
            this.showTyping();

            // 4. Save session
            this.saveSession();

            // 5. Send to server
            this.sendToServer(text);
        },

        // ====================================================================
        // AJAX — Server Communication
        // ====================================================================

        sendToServer: function (message) {
            var self = this;

            $.ajax({
                url:      self.config.ajaxUrl,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:     'gmat_chatbox_send',
                    nonce:      self.config.nonce,
                    message:    message,
                    session_id: self.sessionId,
                },
                timeout: 35000, // Slightly longer than server-side timeout
                success: function (response) {
                    self.hideTyping();
                    self.isSending = false;

                    if (response.success && response.data && response.data.reply) {
                        self.handleResponse(response.data);
                    } else {
                        var errMsg = (response.data && response.data.message)
                            ? response.data.message
                            : 'Something went wrong. Please try again.';
                        self.handleError(errMsg);
                    }
                },
                error: function (xhr, status) {
                    self.hideTyping();
                    self.isSending = false;

                    if (status === 'timeout') {
                        self.handleError('The AI is taking too long to respond. Please try again.');
                    } else if (xhr.status === 429) {
                        var msg = 'Too many messages. Please wait a moment.';
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.data && resp.data.message) {
                                msg = resp.data.message;
                            }
                        } catch (e) { /* use default */ }
                        self.handleError(msg);
                    } else if (xhr.status === 403) {
                        self.handleError('Your session has expired. Please refresh the page.');
                    } else {
                        self.handleError('Unable to reach the AI assistant. Please check your connection.');
                    }
                }
            });
        },

        handleResponse: function (data) {
            this.appendMessage('assistant', data.reply, data.timestamp);
            this.saveSession();

            // Show unread badge if chat is closed (with pop animation via CSS)
            if (!this.isOpen) {
                this.unreadCount++;
                this.$badge.text(this.unreadCount).show().attr('aria-hidden', 'false');
                // Re-trigger pop animation
                this.$badge[0].style.animation = 'none';
                /* jshint ignore:start */
                this.$badge[0].offsetHeight; // Force reflow
                /* jshint ignore:end */
                this.$badge[0].style.animation = '';
            }
        },

        handleError: function (errorMessage) {
            this.appendMessage('error', errorMessage, new Date().toISOString());
            this.saveSession();
        },

        // ====================================================================
        // MESSAGE RENDERING
        // ====================================================================

        appendMessage: function (role, text, timestamp) {
            // Store in memory
            this.messages.push({
                role:      role,
                text:      text,
                timestamp: timestamp,
            });

            // Build DOM
            this.renderMessage(role, text, timestamp);
            this.scrollToBottom();
        },

        renderMessage: function (role, text, timestamp) {
            var timeStr = this.formatTimestamp(timestamp);
            var $msg    = $('<div>').addClass('gmat-cb__msg gmat-cb__msg--' + role);

            // Assistant / welcome avatar — graduation cap icon
            if (role === 'assistant' || role === 'welcome') {
                var $avatar = $('<div>').addClass('gmat-cb__msg-avatar').html(AI_AVATAR_SVG);
                $msg.append($avatar);
            }

            var $bubble = $('<div>').addClass('gmat-cb__msg-bubble');

            // Content rendering based on role
            if (role === 'user') {
                // User messages: safe text insertion (prevents XSS)
                $bubble.text(text);
            } else if (role === 'error') {
                // Error messages: escaped with warning icon
                $bubble.html(
                    '<span class="gmat-cb__msg-error-icon">&#9888;</span> ' +
                    this.escapeHtml(text)
                );
            } else {
                // Assistant + welcome: HTML from server (already sanitized by wp_kses_post)
                $bubble.html(text);
            }

            // Timestamp
            if (role !== 'error') {
                var $time = $('<span>').addClass('gmat-cb__msg-time').text(timeStr);
                $bubble.append($time);
            }

            $msg.append($bubble);
            this.$messages.append($msg);
        },

        restoreMessages: function () {
            this.$messages.empty();

            if (this.messages.length === 0) {
                this.showWelcome();
                return;
            }

            for (var i = 0; i < this.messages.length; i++) {
                var m = this.messages[i];
                this.renderMessage(m.role, m.text, m.timestamp);
            }

            this.scrollToBottom();
        },

        showWelcome: function () {
            var name = this.config.userName || 'there';
            var welcomeHtml =
                '<strong>Hi ' + this.escapeHtml(name) + '! 👋</strong>' +
                '<p>I\'m your GMAT AI study assistant. Ask me anything about:</p>' +
                '<ul>' +
                '<li>GMAT strategies &amp; tips</li>' +
                '<li>Quant, Verbal &amp; Data Insights</li>' +
                '<li>Study planning &amp; time management</li>' +
                '<li>Practice question explanations</li>' +
                '</ul>' +
                '<p>How can I help you today?</p>';

            this.renderMessage('welcome', welcomeHtml, new Date().toISOString());
        },

        // ====================================================================
        // TYPING INDICATOR
        // ====================================================================

        showTyping: function () {
            this.$typing.show().attr('aria-hidden', 'false');
            this.$status.text('Typing…');
            this.scrollToBottom();
        },

        hideTyping: function () {
            this.$typing.hide().attr('aria-hidden', 'true');
            this.$status.text('Online');
        },

        // ====================================================================
        // INPUT MANAGEMENT
        // ====================================================================

        autoResizeInput: function () {
            var el = this.$input[0];
            el.style.height = 'auto';
            var newHeight = Math.max(36, Math.min(el.scrollHeight, 120)); // Min 36px, Max ~5 lines
            el.style.height = newHeight + 'px';
        },

        updateCharCount: function () {
            var len = this.$input.val().length;
            var max = this.config.maxMsgLength;

            this.$charcountNum.text(len);

            // Update color based on proximity to limit
            this.$charcount
                .removeClass('gmat-cb__char-count--warn gmat-cb__char-count--limit');

            if (len >= max) {
                this.$charcount.addClass('gmat-cb__char-count--limit');
            } else if (len >= max * 0.8) {
                this.$charcount.addClass('gmat-cb__char-count--warn');
            }
        },

        updateSendButton: function () {
            var hasText = $.trim(this.$input.val()).length > 0;
            this.$send.prop('disabled', !hasText || this.isSending);
        },

        // ====================================================================
        // UTILITIES
        // ====================================================================

        scrollToBottom: function () {
            var el = this.$messages[0];
            if (el) {
                // Use requestAnimationFrame for smooth scrolling
                requestAnimationFrame(function () {
                    el.scrollTop = el.scrollHeight;
                });
            }
        },

        formatTimestamp: function (isoString) {
            if (!isoString) return '';
            try {
                var d = new Date(isoString);
                if (isNaN(d.getTime())) return '';

                var hours   = d.getHours();
                var minutes = d.getMinutes();
                var ampm    = hours >= 12 ? 'PM' : 'AM';

                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;

                return hours + ':' + minutes + ' ' + ampm;
            } catch (e) {
                return '';
            }
        },

        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        },
    };

    // ====================================================================
    // DOCUMENT READY
    // ====================================================================

    $(document).ready(function () {
        if ($('#gmat-cb').length && typeof window.gmatChatbox !== 'undefined') {
            GmatChat.init();
        }
    });

})(jQuery);
