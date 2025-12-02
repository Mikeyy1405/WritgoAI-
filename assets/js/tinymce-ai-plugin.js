/**
 * TinyMCE AIML Plugin
 *
 * @package WritgoCMS
 */

(function() {
    'use strict';

    var settings = window.writgoaiTinymceAiml || {
        ajaxUrl: '',
        nonce: '',
        i18n: {}
    };

    var i18n = settings.i18n;

    tinymce.PluginManager.add('writgoai_ai', function(editor) {
        
        // Add button
        editor.addButton('writgoai_ai', {
            title: i18n.title || 'AI Content Generator',
            icon: 'dashicons dashicons-admin-customizer',
            image: '',
            text: '🤖 AI',
            onclick: function() {
                openAimlModal(editor);
            }
        });

        // Open modal dialog
        function openAimlModal(editor) {
            var generatedContent = '';
            var generatedImage = '';
            var isGenerating = false;

            editor.windowManager.open({
                title: i18n.title || 'AI Content Generator',
                width: 600,
                height: 450,
                body: [
                    {
                        type: 'container',
                        html: '<div id="aiml-tinymce-container">' +
                              '<div class="aiml-mode-toggle">' +
                              '<button type="button" id="aiml-text-mode" class="aiml-mode-btn active">📝 ' + (i18n.textBtn || 'Generate Text') + '</button>' +
                              '<button type="button" id="aiml-image-mode" class="aiml-mode-btn">🖼️ ' + (i18n.imageBtn || 'Generate Image') + '</button>' +
                              '</div>' +
                              '<div class="aiml-prompt-group">' +
                              '<label for="aiml-prompt">' + (i18n.promptLabel || 'Enter your prompt:') + '</label>' +
                              '<textarea id="aiml-prompt" rows="3" placeholder="Type your prompt here..."></textarea>' +
                              '</div>' +
                              '<div class="aiml-generate-group">' +
                              '<button type="button" id="aiml-generate" class="aiml-generate-btn">✨ Generate</button>' +
                              '<span id="aiml-status"></span>' +
                              '</div>' +
                              '<div id="aiml-result" style="display:none;">' +
                              '<h4>' + (i18n.previewTitle || 'Preview:') + '</h4>' +
                              '<div id="aiml-result-content"></div>' +
                              '</div>' +
                              '</div>'
                    }
                ],
                buttons: [
                    {
                        text: i18n.insertBtn || 'Insert',
                        id: 'aiml-insert-btn',
                        disabled: true,
                        onclick: function() {
                            if (generatedContent) {
                                editor.insertContent(generatedContent);
                            } else if (generatedImage) {
                                editor.insertContent('<img src="' + generatedImage + '" alt="AI Generated Image" />');
                            }
                            editor.windowManager.close();
                        }
                    },
                    {
                        text: i18n.cancelBtn || 'Cancel',
                        onclick: function() {
                            editor.windowManager.close();
                        }
                    }
                ],
                onOpen: function() {
                    initModalEvents();
                }
            });

            function initModalEvents() {
                var mode = 'text';
                var $container = jQuery('#aiml-tinymce-container');
                var $prompt = $container.find('#aiml-prompt');
                var $generateBtn = $container.find('#aiml-generate');
                var $status = $container.find('#aiml-status');
                var $result = $container.find('#aiml-result');
                var $resultContent = $container.find('#aiml-result-content');
                var $insertBtn = jQuery('#aiml-insert-btn');
                var $textMode = $container.find('#aiml-text-mode');
                var $imageMode = $container.find('#aiml-image-mode');

                // Mode toggle
                $textMode.on('click', function() {
                    mode = 'text';
                    $textMode.addClass('active');
                    $imageMode.removeClass('active');
                });

                $imageMode.on('click', function() {
                    mode = 'image';
                    $imageMode.addClass('active');
                    $textMode.removeClass('active');
                });

                // Generate button
                $generateBtn.on('click', function() {
                    var prompt = $prompt.val().trim();

                    if (!prompt) {
                        $status.text(i18n.noPrompt || 'Please enter a prompt').css('color', '#dc2626');
                        return;
                    }

                    if (isGenerating) {
                        return;
                    }

                    isGenerating = true;
                    $generateBtn.prop('disabled', true).text(i18n.generating || 'Generating...');
                    $status.text('').css('color', '');
                    $result.hide();
                    generatedContent = '';
                    generatedImage = '';

                    var action = mode === 'text' ? 'writgoai_generate_text' : 'writgoai_generate_image';

                    jQuery.ajax({
                        url: settings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: action,
                            nonce: settings.nonce,
                            prompt: prompt
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.text(i18n.success || 'Generated successfully!').css('color', '#059669');

                                if (mode === 'text') {
                                    generatedContent = '<p>' + response.data.content.replace(/\n/g, '</p><p>') + '</p>';
                                    $resultContent.text(response.data.content);
                                } else {
                                    generatedImage = response.data.image_url;
                                    $resultContent.html('<img src="' + generatedImage + '" style="max-width:100%;" />');
                                }

                                $result.show();
                                $insertBtn.prop('disabled', false);
                            } else {
                                $status.text((i18n.error || 'Error:') + ' ' + response.data.message).css('color', '#dc2626');
                            }
                        },
                        error: function() {
                            $status.text((i18n.error || 'Error:') + ' Connection failed').css('color', '#dc2626');
                        },
                        complete: function() {
                            isGenerating = false;
                            $generateBtn.prop('disabled', false).text('✨ Generate');
                        }
                    });
                });
            }
        }
    });

})();
