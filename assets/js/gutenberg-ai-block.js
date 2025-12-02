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

    var settings = window.writgoaiAiBlock || {};
    var i18n = settings.i18n || {};

    // Credit costs per action
    var creditCosts = {
        text_generation: 10,
        ai_rewrite_small: 10,
        ai_rewrite_paragraph: 25,
        ai_rewrite_full: 50,
        image_generation: 100,
        ai_image: 100
    };

    registerBlockType('writgoai/ai-generator', {
        title: i18n.blockTitle || 'AI Content Generator',
        description: i18n.blockDescription || 'Generate text or images using AI',
        icon: 'admin-customizer',
        category: 'writgoai-ai',
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

            var _useState7 = useState(null),
                credits = _useState7[0],
                setCredits = _useState7[1];

            var _useState8 = useState(false),
                loadingCredits = _useState8[0],
                setLoadingCredits = _useState8[1];

            var _useState9 = useState(''),
                creditError = _useState9[0],
                setCreditError = _useState9[1];

            // Fetch credits on mount
            useEffect(function() {
                fetchCredits();
            }, []);

            function fetchCredits() {
                setLoadingCredits(true);
                setCreditError('');
                wp.apiFetch({
                    path: '/writgo/v1/credits',
                    method: 'GET'
                }).then(function(response) {
                    setCredits(response);
                    setLoadingCredits(false);
                }).catch(function(err) {
                    setLoadingCredits(false);
                    // Log error for debugging and set error state.
                    console.warn('WritgoAI: Failed to fetch credits', err);
                    setCreditError(err.message || 'Failed to load credit information');
                });
            }

            function getCreditCost() {
                return mode === 'text' ? creditCosts.text_generation : creditCosts.image_generation;
            }

            function hasEnoughCredits() {
                if (!credits) return true; // Allow if credits not loaded
                return credits.credits_remaining >= getCreditCost();
            }

            function handleGenerate() {
                if (!prompt.trim()) {
                    setError(i18n.noPrompt || 'Please enter a prompt');
                    return;
                }

                // Check credits before generating
                var cost = getCreditCost();
                if (credits && credits.credits_remaining < cost) {
                    setError((i18n.insufficientCredits || 'Insufficient credits. Required: ') + cost + ', Available: ' + credits.credits_remaining);
                    return;
                }

                setIsLoading(true);
                setError('');
                setResult('');
                setImageResult('');

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
                            if (mode === 'text') {
                                setResult(response.data.content);
                            } else {
                                setImageResult(response.data.image_url);
                                setAttributes({
                                    imageId: response.data.attachment_id || 0
                                });
                            }
                            // Refresh credits after successful generation
                            fetchCredits();
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

            // Credit display component
            function CreditDisplay() {
                if (loadingCredits) {
                    return createElement('div', { className: 'aiml-credits-loading' },
                        createElement(Spinner, { size: 16 }),
                        ' Loading credits...'
                    );
                }
                if (creditError) {
                    return createElement('div', { 
                        className: 'aiml-credits-error',
                        style: { padding: '10px', background: '#fee', borderRadius: '6px', color: '#c00', fontSize: '13px' }
                    }, '⚠️ ' + creditError);
                }
                if (!credits) return null;

                var remaining = credits.credits_remaining;
                var total = credits.credits_total;
                var percentage = total > 0 ? (remaining / total) * 100 : 0;
                var barColor = percentage > 50 ? '#28a745' : (percentage > 20 ? '#ffc107' : '#dc3545');
                var cost = getCreditCost();
                var canGenerate = remaining >= cost;

                return createElement('div', { className: 'aiml-credits-display' },
                    createElement('div', { className: 'aiml-credits-header' },
                        createElement('span', { className: 'aiml-credits-label' }, '🪙 Credits: '),
                        createElement('strong', { style: { color: canGenerate ? '#28a745' : '#dc3545' } }, 
                            remaining.toLocaleString()
                        ),
                        createElement('span', { className: 'aiml-credits-total' }, ' / ' + total.toLocaleString())
                    ),
                    createElement('div', { className: 'aiml-credits-bar' },
                        createElement('div', { 
                            className: 'aiml-credits-bar-fill',
                            style: { width: percentage + '%', background: barColor }
                        })
                    ),
                    createElement('div', { className: 'aiml-credits-cost' },
                        createElement('span', null, 'This action costs: '),
                        createElement('strong', null, cost),
                        createElement('span', null, ' credits')
                    )
                );
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
                        ),
                        credits && createElement(PanelBody, { title: 'Credit Usage', initialOpen: true },
                            createElement('p', null, 'Remaining: ' + credits.credits_remaining),
                            createElement('p', null, 'Total: ' + credits.credits_total),
                            credits.period_end && createElement('p', null, 'Resets: ' + credits.period_end)
                        )
                    )
                ),
                createElement(
                    'div',
                    { className: 'writgoai-ai-generator-block' },
                    createElement(
                        'div',
                        { className: 'aiml-block-header' },
                        createElement('span', { className: 'aiml-block-icon' }, '🤖'),
                        createElement('span', { className: 'aiml-block-title' }, i18n.blockTitle || 'AI Content Generator')
                    ),
                    createElement(CreditDisplay, null),
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
                                '📝 ' + (i18n.textMode || 'Text') + ' (' + creditCosts.text_generation + ')'
                            ),
                            createElement(
                                Button,
                                {
                                    isPrimary: mode === 'image',
                                    isSecondary: mode !== 'image',
                                    onClick: function() { setMode('image'); }
                                },
                                '🖼️ ' + (i18n.imageMode || 'Image') + ' (' + creditCosts.image_generation + ')'
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
                                disabled: isLoading || !hasEnoughCredits(),
                                className: 'aiml-generate-btn'
                            },
                            isLoading ? createElement(Spinner, null) : null,
                            isLoading ? (i18n.generating || 'Generating...') : ('✨ ' + (i18n.generateBtn || 'Generate'))
                        ),
                        !hasEnoughCredits() && createElement('span', { 
                            style: { color: '#dc3545', marginLeft: '10px', fontSize: '12px' }
                        }, '⚠️ ' + (i18n.insufficientCredits || 'Insufficient credits'))
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
