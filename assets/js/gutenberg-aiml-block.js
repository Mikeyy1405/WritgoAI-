/**
 * Gutenberg AIML Block
 *
 * @package WritgoCMS
 */

(function(wp) {
    'use strict';

    var registerBlockType = wp.blocks.registerBlockType;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var Button = wp.components.Button;
    var TextareaControl = wp.components.TextareaControl;
    var ButtonGroup = wp.components.ButtonGroup;
    var Spinner = wp.components.Spinner;
    var Notice = wp.components.Notice;
    var dispatch = wp.data.dispatch;
    var __ = wp.i18n.__;

    var settings = window.writgocmsAimlBlock || {};
    var i18n = settings.i18n || {};

    registerBlockType('writgocms/ai-generator', {
        title: i18n.blockTitle || 'AI Content Generator',
        description: i18n.blockDescription || 'Generate text or images using AI',
        icon: 'admin-customizer',
        category: 'writgocms-ai',
        keywords: ['ai', 'generate', 'content', 'image', 'text', 'gpt', 'dalle'],
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            imageUrl: {
                type: 'string',
                default: ''
            },
            imageId: {
                type: 'number',
                default: 0
            },
            blockType: {
                type: 'string',
                default: 'text'
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var _useState = useState(''),
                prompt = _useState[0],
                setPrompt = _useState[1];

            var _useState2 = useState('text'),
                mode = _useState2[0],
                setMode = _useState2[1];

            var _useState3 = useState(false),
                isLoading = _useState3[0],
                setIsLoading = _useState3[1];

            var _useState4 = useState(''),
                result = _useState4[0],
                setResult = _useState4[1];

            var _useState5 = useState(''),
                imageResult = _useState5[0],
                setImageResult = _useState5[1];

            var _useState6 = useState(''),
                error = _useState6[0],
                setError = _useState6[1];

            function handleGenerate() {
                if (!prompt.trim()) {
                    setError(i18n.noPrompt || 'Please enter a prompt');
                    return;
                }

                setIsLoading(true);
                setError('');
                setResult('');
                setImageResult('');

                var action = mode === 'text' ? 'writgocms_generate_text' : 'writgocms_generate_image';

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
                            if (mode === 'text') {
                                setResult(response.data.content);
                            } else {
                                setImageResult(response.data.image_url);
                                setAttributes({
                                    imageId: response.data.attachment_id || 0
                                });
                            }
                        } else {
                            setError(response.data.message || 'Generation failed');
                        }
                    },
                    error: function() {
                        setError('Connection error');
                    },
                    complete: function() {
                        setIsLoading(false);
                    }
                });
            }

            function handleInsert() {
                if (mode === 'text' && result) {
                    // Insert as paragraph block
                    var block = wp.blocks.createBlock('core/paragraph', {
                        content: result
                    });
                    dispatch('core/block-editor').insertBlocks(block);
                } else if (mode === 'image' && imageResult) {
                    // Insert as image block
                    var imgBlock = wp.blocks.createBlock('core/image', {
                        url: imageResult,
                        alt: prompt
                    });
                    dispatch('core/block-editor').insertBlocks(imgBlock);
                }

                // Clear the generator
                setResult('');
                setImageResult('');
                setPrompt('');
            }

            function handleClear() {
                setResult('');
                setImageResult('');
                setError('');
            }

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: 'AI Generator Settings' },
                        createElement('p', null, 
                            'Text Provider: ' + (settings.textProvider || 'openai')
                        ),
                        createElement('p', null, 
                            'Image Provider: ' + (settings.imageProvider || 'dalle')
                        )
                    )
                ),
                createElement(
                    'div',
                    { className: 'writgocms-ai-generator-block' },
                    createElement(
                        'div',
                        { className: 'aiml-block-header' },
                        createElement('span', { className: 'aiml-block-icon' }, '🤖'),
                        createElement('span', { className: 'aiml-block-title' }, i18n.blockTitle || 'AI Content Generator')
                    ),
                    createElement(
                        'div',
                        { className: 'aiml-block-mode-toggle' },
                        createElement(
                            ButtonGroup,
                            null,
                            createElement(
                                Button,
                                {
                                    isPrimary: mode === 'text',
                                    isSecondary: mode !== 'text',
                                    onClick: function() { setMode('text'); }
                                },
                                '📝 ' + (i18n.textMode || 'Text')
                            ),
                            createElement(
                                Button,
                                {
                                    isPrimary: mode === 'image',
                                    isSecondary: mode !== 'image',
                                    onClick: function() { setMode('image'); }
                                },
                                '🖼️ ' + (i18n.imageMode || 'Image')
                            )
                        )
                    ),
                    createElement(
                        TextareaControl,
                        {
                            label: i18n.promptLabel || 'Enter your prompt',
                            value: prompt,
                            onChange: setPrompt,
                            rows: 3,
                            className: 'aiml-block-prompt'
                        }
                    ),
                    createElement(
                        'div',
                        { className: 'aiml-block-actions' },
                        createElement(
                            Button,
                            {
                                isPrimary: true,
                                onClick: handleGenerate,
                                disabled: isLoading,
                                className: 'aiml-generate-btn'
                            },
                            isLoading ? createElement(Spinner, null) : null,
                            isLoading ? (i18n.generating || 'Generating...') : ('✨ ' + (i18n.generateBtn || 'Generate'))
                        )
                    ),
                    error && createElement(
                        Notice,
                        {
                            status: 'error',
                            isDismissible: true,
                            onRemove: function() { setError(''); }
                        },
                        error
                    ),
                    (result || imageResult) && createElement(
                        'div',
                        { className: 'aiml-block-result' },
                        createElement('h4', null, i18n.previewTitle || 'Preview'),
                        result && createElement(
                            'div',
                            { className: 'aiml-block-result-text' },
                            result
                        ),
                        imageResult && createElement(
                            'div',
                            { className: 'aiml-block-result-image' },
                            createElement('img', { src: imageResult, alt: 'Generated' })
                        ),
                        createElement(
                            'div',
                            { className: 'aiml-block-result-actions' },
                            createElement(
                                Button,
                                {
                                    isPrimary: true,
                                    onClick: handleInsert
                                },
                                '➕ ' + (i18n.insertBtn || 'Insert as Block')
                            ),
                            createElement(
                                Button,
                                {
                                    isSecondary: true,
                                    onClick: handleClear
                                },
                                '🗑️ ' + (i18n.clearBtn || 'Clear')
                            )
                        )
                    )
                )
            );
        },

        save: function() {
            // Dynamic block - rendered server-side
            return null;
        }
    });

})(window.wp);
