/**
 * AI API Client
 *
 * Handles communication with the WritgoAI backend API.
 *
 * @package WritgoCMS
 */

(function(window, $) {
    'use strict';

    var settings = window.writgoaiAiToolbar || {};

    /**
     * WritgoAI API Client
     */
    var WritgoAIClient = {
        /**
         * AJAX URL
         */
        ajaxUrl: settings.ajaxUrl || '/wp-admin/admin-ajax.php',

        /**
         * Nonce for AJAX requests
         */
        nonce: settings.nonce || '',

        /**
         * Current post ID
         */
        postId: settings.postId || 0,

        /**
         * License status (includes WordPress admin users)
         */
        isLicensed: settings.isLicensed !== false,

        /**
         * WordPress admin status
         */
        isAdmin: settings.isAdmin || false,

        /**
         * Usage tracking
         */
        usage: settings.usage || {
            requests_used: 0,
            requests_remaining: 1000,
            daily_limit: 1000
        },

        /**
         * Active requests tracking
         */
        activeRequests: {},

        /**
         * Make an AJAX request
         *
         * @param {string} action - AJAX action name
         * @param {object} data - Request data
         * @param {object} options - Additional options
         * @returns {Promise}
         */
        request: function(action, data, options) {
            var self = this;
            options = options || {};

            return new Promise(function(resolve, reject) {
                // Check license status (WordPress admins always have access)
                if (!self.isLicensed && !self.isAdmin && !options.skipLicenseCheck) {
                    reject({
                        success: false,
                        message: settings.i18n ? settings.i18n.licenseInvalid : 'License not active'
                    });
                    return;
                }

                // Generate request ID
                var requestId = 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                var requestData = $.extend({}, data, {
                    action: action,
                    nonce: self.nonce,
                    post_id: self.postId
                });

                // Track active request
                self.activeRequests[requestId] = true;

                // Trigger start event
                $(document).trigger('writgoai:request:start', { requestId: requestId, action: action });

                $.ajax({
                    url: self.ajaxUrl,
                    type: 'POST',
                    data: requestData,
                    timeout: options.timeout || 60000,
                    success: function(response) {
                        delete self.activeRequests[requestId];

                        if (response.success) {
                            // Update usage if returned
                            if (response.data && response.data.usage) {
                                self.updateUsage(response.data.usage);
                            }

                            resolve(response.data);
                            $(document).trigger('writgoai:request:success', { requestId: requestId, action: action, data: response.data });
                        } else {
                            var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error';
                            reject({ success: false, message: errorMessage });
                            $(document).trigger('writgoai:request:error', { requestId: requestId, action: action, error: errorMessage });
                        }
                    },
                    error: function(xhr, status, error) {
                        delete self.activeRequests[requestId];

                        var errorMessage = 'Connection error';
                        if (status === 'timeout') {
                            errorMessage = 'Request timeout';
                        } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        }

                        reject({ success: false, message: errorMessage });
                        $(document).trigger('writgoai:request:error', { requestId: requestId, action: action, error: errorMessage });
                    }
                });
            });
        },

        /**
         * Rewrite text
         *
         * @param {string} text - Text to rewrite
         * @returns {Promise}
         */
        rewriteText: function(text) {
            return this.request('writgoai_ai_rewrite', { text: text });
        },

        /**
         * Improve text (grammar, style)
         *
         * @param {string} text - Text to improve
         * @returns {Promise}
         */
        improveText: function(text) {
            return this.request('writgoai_ai_improve', { text: text });
        },

        /**
         * Expand text (make longer)
         *
         * @param {string} text - Text to expand
         * @returns {Promise}
         */
        expandText: function(text) {
            return this.request('writgoai_ai_expand', { text: text }, { timeout: 90000 });
        },

        /**
         * Shorten text
         *
         * @param {string} text - Text to shorten
         * @returns {Promise}
         */
        shortenText: function(text) {
            return this.request('writgoai_ai_shorten', { text: text });
        },

        /**
         * Add internal links to text
         *
         * @param {string} text - Text to add links to
         * @returns {Promise}
         */
        addLinks: function(text) {
            return this.request('writgoai_ai_add_links', { text: text }, { timeout: 90000 });
        },

        /**
         * Rewrite entire block
         *
         * @param {string} content - Block content
         * @returns {Promise}
         */
        rewriteBlock: function(content) {
            return this.request('writgoai_ai_rewrite_block', { content: content }, { timeout: 90000 });
        },

        /**
         * Rewrite entire article
         *
         * @param {string} content - Article content
         * @param {string} title - Article title
         * @returns {Promise}
         */
        rewriteArticle: function(content, title) {
            return this.request('writgoai_ai_rewrite_article', { content: content, title: title }, { timeout: 180000 });
        },

        /**
         * SEO optimize article
         *
         * @param {string} content - Article content
         * @param {string} title - Article title
         * @param {string} keyword - Focus keyword (optional)
         * @returns {Promise}
         */
        seoOptimize: function(content, title, keyword) {
            return this.request('writgoai_ai_seo_optimize', { content: content, title: title, keyword: keyword || '' }, { timeout: 90000 });
        },

        /**
         * Generate meta description
         *
         * @param {string} content - Article content
         * @param {string} title - Article title
         * @returns {Promise}
         */
        generateMeta: function(content, title) {
            return this.request('writgoai_ai_generate_meta', { content: content, title: title });
        },

        /**
         * Generate image
         *
         * @param {string} prompt - Image generation prompt
         * @returns {Promise}
         */
        generateImage: function(prompt) {
            return this.request('writgoai_generate_image', { prompt: prompt }, { timeout: 120000 });
        },

        /**
         * Generate text
         *
         * @param {string} prompt - Text generation prompt
         * @returns {Promise}
         */
        generateText: function(prompt) {
            return this.request('writgoai_generate_text', { prompt: prompt });
        },

        /**
         * Get usage stats
         *
         * @returns {Promise}
         */
        getUsage: function() {
            return this.request('writgoai_ai_get_usage', {}, { skipLicenseCheck: true });
        },

        /**
         * Update usage tracking
         *
         * @param {object} usage - Usage data
         */
        updateUsage: function(usage) {
            if (usage) {
                this.usage = $.extend({}, this.usage, usage);
                $(document).trigger('writgoai:usage:updated', this.usage);
            }
        },

        /**
         * Check if there are active requests
         *
         * @returns {boolean}
         */
        hasActiveRequests: function() {
            return Object.keys(this.activeRequests).length > 0;
        },

        /**
         * Get remaining requests
         *
         * @returns {number}
         */
        getRemainingRequests: function() {
            return Math.max(0, this.usage.daily_limit - this.usage.requests_used);
        }
    };

    // Export to window
    window.WritgoAIClient = WritgoAIClient;

})(window, jQuery);
