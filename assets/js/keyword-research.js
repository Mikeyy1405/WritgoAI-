/**
 * Keyword Research JavaScript
 *
 * Handles keyword research operations and UI interactions.
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Keyword Research object
		const KeywordResearch = {
			currentKeyword: null,
			currentKeywordData: null,

			init: function() {
				this.bindEvents();
			},

			bindEvents: function() {
				$('#keyword-search-btn').on('click', this.searchKeyword.bind(this));
				$('#keyword-search-input').on('keypress', function(e) {
					if (e.which === 13) {
						e.preventDefault();
						KeywordResearch.searchKeyword();
					}
				});
				$('#save-keyword-btn').on('click', this.saveKeyword.bind(this));
				$('#load-related-btn').on('click', this.loadRelatedKeywords.bind(this));
				$('#view-serp-btn').on('click', this.viewSerpData.bind(this));
			},

			searchKeyword: function() {
				const keyword = $('#keyword-search-input').val().trim();
				
				if (!keyword) {
					alert(writgoaiKeywordResearch.i18n.noResults);
					return;
				}

				this.currentKeyword = keyword;
				
				$('#keyword-loading').show();
				$('#keyword-results').hide();
				$('#serp-results').hide();

				$.ajax({
					url: writgoaiKeywordResearch.ajaxUrl,
					method: 'POST',
					data: {
						action: 'writgoai_search_keyword',
						nonce: writgoaiKeywordResearch.nonce,
						keyword: keyword
					},
					success: function(response) {
						$('#keyword-loading').hide();
						
						if (response.success) {
							KeywordResearch.displayResults(response.data);
							KeywordResearch.currentKeywordData = response.data;
						} else {
							alert(writgoaiKeywordResearch.i18n.error + ': ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						$('#keyword-loading').hide();
						console.error('Keyword search error:', error);
						alert(writgoaiKeywordResearch.i18n.error);
					}
				});
			},

			displayResults: function(data) {
				$('#result-keyword').text(data.keyword || '');
				$('#result-volume').text(this.formatNumber(data.search_volume || 0));
				$('#result-difficulty').text(data.difficulty || 'N/A');
				$('#result-cpc').text('€' + (data.cpc || 0).toFixed(2));
				$('#result-competition').text(data.competition || 'N/A');

				// Set difficulty badge
				const difficulty = parseInt(data.difficulty);
				let badge = '';
				let badgeClass = '';
				
				if (difficulty <= 30) {
					badge = 'Easy';
					badgeClass = 'easy';
				} else if (difficulty <= 60) {
					badge = 'Medium';
					badgeClass = 'medium';
				} else {
					badge = 'Hard';
					badgeClass = 'hard';
				}
				
				$('#result-difficulty-badge').text(badge).attr('class', 'metric-badge ' + badgeClass);

				// Clear related keywords
				$('#related-keywords-list').empty();

				$('#keyword-results').show();
			},

			loadRelatedKeywords: function(e) {
				e.preventDefault();

				if (!this.currentKeyword) {
					return;
				}

				const $btn = $(e.currentTarget);
				$btn.prop('disabled', true).text(writgoaiKeywordResearch.i18n.searching);

				$.ajax({
					url: writgoaiKeywordResearch.ajaxUrl,
					method: 'POST',
					data: {
						action: 'writgoai_get_related_keywords',
						nonce: writgoaiKeywordResearch.nonce,
						keyword: this.currentKeyword
					},
					success: function(response) {
						if (response.success && response.data.length > 0) {
							KeywordResearch.displayRelatedKeywords(response.data);
						} else {
							alert(writgoaiKeywordResearch.i18n.noResults);
						}
					},
					error: function(xhr, status, error) {
						console.error('Related keywords error:', error);
						alert(writgoaiKeywordResearch.i18n.error);
					},
					complete: function() {
						$btn.prop('disabled', false).text('Load Related Keywords (5 credits)');
					}
				});
			},

			displayRelatedKeywords: function(keywords) {
				const $list = $('#related-keywords-list');
				$list.empty();

				keywords.forEach(function(keyword) {
					const html = `
						<div class="related-keyword-item">
							<span class="related-keyword-text">${KeywordResearch.escapeHtml(keyword.keyword)}</span>
							<span class="related-keyword-volume">${KeywordResearch.formatNumber(keyword.search_volume)}</span>
						</div>
					`;
					$list.append(html);
				});
			},

			viewSerpData: function(e) {
				e.preventDefault();

				if (!this.currentKeyword) {
					return;
				}

				const $btn = $(e.currentTarget);
				$btn.prop('disabled', true).text(writgoaiKeywordResearch.i18n.searching);

				$.ajax({
					url: writgoaiKeywordResearch.ajaxUrl,
					method: 'POST',
					data: {
						action: 'writgoai_get_serp_data',
						nonce: writgoaiKeywordResearch.nonce,
						keyword: this.currentKeyword
					},
					success: function(response) {
						if (response.success && response.data.length > 0) {
							KeywordResearch.displaySerpResults(response.data);
						} else {
							alert(writgoaiKeywordResearch.i18n.noResults);
						}
					},
					error: function(xhr, status, error) {
						console.error('SERP data error:', error);
						alert(writgoaiKeywordResearch.i18n.error);
					},
					complete: function() {
						$btn.prop('disabled', false).text('📊 View SERP (10 credits)');
					}
				});
			},

			displaySerpResults: function(results) {
				const $list = $('#serp-list');
				$list.empty();

				results.forEach(function(result) {
					const html = `
						<div class="serp-item">
							<span class="serp-position">${result.position}</span>
							<div style="display: inline-block; vertical-align: top; width: calc(100% - 50px);">
								<div class="serp-title">${KeywordResearch.escapeHtml(result.title)}</div>
								<div class="serp-url">${KeywordResearch.escapeHtml(result.url)}</div>
								<div class="serp-description">${KeywordResearch.escapeHtml(result.description)}</div>
							</div>
						</div>
					`;
					$list.append(html);
				});

				$('#serp-results').show();
			},

			saveKeyword: function(e) {
				e.preventDefault();

				if (!this.currentKeywordData) {
					return;
				}

				const $btn = $(e.currentTarget);
				$btn.prop('disabled', true).text('Saving...');

				$.ajax({
					url: writgoaiKeywordResearch.ajaxUrl,
					method: 'POST',
					data: {
						action: 'writgoai_save_keyword',
						nonce: writgoaiKeywordResearch.nonce,
						keyword_data: this.currentKeywordData
					},
					success: function(response) {
						if (response.success) {
							alert(writgoaiKeywordResearch.i18n.saved);
							// Reload to show in saved keywords list
							location.reload();
						} else {
							alert(writgoaiKeywordResearch.i18n.error + ': ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('Save keyword error:', error);
						alert(writgoaiKeywordResearch.i18n.error);
					},
					complete: function() {
						$btn.prop('disabled', false).text('💾 Save to Plan');
					}
				});
			},

			formatNumber: function(num) {
				return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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

		// Initialize keyword research
		if ($('.writgoai-keyword-research').length) {
			KeywordResearch.init();
		}
	});

})(jQuery);
