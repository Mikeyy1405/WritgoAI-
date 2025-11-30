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
        currentTopicalMap: null,

        init: function() {
            this.bindPasswordToggles();
            this.bindApiValidation();
            this.bindRangeInputs();
            this.bindTestInterface();
            this.bindContentPlanner();
            this.loadSavedPlans();
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
         * Bind content planner interface
         */
        bindContentPlanner: function() {
            var self = this;

            // Generate topical map button
            $('#generate-topical-map').on('click', function() {
                var $button = $(this);
                var niche = $('#planner-niche').val();
                var websiteType = $('#planner-website-type').val();
                var targetAudience = $('#planner-audience').val();

                if (!niche) {
                    self.showNotification(writgocmsAiml.i18n.noNiche, 'error');
                    return;
                }

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgocmsAiml.i18n.generatingMap);
                $('.planner-status').text('').removeClass('error success');

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_generate_topical_map',
                        nonce: writgocmsAiml.nonce,
                        niche: niche,
                        website_type: websiteType,
                        target_audience: targetAudience
                    },
                    success: function(response) {
                        if (response.success) {
                            self.currentTopicalMap = response.data.topical_map;
                            self.renderTopicalMap(response.data.topical_map);
                            $('.content-planner-results').show();
                            self.showNotification(writgocmsAiml.i18n.success, 'success');
                        } else {
                            $('.planner-status').text(response.data.message).addClass('error');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $('.planner-status').text('Connection error').addClass('error');
                        self.showNotification('Connection error', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('✨ ' + 'Generate Topical Authority Map');
                    }
                });
            });

            // Save plan button
            $('#save-content-plan').on('click', function() {
                $('#save-plan-modal').show();
            });

            // Cancel save button
            $('#cancel-save-plan').on('click', function() {
                $('#save-plan-modal').hide();
                $('#plan-name').val('');
            });

            // Confirm save button
            $('#confirm-save-plan').on('click', function() {
                var planName = $('#plan-name').val();

                if (!planName) {
                    self.showNotification(writgocmsAiml.i18n.noPlanName, 'error');
                    return;
                }

                if (!self.currentTopicalMap) {
                    self.showNotification('No content plan to save', 'error');
                    return;
                }

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_save_content_plan',
                        nonce: writgocmsAiml.nonce,
                        plan_name: planName,
                        plan_data: JSON.stringify(self.currentTopicalMap)
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#save-plan-modal').hide();
                            $('#plan-name').val('');
                            self.loadSavedPlans();
                            self.showNotification(writgocmsAiml.i18n.planSaved, 'success');
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Connection error', 'error');
                    }
                });
            });

            // Export plan button
            $('#export-content-plan').on('click', function() {
                if (!self.currentTopicalMap) {
                    self.showNotification('No content plan to export', 'error');
                    return;
                }

                var dataStr = JSON.stringify(self.currentTopicalMap, null, 2);
                var dataBlob = new Blob([dataStr], { type: 'application/json' });
                var url = URL.createObjectURL(dataBlob);
                var link = document.createElement('a');
                link.href = url;
                link.download = 'topical-authority-map.json';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });

            // Close modal when clicking outside
            $('#save-plan-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Delegate click for generate detailed plan buttons
            $(document).on('click', '.generate-detail-btn', function() {
                var $button = $(this);
                var topic = $button.data('topic');
                var keywords = $button.data('keywords') || [];

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgocmsAiml.i18n.generatingPlan);

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_generate_content_plan',
                        nonce: writgocmsAiml.nonce,
                        topic: topic,
                        content_type: 'article',
                        keywords: keywords
                    },
                    success: function(response) {
                        if (response.success) {
                            self.renderContentPlan(response.data.content_plan);
                            $('#content-detail-panel').show();
                            self.showNotification(writgocmsAiml.i18n.success, 'success');
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Connection error', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('📋 ' + writgocmsAiml.i18n.generateDetailedPlan);
                    }
                });
            });

            // Delegate click for delete plan buttons
            $(document).on('click', '.delete-plan-btn', function() {
                var $button = $(this);
                var planId = $button.data('plan-id');

                if (!confirm(writgocmsAiml.i18n.confirmDelete)) {
                    return;
                }

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_delete_content_plan',
                        nonce: writgocmsAiml.nonce,
                        plan_id: planId
                    },
                    success: function(response) {
                        if (response.success) {
                            self.loadSavedPlans();
                            self.showNotification(writgocmsAiml.i18n.planDeleted, 'success');
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Connection error', 'error');
                    }
                });
            });

            // Delegate click for load plan buttons
            $(document).on('click', '.load-plan-btn', function() {
                var $button = $(this);
                var planData = $button.data('plan');

                if (planData) {
                    self.currentTopicalMap = planData;
                    self.renderTopicalMap(planData);
                    $('.content-planner-results').show();
                }
            });
        },

        /**
         * Load saved content plans
         */
        loadSavedPlans: function() {
            var self = this;
            var $container = $('#saved-plans-list');

            if (!$container.length) {
                return;
            }

            $.ajax({
                url: writgocmsAiml.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgocms_get_saved_plans',
                    nonce: writgocmsAiml.nonce
                },
                success: function(response) {
                    if (response.success && response.data.plans) {
                        var plans = response.data.plans;
                        var keys = Object.keys(plans);

                        if (keys.length === 0) {
                            $container.html('<p class="no-plans">No saved content plans yet. Generate a topical map to get started!</p>');
                            return;
                        }

                        var html = '<ul class="saved-plans">';
                        keys.forEach(function(planId) {
                            var plan = plans[planId];
                            html += '<li class="saved-plan-item">';
                            html += '<div class="plan-info">';
                            html += '<strong>' + self.escapeHtml(plan.name) + '</strong>';
                            html += '<span class="plan-date">' + self.escapeHtml(plan.created_at) + '</span>';
                            html += '</div>';
                            html += '<div class="plan-actions">';
                            html += '<button type="button" class="button button-small load-plan-btn" data-plan="' + self.escapeJsonAttr(plan.data) + '">📂 Load</button>';
                            html += '<button type="button" class="button button-small delete-plan-btn" data-plan-id="' + self.escapeHtml(planId) + '">🗑️ Delete</button>';
                            html += '</div>';
                            html += '</li>';
                        });
                        html += '</ul>';

                        $container.html(html);
                    }
                }
            });
        },

        /**
         * Render topical authority map
         */
        renderTopicalMap: function(data) {
            var self = this;
            var $container = $('#topical-map-content');

            if (data.error) {
                $container.html('<div class="notice notice-error"><p>' + self.escapeHtml(data.message) + '</p><pre>' + self.escapeHtml(data.raw_content || '') + '</pre></div>');
                return;
            }

            var html = '<div class="topical-map">';

            // Main topic header
            html += '<div class="main-topic-header">';
            html += '<h4>🎯 ' + self.escapeHtml(data.main_topic || 'Content Strategy') + '</h4>';
            html += '</div>';

            // Pillar content
            if (data.pillar_content && data.pillar_content.length > 0) {
                html += '<div class="pillar-content-section">';
                html += '<h4>' + writgocmsAiml.i18n.pillarContent + '</h4>';

                data.pillar_content.forEach(function(pillar, index) {
                    html += '<div class="pillar-item">';
                    html += '<div class="pillar-header">';
                    html += '<span class="pillar-number">' + (index + 1) + '</span>';
                    html += '<div class="pillar-info">';
                    html += '<h5>' + self.escapeHtml(pillar.title) + '</h5>';
                    html += '<p>' + self.escapeHtml(pillar.description || '') + '</p>';

                    // Keywords
                    if (pillar.keywords && pillar.keywords.length > 0) {
                        html += '<div class="keywords-list">';
                        html += '<span class="keywords-label">' + writgocmsAiml.i18n.keywords + ': </span>';
                        pillar.keywords.forEach(function(keyword) {
                            html += '<span class="keyword-tag">' + self.escapeHtml(keyword) + '</span>';
                        });
                        html += '</div>';
                    }

                    html += '</div>';
                    html += self.createDetailButton(pillar.title, pillar.keywords);
                    html += '</div>';

                    // Cluster articles
                    if (pillar.cluster_articles && pillar.cluster_articles.length > 0) {
                        html += '<div class="cluster-articles">';
                        html += '<h6>' + writgocmsAiml.i18n.clusterArticles + '</h6>';
                        html += '<ul>';

                        pillar.cluster_articles.forEach(function(article) {
                            var priorityClass = 'priority-' + (article.priority || 'medium');
                            var priorityLabel = writgocmsAiml.i18n[article.priority] || article.priority || 'Medium';

                            html += '<li class="cluster-article ' + priorityClass + '">';
                            html += '<div class="article-info">';
                            html += '<strong>' + self.escapeHtml(article.title) + '</strong>';
                            html += '<span class="priority-badge">' + priorityLabel + '</span>';
                            html += '</div>';
                            html += '<p>' + self.escapeHtml(article.description || '') + '</p>';

                            if (article.keywords && article.keywords.length > 0) {
                                html += '<div class="keywords-list small">';
                                article.keywords.forEach(function(keyword) {
                                    html += '<span class="keyword-tag">' + self.escapeHtml(keyword) + '</span>';
                                });
                                html += '</div>';
                            }

                            html += self.createDetailButton(article.title, article.keywords);
                            html += '</li>';
                        });

                        html += '</ul>';
                        html += '</div>';
                    }

                    html += '</div>';
                });

                html += '</div>';
            }

            // Content gaps
            if (data.content_gaps && data.content_gaps.length > 0) {
                html += '<div class="content-gaps-section">';
                html += '<h4>🔍 ' + writgocmsAiml.i18n.contentGaps + '</h4>';
                html += '<ul>';
                data.content_gaps.forEach(function(gap) {
                    html += '<li>' + self.escapeHtml(gap) + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            // Recommended order
            if (data.recommended_order && data.recommended_order.length > 0) {
                html += '<div class="recommended-order-section">';
                html += '<h4>📅 ' + writgocmsAiml.i18n.recommendedOrder + '</h4>';
                html += '<ol>';
                data.recommended_order.forEach(function(item) {
                    html += '<li>' + self.escapeHtml(item) + '</li>';
                });
                html += '</ol>';
                html += '</div>';
            }

            html += '</div>';

            $container.html(html);
        },

        /**
         * Render detailed content plan
         */
        renderContentPlan: function(data) {
            var self = this;
            var $container = $('#content-detail-result');

            if (data.error) {
                $container.html('<div class="notice notice-error"><p>' + self.escapeHtml(data.message) + '</p></div>');
                return;
            }

            var html = '<div class="content-plan-detail">';

            // Title and meta
            html += '<div class="plan-header">';
            html += '<h4>' + self.escapeHtml(data.title || 'Article Outline') + '</h4>';
            if (data.meta_description) {
                html += '<p class="meta-description"><strong>Meta Description:</strong> ' + self.escapeHtml(data.meta_description) + '</p>';
            }
            if (data.estimated_word_count) {
                html += '<p class="word-count"><strong>Estimated Word Count:</strong> ' + data.estimated_word_count + '</p>';
            }
            html += '</div>';

            // Target keywords
            if (data.target_keywords && data.target_keywords.length > 0) {
                html += '<div class="target-keywords">';
                html += '<strong>Target Keywords:</strong> ';
                data.target_keywords.forEach(function(keyword) {
                    html += '<span class="keyword-tag">' + self.escapeHtml(keyword) + '</span>';
                });
                html += '</div>';
            }

            // Content structure
            if (data.content_structure) {
                html += '<div class="content-structure">';
                html += '<h5>📝 Content Structure</h5>';

                if (data.content_structure.introduction) {
                    html += '<div class="structure-section intro">';
                    html += '<strong>Introduction:</strong> ' + self.escapeHtml(data.content_structure.introduction);
                    html += '</div>';
                }

                if (data.content_structure.sections && data.content_structure.sections.length > 0) {
                    data.content_structure.sections.forEach(function(section) {
                        html += '<div class="structure-section">';
                        html += '<h6>📌 ' + self.escapeHtml(section.heading) + '</h6>';

                        if (section.key_points && section.key_points.length > 0) {
                            html += '<ul class="key-points">';
                            section.key_points.forEach(function(point) {
                                html += '<li>' + self.escapeHtml(point) + '</li>';
                            });
                            html += '</ul>';
                        }

                        // Subsections
                        if (section.subsections && section.subsections.length > 0) {
                            section.subsections.forEach(function(sub) {
                                html += '<div class="subsection">';
                                html += '<strong>' + self.escapeHtml(sub.heading) + '</strong>';
                                if (sub.key_points && sub.key_points.length > 0) {
                                    html += '<ul>';
                                    sub.key_points.forEach(function(point) {
                                        html += '<li>' + self.escapeHtml(point) + '</li>';
                                    });
                                    html += '</ul>';
                                }
                                html += '</div>';
                            });
                        }

                        html += '</div>';
                    });
                }

                if (data.content_structure.conclusion) {
                    html += '<div class="structure-section conclusion">';
                    html += '<strong>Conclusion:</strong> ' + self.escapeHtml(data.content_structure.conclusion);
                    html += '</div>';
                }

                html += '</div>';
            }

            // Internal links
            if (data.internal_links && data.internal_links.length > 0) {
                html += '<div class="internal-links">';
                html += '<h5>🔗 Suggested Internal Links</h5>';
                html += '<ul>';
                data.internal_links.forEach(function(link) {
                    html += '<li>' + self.escapeHtml(link) + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            // CTA suggestions
            if (data.cta_suggestions && data.cta_suggestions.length > 0) {
                html += '<div class="cta-suggestions">';
                html += '<h5>🎯 CTA Suggestions</h5>';
                html += '<ul>';
                data.cta_suggestions.forEach(function(cta) {
                    html += '<li>' + self.escapeHtml(cta) + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            html += '</div>';

            $container.html(html);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        },

        /**
         * Escape JSON for use in HTML attributes
         * Uses proper HTML entity encoding for all special characters
         */
        escapeJsonAttr: function(obj) {
            var json = JSON.stringify(obj);
            return this.escapeHtml(json);
        },

        /**
         * Create a generate detail button HTML
         */
        createDetailButton: function(topic, keywords) {
            return '<button type="button" class="button button-small generate-detail-btn" ' +
                'data-topic="' + this.escapeHtml(topic) + '" ' +
                'data-keywords="' + this.escapeJsonAttr(keywords || []) + '">' +
                '📋 ' + writgocmsAiml.i18n.generateDetailedPlan + '</button>';
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="aiml-notification ' + type + '">' + this.escapeHtml(message) + '</div>');
            $('body').append($notification);

            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    /**
     * Post Updater Module
     */
    var PostUpdater = {
        currentPostId: null,
        improvedData: null,
        selectedPosts: [],
        currentPage: 1,

        init: function() {
            if (!$('.writgocms-post-updater').length) {
                return;
            }

            this.bindEvents();
            this.loadPosts();
        },

        bindEvents: function() {
            var self = this;

            // Filter changes
            $('#filter-months, #filter-seo, #filter-category').on('change', function() {
                self.currentPage = 1;
                self.loadPosts();
            });

            // Search
            $('#btn-search').on('click', function() {
                self.currentPage = 1;
                self.loadPosts();
            });

            $('#filter-search').on('keypress', function(e) {
                if (e.which === 13) {
                    self.currentPage = 1;
                    self.loadPosts();
                }
            });

            // Select all
            $('#btn-select-all').on('click', function() {
                var $checkboxes = $('.post-item-checkbox input');
                var allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
                $checkboxes.prop('checked', !allChecked);
                self.updateSelectedCount();
            });

            // Individual checkbox
            $(document).on('change', '.post-item-checkbox input', function() {
                self.updateSelectedCount();
            });

            // Improve button
            $(document).on('click', '.btn-improve', function() {
                var postId = $(this).data('post-id');
                var postTitle = $(this).data('post-title');
                self.openImprovementModal(postId, postTitle);
            });

            // Modal close buttons
            $('.modal-close, .modal-cancel, .modal-back').on('click', function() {
                $(this).closest('.post-updater-modal').hide();
            });

            // Close modal on backdrop click
            $('.post-updater-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Start improvement
            $('#btn-start-improvement').on('click', function() {
                self.startImprovement();
            });

            // Comparison tabs
            $(document).on('click', '.tab-btn', function() {
                var tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-panel').removeClass('active');
                $('.tab-panel[data-panel="' + tab + '"]').addClass('active');
            });

            // Save as draft
            $('#btn-save-draft').on('click', function() {
                self.savePost('draft');
            });

            // Publish
            $('#btn-publish').on('click', function() {
                self.savePost('publish');
            });

            // Bulk improve button
            $('#btn-bulk-improve').on('click', function() {
                self.openBulkModal();
            });

            // Start bulk action
            $('#btn-start-bulk').on('click', function() {
                self.startBulkImprovement();
            });
        },

        loadPosts: function() {
            var self = this;
            var $list = $('#posts-list');
            var seoFilter = $('#filter-seo').val().split('-');

            $list.html('<div class="loading-state"><span class="spinner is-active"></span><p>' + (writgocmsAiml.i18n.postUpdater ? writgocmsAiml.i18n.postUpdater.loading : 'Laden...') + '</p></div>');

            $.ajax({
                url: writgocmsAiml.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgocms_get_posts_for_update',
                    nonce: writgocmsAiml.nonce,
                    page: self.currentPage,
                    per_page: 20,
                    months_old: $('#filter-months').val(),
                    min_seo_score: seoFilter[0],
                    max_seo_score: seoFilter[1],
                    category: $('#filter-category').val(),
                    search: $('#filter-search').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.renderPosts(response.data);
                    } else {
                        $list.html('<div class="no-posts-message">' + (response.data.message || 'Error loading posts') + '</div>');
                    }
                },
                error: function() {
                    $list.html('<div class="no-posts-message">Connection error</div>');
                }
            });
        },

        renderPosts: function(data) {
            var self = this;
            var $list = $('#posts-list');
            var $pagination = $('#posts-pagination');

            $('#posts-count').text('(' + data.total + ')');

            if (!data.posts || data.posts.length === 0) {
                $list.html('<div class="no-posts-message">' + (writgocmsAiml.i18n.postUpdater ? writgocmsAiml.i18n.postUpdater.noPostsFound : 'Geen posts gevonden.') + '</div>');
                $pagination.html('');
                return;
            }

            var html = '';
            data.posts.forEach(function(post) {
                var scoreClass = 'score-red';
                var seoScore = post.seo_data.score || 0;
                if (seoScore > 70) scoreClass = 'score-green';
                else if (seoScore > 40) scoreClass = 'score-orange';

                var seoPlugin = post.seo_data.plugin === 'yoast' ? 'Yoast SEO' : (post.seo_data.plugin === 'rankmath' ? 'Rank Math' : 'SEO');

                html += '<div class="post-item" data-post-id="' + post.id + '">';
                html += '<div class="post-item-header">';
                html += '<div class="post-item-checkbox"><input type="checkbox" value="' + post.id + '"></div>';
                html += '<div class="post-item-info">';
                html += '<h4 class="post-item-title">📄 ' + self.escapeHtml(post.title) + '</h4>';
                html += '<div class="post-item-meta">';
                html += '<span>📅 ' + post.date_display + ' (' + post.age_months + ' maanden oud)</span>';
                html += '<span>📈 ' + post.word_count + ' woorden</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                html += '<div class="post-item-seo">';
                html += '<div class="seo-status-row">';
                html += '<span class="seo-score ' + scoreClass + '">';
                if (scoreClass === 'score-red') html += '🔴 ';
                else if (scoreClass === 'score-orange') html += '🟠 ';
                else html += '🟢 ';
                html += seoPlugin + ': ' + seoScore + '/100';
                html += '</span>';
                html += '</div>';

                if (post.seo_data.issues && post.seo_data.issues.length > 0) {
                    html += '<ul class="seo-issues">';
                    post.seo_data.issues.forEach(function(issue) {
                        html += '<li>⚠️ ' + self.escapeHtml(issue.message) + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';

                html += '<div class="post-item-actions">';
                html += '<button type="button" class="button btn-improve" data-post-id="' + post.id + '" data-post-title="' + self.escapeHtml(post.title) + '">🔄 Verbeter & Herschrijf</button>';
                html += '<a href="' + post.view_link + '" target="_blank" class="button">👁️ Bekijk Post</a>';
                html += '</div>';
                html += '</div>';
            });

            $list.html(html);

            // Render pagination
            if (data.total_pages > 1) {
                var pagHtml = '';
                for (var i = 1; i <= data.total_pages; i++) {
                    pagHtml += '<button type="button" class="button ' + (i === data.current ? 'current' : '') + '" data-page="' + i + '">' + i + '</button>';
                }
                $pagination.html(pagHtml);

                $pagination.find('button').on('click', function() {
                    self.currentPage = parseInt($(this).data('page'));
                    self.loadPosts();
                });
            } else {
                $pagination.html('');
            }
        },

        updateSelectedCount: function() {
            var count = $('.post-item-checkbox input:checked').length;
            $('#selected-count').text(count);
            $('#btn-bulk-improve').prop('disabled', count === 0);

            this.selectedPosts = [];
            var self = this;
            $('.post-item-checkbox input:checked').each(function() {
                self.selectedPosts.push(parseInt($(this).val()));
            });
        },

        openImprovementModal: function(postId, postTitle) {
            this.currentPostId = postId;
            $('#modal-post-title').text(postTitle);
            $('#improvement-modal').show();
        },

        startImprovement: function() {
            var self = this;
            var $btn = $('#btn-start-improvement');
            var $modal = $('#improvement-modal');

            var options = {
                update_dates: $modal.find('input[name="update_dates"]').is(':checked'),
                extend_content: $modal.find('input[name="extend_content"]').is(':checked'),
                optimize_seo: $modal.find('input[name="optimize_seo"]').is(':checked'),
                rewrite_intro: $modal.find('input[name="rewrite_intro"]').is(':checked'),
                improve_readability: $modal.find('input[name="improve_readability"]').is(':checked'),
                add_links: $modal.find('input[name="add_links"]').is(':checked'),
                add_faq: $modal.find('input[name="add_faq"]').is(':checked'),
                focus_keyword: $('#focus-keyword').val(),
                tone: $('#writing-tone').val(),
                target_audience: $('#target-audience').val(),
                improvement_level: $modal.find('input[name="improvement_level"]:checked').val()
            };

            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Verbeteren...');

            $.ajax({
                url: writgocmsAiml.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'writgocms_improve_post',
                    nonce: writgocmsAiml.nonce,
                    post_id: self.currentPostId
                }, options),
                success: function(response) {
                    if (response.success) {
                        self.improvedData = response.data;
                        $modal.hide();
                        self.showPreview(response.data);
                    } else {
                        WritgoCMSAiml.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WritgoCMSAiml.showNotification('Connection error', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('🚀 Start Verbetering');
                }
            });
        },

        showPreview: function(data) {
            var self = this;
            var $modal = $('#preview-modal');

            // Render stats
            var wordDiff = data.improved.word_count - data.original.word_count;
            var seoDiff = data.improved.seo_score - data.original.seo_score;

            var statsHtml = '<div class="stat-item">';
            statsHtml += '<div class="stat-label">Woorden</div>';
            statsHtml += '<div class="stat-value">' + data.original.word_count + ' → ' + data.improved.word_count + '</div>';
            statsHtml += '<div class="stat-change">' + (wordDiff >= 0 ? '+' : '') + wordDiff + '</div>';
            statsHtml += '</div>';

            statsHtml += '<div class="stat-item">';
            statsHtml += '<div class="stat-label">SEO Score</div>';
            statsHtml += '<div class="stat-value">' + data.original.seo_score + ' → ' + data.improved.seo_score + '</div>';
            statsHtml += '<div class="stat-change">' + (seoDiff >= 0 ? '+' : '') + seoDiff + ' 🎉</div>';
            statsHtml += '</div>';

            $('#improvement-stats').html(statsHtml);

            // Before tab
            var beforeHtml = '<div class="comparison-section">';
            beforeHtml += '<div class="comparison-label">Titel</div>';
            beforeHtml += '<div class="comparison-old"><div class="comparison-value">' + self.escapeHtml(data.original.title) + '</div></div>';
            beforeHtml += '</div>';

            beforeHtml += '<div class="comparison-section">';
            beforeHtml += '<div class="comparison-label">Meta Beschrijving</div>';
            beforeHtml += '<div class="comparison-old"><div class="comparison-value">' + self.escapeHtml(data.original.meta_description || 'Geen meta beschrijving') + '</div>';
            beforeHtml += '<div class="char-count">' + (data.original.meta_description ? data.original.meta_description.length : 0) + ' karakters</div></div>';
            beforeHtml += '</div>';

            $('.tab-panel[data-panel="before"]').html(beforeHtml);

            // After tab
            var afterHtml = '<div class="comparison-section">';
            afterHtml += '<div class="comparison-label">Nieuwe Titel</div>';
            afterHtml += '<div class="comparison-new"><div class="comparison-value">' + self.escapeHtml(data.improved.title) + '</div></div>';
            afterHtml += '</div>';

            afterHtml += '<div class="comparison-section">';
            afterHtml += '<div class="comparison-label">Nieuwe Meta Beschrijving</div>';
            afterHtml += '<div class="comparison-new"><div class="comparison-value">' + self.escapeHtml(data.improved.meta_description) + '</div>';
            var charCount = data.improved.meta_description ? data.improved.meta_description.length : 0;
            var charClass = (charCount >= 120 && charCount <= 160) ? 'valid' : 'invalid';
            afterHtml += '<div class="char-count ' + charClass + '">' + charCount + ' karakters ' + (charClass === 'valid' ? '✅' : '❌') + '</div></div>';
            afterHtml += '</div>';

            $('.tab-panel[data-panel="after"]').html(afterHtml);

            // Changes tab
            var changesHtml = '<ul class="changes-list">';
            if (data.improved.changes_summary && data.improved.changes_summary.length > 0) {
                data.improved.changes_summary.forEach(function(change) {
                    changesHtml += '<li><span class="change-icon">✅</span><span class="change-text">' + self.escapeHtml(change) + '</span></li>';
                });
            } else {
                changesHtml += '<li><span class="change-icon">ℹ️</span><span class="change-text">Geen specifieke wijzigingen geregistreerd</span></li>';
            }
            changesHtml += '</ul>';
            $('.tab-panel[data-panel="changes"]').html(changesHtml);

            // Show first tab
            $('.tab-btn').removeClass('active').first().addClass('active');
            $('.tab-panel').removeClass('active').first().addClass('active');

            $modal.show();
        },

        savePost: function(status) {
            var self = this;
            var $btnDraft = $('#btn-save-draft');
            var $btnPublish = $('#btn-publish');

            var $btn = status === 'publish' ? $btnPublish : $btnDraft;
            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Opslaan...');

            $.ajax({
                url: writgocmsAiml.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgocms_save_improved_post',
                    nonce: writgocmsAiml.nonce,
                    post_id: self.currentPostId,
                    improved_data: JSON.stringify(self.improvedData.improved),
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        $('#preview-modal').hide();
                        WritgoCMSAiml.showNotification(response.data.message, 'success');
                        self.loadPosts();
                    } else {
                        WritgoCMSAiml.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WritgoCMSAiml.showNotification('Connection error', 'error');
                },
                complete: function() {
                    $btnDraft.prop('disabled', false).html('💾 Opslaan als Concept');
                    $btnPublish.prop('disabled', false).html('🚀 Direct Publiceren');
                }
            });
        },

        openBulkModal: function() {
            $('#bulk-selected-info').text(this.selectedPosts.length + ' posts geselecteerd voor verbetering');
            $('#bulk-progress').hide();
            $('#bulk-modal').show();
        },

        startBulkImprovement: function() {
            var self = this;
            var $btn = $('#btn-start-bulk');
            var $progress = $('#bulk-progress');
            var $progressFill = $progress.find('.progress-fill');
            var $progressText = $progress.find('.progress-text');

            var options = {
                update_dates: $('#bulk-modal').find('input[name="bulk_update_dates"]').is(':checked'),
                optimize_seo: $('#bulk-modal').find('input[name="bulk_optimize_seo"]').is(':checked'),
                extend_content: $('#bulk-modal').find('input[name="bulk_extend_content"]').is(':checked'),
                add_faq: $('#bulk-modal').find('input[name="bulk_add_faq"]').is(':checked')
            };

            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Bezig...');
            $progress.show();
            $progressFill.css('width', '0%');
            $progressText.text('Verbeteren van posts...');

            $.ajax({
                url: writgocmsAiml.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'writgocms_bulk_improve_posts',
                    nonce: writgocmsAiml.nonce,
                    post_ids: self.selectedPosts
                }, options),
                success: function(response) {
                    if (response.success) {
                        $progressFill.css('width', '100%');
                        $progressText.text('Voltooid! ' + response.data.success + ' succesvol, ' + response.data.failed + ' mislukt.');
                        WritgoCMSAiml.showNotification('Bulk verbetering voltooid!', 'success');

                        setTimeout(function() {
                            $('#bulk-modal').hide();
                            self.loadPosts();
                        }, 2000);
                    } else {
                        WritgoCMSAiml.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WritgoCMSAiml.showNotification('Connection error', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('▶️ Start Bulk Actie');
                }
            });
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        WritgoCMSAiml.init();
        PostUpdater.init();
    });

})(jQuery);
