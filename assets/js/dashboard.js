/**
 * Dashboard JavaScript
 *
 * Handles dashboard interactions and real-time updates.
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Dashboard object
		const Dashboard = {
			init: function() {
				this.bindEvents();
				this.loadStats();
			},

			bindEvents: function() {
				// Refresh button (if added later)
				$(document).on('click', '.refresh-stats', this.loadStats.bind(this));
			},

			loadStats: function() {
				$.ajax({
					url: writgoaiDashboard.ajaxUrl,
					method: 'POST',
					data: {
						action: 'writgoai_get_dashboard_stats',
						nonce: writgoaiDashboard.nonce
					},
					success: function(response) {
						if (response.success) {
							Dashboard.updateStats(response.data);
						}
					},
					error: function(xhr, status, error) {
						console.error('Failed to load dashboard stats:', error);
					}
				});
			},

			updateStats: function(stats) {
				// Update health score
				if (stats.health_score !== undefined) {
					$('.score-value').text(stats.health_score).attr('class', 'score-value ' + this.getScoreClass(stats.health_score));
				}

				// Update metrics
				if (stats.content_coverage !== undefined) {
					$('.health-metrics .metric:eq(0) .metric-value').text(stats.content_coverage + '%');
				}
				if (stats.topical_authority !== undefined) {
					$('.health-metrics .metric:eq(1) .metric-value').text(stats.topical_authority + '%');
				}
				if (stats.internal_links_score !== undefined) {
					$('.health-metrics .metric:eq(2) .metric-value').text(stats.internal_links_score + '%');
				}
				if (stats.technical_seo_score !== undefined) {
					$('.health-metrics .metric:eq(3) .metric-value').text(stats.technical_seo_score + '%');
				}

				// Update quick stats
				if (stats.total_posts !== undefined) {
					$('.stat-card:eq(0) .stat-value').text(stats.total_posts);
				}
				if (stats.optimized_posts !== undefined) {
					$('.stat-card:eq(1) .stat-value').text(stats.optimized_posts);
				}
				if (stats.avg_ranking !== undefined) {
					$('.stat-card:eq(2) .stat-value').text(stats.avg_ranking);
				}
				if (stats.monthly_traffic !== undefined) {
					$('.stat-card:eq(3) .stat-value').text(this.formatNumber(stats.monthly_traffic));
				}
			},

			getScoreClass: function(score) {
				if (score >= 71) return 'score-good';
				if (score >= 41) return 'score-warning';
				return 'score-poor';
			},

			formatNumber: function(num) {
				return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			}
		};

		// Initialize dashboard
		Dashboard.init();
	});

})(jQuery);
