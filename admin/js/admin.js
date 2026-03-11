/**
 * Royal SMTP Admin JavaScript
 */

(function($) {
    'use strict';

    var RSMTP = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.handleMailerSwitch();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Mailer selection change
            $('#mailer').on('change', this.handleMailerSwitch);

            // Send test email
            $('#rsmtp-send-test').on('click', this.sendTestEmail);

            // Clear log
            $('#rsmtp-clear-log').on('click', this.clearLog);

            // Clear password fields on focus if masked
            $('input[type="password"]').on('focus', function() {
                var $input = $(this);
                if ($input.val().indexOf('****') === 0) {
                    $input.val('');
                }
            });
        },

        /**
         * Handle mailer switch
         */
        handleMailerSwitch: function() {
            var selected = $('#mailer').val();

            // Hide all mailer settings
            $('.rsmtp-mailer-settings').hide();

            // Show selected mailer settings
            $('#rsmtp-mailer-' + selected).show();
        },

        /**
         * Send test email
         */
        sendTestEmail: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $result = $('#rsmtp-test-result');
            var email = $('#test_email').val();

            if (!email) {
                $result.removeClass('success').addClass('error').text(rsmtp_ajax.strings.test_failed + ' Please enter an email address.');
                return;
            }

            $button.prop('disabled', true).text(rsmtp_ajax.strings.sending);
            $result.removeClass('success error').text('');

            $.ajax({
                url: rsmtp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rsmtp_send_test_email',
                    nonce: rsmtp_ajax.nonce,
                    to: email
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(response.data.message);
                    } else {
                        $result.removeClass('success').addClass('error').text(rsmtp_ajax.strings.test_failed + ' ' + response.data.message);
                    }
                },
                error: function() {
                    $result.removeClass('success').addClass('error').text(rsmtp_ajax.strings.test_failed + ' Connection error.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(rsmtp_ajax.strings.send_test);
                }
            });
        },

        /**
         * Clear email log
         */
        clearLog: function(e) {
            e.preventDefault();

            if (!confirm(rsmtp_ajax.strings.confirm_clear)) {
                return;
            }

            var $button = $(this);
            var originalText = $button.text();

            $button.prop('disabled', true).text(rsmtp_ajax.strings.clearing);

            $.ajax({
                url: rsmtp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rsmtp_clear_log',
                    nonce: rsmtp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Connection error.');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        RSMTP.init();
    });

})(jQuery);
