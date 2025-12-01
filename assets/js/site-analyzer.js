/**
 * Site Analyzer JavaScript
 *
 * Handles site analysis operations and UI interactions.
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Site Analyzer object
		const SiteAnalyzer = {
			isAnalyzing: false,

			init: function() {
				this.bindEvents();
			},

			bindEvents: function() {
				$(document).on('click', '#start-site-analysis', this.startAnalysis.bind(this));
				$(document).on('click', '#analyze-single-post', this.analyzeSinglePost.bind(this));
			},

			startAnalysis: function(e) {
				e.preventDefault();

				if (this.isAnalyzing) {
					return;
				}

				const $btn = $(e.currentTarget);
				const $progress = $('#analysis-progress');
				const $results = $('#analysis-results');

				this.isAnalyzing = true;
				$btn.prop('disabled', true).text('Analyzing...');
				$progress.show();
				$results.hide();

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'writgocms_analyze_site',
						nonce: $btn.data('nonce')
					},
					success: function(response) {
						if (response.success) {
							SiteAnalyzer.showResults(response.data);
							$progress.hide();
							$results.show();
						} else {
							alert('Analysis failed: ' + (response.data.message || 'Unknown error'));
							$progress.hide();
						}
					},
					error: function(xhr, status, error) {
						console.error('Analysis error:', error);
						alert('Analysis failed. Please try again.');
						$progress.hide();
					},
					complete: function() {
						SiteAnalyzer.isAnalyzing = false;
						$btn.prop('disabled', false).text('Start Analysis');
					}
				});
			},

			analyzeSinglePost: function(e) {
				e.preventDefault();

				const $btn = $(e.currentTarget);
				const postId = $btn.data('post-id');
				const nonce = $btn.data('nonce');

				$btn.prop('disabled', true).text('Analyzing...');

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'writgocms_analyze_post',
						nonce: nonce,
						post_id: postId
					},
					success: function(response) {
						if (response.success) {
							alert('Post analyzed successfully!');
							location.reload();
						} else {
							alert('Analysis failed: ' + (response.data.message || 'Unknown error'));
						}
					},
					error: function(xhr, status, error) {
						console.error('Analysis error:', error);
						alert('Analysis failed. Please try again.');
					},
					complete: function() {
						$btn.prop('disabled', false).text('Analyze');
					}
				});
			},

			showResults: function(data) {
				const $results = $('#analysis-results');
				
				// Update results display
				$results.find('.health-score-value').text(data.health_score || 0);
				$results.find('.total-posts-value').text(data.total_posts || 0);
				$results.find('.optimized-posts-value').text(data.optimized_posts || 0);
				$results.find('.niche-value').text(data.niche || 'General');

				// Update topics list
				if (data.topics && data.topics.length > 0) {
					const topicsHtml = data.topics.map(topic => 
						'<span class="topic-tag">' + this.escapeHtml(topic) + '</span>'
					).join('');
					$results.find('.topics-list').html(topicsHtml);
				}
			},

			escapeHtml: function(text) {
				const map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#039;'
				};
				return text.replace(/[&<>"']/g, function(m) { return map[m]; });
			}
		};

		// Initialize site analyzer if on the analysis page
		if ($('#start-site-analysis').length || $('#analyze-single-post').length) {
			SiteAnalyzer.init();
		}
	});

})(jQuery);
