/**
 * Gutenberg AI Toolbar
 *
 * Adds AI-powered toolbar buttons to the Gutenberg editor for:
 * - Text rewriting
 * - Internal link suggestions
 * - AI image generation
 * - Full block rewriting
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

	var settings = window.writgocmsToolbar || {};
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
		toast.className = 'writgocms-toast ' + type;
		
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
				action: 'writgocms_toolbar_rewrite',
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
				action: 'writgocms_toolbar_get_internal_links',
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
				action: 'writgocms_toolbar_generate_image',
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
						{ className: 'writgocms-modal-title' },
						createElement( 'span', { className: 'header-icon' }, '🤖' ),
						' ',
						isRewriteAll ? ( i18n.rewriteAll || 'Rewrite All' ) : ( i18n.rewriteTitle || 'AI Rewrite Text' )
					),
					onRequestClose: function() { setShowRewriteModal( false ); },
					className: 'writgocms-rewrite-modal'
				},
				createElement(
					'div',
					{ className: 'writgocms-modal-body' },
					// Tone selector
					createElement(
						'div',
						{ className: 'writgocms-tone-selector' },
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
							{ className: 'writgocms-loading' },
							createElement( 'div', { className: 'writgocms-spinner' } ),
							createElement( 'span', { className: 'writgocms-loading-text' }, i18n.loading || 'Generating...' )
						) :
						createElement(
							'div',
							{ className: 'writgocms-text-preview' },
							createElement(
								'div',
								{ className: 'writgocms-text-section original' },
								createElement( 'h4', null, i18n.originalText || 'Original Text' ),
								createElement( 'p', null, selectedText )
							),
							rewrittenText && createElement(
								'div',
								{ className: 'writgocms-text-section rewritten' },
								createElement( 'h4', null, i18n.rewrittenText || 'Rewritten Text' ),
								createElement( 'p', null, rewrittenText )
							)
						)
				),
				createElement(
					'div',
					{ className: 'writgocms-modal-footer' },
					! rewrittenText ? 
						createElement(
							Fragment,
							null,
							createElement(
								Button,
								{
									className: 'writgocms-btn writgocms-btn-secondary',
									onClick: function() { setShowRewriteModal( false ); }
								},
								i18n.cancel || 'Cancel'
							),
							createElement(
								Button,
								{
									className: 'writgocms-btn writgocms-btn-primary',
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
									className: 'writgocms-btn writgocms-btn-secondary',
									onClick: performRewrite,
									disabled: isLoading
								},
								'🔄 ',
								i18n.regenerate || 'Regenerate'
							),
							createElement(
								Button,
								{
									className: 'writgocms-btn writgocms-btn-success',
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
						{ className: 'writgocms-modal-title' },
						createElement( 'span', { className: 'header-icon' }, '🔗' ),
						' ',
						i18n.linksTitle || 'Suggested Internal Links'
					),
					onRequestClose: function() { setShowLinksModal( false ); },
					className: 'writgocms-links-modal'
				},
				createElement(
					'div',
					{ className: 'writgocms-modal-body' },
					isLoading ?
						createElement(
							'div',
							{ className: 'writgocms-loading' },
							createElement( 'div', { className: 'writgocms-spinner' } ),
							createElement( 'span', { className: 'writgocms-loading-text' }, i18n.loading || 'Loading...' )
						) :
						suggestedLinks.length > 0 ?
							createElement(
								'div',
								{ className: 'writgocms-links-list' },
								suggestedLinks.map( function( link ) {
									var isSelected = selectedLinks.indexOf( link.id ) !== -1;
									return createElement(
										'div',
										{
											key: link.id,
											className: 'writgocms-link-item' + ( isSelected ? ' selected' : '' ),
											onClick: function() { toggleLinkSelection( link.id ); }
										},
										createElement(
											'div',
											{ className: 'writgocms-link-checkbox' },
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
											{ className: 'writgocms-link-content' },
											createElement( 'div', { className: 'writgocms-link-title' }, link.title ),
											createElement( 'div', { className: 'writgocms-link-excerpt' }, link.excerpt )
										),
										createElement(
											'span',
											{ className: 'writgocms-link-type' },
											link.type
										)
									);
								} )
							) :
							createElement(
								'div',
								{ className: 'writgocms-no-links' },
								createElement( 'span', { className: 'no-links-icon' }, '🔍' ),
								createElement( 'p', null, i18n.noLinksFound || 'No relevant internal links found.' )
							)
				),
				createElement(
					'div',
					{ className: 'writgocms-modal-footer' },
					createElement(
						Button,
						{
							className: 'writgocms-btn writgocms-btn-secondary',
							onClick: function() { setShowLinksModal( false ); }
						},
						i18n.cancel || 'Cancel'
					),
					createElement(
						Button,
						{
							className: 'writgocms-btn writgocms-btn-primary',
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
						{ className: 'writgocms-modal-title' },
						createElement( 'span', { className: 'header-icon' }, '🖼️' ),
						' ',
						i18n.imageTitle || 'Generate AI Image'
					),
					onRequestClose: function() { setShowImageModal( false ); },
					className: 'writgocms-image-modal'
				},
				createElement(
					'div',
					{ className: 'writgocms-modal-body' },
					! generatedImage ?
						createElement(
							'div',
							{ className: 'writgocms-image-form' },
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
								{ className: 'writgocms-image-hint' },
								i18n.useSelectedText || 'Selected text has been used as the initial prompt.'
							),
							isLoading && createElement(
								'div',
								{ className: 'writgocms-loading' },
								createElement( 'div', { className: 'writgocms-spinner' } ),
								createElement( 'span', { className: 'writgocms-loading-text' }, i18n.loading || 'Generating image...' )
							)
						) :
						createElement(
							'div',
							{ className: 'writgocms-image-preview' },
							createElement( 'img', { src: generatedImage.url, alt: imagePrompt } )
						)
				),
				createElement(
					'div',
					{ className: 'writgocms-modal-footer' },
					! generatedImage ?
						createElement(
							Fragment,
							null,
							createElement(
								Button,
								{
									className: 'writgocms-btn writgocms-btn-secondary',
									onClick: function() { setShowImageModal( false ); }
								},
								i18n.cancel || 'Cancel'
							),
							createElement(
								Button,
								{
									className: 'writgocms-btn writgocms-btn-primary',
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
									className: 'writgocms-btn writgocms-btn-secondary',
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
									className: 'writgocms-btn writgocms-btn-success',
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
	registerFormatType( 'writgocms/ai-toolbar', {
		title: 'WritgoAI',
		tagName: 'span',
		className: 'writgocms-ai-enhanced',
		edit: WritgoCMSAIToolbar
	} );

} )( window.wp );
