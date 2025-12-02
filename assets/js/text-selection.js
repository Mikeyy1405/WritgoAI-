/**
 * Text Selection Handler
 *
 * Handles text selection in the Gutenberg editor and shows a floating toolbar.
 *
 * @package WritgoCMS
 */

(function(window, $) {
    'use strict';

    var settings = window.writgoaiAiToolbar || {};
    var i18n = settings.i18n || {};

    /**
     * Text Selection Toolbar
     */
    var WritgoAISelection = {
        /**
         * Toolbar element
         */
        $toolbar: null,

        /**
         * Currently selected text
         */
        selectedText: '',

        /**
         * Current selection range
         */
        currentRange: null,

        /**
         * Is processing
         */
        isProcessing: false,

        /**
         * Original content for undo
         */
        originalContent: null,

        /**
         * Initialize the selection toolbar
         */
        init: function() {
            this.createToolbar();
            this.bindEvents();
        },

        /**
         * Create the floating toolbar element
         */
        createToolbar: function() {
            var toolbarHtml = '<div id="writgoai-selection-toolbar" class="writgoai-selection-toolbar" style="display: none;">' +
                '<div class="writgoai-toolbar-inner">' +
                    '<button type="button" class="writgoai-toolbar-btn" data-action="rewrite" title="' + (i18n.rewrite || 'Herschrijven') + '">' +
                        '<span class="dashicons dashicons-update"></span>' +
                        '<span class="btn-text">' + (i18n.rewrite || 'Herschrijven') + '</span>' +
                    '</button>' +
                    '<button type="button" class="writgoai-toolbar-btn" data-action="improve" title="' + (i18n.improve || 'Verbeteren') + '">' +
                        '<span class="dashicons dashicons-yes-alt"></span>' +
                        '<span class="btn-text">' + (i18n.improve || 'Verbeteren') + '</span>' +
                    '</button>' +
                    '<div class="writgoai-toolbar-dropdown">' +
                        '<button type="button" class="writgoai-toolbar-btn writgoai-dropdown-toggle" data-action="length">' +
                            '<span class="dashicons dashicons-editor-expand"></span>' +
                            '<span class="btn-text">Lengte</span>' +
                            '<span class="dashicons dashicons-arrow-down-alt2 dropdown-arrow"></span>' +
                        '</button>' +
                        '<div class="writgoai-dropdown-menu">' +
                            '<button type="button" class="writgoai-dropdown-item" data-action="expand">' +
                                '<span class="dashicons dashicons-plus-alt2"></span> ' + (i18n.expand || 'Uitbreiden') +
                            '</button>' +
                            '<button type="button" class="writgoai-dropdown-item" data-action="shorten">' +
                                '<span class="dashicons dashicons-minus"></span> ' + (i18n.shorten || 'Inkorten') +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="writgoai-toolbar-btn" data-action="addLinks" title="' + (i18n.addLinks || 'Links toevoegen') + '">' +
                        '<span class="dashicons dashicons-admin-links"></span>' +
                        '<span class="btn-text">' + (i18n.addLinks || 'Links') + '</span>' +
                    '</button>' +
                '</div>' +
                '<div class="writgoai-toolbar-loading" style="display: none;">' +
                    '<span class="spinner is-active"></span>' +
                    '<span class="loading-text">' + (i18n.processing || 'Bezig...') + '</span>' +
                '</div>' +
                '<div class="writgoai-toolbar-result" style="display: none;">' +
                    '<div class="result-preview"></div>' +
                    '<div class="result-actions">' +
                        '<button type="button" class="writgoai-result-btn apply" data-action="apply">' +
                            '<span class="dashicons dashicons-yes"></span> ' + (i18n.apply || 'Toepassen') +
                        '</button>' +
                        '<button type="button" class="writgoai-result-btn cancel" data-action="cancel">' +
                            '<span class="dashicons dashicons-no"></span> ' + (i18n.cancel || 'Annuleren') +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            $('body').append(toolbarHtml);
            this.$toolbar = $('#writgoai-selection-toolbar');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Listen for text selection in the editor
            $(document).on('mouseup', '.editor-styles-wrapper', function(e) {
                // Small delay to ensure selection is complete
                setTimeout(function() {
                    self.handleSelection(e);
                }, 10);
            });

            // Listen for keyboard selection
            $(document).on('keyup', '.editor-styles-wrapper', function(e) {
                // Only handle selection keys (shift + arrow keys)
                if (e.shiftKey && [37, 38, 39, 40, 35, 36].indexOf(e.keyCode) !== -1) {
                    setTimeout(function() {
                        self.handleSelection(e);
                    }, 10);
                }
            });

            // Hide toolbar on click outside
            $(document).on('mousedown', function(e) {
                if (!$(e.target).closest('#writgoai-selection-toolbar').length) {
                    // Don't hide if clicking in editor and not changing selection
                    if (!$(e.target).closest('.editor-styles-wrapper').length) {
                        self.hideToolbar();
                    }
                }
            });

            // Handle toolbar button clicks
            this.$toolbar.on('click', '.writgoai-toolbar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var action = $(this).data('action');

                // Handle dropdown toggle
                if ($(this).hasClass('writgoai-dropdown-toggle')) {
                    $(this).closest('.writgoai-toolbar-dropdown').toggleClass('is-open');
                    return;
                }

                self.handleAction(action);
            });

            // Handle dropdown item clicks
            this.$toolbar.on('click', '.writgoai-dropdown-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var action = $(this).data('action');
                $(this).closest('.writgoai-toolbar-dropdown').removeClass('is-open');
                self.handleAction(action);
            });

            // Handle result actions
            this.$toolbar.on('click', '.writgoai-result-btn', function(e) {
                e.preventDefault();
                var action = $(this).data('action');

                if (action === 'apply') {
                    self.applyResult();
                } else if (action === 'cancel') {
                    self.cancelResult();
                }
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.writgoai-toolbar-dropdown').length) {
                    self.$toolbar.find('.writgoai-toolbar-dropdown').removeClass('is-open');
                }
            });

            // Hide on scroll
            $(document).on('scroll', '.interface-navigable-region, .editor-styles-wrapper', function() {
                if (!self.isProcessing) {
                    self.hideToolbar();
                }
            });

            // Listen for Escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape
                    self.hideToolbar();
                }
            });
        },

        /**
         * Handle text selection
         *
         * @param {Event} e - Mouse or keyboard event
         */
        handleSelection: function(e) {
            var selection = window.getSelection();

            if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
                if (!this.isProcessing) {
                    this.hideToolbar();
                }
                return;
            }

            var selectedText = selection.toString().trim();

            if (selectedText.length < 3) {
                if (!this.isProcessing) {
                    this.hideToolbar();
                }
                return;
            }

            // Check if selection is within editor
            var range = selection.getRangeAt(0);
            var container = range.commonAncestorContainer;

            // Navigate up to find if we're in the editor
            var isInEditor = false;
            var node = container;
            while (node && node !== document) {
                if (node.classList && node.classList.contains('editor-styles-wrapper')) {
                    isInEditor = true;
                    break;
                }
                node = node.parentNode;
            }

            if (!isInEditor) {
                return;
            }

            this.selectedText = selectedText;
            this.currentRange = range.cloneRange();

            this.showToolbar(range);
        },

        /**
         * Show the toolbar near the selection
         *
         * @param {Range} range - Selection range
         */
        showToolbar: function(range) {
            var rect = range.getBoundingClientRect();

            // Calculate position
            var top = rect.top + window.scrollY - this.$toolbar.outerHeight() - 10;
            var left = rect.left + window.scrollX + (rect.width / 2) - (this.$toolbar.outerWidth() / 2);

            // Keep toolbar in viewport
            var viewportWidth = $(window).width();
            var toolbarWidth = this.$toolbar.outerWidth();

            if (left < 10) {
                left = 10;
            } else if (left + toolbarWidth > viewportWidth - 10) {
                left = viewportWidth - toolbarWidth - 10;
            }

            // If toolbar would be above viewport, show below selection
            if (top < 10) {
                top = rect.bottom + window.scrollY + 10;
            }

            this.$toolbar.css({
                top: top + 'px',
                left: left + 'px'
            });

            // Reset toolbar state
            this.$toolbar.find('.writgoai-toolbar-inner').show();
            this.$toolbar.find('.writgoai-toolbar-loading').hide();
            this.$toolbar.find('.writgoai-toolbar-result').hide();

            this.$toolbar.fadeIn(150);
        },

        /**
         * Hide the toolbar
         */
        hideToolbar: function() {
            this.$toolbar.fadeOut(100);
            this.isProcessing = false;
            this.selectedText = '';
            this.currentRange = null;
            this.originalContent = null;
            this.$toolbar.find('.writgoai-toolbar-dropdown').removeClass('is-open');
        },

        /**
         * Handle toolbar action
         *
         * @param {string} action - Action name
         */
        handleAction: function(action) {
            if (this.isProcessing || !this.selectedText) {
                return;
            }

            var self = this;
            var client = window.WritgoAIClient;

            if (!client) {
                console.error('WritgoAIClient not available');
                return;
            }

            this.isProcessing = true;
            this.showLoading();

            var promise;

            switch (action) {
                case 'rewrite':
                    promise = client.rewriteText(this.selectedText);
                    break;
                case 'improve':
                    promise = client.improveText(this.selectedText);
                    break;
                case 'expand':
                    promise = client.expandText(this.selectedText);
                    break;
                case 'shorten':
                    promise = client.shortenText(this.selectedText);
                    break;
                case 'addLinks':
                    promise = client.addLinks(this.selectedText);
                    break;
                default:
                    this.isProcessing = false;
                    return;
            }

            promise
                .then(function(result) {
                    self.showResult(result.content, result.original);
                })
                .catch(function(error) {
                    self.showError(error.message);
                });
        },

        /**
         * Show loading state
         */
        showLoading: function() {
            this.$toolbar.find('.writgoai-toolbar-inner').hide();
            this.$toolbar.find('.writgoai-toolbar-result').hide();
            this.$toolbar.find('.writgoai-toolbar-loading').show();

            // Adjust position
            this.repositionToolbar();
        },

        /**
         * Show result
         *
         * @param {string} content - New content
         * @param {string} original - Original content
         */
        showResult: function(content, original) {
            this.isProcessing = false;
            this.originalContent = original;

            var $result = this.$toolbar.find('.writgoai-toolbar-result');
            $result.find('.result-preview').html(this.escapeHtml(content).substring(0, 300) + (content.length > 300 ? '...' : ''));

            this.$toolbar.find('.writgoai-toolbar-loading').hide();
            this.$toolbar.find('.writgoai-toolbar-inner').hide();
            $result.show();

            // Store the full result
            $result.data('content', content);

            // Adjust position
            this.repositionToolbar();
        },

        /**
         * Show error
         *
         * @param {string} message - Error message
         */
        showError: function(message) {
            this.isProcessing = false;

            var $result = this.$toolbar.find('.writgoai-toolbar-result');
            $result.find('.result-preview').html('<span class="error-message"><span class="dashicons dashicons-warning"></span> ' + this.escapeHtml(message) + '</span>');

            this.$toolbar.find('.writgoai-toolbar-loading').hide();
            this.$toolbar.find('.writgoai-toolbar-inner').hide();
            $result.show();
            $result.find('.apply').hide();

            // Adjust position
            this.repositionToolbar();

            // Auto hide after delay
            setTimeout(function() {
                $result.find('.apply').show();
            }, 3000);
        },

        /**
         * Apply the result to the editor
         */
        applyResult: function() {
            var $result = this.$toolbar.find('.writgoai-toolbar-result');
            var newContent = $result.data('content');

            if (!newContent || !this.currentRange) {
                this.hideToolbar();
                return;
            }

            // Use Gutenberg's data API to update the content
            var selection = window.getSelection();
            var wp = window.wp;

            if (wp && wp.data && wp.richText) {
                try {
                    // Get the current block
                    var editorStore = wp.data.select('core/block-editor');
                    var selectedBlock = editorStore.getSelectedBlock();

                    if (selectedBlock) {
                        // For paragraph and other text blocks, update content
                        var blockContent = selectedBlock.attributes.content || '';
                        var originalText = this.selectedText;

                        // Replace the selected text with new content
                        var updatedContent = blockContent.replace(originalText, newContent);

                        wp.data.dispatch('core/block-editor').updateBlockAttributes(
                            selectedBlock.clientId,
                            { content: updatedContent }
                        );
                    }
                } catch (e) {
                    console.error('Error applying content:', e);
                    // Fallback: try to replace text directly
                    this.replaceTextDirectly(newContent);
                }
            } else {
                this.replaceTextDirectly(newContent);
            }

            this.hideToolbar();
        },

        /**
         * Replace text directly using DOM manipulation (fallback)
         *
         * @param {string} newContent - New content to insert
         */
        replaceTextDirectly: function(newContent) {
            if (!this.currentRange) {
                return;
            }

            try {
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(this.currentRange);

                // Use modern Range API to replace content
                this.currentRange.deleteContents();
                var textNode = document.createTextNode(newContent);
                this.currentRange.insertNode(textNode);

                // Collapse selection to end of inserted content
                selection.removeAllRanges();
                var newRange = document.createRange();
                newRange.setStartAfter(textNode);
                newRange.collapse(true);
                selection.addRange(newRange);
            } catch (e) {
                console.error('Error replacing text:', e);
            }
        },

        /**
         * Cancel the result and go back to toolbar
         */
        cancelResult: function() {
            this.$toolbar.find('.writgoai-toolbar-result').hide();
            this.$toolbar.find('.writgoai-toolbar-inner').show();

            this.repositionToolbar();
        },

        /**
         * Reposition toolbar after content change
         */
        repositionToolbar: function() {
            if (this.currentRange) {
                var rect = this.currentRange.getBoundingClientRect();
                var top = rect.top + window.scrollY - this.$toolbar.outerHeight() - 10;
                var left = rect.left + window.scrollX + (rect.width / 2) - (this.$toolbar.outerWidth() / 2);

                var viewportWidth = $(window).width();
                var toolbarWidth = this.$toolbar.outerWidth();

                if (left < 10) {
                    left = 10;
                } else if (left + toolbarWidth > viewportWidth - 10) {
                    left = viewportWidth - toolbarWidth - 10;
                }

                if (top < 10) {
                    top = rect.bottom + window.scrollY + 10;
                }

                this.$toolbar.css({
                    top: top + 'px',
                    left: left + 'px'
                });
            }
        },

        /**
         * Escape HTML for safe display
         *
         * @param {string} text - Text to escape
         * @returns {string}
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Wait a bit for Gutenberg to fully initialize
        setTimeout(function() {
            WritgoAISelection.init();
        }, 500);
    });

    // Export
    window.WritgoAISelection = WritgoAISelection;

})(window, jQuery);
