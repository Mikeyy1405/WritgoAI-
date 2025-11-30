/**
 * AIML Admin JavaScript
 *
 * Admin interface for AIMLAPI integration.
 * Nederlandse versie - Dutch interface for WritgoAI.
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
            this.bindSiteAnalysis();
            this.bindContentGeneration();
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
                    self.showNotification(writgocmsAiml.i18n.error + ': API sleutel is vereist', 'error');
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
                            self.showNotification(writgocmsAiml.i18n.success + ' API sleutel gevalideerd!', 'success');
                        } else {
                            $status.text(writgocmsAiml.i18n.invalid).removeClass('validating valid').addClass('invalid');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text(writgocmsAiml.i18n.error).removeClass('validating valid').addClass('invalid');
                        self.showNotification('Verbindingsfout', 'error');
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
         * Bind site analysis functionality
         */
        bindSiteAnalysis: function() {
            var self = this;

            // Start site analysis button
            $('#start-site-analysis').on('click', function() {
                var $button = $(this);
                var $status = $('.analysis-status');
                var manualTheme = $('#manual-theme').val();

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgocmsAiml.i18n.analyzing);
                $status.text('').removeClass('error success');

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_analyze_sitemap',
                        nonce: writgocmsAiml.nonce,
                        manual_theme: manualTheme
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgocmsAiml.i18n.analysisComplete).addClass('success');
                            self.showNotification(writgocmsAiml.i18n.analysisComplete, 'success');
                            // Reload page to show results
                            location.reload();
                        } else {
                            $status.text(response.data.message).addClass('error');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text(writgocmsAiml.i18n.analysisError).addClass('error');
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('🔍 ' + writgocmsAiml.i18n.refreshAnalysis);
                    }
                });
            });

            // Generate content plan button
            $('#generate-content-plan').on('click', function() {
                var $button = $(this);
                var $status = $('.content-plan-status');

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgocmsAiml.i18n.generatingMap);
                $status.text('').removeClass('error success');

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_generate_categorized_plan',
                        nonce: writgocmsAiml.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgocmsAiml.i18n.success).addClass('success');
                            self.showNotification('Contentplan succesvol gegenereerd!', 'success');
                            // Redirect to content plan page
                            window.location.href = writgocmsAiml.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=writgocms-aiml-contentplan');
                        } else {
                            $status.text(response.data.message).addClass('error');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text('Verbindingsfout').addClass('error');
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('✨ Genereer Contentplan');
                    }
                });
            });
        },

        /**
         * Bind content generation functionality
         */
        bindContentGeneration: function() {
            var self = this;

            // Generate content button click
            $(document).on('click', '.generate-content-btn', function() {
                var $button = $(this);
                var itemData = $button.data('item');

                if (!itemData) {
                    self.showNotification('Geen artikel data gevonden', 'error');
                    return;
                }

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgocmsAiml.i18n.generating);

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_generate_article_content',
                        nonce: writgocmsAiml.nonce,
                        item: JSON.stringify(itemData)
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification('Content succesvol gegenereerd!', 'success');
                            self.showContentPreview(response.data.content);
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('✨ Genereer Content');
                    }
                });
            });

            // Publish content button
            $(document).on('click', '.publish-content-btn', function() {
                var $button = $(this);
                var contentIndex = $button.data('index');
                var status = $button.data('status') || 'draft';

                // Get content from stored data
                var contentData = $button.closest('.generated-content-item').data('content');

                if (!contentData) {
                    self.showNotification('Geen content data gevonden', 'error');
                    return;
                }

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> Publiceren...');

                $.ajax({
                    url: writgocmsAiml.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_publish_content',
                        nonce: writgocmsAiml.nonce,
                        content: JSON.stringify(contentData),
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification(response.data.message, 'success');
                            // Redirect to edit post
                            if (response.data.edit_url) {
                                window.location.href = response.data.edit_url;
                            }
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('Publiceer als Concept');
                    }
                });
            });
        },

        /**
         * Show content preview modal
         */
        showContentPreview: function(content) {
            var html = '<div class="content-preview-modal" style="display:block;">';
            html += '<div class="content-preview-overlay"></div>';
            html += '<div class="content-preview-content">';
            html += '<div class="preview-header">';
            html += '<h3>' + this.escapeHtml(content.title) + '</h3>';
            html += '<button type="button" class="close-preview">&times;</button>';
            html += '</div>';
            html += '<div class="preview-body">';
            html += content.content;
            html += '</div>';
            html += '<div class="preview-footer">';
            html += '<button type="button" class="button button-primary publish-preview-btn" data-status="draft">📝 Publiceer als Concept</button>';
            html += '<button type="button" class="button publish-preview-btn" data-status="publish">🚀 Direct Publiceren</button>';
            html += '<button type="button" class="button close-preview">Annuleren</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';

            var $modal = $(html);
            $modal.data('content', content);
            $('body').append($modal);

            // Bind close button
            $modal.find('.close-preview, .content-preview-overlay').on('click', function() {
                $modal.remove();
            });

            // Bind publish buttons
            var self = this;
            $modal.find('.publish-preview-btn').on('click', function() {
                var status = $(this).data('status');
                self.publishContent(content, status, $modal);
            });
        },

        /**
         * Publish content from preview
         */
        publishContent: function(content, status, $modal) {
            var self = this;

            $.ajax({
                url: writgocmsAiml.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgocms_publish_content',
                    nonce: writgocmsAiml.nonce,
                    content: JSON.stringify(content),
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        $modal.remove();
                        // Redirect to edit post
                        if (response.data.edit_url) {
                            window.location.href = response.data.edit_url;
                        }
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Verbindingsfout', 'error');
                }
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
                    self.showNotification('Voer een prompt in', 'error');
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
                                $resultContent.html('<img src="' + response.data.image_url + '" alt="Gegenereerde Afbeelding">');
                            }

                            $result.show();
                            self.showNotification('Generatie voltooid!', 'success');
                        } else {
                            $status.text(response.data.message).addClass('error');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text('Verbindingsfout').addClass('error');
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).removeClass('loading').html('✨ Genereer');
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
                        $('.planner-status').text('Verbindingsfout').addClass('error');
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('✨ Genereer Topical Authority Map');
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
                    self.showNotification('Geen contentplan om op te slaan', 'error');
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
                        self.showNotification('Verbindingsfout', 'error');
                    }
                });
            });

            // Export plan button
            $('#export-content-plan').on('click', function() {
                if (!self.currentTopicalMap) {
                    self.showNotification('Geen contentplan om te exporteren', 'error');
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

    $(document).ready(function() {
        WritgoCMSAiml.init();
    });

})(jQuery);
