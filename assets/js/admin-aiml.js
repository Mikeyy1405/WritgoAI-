/**
 * AIML Admin JavaScript
 *
 * @package WritgoCMS
 */

(function($) {
    'use strict';

    var WritgoCMSAiml = {
        testType: 'text',

        init: function() {
            this.bindProviderCards();
            this.bindPasswordToggles();
            this.bindApiValidation();
            this.bindRangeInputs();
            this.bindTestInterface();
        },

        /**
         * Bind provider card selection
         */
        bindProviderCards: function() {
            $('.provider-card').on('click', function() {
                var $card = $(this);
                var $container = $card.closest('.provider-cards');
                var provider = $card.data('provider');

                // Update active state
                $container.find('.provider-card').removeClass('active');
                $card.addClass('active');

                // Check the radio button
                $card.find('input[type="radio"]').prop('checked', true);

                // Show/hide provider settings
                var $settingsContainer = $card.closest('form').find('.provider-settings');
                $settingsContainer.find('.provider-setting-group').hide();
                $settingsContainer.find('.provider-setting-group[data-provider="' + provider + '"]').show();
            });
        },

        /**
         * Bind password toggle buttons
         */
        bindPasswordToggles: function() {
            $('.toggle-password').on('click', function() {
                var $button = $(this);
                var $input = $button.siblings('input');

                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $button.text('🔒');
                } else {
                    $input.attr('type', 'password');
                    $button.text('👁️');
                }
            });
        },

        /**
         * Bind API validation buttons
         */
        bindApiValidation: function() {
            var self = this;

            $('.validate-api').on('click', function() {
                var $button = $(this);
                var $status = $button.siblings('.validation-status');
                var $input = $button.siblings('input');
                var provider = $button.data('provider');
                var apiKey = $input.val();

                if (!apiKey) {
                    self.showNotification(writgocmsAiml.i18n.error + ': API key is required', 'error');
                    return;
                }

                $button.prop('disabled', true);
                $status.text(writgocmsAiml.i18n.validating).removeClass('valid invalid').addClass('validating');

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_validate_api_key',
                        nonce: writgocmsAiml.nonce,
                        provider: provider,
                        api_key: apiKey
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgocmsAiml.i18n.valid).removeClass('validating invalid').addClass('valid');
                            self.showNotification(writgocmsAiml.i18n.success + ' API key validated!', 'success');
                        } else {
                            $status.text(writgocmsAiml.i18n.invalid).removeClass('validating valid').addClass('invalid');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text(writgocmsAiml.i18n.error).removeClass('validating valid').addClass('invalid');
                        self.showNotification('Connection error', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Bind range input updates
         */
        bindRangeInputs: function() {
            $('.range-input').on('input', function() {
                var $input = $(this);
                var $value = $input.siblings('.range-value');
                $value.text($input.val());
            });
        },

        /**
         * Bind test interface
         */
        bindTestInterface: function() {
            var self = this;

            // Type toggle
            $('.test-type-btn').on('click', function() {
                var $button = $(this);
                self.testType = $button.data('type');

                $('.test-type-btn').removeClass('active');
                $button.addClass('active');

                // Update placeholder
                if (self.testType === 'text') {
                    $('#test-prompt').attr('placeholder', writgocmsAiml.i18n.testPrompt);
                } else {
                    $('#test-prompt').attr('placeholder', writgocmsAiml.i18n.imagePrompt);
                }
            });

            // Generate button
            $('#test-generate').on('click', function() {
                var $button = $(this);
                var $status = $('.test-status');
                var $result = $('.test-result');
                var $resultContent = $('.test-result-content');
                var prompt = $('#test-prompt').val();

                if (!prompt) {
                    self.showNotification('Please enter a prompt', 'error');
                    return;
                }

                $button.prop('disabled', true).addClass('loading').html('<span class="loading-spinner"></span>' + writgocmsAiml.i18n.generating);
                $status.text('').removeClass('error success');
                $result.hide();

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_test_generation',
                        nonce: writgocmsAiml.nonce,
                        type: self.testType,
                        prompt: prompt
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgocmsAiml.i18n.success).addClass('success');

                            if (self.testType === 'text') {
                                $resultContent.text(response.data.content);
                            } else {
                                $resultContent.html('<img src="' + response.data.image_url + '" alt="Generated Image">');
                            }

                            $result.show();
                            self.showNotification('Generation completed!', 'success');
                        } else {
                            $status.text(response.data.message).addClass('error');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text('Connection error').addClass('error');
                        self.showNotification('Connection error', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).removeClass('loading').html('✨ Generate');
                    }
                });
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="aiml-notification ' + type + '">' + message + '</div>');
            $('body').append($notification);

            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        WritgoCMSAiml.init();
    });

})(jQuery);
