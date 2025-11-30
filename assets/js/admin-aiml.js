/**
 * AIML Admin JavaScript
 *
 * Admin interface for AIMLAPI integration.
 *
 * @package WritgoCMS
 */

(function($) {
    'use strict';

    var WritgoCMSAiml = {
        testType: 'text',

        init: function() {
            this.bindPasswordToggles();
            this.bindApiValidation();
            this.bindRangeInputs();
            this.bindTestInterface();
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

            $('#validate-aimlapi-key').on('click', function() {
                var $button = $(this);
                var $status = $button.siblings('.validation-status');
                var $input = $('#writgocms_aimlapi_key');
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
                        api_key: apiKey
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgocmsAiml.i18n.valid).removeClass('validating invalid').addClass('valid');
                            self.showNotification(writgocmsAiml.i18n.success + ' AIMLAPI key validated!', 'success');
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

                // Update placeholder and model options
                var $modelSelect = $('#test-model');
                if (self.testType === 'text') {
                    $('#test-prompt').attr('placeholder', writgocmsAiml.i18n.testPrompt);
                    $modelSelect.find('.text-models').show();
                    $modelSelect.find('.image-models').hide();
                    $modelSelect.find('.text-models option:first').prop('selected', true);
                } else {
                    $('#test-prompt').attr('placeholder', writgocmsAiml.i18n.imagePrompt);
                    $modelSelect.find('.text-models').hide();
                    $modelSelect.find('.image-models').show();
                    $modelSelect.find('.image-models option:first').prop('selected', true);
                }
            });

            // Generate button
            $('#test-generate').on('click', function() {
                var $button = $(this);
                var $status = $('.test-status');
                var $result = $('.test-result');
                var $resultContent = $('.test-result-content');
                var prompt = $('#test-prompt').val();
                var model = $('#test-model').val();

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
                        prompt: prompt,
                        model: model
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
