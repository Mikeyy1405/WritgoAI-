/**
 * Gutenberg AI Toolbar
 *
 * Adds AI-powered toolbar buttons to the Gutenberg editor for:
 * - Text rewriting
 * - Internal link suggestions
 * - AI image generation
 * - Full block rewriting
 * Adds AI-powered block toolbar and sidebar panel to Gutenberg.
 *
 * @package WritgoCMS
 */

( function( wp ) {
	'use strict';

	var registerFormatType = wp.richText.registerFormatType;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useCallback = wp.element.useCallback;
	var RichTextToolbarButton = wp.blockEditor.RichTextToolbarButton;
	var BlockControls = wp.blockEditor.BlockControls;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var select = wp.data.select;
	var dispatch = wp.data.dispatch;
	var createBlock = wp.blocks.createBlock;
	var ToolbarGroup = wp.components.ToolbarGroup;
	var ToolbarButton = wp.components.ToolbarButton;
	var ToolbarDropdownMenu = wp.components.ToolbarDropdownMenu;
	var Modal = wp.components.Modal;
	var Button = wp.components.Button;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var Spinner = wp.components.Spinner;
	var CheckboxControl = wp.components.CheckboxControl;
	var addFilter = wp.hooks.addFilter;
	var __ = wp.i18n.__;

	var settings = window.writgoaiToolbar || {};
	var i18n = settings.i18n || {};
	var buttons = settings.buttons || {};

	/**
	 * Helper function to make AJAX requests using fetch API
	 *
	 * @param {Object} options Request options.
	 * @param {string} options.action AJAX action name.
	 * @param {Object} options.data Additional data to send.
	 * @return {Promise} Promise resolving to response data.
	 */
	function ajaxRequest( options ) {
		var formData = new FormData();
		formData.append( 'action', options.action );
		formData.append( 'nonce', settings.nonce );
		
		if ( options.data ) {
			Object.keys( options.data ).forEach( function( key ) {
				formData.append( key, options.data[ key ] );
			} );
		}

		return fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		} )
		.then( function( response ) {
			if ( ! response.ok ) {
				throw new Error( 'Network response was not ok' );
			}
			return response.json();
		} );
	}

	/**
	 * Toast notification helper
	 */
	function showToast( message, type ) {
		type = type || 'success';
		var toast = document.createElement( 'div' );
		toast.className = 'writgoai-toast ' + type;
		
		var icon = type === 'success' ? '✅' : ( type === 'error' ? '❌' : 'ℹ️' );
		toast.innerHTML = '<span class="toast-icon">' + icon + '</span><span>' + message + '</span>';
		
		document.body.appendChild( toast );
		
		setTimeout( function() {
			toast.classList.add( 'hiding' );
			setTimeout( function() {
				if ( toast.parentNode ) {
					toast.parentNode.removeChild( toast );
				}
			}, 300 );
		}, 3000 );
	}

	/**
	 * AI Toolbar Component
	 */
	function WritgoCMSAIToolbar( props ) {
		var value = props.value;
		var onChange = props.onChange;
		var isActive = props.isActive;
		var activeAttributes = props.activeAttributes;
		var contentRef = props.contentRef;

		var _useState1 = useState( false );
		var showRewriteModal = _useState1[0];
		var setShowRewriteModal = _useState1[1];

		var _useState2 = useState( false );
		var showImageModal = _useState2[0];
		var setShowImageModal = _useState2[1];

		var _useState3 = useState( false );
		var showLinksModal = _useState3[0];
		var setShowLinksModal = _useState3[1];

		var _useState4 = useState( false );
		var isLoading = _useState4[0];
		var setIsLoading = _useState4[1];

		var _useState5 = useState( '' );
		var selectedText = _useState5[0];
		var setSelectedText = _useState5[1];

		var _useState6 = useState( '' );
		var rewrittenText = _useState6[0];
		var setRewrittenText = _useState6[1];

		var _useState7 = useState( settings.defaultTone || 'professional' );
		var selectedTone = _useState7[0];
		var setSelectedTone = _useState7[1];

		var _useState8 = useState( '' );
		var imagePrompt = _useState8[0];
		var setImagePrompt = _useState8[1];

		var _useState9 = useState( null );
		var generatedImage = _useState9[0];
		var setGeneratedImage = _useState9[1];

		var _useState10 = useState( [] );
		var suggestedLinks = _useState10[0];
		var setSuggestedLinks = _useState10[1];

		var _useState11 = useState( [] );
		var selectedLinks = _useState11[0];
		var setSelectedLinks = _useState11[1];

		var _useState12 = useState( false );
		var isRewriteAll = _useState12[0];
		var setIsRewriteAll = _useState12[1];

		/**
		 * Get currently selected text from editor
		 */
		function getSelectedText() {
			if ( value && value.text && value.start !== value.end ) {
				return value.text.substring( value.start, value.end );
			}
			return '';
		}

		/**
		 * Handle AI Rewrite button click
		 */
		function handleRewriteClick() {
			var text = getSelectedText();
			if ( ! text ) {
				showToast( i18n.errorNoSelection || 'Please select some text first.', 'error' );
				return;
			}
			setSelectedText( text );
			setRewrittenText( '' );
			setIsRewriteAll( false );
			setShowRewriteModal( true );
		}

		/**
		 * Handle Rewrite All button click
		 */
		function handleRewriteAllClick() {
			if ( value && value.text ) {
				setSelectedText( value.text );
				setRewrittenText( '' );
				setIsRewriteAll( true );
				setShowRewriteModal( true );
			}
		}

		/**
		 * Handle Add Links button click
		 */
		function handleLinksClick() {
			var text = getSelectedText();
			if ( ! text ) {
				showToast( i18n.errorNoSelection || 'Please select some text first.', 'error' );
				return;
			}
			setSelectedText( text );
			setSuggestedLinks( [] );
			setSelectedLinks( [] );
			setShowLinksModal( true );
			fetchInternalLinks( text );
		}

		/**
		 * Handle Generate Image button click
		 */
		function handleImageClick() {
			var text = getSelectedText();
			setSelectedText( text );
			setImagePrompt( text );
			setGeneratedImage( null );
			setShowImageModal( true );
		}

		/**
		 * Perform text rewriting via AJAX
		 */
		function performRewrite() {
			setIsLoading( true );
			setRewrittenText( '' );

			ajaxRequest( {
				action: 'writgoai_toolbar_rewrite',
				data: {
					text: selectedText,
					tone: selectedTone
				}
			} )
			.then( function( response ) {
				if ( response.success && response.data.rewritten ) {
					setRewrittenText( response.data.rewritten );
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : ( i18n.errorGeneral || 'An error occurred.' );
					showToast( errorMsg, 'error' );
				}
			} )
			.catch( function() {
				showToast( i18n.errorGeneral || 'An error occurred.', 'error' );
			} )
			.finally( function() {
				setIsLoading( false );
			} );
		}

		/**
		 * Accept rewritten text and replace selection
		 */
		function acceptRewrite() {
			if ( ! rewrittenText ) return;

			if ( isRewriteAll ) {
				// Replace entire block content
				var newValue = {
					...value,
					text: rewrittenText,
					start: 0,
					end: rewrittenText.length
				};
				onChange( newValue );
			} else {
				// Replace only selected text
				var before = value.text.substring( 0, value.start );
				var after = value.text.substring( value.end );
				var newText = before + rewrittenText + after;
				
				var newValue = {
					...value,
					text: newText,
					start: value.start,
					end: value.start + rewrittenText.length
				};
				onChange( newValue );
			}

			setShowRewriteModal( false );
			showToast( i18n.successRewrite || 'Text rewritten successfully!', 'success' );
		}

		/**
		 * Fetch internal link suggestions
		 */
		function fetchInternalLinks( text ) {
			setIsLoading( true );

			ajaxRequest( {
				action: 'writgoai_toolbar_get_internal_links',
				data: {
					text: text,
					limit: settings.linksLimit || 5
				}
			} )
			.then( function( response ) {
				if ( response.success && response.data.links ) {
					setSuggestedLinks( response.data.links );
				} else {
					setSuggestedLinks( [] );
				}
			} )
			.catch( function() {
				setSuggestedLinks( [] );
				showToast( i18n.errorGeneral || 'An error occurred.', 'error' );
			} )
			.finally( function() {
				setIsLoading( false );
			} );
		}

		/**
		 * Toggle link selection
		 */
		function toggleLinkSelection( linkId ) {
			setSelectedLinks( function( prev ) {
				if ( prev.indexOf( linkId ) !== -1 ) {
					return prev.filter( function( id ) { return id !== linkId; } );
				}
				return prev.concat( [ linkId ] );
			} );
		}

		/**
		 * Insert selected links into content
		 */
		function insertSelectedLinks() {
			if ( selectedLinks.length === 0 ) return;

			// Get the first selected link to insert
			var linkToInsert = suggestedLinks.find( function( link ) {
				return selectedLinks.indexOf( link.id ) !== -1;
			} );

			if ( linkToInsert ) {
				// Create a link format
				var newValue = wp.richText.applyFormat( value, {
					type: 'core/link',
					attributes: {
						url: linkToInsert.url,
						type: 'internal'
					}
				} );
				onChange( newValue );
			}

			setShowLinksModal( false );
			showToast( i18n.successLinks || 'Links inserted successfully!', 'success' );
		}

		/**
		 * Generate AI image
		 */
		function generateImage() {
			if ( ! imagePrompt.trim() ) {
				showToast( i18n.errorNoSelection || 'Please enter a prompt.', 'error' );
				return;
			}

			setIsLoading( true );
			setGeneratedImage( null );

			ajaxRequest( {
				action: 'writgoai_toolbar_generate_image',
				data: {
					prompt: imagePrompt
				}
			} )
			.then( function( response ) {
				if ( response.success && response.data.image_url ) {
					setGeneratedImage( {
						url: response.data.image_url,
						attachmentId: response.data.attachment_id
					} );
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : ( i18n.errorGeneral || 'An error occurred.' );
					showToast( errorMsg, 'error' );
				}
			} )
			.catch( function() {
				showToast( i18n.errorGeneral || 'An error occurred.', 'error' );
			} )
			.finally( function() {
				setIsLoading( false );
			} );
		}

		/**
		 * Insert generated image into editor
		 */
		function insertImage() {
			if ( ! generatedImage ) return;

			// Create and insert an image block
			var imageBlock = createBlock( 'core/image', {
				url: generatedImage.url,
				id: generatedImage.attachmentId,
				alt: imagePrompt
			} );

			dispatch( 'core/block-editor' ).insertBlocks( imageBlock );

			setShowImageModal( false );
			showToast( i18n.successImage || 'Image generated and inserted!', 'success' );
		}

		/**
		 * Render Rewrite Modal
		 */
		function renderRewriteModal() {
			if ( ! showRewriteModal ) return null;

			return createElement(
				Modal,
				{
					title: createElement(
						'span',
						{ className: 'writgoai-modal-title' },
						createElement( 'span', { className: 'header-icon' }, '🤖' ),
						' ',
						isRewriteAll ? ( i18n.rewriteAll || 'Rewrite All' ) : ( i18n.rewriteTitle || 'AI Rewrite Text' )
					),
					onRequestClose: function() { setShowRewriteModal( false ); },
					className: 'writgoai-rewrite-modal'
				},
				createElement(
					'div',
					{ className: 'writgoai-modal-body' },
					// Tone selector
					createElement(
						'div',
						{ className: 'writgoai-tone-selector' },
						createElement(
							SelectControl,
							{
								label: i18n.toneLabel || 'Rewrite Tone',
								value: selectedTone,
								options: [
									{ label: i18n.toneProfessional || 'Professional', value: 'professional' },
									{ label: i18n.toneCasual || 'Casual', value: 'casual' },
									{ label: i18n.toneFriendly || 'Friendly', value: 'friendly' },
									{ label: i18n.toneFormal || 'Formal', value: 'formal' },
									{ label: i18n.toneCreative || 'Creative', value: 'creative' }
								],
								onChange: setSelectedTone,
								disabled: isLoading
							}
						)
					),
					// Loading state or result
					isLoading ? 
						createElement(
							'div',
							{ className: 'writgoai-loading' },
							createElement( 'div', { className: 'writgoai-spinner' } ),
							createElement( 'span', { className: 'writgoai-loading-text' }, i18n.loading || 'Generating...' )
						) :
						createElement(
							'div',
							{ className: 'writgoai-text-preview' },
							createElement(
								'div',
								{ className: 'writgoai-text-section original' },
								createElement( 'h4', null, i18n.originalText || 'Original Text' ),
								createElement( 'p', null, selectedText )
							),
							rewrittenText && createElement(
								'div',
								{ className: 'writgoai-text-section rewritten' },
								createElement( 'h4', null, i18n.rewrittenText || 'Rewritten Text' ),
								createElement( 'p', null, rewrittenText )
							)
						)
				),
				createElement(
					'div',
					{ className: 'writgoai-modal-footer' },
					! rewrittenText ? 
						createElement(
							Fragment,
							null,
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-secondary',
									onClick: function() { setShowRewriteModal( false ); }
								},
								i18n.cancel || 'Cancel'
							),
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-primary',
									onClick: performRewrite,
									disabled: isLoading
								},
								'✨ ',
								i18n.generate || 'Generate'
							)
						) :
						createElement(
							Fragment,
							null,
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-secondary',
									onClick: performRewrite,
									disabled: isLoading
								},
								'🔄 ',
								i18n.regenerate || 'Regenerate'
							),
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-success',
									onClick: acceptRewrite
								},
								'✓ ',
								i18n.accept || 'Accept'
							)
						)
				)
			);
		}

		/**
		 * Render Links Modal
		 */
		function renderLinksModal() {
			if ( ! showLinksModal ) return null;

			return createElement(
				Modal,
				{
					title: createElement(
						'span',
						{ className: 'writgoai-modal-title' },
						createElement( 'span', { className: 'header-icon' }, '🔗' ),
						' ',
						i18n.linksTitle || 'Suggested Internal Links'
					),
					onRequestClose: function() { setShowLinksModal( false ); },
					className: 'writgoai-links-modal'
				},
				createElement(
					'div',
					{ className: 'writgoai-modal-body' },
					isLoading ?
						createElement(
							'div',
							{ className: 'writgoai-loading' },
							createElement( 'div', { className: 'writgoai-spinner' } ),
							createElement( 'span', { className: 'writgoai-loading-text' }, i18n.loading || 'Loading...' )
						) :
						suggestedLinks.length > 0 ?
							createElement(
								'div',
								{ className: 'writgoai-links-list' },
								suggestedLinks.map( function( link ) {
									var isSelected = selectedLinks.indexOf( link.id ) !== -1;
									return createElement(
										'div',
										{
											key: link.id,
											className: 'writgoai-link-item' + ( isSelected ? ' selected' : '' ),
											onClick: function() { toggleLinkSelection( link.id ); }
										},
										createElement(
											'div',
											{ className: 'writgoai-link-checkbox' },
											createElement(
												'input',
												{
													type: 'checkbox',
													checked: isSelected,
													onChange: function() {}
												}
											)
										),
										createElement(
											'div',
											{ className: 'writgoai-link-content' },
											createElement( 'div', { className: 'writgoai-link-title' }, link.title ),
											createElement( 'div', { className: 'writgoai-link-excerpt' }, link.excerpt )
										),
										createElement(
											'span',
											{ className: 'writgoai-link-type' },
											link.type
										)
									);
								} )
							) :
							createElement(
								'div',
								{ className: 'writgoai-no-links' },
								createElement( 'span', { className: 'no-links-icon' }, '🔍' ),
								createElement( 'p', null, i18n.noLinksFound || 'No relevant internal links found.' )
							)
				),
				createElement(
					'div',
					{ className: 'writgoai-modal-footer' },
					createElement(
						Button,
						{
							className: 'writgoai-btn writgoai-btn-secondary',
							onClick: function() { setShowLinksModal( false ); }
						},
						i18n.cancel || 'Cancel'
					),
					createElement(
						Button,
						{
							className: 'writgoai-btn writgoai-btn-primary',
							onClick: insertSelectedLinks,
							disabled: selectedLinks.length === 0
						},
						'🔗 ',
						i18n.insertLinks || 'Insert Selected',
						selectedLinks.length > 0 ? ' (' + selectedLinks.length + ')' : ''
					)
				)
			);
		}

		/**
		 * Render Image Modal
		 */
		function renderImageModal() {
			if ( ! showImageModal ) return null;

			return createElement(
				Modal,
				{
					title: createElement(
						'span',
						{ className: 'writgoai-modal-title' },
						createElement( 'span', { className: 'header-icon' }, '🖼️' ),
						' ',
						i18n.imageTitle || 'Generate AI Image'
					),
					onRequestClose: function() { setShowImageModal( false ); },
					className: 'writgoai-image-modal'
				},
				createElement(
					'div',
					{ className: 'writgoai-modal-body' },
					! generatedImage ?
						createElement(
							'div',
							{ className: 'writgoai-image-form' },
							createElement(
								TextareaControl,
								{
									label: i18n.imagePrompt || 'Describe the image you want to generate...',
									value: imagePrompt,
									onChange: setImagePrompt,
									rows: 4,
									disabled: isLoading
								}
							),
							selectedText && createElement(
								'p',
								{ className: 'writgoai-image-hint' },
								i18n.useSelectedText || 'Selected text has been used as the initial prompt.'
							),
							isLoading && createElement(
								'div',
								{ className: 'writgoai-loading' },
								createElement( 'div', { className: 'writgoai-spinner' } ),
								createElement( 'span', { className: 'writgoai-loading-text' }, i18n.loading || 'Generating image...' )
							)
						) :
						createElement(
							'div',
							{ className: 'writgoai-image-preview' },
							createElement( 'img', { src: generatedImage.url, alt: imagePrompt } )
						)
				),
				createElement(
					'div',
					{ className: 'writgoai-modal-footer' },
					! generatedImage ?
						createElement(
							Fragment,
							null,
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-secondary',
									onClick: function() { setShowImageModal( false ); }
								},
								i18n.cancel || 'Cancel'
							),
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-primary',
									onClick: generateImage,
									disabled: isLoading || ! imagePrompt.trim()
								},
								'🖼️ ',
								i18n.generate || 'Generate'
							)
						) :
						createElement(
							Fragment,
							null,
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-secondary',
									onClick: function() {
										setGeneratedImage( null );
									}
								},
								'🔄 ',
								i18n.regenerate || 'Regenerate'
							),
							createElement(
								Button,
								{
									className: 'writgoai-btn writgoai-btn-success',
									onClick: insertImage
								},
								'➕ ',
								i18n.insertImage || 'Insert Image'
							)
						)
				)
			);
		}

		// Build toolbar buttons array based on settings
		var toolbarButtons = [];

		if ( buttons.rewrite !== false ) {
			toolbarButtons.push( {
				icon: createElement( 'span', { style: { fontSize: '16px' } }, '🤖' ),
				title: i18n.rewrite || 'AI Rewrite',
				onClick: handleRewriteClick
			} );
		}

		if ( buttons.links !== false ) {
			toolbarButtons.push( {
				icon: createElement( 'span', { style: { fontSize: '16px' } }, '🔗' ),
				title: i18n.addLinks || 'Add Links',
				onClick: handleLinksClick
			} );
		}

		if ( buttons.image !== false ) {
			toolbarButtons.push( {
				icon: createElement( 'span', { style: { fontSize: '16px' } }, '🖼️' ),
				title: i18n.generateImage || 'Generate Image',
				onClick: handleImageClick
			} );
		}

		if ( buttons.rewrite_all !== false ) {
			toolbarButtons.push( {
				icon: createElement( 'span', { style: { fontSize: '16px' } }, '📝' ),
				title: i18n.rewriteAll || 'Rewrite All',
				onClick: handleRewriteAllClick
			} );
		}

		return createElement(
			Fragment,
			null,
			toolbarButtons.map( function( button, index ) {
				return createElement(
					RichTextToolbarButton,
					{
						key: index,
						icon: button.icon,
						title: button.title,
						onClick: button.onClick,
						isActive: isActive
					}
				);
			} ),
			renderRewriteModal(),
			renderLinksModal(),
			renderImageModal()
		);
	}

	// Register the format type with the toolbar
	registerFormatType( 'writgoai/ai-toolbar', {
		title: 'WritgoAI',
		tagName: 'span',
		className: 'writgoai-ai-enhanced',
		edit: WritgoCMSAIToolbar
	} );

} )( window.wp );
(function(wp) {
    'use strict';

    var registerPlugin = wp.plugins.registerPlugin;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var Button = wp.components.Button;
    var PanelBody = wp.components.PanelBody;
    var PanelRow = wp.components.PanelRow;
    var TextareaControl = wp.components.TextareaControl;
    var Spinner = wp.components.Spinner;
    var Notice = wp.components.Notice;
    var Modal = wp.components.Modal;
    var BlockControls = wp.blockEditor.BlockControls;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var addFilter = wp.hooks.addFilter;
    var DropdownMenu = wp.components.DropdownMenu;
    var MenuGroup = wp.components.MenuGroup;
    var MenuItem = wp.components.MenuItem;
    var __ = wp.i18n.__;

    var settings = window.writgoaiAiToolbar || {};
    var i18n = settings.i18n || {};

    /**
     * AI Sidebar Panel Component
     */
    function WritgoAISidebar() {
        var _useState = useState(false),
            isLoading = _useState[0],
            setIsLoading = _useState[1];

        var _useState2 = useState(null),
            error = _useState2[0],
            setError = _useState2[1];

        var _useState3 = useState(null),
            success = _useState3[0],
            setSuccess = _useState3[1];

        var _useState4 = useState(null),
            result = _useState4[0],
            setResult = _useState4[1];

        var _useState5 = useState(false),
            showModal = _useState5[0],
            setShowModal = _useState5[1];

        var _useState6 = useState(''),
            modalAction = _useState6[0],
            setModalAction = _useState6[1];

        var _useState7 = useState(''),
            imagePrompt = _useState7[0],
            setImagePrompt = _useState7[1];

        var _useState8 = useState({
            requests_used: settings.usage ? settings.usage.requests_used : 0,
            requests_remaining: settings.usage ? settings.usage.requests_remaining : 1000,
            daily_limit: settings.usage ? settings.usage.daily_limit : 1000
        }),
            usage = _useState8[0],
            setUsage = _useState8[1];

        // Get post data
        var postData = useSelect(function(select) {
            var editor = select('core/editor');
            return {
                title: editor.getEditedPostAttribute('title'),
                content: editor.getEditedPostContent(),
                blocks: select('core/block-editor').getBlocks()
            };
        }, []);

        var _useDispatch = useDispatch('core/editor'),
            editPost = _useDispatch.editPost;

        var _useDispatch2 = useDispatch('core/block-editor'),
            updateBlockAttributes = _useDispatch2.updateBlockAttributes,
            replaceBlocks = _useDispatch2.replaceBlocks,
            insertBlocks = _useDispatch2.insertBlocks;

        // Update usage when events fire
        useEffect(function() {
            var handleUsageUpdate = function(e, usageData) {
                setUsage(usageData);
            };

            jQuery(document).on('writgoai:usage:updated', handleUsageUpdate);

            return function() {
                jQuery(document).off('writgoai:usage:updated', handleUsageUpdate);
            };
        }, []);

        /**
         * Handle AI action
         */
        function handleAction(action) {
            if (isLoading) return;

            var client = window.WritgoAIClient;
            if (!client) {
                setError('AI client not available');
                return;
            }

            setIsLoading(true);
            setError(null);
            setSuccess(null);
            setResult(null);

            var promise;
            var plainContent = getPlainContent(postData.content);

            switch (action) {
                case 'rewriteArticle':
                    promise = client.rewriteArticle(plainContent, postData.title);
                    break;
                case 'seoOptimize':
                    promise = client.seoOptimize(plainContent, postData.title);
                    break;
                case 'generateMeta':
                    promise = client.generateMeta(plainContent, postData.title);
                    break;
                case 'generateFeatured':
                    setModalAction('generateFeatured');
                    setShowModal(true);
                    setIsLoading(false);
                    return;
                case 'autoLinkContent':
                    promise = client.addLinks(plainContent);
                    break;
                default:
                    setIsLoading(false);
                    return;
            }

            promise
                .then(function(response) {
                    setIsLoading(false);

                    if (action === 'rewriteArticle') {
                        setResult({
                            type: 'rewrite',
                            content: response.content
                        });
                        setSuccess(i18n.success || 'Artikel herschreven!');
                    } else if (action === 'seoOptimize') {
                        setResult({
                            type: 'seo',
                            data: response.seo_data || response.raw
                        });
                        setSuccess('SEO analyse gereed!');
                    } else if (action === 'generateMeta') {
                        setResult({
                            type: 'meta',
                            content: response.meta_description
                        });
                        setSuccess('Meta description gegenereerd!');
                    } else if (action === 'autoLinkContent') {
                        setResult({
                            type: 'links',
                            content: response.content
                        });
                        setSuccess('Links toegevoegd!');
                    }

                    // Update usage
                    if (response.usage) {
                        setUsage(response.usage);
                    }
                })
                .catch(function(err) {
                    setIsLoading(false);
                    setError(err.message || 'Er is een fout opgetreden');
                });
        }

        /**
         * Handle image generation
         */
        function handleGenerateImage() {
            if (!imagePrompt.trim()) {
                setError('Voer een prompt in voor de afbeelding');
                return;
            }

            var client = window.WritgoAIClient;
            if (!client) {
                setError('AI client not available');
                return;
            }

            setIsLoading(true);
            setError(null);

            client.generateImage(imagePrompt)
                .then(function(response) {
                    setIsLoading(false);
                    setShowModal(false);

                    if (response.image_url) {
                        // Set as featured image if attachment_id is available
                        if (response.attachment_id) {
                            editPost({ featured_media: response.attachment_id });
                            setSuccess('Featured image ingesteld!');
                        } else {
                            // Insert as image block
                            var imageBlock = wp.blocks.createBlock('core/image', {
                                url: response.image_url,
                                alt: imagePrompt
                            });
                            insertBlocks(imageBlock);
                            setSuccess('Afbeelding ingevoegd!');
                        }
                    }

                    setImagePrompt('');
                })
                .catch(function(err) {
                    setIsLoading(false);
                    setError(err.message || 'Afbeelding genereren mislukt');
                });
        }

        /**
         * Get plain text content from blocks
         */
        function getPlainContent(content) {
            // Remove HTML tags for AI processing
            var temp = document.createElement('div');
            temp.innerHTML = content;
            return temp.textContent || temp.innerText || '';
        }

        /**
         * Apply rewritten content
         */
        function applyRewrittenContent() {
            if (!result || result.type !== 'rewrite') return;

            // Split the content by paragraphs and create blocks
            var paragraphs = result.content.split(/\n\n+/);
            var newBlocks = paragraphs.map(function(paragraph) {
                if (paragraph.trim()) {
                    return wp.blocks.createBlock('core/paragraph', {
                        content: paragraph.replace(/\n/g, '<br>')
                    });
                }
                return null;
            }).filter(function(block) {
                return block !== null;
            });

            if (newBlocks && newBlocks.length > 0) {
                // Replace all content blocks
                var currentBlocks = postData.blocks;
                var blockIds = currentBlocks.map(function(block) {
                    return block.clientId;
                });

                if (blockIds.length > 0) {
                    replaceBlocks(blockIds, newBlocks);
                }
            }

            setResult(null);
            setSuccess('Content toegepast!');
        }

        return createElement(
            Fragment,
            null,
            createElement(
                PluginSidebarMoreMenuItem,
                {
                    target: 'writgoai-sidebar',
                    icon: 'superhero'
                },
                i18n.sidebarTitle || 'WritgoAI'
            ),
            createElement(
                PluginSidebar,
                {
                    name: 'writgoai-sidebar',
                    title: i18n.sidebarTitle || 'WritgoAI',
                    icon: 'superhero'
                },
                createElement(
                    PanelBody,
                    { title: 'AI Acties', initialOpen: true },
                    // Error notice
                    error && createElement(
                        Notice,
                        {
                            status: 'error',
                            isDismissible: true,
                            onRemove: function() { setError(null); }
                        },
                        error
                    ),
                    // Success notice
                    success && createElement(
                        Notice,
                        {
                            status: 'success',
                            isDismissible: true,
                            onRemove: function() { setSuccess(null); }
                        },
                        success
                    ),
                    // Action buttons
                    createElement(
                        PanelRow,
                        null,
                        createElement(
                            Button,
                            {
                                variant: 'secondary',
                                onClick: function() { handleAction('rewriteArticle'); },
                                disabled: isLoading,
                                className: 'writgoai-sidebar-btn',
                                icon: 'edit'
                            },
                            isLoading ? createElement(Spinner, null) : null,
                            ' ',
                            i18n.rewriteArticle || 'Artikel herschrijven'
                        )
                    ),
                    createElement(
                        PanelRow,
                        null,
                        createElement(
                            Button,
                            {
                                variant: 'secondary',
                                onClick: function() { handleAction('seoOptimize'); },
                                disabled: isLoading,
                                className: 'writgoai-sidebar-btn',
                                icon: 'search'
                            },
                            i18n.seoOptimize || 'SEO optimaliseren'
                        )
                    ),
                    createElement(
                        PanelRow,
                        null,
                        createElement(
                            Button,
                            {
                                variant: 'secondary',
                                onClick: function() { handleAction('generateMeta'); },
                                disabled: isLoading,
                                className: 'writgoai-sidebar-btn',
                                icon: 'text'
                            },
                            i18n.generateMeta || 'Meta description'
                        )
                    ),
                    createElement(
                        PanelRow,
                        null,
                        createElement(
                            Button,
                            {
                                variant: 'secondary',
                                onClick: function() { handleAction('generateFeatured'); },
                                disabled: isLoading,
                                className: 'writgoai-sidebar-btn',
                                icon: 'format-image'
                            },
                            i18n.generateFeatured || 'Featured image'
                        )
                    ),
                    createElement(
                        PanelRow,
                        null,
                        createElement(
                            Button,
                            {
                                variant: 'secondary',
                                onClick: function() { handleAction('autoLinkContent'); },
                                disabled: isLoading,
                                className: 'writgoai-sidebar-btn',
                                icon: 'admin-links'
                            },
                            i18n.autoLinkContent || 'Auto-link content'
                        )
                    )
                ),
                // Result panel
                result && createElement(
                    PanelBody,
                    { title: 'Resultaat', initialOpen: true },
                    result.type === 'rewrite' && createElement(
                        Fragment,
                        null,
                        createElement(
                            'div',
                            { className: 'writgoai-result-preview' },
                            result.content.substring(0, 500) + (result.content.length > 500 ? '...' : '')
                        ),
                        createElement(
                            PanelRow,
                            null,
                            createElement(
                                Button,
                                {
                                    variant: 'primary',
                                    onClick: applyRewrittenContent
                                },
                                'Toepassen'
                            ),
                            createElement(
                                Button,
                                {
                                    variant: 'secondary',
                                    onClick: function() { setResult(null); },
                                    style: { marginLeft: '8px' }
                                },
                                'Annuleren'
                            )
                        )
                    ),
                    result.type === 'meta' && createElement(
                        Fragment,
                        null,
                        createElement(
                            'div',
                            { className: 'writgoai-result-preview writgoai-meta-result' },
                            createElement('strong', null, 'Meta description:'),
                            createElement('p', null, result.content)
                        ),
                        createElement(
                            PanelRow,
                            null,
                            createElement(
                                Button,
                                {
                                    variant: 'secondary',
                                    onClick: function() {
                                        // Copy to clipboard
                                        navigator.clipboard.writeText(result.content);
                                        setSuccess('Gekopieerd naar klembord!');
                                    }
                                },
                                'Kopiëren'
                            )
                        )
                    ),
                    result.type === 'seo' && createElement(
                        'div',
                        { className: 'writgoai-seo-result' },
                        typeof result.data === 'object' ? createElement(
                            Fragment,
                            null,
                            result.data.optimized_title && createElement(
                                'div',
                                { className: 'seo-item' },
                                createElement('strong', null, 'Geoptimaliseerde titel:'),
                                createElement('p', null, result.data.optimized_title)
                            ),
                            result.data.meta_description && createElement(
                                'div',
                                { className: 'seo-item' },
                                createElement('strong', null, 'Meta description:'),
                                createElement('p', null, result.data.meta_description)
                            ),
                            result.data.improvements && createElement(
                                'div',
                                { className: 'seo-item' },
                                createElement('strong', null, 'Verbeterpunten:'),
                                createElement(
                                    'ul',
                                    null,
                                    result.data.improvements.map(function(item, idx) {
                                        return createElement('li', { key: idx }, item);
                                    })
                                )
                            ),
                            result.data.seo_score && createElement(
                                'div',
                                { className: 'seo-score' },
                                'SEO Score: ',
                                createElement('strong', null, result.data.seo_score + '/100')
                            )
                        ) : createElement('pre', null, result.data)
                    )
                ),
                // Usage panel
                createElement(
                    PanelBody,
                    { title: i18n.usageLabel || 'Gebruik', initialOpen: false },
                    createElement(
                        'div',
                        { className: 'writgoai-usage' },
                        createElement(
                            'div',
                            { className: 'usage-bar' },
                            createElement(
                                'div',
                                {
                                    className: 'usage-fill',
                                    style: {
                                        width: Math.min(100, (usage.requests_used / usage.daily_limit) * 100) + '%'
                                    }
                                }
                            )
                        ),
                        createElement(
                            'div',
                            { className: 'usage-text' },
                            usage.requests_used + ' / ' + usage.daily_limit + ' ' + (i18n.requestsRemaining || 'verzoeken gebruikt')
                        )
                    )
                )
            ),
            // Image generation modal
            showModal && modalAction === 'generateFeatured' && createElement(
                Modal,
                {
                    title: i18n.generateFeatured || 'Featured image genereren',
                    onRequestClose: function() {
                        setShowModal(false);
                        setImagePrompt('');
                    }
                },
                createElement(
                    TextareaControl,
                    {
                        label: i18n.imagePrompt || 'Beschrijf de afbeelding',
                        value: imagePrompt,
                        onChange: setImagePrompt,
                        rows: 3
                    }
                ),
                createElement(
                    'div',
                    { className: 'writgoai-modal-actions' },
                    createElement(
                        Button,
                        {
                            variant: 'primary',
                            onClick: handleGenerateImage,
                            disabled: isLoading || !imagePrompt.trim()
                        },
                        isLoading ? createElement(Spinner, null) : null,
                        ' ',
                        i18n.generateBtn || 'Genereren'
                    ),
                    createElement(
                        Button,
                        {
                            variant: 'secondary',
                            onClick: function() {
                                setShowModal(false);
                                setImagePrompt('');
                            },
                            style: { marginLeft: '8px' }
                        },
                        i18n.cancel || 'Annuleren'
                    )
                )
            )
        );
    }

    /**
     * Block Toolbar Extension
     * Adds AI actions to block toolbar
     */
    var withWritgoAIToolbar = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            var blockName = props.name;
            var clientId = props.clientId;

            var _useState9 = useState(false),
                isLoading = _useState9[0],
                setIsLoading = _useState9[1];

            var _useState10 = useState(false),
                showImageModal = _useState10[0],
                setShowImageModal = _useState10[1];

            var _useState11 = useState(''),
                imagePrompt = _useState11[0],
                setImagePrompt = _useState11[1];

            var _useDispatch3 = useDispatch('core/block-editor'),
                updateBlockAttributes = _useDispatch3.updateBlockAttributes,
                insertBlocks = _useDispatch3.insertBlocks;

            // Only show for text blocks
            var supportedBlocks = [
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote'
            ];

            if (supportedBlocks.indexOf(blockName) === -1) {
                return createElement(BlockEdit, props);
            }

            /**
             * Handle block rewrite
             */
            function handleRewriteBlock() {
                var content = props.attributes.content || '';
                if (!content) return;

                var client = window.WritgoAIClient;
                if (!client) return;

                setIsLoading(true);

                client.rewriteBlock(content)
                    .then(function(response) {
                        setIsLoading(false);
                        updateBlockAttributes(clientId, { content: response.content });
                    })
                    .catch(function(err) {
                        setIsLoading(false);
                        console.error('Rewrite error:', err);
                    });
            }

            /**
             * Handle image generation for block
             */
            function handleGenerateImage() {
                if (!imagePrompt.trim()) return;

                var client = window.WritgoAIClient;
                if (!client) return;

                setIsLoading(true);

                client.generateImage(imagePrompt)
                    .then(function(response) {
                        setIsLoading(false);
                        setShowImageModal(false);

                        if (response.image_url) {
                            // Insert image block after current block
                            var imageBlock = wp.blocks.createBlock('core/image', {
                                url: response.image_url,
                                alt: imagePrompt
                            });

                            var index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                            insertBlocks(imageBlock, index + 1);
                        }

                        setImagePrompt('');
                    })
                    .catch(function(err) {
                        setIsLoading(false);
                        console.error('Image generation error:', err);
                    });
            }

            /**
             * Handle auto-linking
             */
            function handleAutoLink() {
                var content = props.attributes.content || '';
                if (!content) return;

                var client = window.WritgoAIClient;
                if (!client) return;

                setIsLoading(true);

                client.addLinks(content)
                    .then(function(response) {
                        setIsLoading(false);
                        updateBlockAttributes(clientId, { content: response.content });
                    })
                    .catch(function(err) {
                        setIsLoading(false);
                        console.error('Auto-link error:', err);
                    });
            }

            return createElement(
                Fragment,
                null,
                createElement(BlockEdit, props),
                createElement(
                    BlockControls,
                    { group: 'other' },
                    createElement(
                        DropdownMenu,
                        {
                            icon: 'superhero',
                            label: i18n.aiActions || 'WritgoAI',
                            className: 'writgoai-block-dropdown'
                        },
                        function(_ref) {
                            var onClose = _ref.onClose;
                            return createElement(
                                Fragment,
                                null,
                                createElement(
                                    MenuGroup,
                                    null,
                                    createElement(
                                        MenuItem,
                                        {
                                            icon: 'edit',
                                            onClick: function() {
                                                onClose();
                                                handleRewriteBlock();
                                            },
                                            disabled: isLoading
                                        },
                                        isLoading ? createElement(Spinner, null) : null,
                                        ' ',
                                        i18n.rewriteBlock || 'Blok herschrijven'
                                    ),
                                    createElement(
                                        MenuItem,
                                        {
                                            icon: 'format-image',
                                            onClick: function() {
                                                onClose();
                                                setShowImageModal(true);
                                            }
                                        },
                                        i18n.generateImage || 'Afbeelding genereren'
                                    ),
                                    createElement(
                                        MenuItem,
                                        {
                                            icon: 'admin-links',
                                            onClick: function() {
                                                onClose();
                                                handleAutoLink();
                                            },
                                            disabled: isLoading
                                        },
                                        i18n.autoLink || 'Auto-link'
                                    )
                                )
                            );
                        }
                    )
                ),
                // Image generation modal
                showImageModal && createElement(
                    Modal,
                    {
                        title: i18n.generateImage || 'Afbeelding genereren',
                        onRequestClose: function() {
                            setShowImageModal(false);
                            setImagePrompt('');
                        }
                    },
                    createElement(
                        TextareaControl,
                        {
                            label: i18n.imagePrompt || 'Beschrijf de afbeelding',
                            value: imagePrompt,
                            onChange: setImagePrompt,
                            rows: 3
                        }
                    ),
                    createElement(
                        'div',
                        { className: 'writgoai-modal-actions' },
                        createElement(
                            Button,
                            {
                                variant: 'primary',
                                onClick: handleGenerateImage,
                                disabled: isLoading || !imagePrompt.trim()
                            },
                            isLoading ? createElement(Spinner, null) : null,
                            ' ',
                            i18n.generateBtn || 'Genereren'
                        ),
                        createElement(
                            Button,
                            {
                                variant: 'secondary',
                                onClick: function() {
                                    setShowImageModal(false);
                                    setImagePrompt('');
                                },
                                style: { marginLeft: '8px' }
                            },
                            i18n.cancel || 'Annuleren'
                        )
                    )
                )
            );
        };
    }, 'withWritgoAIToolbar');

    // Register the sidebar plugin
    registerPlugin('writgoai-sidebar', {
        render: WritgoAISidebar,
        icon: 'superhero'
    });

    // Add the block toolbar extension
    addFilter(
        'editor.BlockEdit',
        'writgoai/ai-toolbar',
        withWritgoAIToolbar
    );

})(window.wp);
