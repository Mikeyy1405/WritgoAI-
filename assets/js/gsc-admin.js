/**
 * GSC Admin JavaScript
 *
 * Admin interface for Google Search Console integration.
 *
 * @package WritgoAI
 */

(function($) {
    'use strict';

    var WritgoAIGsc = {
        currentOpportunityType: 'quick_win',
        selectedPostId: null,

        init: function() {
            this.bindPasswordToggles();
            this.bindSiteLoader();
            this.bindSyncButton();
            this.bindOpportunityTabs();
            this.bindPostSelector();
            this.bindCtrAnalysis();
            this.bindDisconnect();

            // Load dashboard data if connected.
            if (writgoaiGsc.isConnected && writgoaiGsc.selectedSite) {
                this.loadDashboardData();
                this.loadOpportunities('quick_win');
            }
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
         * Bind site loader button
         */
        bindSiteLoader: function() {
            var self = this;

            $('#load-sites-btn').on('click', function() {
                var $button = $(this);
                var $container = $('#sites-list');

                $button.prop('disabled', true).text(writgoaiGsc.i18n.loading);

                $.ajax({
                    url: writgoaiGsc.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_gsc_get_sites',
                        nonce: writgoaiGsc.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.siteEntry) {
                            var html = '';
                            response.data.siteEntry.forEach(function(site) {
                                var isSelected = site.siteUrl === writgoaiGsc.selectedSite;
                                html += '<div class="site-item' + (isSelected ? ' selected' : '') + '" data-url="' + self.escapeHtml(site.siteUrl) + '">';
                                html += '<span class="site-url">' + self.escapeHtml(site.siteUrl) + '</span>';
                                html += '<button type="button" class="button button-small select-site-btn">' + (isSelected ? '✓ Geselecteerd' : 'Selecteer') + '</button>';
                                html += '</div>';
                            });
                            $container.html(html).show();
                        } else {
                            $container.html('<p>' + (response.data ? response.data.message : writgoaiGsc.i18n.noData) + '</p>').show();
                        }
                    },
                    error: function() {
                        $container.html('<p class="error">' + writgoaiGsc.i18n.error + '</p>').show();
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('🔄 Laad Sites');
                    }
                });
            });

            // Handle site selection
            $(document).on('click', '.select-site-btn', function() {
                var $button = $(this);
                var $item = $button.closest('.site-item');
                var siteUrl = $item.data('url');

                $button.prop('disabled', true).text(writgoaiGsc.i18n.loading);

                $.ajax({
                    url: writgoaiGsc.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_gsc_select_site',
                        nonce: writgoaiGsc.nonce,
                        site_url: siteUrl
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            self.showNotification(response.data.message, 'error');
                            $button.prop('disabled', false).text('Selecteer');
                        }
                    },
                    error: function() {
                        self.showNotification(writgoaiGsc.i18n.error, 'error');
                        $button.prop('disabled', false).text('Selecteer');
                    }
                });
            });
        },

        /**
         * Bind sync button
         */
        bindSyncButton: function() {
            var self = this;

            $('#sync-now-btn').on('click', function() {
                var $button = $(this);

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiGsc.i18n.syncing);

                $.ajax({
                    url: writgoaiGsc.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_gsc_sync_now',
                        nonce: writgoaiGsc.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification(writgoaiGsc.i18n.syncComplete, 'success');
                            self.loadDashboardData();
                            self.loadOpportunities(self.currentOpportunityType);
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification(writgoaiGsc.i18n.error, 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('🔄 Synchroniseer Nu');
                    }
                });
            });
        },

        /**
         * Bind opportunity tabs
         */
        bindOpportunityTabs: function() {
            var self = this;

            $('.opportunity-tab').on('click', function() {
                var $tab = $(this);
                var type = $tab.data('type');

                $('.opportunity-tab').removeClass('active');
                $tab.addClass('active');

                self.currentOpportunityType = type;
                self.loadOpportunities(type);
            });
        },

        /**
         * Bind post selector for CTR optimizer
         */
        bindPostSelector: function() {
            var self = this;

            // Search functionality
            $('#post-search-input').on('input', function() {
                var query = $(this).val().toLowerCase();
                $('#post-list .post-item').each(function() {
                    var title = $(this).find('.post-title').text().toLowerCase();
                    $(this).toggle(title.indexOf(query) !== -1);
                });
            });

            // Post selection
            $('#post-list').on('click', '.post-item', function() {
                var $item = $(this);
                var postId = $item.data('post-id');

                $('#post-list .post-item').removeClass('selected');
                $item.addClass('selected');

                self.selectedPostId = postId;
                self.loadPostAnalysis(postId);
            });
        },

        /**
         * Bind CTR analysis
         */
        bindCtrAnalysis: function() {
            var self = this;

            $('#generate-suggestions-btn').on('click', function() {
                if (!self.selectedPostId) {
                    self.showNotification('Selecteer eerst een post', 'error');
                    return;
                }

                var $button = $(this);
                var keyword = $('#target-keyword').val();

                $button.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiGsc.i18n.generating);

                $.ajax({
                    url: writgoaiGsc.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_ctr_generate_suggestions',
                        nonce: writgoaiGsc.nonce,
                        post_id: self.selectedPostId,
                        keyword: keyword
                    },
                    success: function(response) {
                        if (response.success) {
                            self.renderSuggestions(response.data.suggestions);
                            $('#ctr-suggestions').show();
                        } else {
                            self.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification(writgoaiGsc.i18n.error, 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('✨ Genereer AI Suggesties');
                    }
                });
            });
        },

        /**
         * Bind disconnect button
         */
        bindDisconnect: function() {
            var self = this;

            $('#disconnect-gsc-btn').on('click', function() {
                if (!confirm(writgoaiGsc.i18n.confirmDisconnect)) {
                    return;
                }

                var $button = $(this);
                $button.prop('disabled', true);

                $.ajax({
                    url: writgoaiGsc.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgoai_gsc_disconnect',
                        nonce: writgoaiGsc.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            self.showNotification(response.data.message, 'error');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        self.showNotification(writgoaiGsc.i18n.error, 'error');
                        $button.prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Load dashboard data
         */
        loadDashboardData: function() {
            var self = this;

            $.ajax({
                url: writgoaiGsc.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_gsc_get_dashboard_data',
                    nonce: writgoaiGsc.nonce,
                    days: 28
                },
                success: function(response) {
                    if (response.success) {
                        self.renderMetrics(response.data.totals);
                        self.renderTopQueries(response.data.top_queries);
                        self.renderTopPages(response.data.top_pages);
                        self.updateOpportunityCounts(response.data.opportunity_counts);
                    }
                }
            });
        },

        /**
         * Render metrics
         */
        renderMetrics: function(totals) {
            if (!totals) return;

            $('#total-clicks').text(this.formatNumber(totals.total_clicks || 0));
            $('#total-impressions').text(this.formatNumber(totals.total_impressions || 0));
            $('#avg-ctr').text(((totals.avg_ctr || 0) * 100).toFixed(2) + '%');
            $('#avg-position').text((totals.avg_position || 0).toFixed(1));
        },

        /**
         * Render top queries
         */
        renderTopQueries: function(queries) {
            var $container = $('#top-queries');

            if (!queries || queries.length === 0) {
                $container.html('<p>' + writgoaiGsc.i18n.noData + '</p>');
                return;
            }

            var html = '<table class="data-table">';
            html += '<thead><tr><th>Keyword</th><th>Clicks</th><th>Impressies</th><th>CTR</th><th>Pos.</th></tr></thead>';
            html += '<tbody>';

            queries.forEach(function(query) {
                html += '<tr>';
                html += '<td class="query-cell" title="' + this.escapeHtml(query.query) + '">' + this.escapeHtml(query.query) + '</td>';
                html += '<td>' + this.formatNumber(query.clicks) + '</td>';
                html += '<td>' + this.formatNumber(query.impressions) + '</td>';
                html += '<td>' + (query.ctr * 100).toFixed(2) + '%</td>';
                html += '<td>' + parseFloat(query.position).toFixed(1) + '</td>';
                html += '</tr>';
            }.bind(this));

            html += '</tbody></table>';
            $container.html(html);
        },

        /**
         * Render top pages
         */
        renderTopPages: function(pages) {
            var self = this;
            var $container = $('#top-pages');

            if (!pages || pages.length === 0) {
                $container.html('<p>' + writgoaiGsc.i18n.noData + '</p>');
                return;
            }

            var html = '<table class="data-table">';
            html += '<thead><tr><th>Pagina</th><th>Clicks</th><th>Impressies</th><th>CTR</th><th>Pos.</th></tr></thead>';
            html += '<tbody>';

            pages.forEach(function(page) {
                var displayUrl = page.url.replace(/^https?:\/\/[^\/]+/, '');
                html += '<tr>';
                html += '<td class="url-cell" title="' + self.escapeHtml(page.url) + '"><a href="' + self.escapeHtml(page.url) + '" target="_blank">' + self.escapeHtml(displayUrl) + '</a></td>';
                html += '<td>' + self.formatNumber(page.clicks) + '</td>';
                html += '<td>' + self.formatNumber(page.impressions) + '</td>';
                html += '<td>' + (page.ctr * 100).toFixed(2) + '%</td>';
                html += '<td>' + parseFloat(page.position).toFixed(1) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        /**
         * Update opportunity counts
         */
        updateOpportunityCounts: function(counts) {
            if (!counts) return;

            var countMap = {};
            counts.forEach(function(item) {
                countMap[item.opportunity_type] = item.count;
            });

            $('#count-quick_win').text(countMap.quick_win || 0);
            $('#count-low_ctr').text(countMap.low_ctr || 0);
            $('#count-declining').text(countMap.declining || 0);
            $('#count-content_gap').text(countMap.content_gap || 0);
        },

        /**
         * Load opportunities
         */
        loadOpportunities: function(type) {
            var self = this;
            var $container = $('#opportunities-list');

            $container.html('<p class="loading-text">' + writgoaiGsc.i18n.loading + '</p>');

            $.ajax({
                url: writgoaiGsc.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_gsc_get_opportunities',
                    nonce: writgoaiGsc.nonce,
                    type: type,
                    limit: 20
                },
                success: function(response) {
                    if (response.success) {
                        self.renderOpportunities(response.data.opportunities);
                    } else {
                        $container.html('<p>' + writgoaiGsc.i18n.noData + '</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="error">' + writgoaiGsc.i18n.error + '</p>');
                }
            });
        },

        /**
         * Render opportunities
         */
        renderOpportunities: function(opportunities) {
            var self = this;
            var $container = $('#opportunities-list');

            if (!opportunities || opportunities.length === 0) {
                $container.html('<p>' + writgoaiGsc.i18n.noData + '</p>');
                return;
            }

            var html = '';
            opportunities.forEach(function(opp) {
                var scoreClass = opp.score >= 70 ? '' : (opp.score >= 40 ? 'medium' : 'low');

                html += '<div class="opportunity-item">';
                html += '<div class="opp-main">';
                html += '<div class="opp-keyword">' + self.escapeHtml(opp.keyword) + '</div>';
                html += '<div class="opp-stats">';

                if (opp.current_position) {
                    html += '<span>📍 Positie: ' + parseFloat(opp.current_position).toFixed(1) + '</span>';
                }
                if (opp.current_ctr) {
                    html += '<span>📈 CTR: ' + (opp.current_ctr * 100).toFixed(2) + '%</span>';
                }
                if (opp.impressions) {
                    html += '<span>👁️ ' + self.formatNumber(opp.impressions) + ' impressies</span>';
                }
                if (opp.position_change) {
                    html += '<span>⬇️ ' + parseFloat(opp.position_change).toFixed(1) + ' posities gedaald</span>';
                }

                html += '</div>';

                if (opp.suggested_action) {
                    html += '<div class="opp-action">' + self.escapeHtml(opp.suggested_action) + '</div>';
                }

                html += '</div>';
                html += '<div class="opp-score">';
                html += '<span class="score-badge ' + scoreClass + '">' + Math.round(opp.score) + '</span>';
                html += '</div>';
                html += '</div>';
            });

            $container.html(html);
        },

        /**
         * Load post analysis
         */
        loadPostAnalysis: function(postId) {
            var self = this;
            var $panel = $('#ctr-analysis-panel');
            var $content = $('#ctr-analysis-content');

            $panel.show();
            $content.html('<p class="loading-text">' + writgoaiGsc.i18n.analyzing + '</p>');
            $('#ctr-suggestions').hide();

            $.ajax({
                url: writgoaiGsc.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_ctr_analyze',
                    nonce: writgoaiGsc.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderAnalysis(response.data);
                    } else {
                        $content.html('<p class="error">' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $content.html('<p class="error">' + writgoaiGsc.i18n.error + '</p>');
                }
            });
        },

        /**
         * Render analysis
         */
        renderAnalysis: function(data) {
            var self = this;
            var $content = $('#ctr-analysis-content');

            var html = '';

            // Meta Preview Section
            html += '<div class="analysis-section">';
            html += '<h4>🔍 Huidige Meta Tags</h4>';
            html += '<div class="meta-preview">';
            html += '<div class="meta-title">' + self.escapeHtml(data.current_title || data.post_title) + '</div>';
            html += '<div class="meta-url">example.com/...</div>';
            html += '<div class="meta-description">' + self.escapeHtml(data.current_description || 'Geen meta description ingesteld.') + '</div>';
            html += '</div>';

            html += '<div class="meta-stats">';
            html += '<div class="stat-item"><span class="stat-label">Title lengte:</span> <span class="stat-value ' + self.getLengthClass(data.title_length, 50, 60) + '">' + data.title_length + ' karakters</span></div>';
            html += '<div class="stat-item"><span class="stat-label">Description lengte:</span> <span class="stat-value ' + self.getLengthClass(data.description_length, 150, 160) + '">' + data.description_length + ' karakters</span></div>';
            html += '</div>';

            // Issues
            if (data.title_issues && data.title_issues.length > 0) {
                html += '<h5>Title problemen:</h5>';
                html += '<ul class="issues-list">';
                data.title_issues.forEach(function(issue) {
                    var icon = issue.type === 'error' ? '❌' : (issue.type === 'warning' ? '⚠️' : '💡');
                    html += '<li class="' + issue.type + '"><span class="issue-icon">' + icon + '</span> ' + self.escapeHtml(issue.message) + '</li>';
                });
                html += '</ul>';
            }

            if (data.description_issues && data.description_issues.length > 0) {
                html += '<h5>Description problemen:</h5>';
                html += '<ul class="issues-list">';
                data.description_issues.forEach(function(issue) {
                    var icon = issue.type === 'error' ? '❌' : (issue.type === 'warning' ? '⚠️' : '💡');
                    html += '<li class="' + issue.type + '"><span class="issue-icon">' + icon + '</span> ' + self.escapeHtml(issue.message) + '</li>';
                });
                html += '</ul>';
            }

            html += '</div>';

            // CTR Performance Section (if GSC data available)
            if (data.gsc_data && data.gsc_data.page_data) {
                html += '<div class="analysis-section">';
                html += '<h4>📊 CTR Prestaties</h4>';
                html += '<div class="ctr-performance">';

                html += '<div class="ctr-comparison">';
                html += '<div class="ctr-item">';
                html += '<div class="ctr-label">Huidige CTR</div>';
                html += '<div class="ctr-value current">' + (data.current_ctr * 100).toFixed(2) + '%</div>';
                html += '</div>';
                html += '<div class="ctr-item">';
                html += '<div class="ctr-label">Benchmark CTR</div>';
                html += '<div class="ctr-value benchmark">' + (data.benchmark_ctr * 100).toFixed(2) + '%</div>';
                html += '</div>';
                html += '</div>';

                if (data.ctr_performance) {
                    html += '<div class="ctr-status ' + data.ctr_performance.status + '">' + self.escapeHtml(data.ctr_performance.label) + '</div>';
                }

                if (data.potential_clicks > 0) {
                    html += '<div class="potential-clicks">';
                    html += '<div class="potential-label">Potentiële extra clicks</div>';
                    html += '<div class="potential-value">+' + data.potential_clicks + '</div>';
                    html += '</div>';
                }

                html += '</div>';

                // Trend indicator
                if (data.gsc_data.trend) {
                    var trendIcon = data.gsc_data.trend === 'rising' ? '📈' : (data.gsc_data.trend === 'declining' ? '📉' : '➡️');
                    var trendLabel = data.gsc_data.trend === 'rising' ? 'Stijgend' : (data.gsc_data.trend === 'declining' ? 'Dalend' : 'Stabiel');
                    html += '<p style="margin-top: 15px;">Trend: ' + trendIcon + ' ' + trendLabel + '</p>';
                }

                html += '</div>';
            }

            $content.html(html);
        },

        /**
         * Render suggestions
         */
        renderSuggestions: function(data) {
            var self = this;
            var $content = $('#suggestions-content');

            if (data.error) {
                $content.html('<p class="error">' + self.escapeHtml(data.message) + '</p>');
                return;
            }

            var html = '';

            if (data.suggestions && data.suggestions.length > 0) {
                data.suggestions.forEach(function(suggestion, index) {
                    html += '<div class="suggestion-card">';
                    html += '<div class="suggestion-header">';
                    html += '<span class="suggestion-number">' + (index + 1) + '</span>';
                    if (suggestion.expected_ctr_improvement) {
                        html += '<span class="ctr-improvement">+' + suggestion.expected_ctr_improvement + ' CTR</span>';
                    }
                    html += '</div>';

                    html += '<div class="suggestion-preview">';
                    html += '<div class="preview-title">' + self.escapeHtml(suggestion.title) + '</div>';
                    html += '<div class="preview-description">' + self.escapeHtml(suggestion.description) + '</div>';
                    html += '<div class="char-counts">';
                    html += '<span>Title: ' + suggestion.title_length + ' karakters</span>';
                    html += '<span>Description: ' + suggestion.description_length + ' karakters</span>';
                    html += '</div>';
                    html += '</div>';

                    if (suggestion.reasoning) {
                        html += '<div class="reasoning">💡 ' + self.escapeHtml(suggestion.reasoning) + '</div>';
                    }

                    html += '<div class="suggestion-actions">';
                    html += '<button type="button" class="button button-small copy-title-btn" data-title="' + self.escapeHtml(suggestion.title) + '">📋 Kopieer Title</button>';
                    html += '<button type="button" class="button button-small copy-desc-btn" data-desc="' + self.escapeHtml(suggestion.description) + '">📋 Kopieer Description</button>';
                    html += '</div>';

                    html += '</div>';
                });
            }

            if (data.tips && data.tips.length > 0) {
                html += '<div class="tips-section">';
                html += '<h4>💡 Algemene Tips</h4>';
                html += '<ul>';
                data.tips.forEach(function(tip) {
                    html += '<li>' + self.escapeHtml(tip) + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            $content.html(html);

            // Bind copy buttons
            $('.copy-title-btn').on('click', function() {
                var title = $(this).data('title');
                self.copyToClipboard(title);
                self.showNotification('Title gekopieerd!', 'success');
            });

            $('.copy-desc-btn').on('click', function() {
                var desc = $(this).data('desc');
                self.copyToClipboard(desc);
                self.showNotification('Description gekopieerd!', 'success');
            });
        },

        /**
         * Get length class for meta tag length
         */
        getLengthClass: function(length, min, max) {
            if (length >= min && length <= max) {
                return 'good';
            } else if (length < min * 0.7 || length > max * 1.2) {
                return 'error';
            }
            return 'warning';
        },

        /**
         * Format number with thousands separator
         */
        formatNumber: function(num) {
            return parseInt(num).toLocaleString('nl-NL');
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
         * Copy to clipboard
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
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
        WritgoAIGsc.init();
    });

})(jQuery);
