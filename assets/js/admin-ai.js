/**
 * AIML Admin JavaScript
 *
 * Admin interface for AIMLAPI integration.
 * Nederlandse versie - Dutch interface for WritgoAI.
 *
 * @package WritgoAI
 */

(function($) {
    'use strict';

    var WritgoAIAiml = {
        testType: 'text',
        currentTopicalMap: null,
        creditRefreshInterval: null,

        init: function() {
            this.bindPasswordToggles();
            this.bindRangeInputs();
            this.bindTestInterface();
            this.bindContentPlanner();
            this.loadSavedPlans();
            this.bindSiteAnalysis();
            this.bindContentGeneration();
            this.loadUsageStats();
            this.initCreditAutoRefresh();
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
         * Load usage stats from REST API
         */
        loadUsageStats: function() {
            var self = this;
            var $dashboard = $('#usage-dashboard');
            
            if (!$dashboard.length || !writgoaiAi.restUrl) {
                return;
            }

            $.ajax({
                url: writgoaiAi.restUrl + 'usage',
                type: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', writgoaiAi.restNonce);
                },
                success: function(response) {
                    if (response) {
                        $('#requests-used').text(response.requests_used || 0);
                        $('#requests-remaining').text(response.requests_remaining || 0);
                        $('#daily-limit').text(response.daily_limit || 0);
                        
                        // Update progress bar
                        var percentage = 0;
                        if (response.daily_limit > 0) {
                            percentage = (response.requests_used / response.daily_limit) * 100;
                        }
                        $('#usage-progress-fill').css('width', Math.min(percentage, 100) + '%');
                        
                        // Update reset time
                        if (response.reset_at) {
                            var resetDate = new Date(response.reset_at);
                            $('#reset-time').text(resetDate.toLocaleTimeString('nl-NL'));
                        }
                    }
                },
                error: function() {
                    // Silently fail - usage stats are informational only
                    $('#requests-used').text('-');
                    $('#requests-remaining').text('-');
                    $('#daily-limit').text('-');
                }
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

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiAi.i18n.analyzing);
                $status.text('').removeClass('error success');

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_analyze_sitemap',
                        nonce: writgoaiAi.nonce,
                        manual_theme: manualTheme
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgoaiAi.i18n.analysisComplete).addClass('success');
                            self.showNotification(writgoaiAi.i18n.analysisComplete, 'success');
                            // Reload page to show results
                            location.reload();
                        } else {
                            $status.text(response.data.message).addClass('error');
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        $status.text(writgoaiAi.i18n.analysisError).addClass('error');
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('🔍 ' + writgoaiAi.i18n.refreshAnalysis);
                    }
                });
            });

            // Generate content plan button
            $('#generate-content-plan').on('click', function() {
                var $button = $(this);
                var $status = $('.content-plan-status');

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiAi.i18n.generatingMap);
                $status.text('').removeClass('error success');

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_generate_categorized_plan',
                        nonce: writgoaiAi.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgoaiAi.i18n.success).addClass('success');
                            self.showNotification('Contentplan succesvol gegenereerd!', 'success');
                            // Redirect to content plan page
                            window.location.href = writgoaiAi.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=writgoai-ai-contentplan');
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

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiAi.i18n.generating);

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_generate_article_content',
                        nonce: writgoaiAi.nonce,
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
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_publish_content',
                        nonce: writgoaiAi.nonce,
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
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_publish_content',
                    nonce: writgoaiAi.nonce,
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
                    $('#test-prompt').attr('placeholder', writgoaiAi.i18n.testPrompt);
                    $modelSelect.find('.text-models').show();
                    $modelSelect.find('.image-models').hide();
                    $modelSelect.find('.text-models option:first').prop('selected', true);
                } else {
                    $('#test-prompt').attr('placeholder', writgoaiAi.i18n.imagePrompt);
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

                $button.prop('disabled', true).addClass('loading').html('<span class="loading-spinner"></span>' + writgoaiAi.i18n.generating);
                $status.text('').removeClass('error success');
                $result.hide();

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_test_generation',
                        nonce: writgoaiAi.nonce,
                        type: self.testType,
                        prompt: prompt,
                        model: model
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(writgoaiAi.i18n.success).addClass('success');

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
                    self.showNotification(writgoaiAi.i18n.noNiche, 'error');
                    return;
                }

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiAi.i18n.generatingMap);
                $('.planner-status').text('').removeClass('error success');

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_generate_topical_map',
                        nonce: writgoaiAi.nonce,
                        niche: niche,
                        website_type: websiteType,
                        target_audience: targetAudience
                    },
                    success: function(response) {
                        if (response.success) {
                            self.currentTopicalMap = response.data.topical_map;
                            self.renderTopicalMap(response.data.topical_map);
                            $('.content-planner-results').show();
                            self.showNotification(writgoaiAi.i18n.success, 'success');
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
                    self.showNotification(writgoaiAi.i18n.noPlanName, 'error');
                    return;
                }

                if (!self.currentTopicalMap) {
                    self.showNotification('Geen contentplan om op te slaan', 'error');
                    return;
                }

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_save_content_plan',
                        nonce: writgoaiAi.nonce,
                        plan_name: planName,
                        plan_data: JSON.stringify(self.currentTopicalMap)
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#save-plan-modal').hide();
                            $('#plan-name').val('');
                            self.loadSavedPlans();
                            self.showNotification(writgoaiAi.i18n.planSaved, 'success');
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

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiAi.i18n.generatingPlan);

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_generate_content_plan',
                        nonce: writgoaiAi.nonce,
                        topic: topic,
                        content_type: 'article',
                        keywords: keywords
                    },
                    success: function(response) {
                        if (response.success) {
                            self.renderContentPlan(response.data.content_plan);
                            $('#content-detail-panel').show();
                            self.showNotification(writgoaiAi.i18n.success, 'success');
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Verbindingsfout', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('📋 ' + writgoaiAi.i18n.generateDetailedPlan);
                    }
                });
            });

            // Delegate click for delete plan buttons
            $(document).on('click', '.delete-plan-btn', function() {
                var $button = $(this);
                var planId = $button.data('plan-id');

                if (!confirm(writgoaiAi.i18n.confirmDelete)) {
                    return;
                }

                $.ajax({
                    url: writgoaiAi.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_delete_content_plan',
                        nonce: writgoaiAi.nonce,
                        plan_id: planId
                    },
                    success: function(response) {
                        if (response.success) {
                            self.loadSavedPlans();
                            self.showNotification(writgoaiAi.i18n.planDeleted, 'success');
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Verbindingsfout', 'error');
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
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_get_saved_plans',
                    nonce: writgoaiAi.nonce
                },
                success: function(response) {
                    if (response.success && response.data.plans) {
                        var plans = response.data.plans;
                        var keys = Object.keys(plans);

                        if (keys.length === 0) {
                            $container.html('<p class="no-plans">Nog geen opgeslagen contentplannen. Genereer een topical map om te beginnen!</p>');
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
                            html += '<button type="button" class="button button-small load-plan-btn" data-plan="' + self.escapeJsonAttr(plan.data) + '">📂 Laden</button>';
                            html += '<button type="button" class="button button-small delete-plan-btn" data-plan-id="' + self.escapeHtml(planId) + '">🗑️ Verwijderen</button>';
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
            html += '<h4>🎯 ' + self.escapeHtml(data.main_topic || 'Contentstrategie') + '</h4>';
            html += '</div>';

            // Pillar content
            if (data.pillar_content && data.pillar_content.length > 0) {
                html += '<div class="pillar-content-section">';
                html += '<h4>' + writgoaiAi.i18n.pillarContent + '</h4>';

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
                        html += '<span class="keywords-label">' + writgoaiAi.i18n.keywords + ': </span>';
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
                        html += '<h6>' + writgoaiAi.i18n.clusterArticles + '</h6>';
                        html += '<ul>';

                        pillar.cluster_articles.forEach(function(article) {
                            var priorityClass = 'priority-' + (article.priority || 'medium');
                            var priorityLabel = writgoaiAi.i18n[article.priority] || article.priority || 'Gemiddeld';

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
                html += '<h4>🔍 ' + writgoaiAi.i18n.contentGaps + '</h4>';
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
                html += '<h4>📅 ' + writgoaiAi.i18n.recommendedOrder + '</h4>';
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
            html += '<h4>' + self.escapeHtml(data.title || 'Artikel Outline') + '</h4>';
            if (data.meta_description) {
                html += '<p class="meta-description"><strong>Meta Beschrijving:</strong> ' + self.escapeHtml(data.meta_description) + '</p>';
            }
            if (data.estimated_word_count) {
                html += '<p class="word-count"><strong>Geschat Aantal Woorden:</strong> ' + data.estimated_word_count + '</p>';
            }
            html += '</div>';

            // Target keywords
            if (data.target_keywords && data.target_keywords.length > 0) {
                html += '<div class="target-keywords">';
                html += '<strong>Doelzoekwoorden:</strong> ';
                data.target_keywords.forEach(function(keyword) {
                    html += '<span class="keyword-tag">' + self.escapeHtml(keyword) + '</span>';
                });
                html += '</div>';
            }

            // Content structure
            if (data.content_structure) {
                html += '<div class="content-structure">';
                html += '<h5>📝 Contentstructuur</h5>';

                if (data.content_structure.introduction) {
                    html += '<div class="structure-section intro">';
                    html += '<strong>Introductie:</strong> ' + self.escapeHtml(data.content_structure.introduction);
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
                    html += '<strong>Conclusie:</strong> ' + self.escapeHtml(data.content_structure.conclusion);
                    html += '</div>';
                }

                html += '</div>';
            }

            // Internal links
            if (data.internal_links && data.internal_links.length > 0) {
                html += '<div class="internal-links">';
                html += '<h5>🔗 Aanbevolen Interne Links</h5>';
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
                html += '<h5>🎯 CTA Suggesties</h5>';
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
                '📋 ' + writgoaiAi.i18n.generateDetailedPlan + '</button>';
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
            if (!$('.writgoai-post-updater').length) {
                return;
            }

            this.bindEvents();
            this.loadPosts();
        },

        /**
         * Get translation string safely
         */
        getTranslation: function(key, fallback) {
            if (writgoaiAi && writgoaiAi.i18n && writgoaiAi.i18n.postUpdater && writgoaiAi.i18n.postUpdater[key]) {
                return writgoaiAi.i18n.postUpdater[key];
            }
            return fallback || key;
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

            $list.html('<div class="loading-state"><span class="spinner is-active"></span><p>' + self.getTranslation('loading', 'Laden...') + '</p></div>');

            $.ajax({
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_get_posts_for_update',
                    nonce: writgoaiAi.nonce,
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
                $list.html('<div class="no-posts-message">' + self.getTranslation('noPostsFound', 'Geen posts gevonden.') + '</div>');
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
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'writgoai_improve_post',
                    nonce: writgoaiAi.nonce,
                    post_id: self.currentPostId
                }, options),
                success: function(response) {
                    if (response.success) {
                        self.improvedData = response.data;
                        $modal.hide();
                        self.showPreview(response.data);
                    } else {
                        WritgoAIAiml.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WritgoAIAiml.showNotification('Connection error', 'error');
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
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_save_improved_post',
                    nonce: writgoaiAi.nonce,
                    post_id: self.currentPostId,
                    improved_data: JSON.stringify(self.improvedData.improved),
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        $('#preview-modal').hide();
                        WritgoAIAiml.showNotification(response.data.message, 'success');
                        self.loadPosts();
                    } else {
                        WritgoAIAiml.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WritgoAIAiml.showNotification('Connection error', 'error');
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
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'writgoai_bulk_improve_posts',
                    nonce: writgoaiAi.nonce,
                    post_ids: self.selectedPosts
                }, options),
                success: function(response) {
                    if (response.success) {
                        $progressFill.css('width', '100%');
                        $progressText.text('Voltooid! ' + response.data.success + ' succesvol, ' + response.data.failed + ' mislukt.');
                        WritgoAIAiml.showNotification('Bulk verbetering voltooid!', 'success');

                        setTimeout(function() {
                            $('#bulk-modal').hide();
                            self.loadPosts();
                        }, 2000);
                    } else {
                        WritgoAIAiml.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WritgoAIAiml.showNotification('Connection error', 'error');
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
        },

        /**
         * Initialize credit auto-refresh
         */
        initCreditAutoRefresh: function() {
            var self = this;
            
            // Load credits immediately if on a page with credit display
            if ($('.writgoai-credits-widget').length || $('#usage-dashboard').length || $('.credit-balance-display').length) {
                self.loadCredits();
                
                // Auto-refresh every 5 minutes (300000 ms)
                self.creditRefreshInterval = setInterval(function() {
                    self.loadCredits(false); // Use cached data unless force refresh
                }, 300000);
            }

            // Bind manual refresh button if exists
            $(document).on('click', '.refresh-credits-btn', function(e) {
                e.preventDefault();
                self.loadCredits(true); // Force refresh
            });
        },

        /**
         * Load credit balance
         */
        loadCredits: function(forceRefresh) {
            var self = this;
            var action = forceRefresh ? 'writgoai_refresh_credits' : 'writgoai_get_credits';

            $.ajax({
                url: writgoaiAi.ajaxUrl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: writgoaiAi.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateCreditDisplays(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    // Silently fail - credit display is non-critical
                    if (typeof console !== 'undefined' && console.warn && writgoaiAi.debug) {
                        console.warn('Credit load failed:', error);
                    }
                }
            });
        },

        /**
         * Update all credit displays on page
         */
        updateCreditDisplays: function(data) {
            // Update dashboard widget
            if ($('.writgoai-credits-widget').length) {
                $('.writgoai-credits-widget .credits-number').text(this.formatNumber(data.credits_remaining || 0));
                
                var percentage = data.credits_total > 0 ? (data.credits_remaining / data.credits_total) * 100 : 0;
                var barColor = percentage > 50 ? '#28a745' : (percentage > 20 ? '#ffc107' : '#dc3545');
                
                $('.writgoai-credits-widget .credits-bar-fill')
                    .css('width', Math.min(percentage, 100) + '%')
                    .css('background', barColor);
                
                $('.writgoai-credits-widget .credits-info').find('strong').eq(0).text(this.formatNumber(data.credits_used || 0));
                $('.writgoai-credits-widget .credits-info').find('strong').eq(1).text(this.formatNumber(data.credits_total || 0));
            }

            // Update admin bar if exists
            if ($('#wp-admin-bar-writgoai-credits').length) {
                $('#wp-admin-bar-writgoai-credits .ab-item').html(
                    '<span style="display: inline-flex; align-items: center; gap: 5px;">🤖 <strong>' + 
                    this.formatNumber(data.credits_remaining || 0) + 
                    '</strong> credits</span>'
                );
            }

            // Update any generic credit balance displays
            $('.credit-balance-display').each(function() {
                $(this).find('.credits-remaining').text(data.credits_remaining || 0);
                $(this).find('.credits-used').text(data.credits_used || 0);
                $(this).find('.credits-total').text(data.credits_total || 0);
            });

            // Update period end date if available
            if (data.period_end && $('.credit-period-end').length) {
                $('.credit-period-end').text(data.period_end);
            }
        },

        /**
         * Format number with thousand separators
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        },

        /**
         * Update credit display after action
         */
        updateCreditsAfterAction: function(creditsConsumed) {
            var self = this;
            
            // Show notification
            if (creditsConsumed > 0) {
                self.showNotification(creditsConsumed + ' credits gebruikt', 'info');
            }

            // Refresh credit display
            setTimeout(function() {
                self.loadCredits(true);
            }, 1000);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            var notificationClass = 'notice-' + (type || 'info');
            var $notification = $('<div class="notice ' + notificationClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap').eq(0).prepend($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        WritgoAIAiml.init();
        PostUpdater.init();
    });

})(jQuery);
