/**
 * Social Media Manager JavaScript
 *
 * Handles Social Media multi-channel posting functionality.
 * Nederlandse versie - Dutch interface for WritgoAI.
 *
 * @package WritgoAI
 */

(function($) {
    'use strict';

    var SocialMediaManager = {
        currentMonth: '',
        selectedBlogPost: null,
        generatedPosts: {},

        init: function() {
            this.currentMonth = $('#calendar-grid').data('month') || this.getCurrentMonth();
            this.bindEvents();
            this.loadInitialData();
        },

        /**
         * Get current month in Y-m format
         */
        getCurrentMonth: function() {
            var now = new Date();
            return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            var self = this;

            // Content source toggle
            $('input[name="content_source"]').on('change', function() {
                var source = $(this).val();
                if (source === 'blog') {
                    $('#blog-selector').show();
                    $('#manual-content').hide();
                } else {
                    $('#blog-selector').hide();
                    $('#manual-content').show();
                }
            });

            // Blog post search
            $('#blog-post-search').on('input', function() {
                var query = $(this).val();
                if (query.length >= 2) {
                    self.searchBlogPosts(query);
                } else {
                    $('#blog-posts-list').removeClass('active').empty();
                }
            });

            // Blog post selection
            $(document).on('click', '.blog-post-item', function() {
                var $item = $(this);
                $('.blog-post-item').removeClass('selected');
                $item.addClass('selected');
                
                self.selectedBlogPost = {
                    id: $item.data('id'),
                    title: $item.data('title'),
                    excerpt: $item.data('excerpt'),
                    url: $item.data('url')
                };

                $('#selected-post-id').val(self.selectedBlogPost.id);
                $('#selected-post-url').val(self.selectedBlogPost.url);
                $('#blog-post-search').val(self.selectedBlogPost.title);
                $('#blog-posts-list').removeClass('active');
            });

            // Image source toggle
            $('input[name="image_source"]').on('change', function() {
                var source = $(this).val();
                if (source === 'ai') {
                    $('#template-selector').show();
                } else {
                    $('#template-selector').hide();
                }
            });

            // Generate posts button
            $('#generate-posts-btn').on('click', function() {
                self.generatePosts();
            });

            // Copy single post
            $(document).on('click', '.copy-post-btn', function() {
                var platform = $(this).data('platform');
                self.copyPostToClipboard(platform);
            });

            // Schedule single post
            $(document).on('click', '.schedule-post-btn', function() {
                var platform = $(this).data('platform');
                self.openScheduleModal(platform);
            });

            // Save single post
            $(document).on('click', '.save-post-btn', function() {
                var platform = $(this).data('platform');
                self.savePost(platform);
            });

            // Copy all posts
            $('#copy-all-btn').on('click', function() {
                self.copyAllPosts();
            });

            // Schedule all posts
            $('#schedule-all-btn').on('click', function() {
                self.scheduleAllPosts();
            });

            // Update character count on textarea change
            $(document).on('input', '.post-textarea', function() {
                var platform = $(this).data('platform');
                self.updateCharCount(platform, $(this).val().length);
            });

            // Calendar navigation
            $('#prev-month').on('click', function() {
                self.navigateMonth(-1);
            });

            $('#next-month').on('click', function() {
                self.navigateMonth(1);
            });

            // Delete scheduled post
            $(document).on('click', '.delete-scheduled-btn', function() {
                var postId = $(this).data('post-id');
                self.deleteScheduledPost(postId);
            });

            // Hashtag research
            $('#suggest-hashtags-btn').on('click', function() {
                self.suggestHashtags();
            });

            // Toggle hashtag selection
            $(document).on('click', '.hashtag-tag', function() {
                $(this).toggleClass('selected');
            });

            // Save hashtags as set
            $('#save-as-set-btn').on('click', function() {
                self.saveHashtagsAsSet();
            });

            // Create new hashtag set
            $('#create-set-btn').on('click', function() {
                self.createHashtagSet();
            });

            // Use hashtag set
            $(document).on('click', '.use-set-btn', function() {
                var setId = $(this).data('set-id');
                self.useHashtagSet(setId);
            });

            // Analytics period change
            $('#analytics-period').on('change', function() {
                self.loadAnalytics($(this).val());
            });
        },

        /**
         * Load initial data
         */
        loadInitialData: function() {
            // Load scheduled posts if on calendar tab
            if ($('.calendar-tab').length) {
                this.renderCalendar();
                this.loadScheduledPosts();
            }

            // Load hashtag sets if on hashtags tab
            if ($('.hashtags-tab').length) {
                this.loadHashtagSets();
            }

            // Load analytics if on analytics tab
            if ($('.analytics-tab').length) {
                this.loadAnalytics($('#analytics-period').val() || 30);
            }
        },

        /**
         * Search blog posts
         */
        searchBlogPosts: function(query) {
            var self = this;
            var $list = $('#blog-posts-list');

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_get_blog_posts',
                    nonce: writgoaiSocialMedia.nonce,
                    search: query
                },
                success: function(response) {
                    if (response.success && response.data.posts) {
                        var html = '';
                        response.data.posts.forEach(function(post) {
                            html += '<div class="blog-post-item" ' +
                                'data-id="' + post.id + '" ' +
                                'data-title="' + self.escapeHtml(post.title) + '" ' +
                                'data-excerpt="' + self.escapeHtml(post.excerpt) + '" ' +
                                'data-url="' + self.escapeHtml(post.url) + '">' +
                                '<div class="post-title">' + self.escapeHtml(post.title) + '</div>' +
                                '<div class="post-date">' + post.date + '</div>' +
                                '</div>';
                        });
                        $list.html(html).addClass('active');
                    }
                }
            });
        },

        /**
         * Generate social media posts
         */
        generatePosts: function() {
            var self = this;
            var $btn = $('#generate-posts-btn');
            var $status = $('.generate-status');
            var source = $('input[name="content_source"]:checked').val();

            var title = '';
            var content = '';
            var linkUrl = '';

            if (source === 'blog') {
                if (!self.selectedBlogPost) {
                    self.showNotification('Selecteer eerst een blog post', 'error');
                    return;
                }
                title = self.selectedBlogPost.title;
                content = self.selectedBlogPost.excerpt;
                linkUrl = self.selectedBlogPost.url;
            } else {
                title = $('#manual-title').val();
                content = $('#manual-text').val();
                linkUrl = $('#manual-link').val();
            }

            if (!title || !content) {
                self.showNotification(writgoaiSocialMedia.i18n.enterContent, 'error');
                return;
            }

            var platforms = [];
            $('input[name="platforms[]"]:checked').each(function() {
                platforms.push($(this).val());
            });

            if (platforms.length === 0) {
                self.showNotification(writgoaiSocialMedia.i18n.selectPlatform, 'error');
                return;
            }

            var tone = $('#content-tone').val();
            var useHashtags = $('#use-hashtags').is(':checked');
            var useEmojis = $('#use-emojis').is(':checked');

            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> ' + writgoaiSocialMedia.i18n.generating);
            $status.text('').removeClass('success error');

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_generate_social_posts',
                    nonce: writgoaiSocialMedia.nonce,
                    title: title,
                    content: content,
                    platforms: platforms,
                    tone: tone,
                    use_hashtags: useHashtags,
                    use_emojis: useEmojis,
                    link_url: linkUrl
                },
                success: function(response) {
                    if (response.success) {
                        self.generatedPosts = response.data.posts;
                        self.renderGeneratedPosts(response.data.posts);
                        $('#generated-posts').show();
                        $status.text(writgoaiSocialMedia.i18n.success).addClass('success');
                        self.showNotification(response.data.message, 'success');
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
                    $btn.prop('disabled', false).html('🤖 Genereer Posts + Afbeeldingen');
                }
            });
        },

        /**
         * Render generated posts
         */
        renderGeneratedPosts: function(posts) {
            var self = this;
            var $list = $('#posts-preview-list');
            var platforms = writgoaiSocialMedia.platforms;
            var html = '';

            Object.keys(posts).forEach(function(platform) {
                var post = posts[platform];
                var platformInfo = platforms[platform] || { icon: '📱', name: platform };
                var charClass = post.char_count > post.max_chars ? 'over-limit' : '';

                html += '<div class="post-preview-item" data-platform="' + platform + '">';
                html += '<div class="post-preview-header">';
                html += '<span class="platform-icon">' + platformInfo.icon + '</span>';
                html += '<span class="platform-name">' + platformInfo.name + '</span>';
                html += '<span class="char-count ' + charClass + '">' + post.char_count + '/' + post.max_chars + ' ' + writgoaiSocialMedia.i18n.characters + '</span>';
                html += '</div>';
                html += '<div class="post-preview-content">';
                html += '<textarea class="post-textarea" data-platform="' + platform + '" data-max="' + post.max_chars + '">' + self.escapeHtml(post.content) + '</textarea>';
                html += '</div>';
                html += '<div class="post-preview-actions">';
                html += '<button type="button" class="button copy-post-btn" data-platform="' + platform + '">📋 Kopiëren</button>';
                html += '<button type="button" class="button schedule-post-btn" data-platform="' + platform + '">📅 Inplannen</button>';
                html += '<button type="button" class="button save-post-btn" data-platform="' + platform + '">💾 Opslaan</button>';
                html += '</div>';
                html += '</div>';
            });

            $list.html(html);
        },

        /**
         * Update character count display
         */
        updateCharCount: function(platform, count) {
            var $item = $('.post-preview-item[data-platform="' + platform + '"]');
            var $textarea = $item.find('.post-textarea');
            var max = parseInt($textarea.data('max'), 10);
            var $counter = $item.find('.char-count');

            $counter.text(count + '/' + max + ' ' + writgoaiSocialMedia.i18n.characters);
            
            if (count > max) {
                $counter.addClass('over-limit');
            } else {
                $counter.removeClass('over-limit');
            }

            // Update stored content
            this.generatedPosts[platform].content = $textarea.val();
            this.generatedPosts[platform].char_count = count;
        },

        /**
         * Copy post to clipboard
         */
        copyPostToClipboard: function(platform) {
            var self = this;
            var content = $('.post-preview-item[data-platform="' + platform + '"] .post-textarea').val();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(content).then(function() {
                    self.showNotification(writgoaiSocialMedia.i18n.copied, 'success');
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<textarea>').val(content).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                self.showNotification(writgoaiSocialMedia.i18n.copied, 'success');
            }
        },

        /**
         * Copy all posts
         */
        copyAllPosts: function() {
            var self = this;
            var allContent = '';
            var platforms = writgoaiSocialMedia.platforms;

            Object.keys(this.generatedPosts).forEach(function(platform) {
                var platformInfo = platforms[platform] || { name: platform };
                allContent += '--- ' + platformInfo.name + ' ---\n\n';
                allContent += self.generatedPosts[platform].content;
                allContent += '\n\n';
            });

            if (navigator.clipboard) {
                navigator.clipboard.writeText(allContent).then(function() {
                    self.showNotification(writgoaiSocialMedia.i18n.copied, 'success');
                });
            }
        },

        /**
         * Save post as draft
         */
        savePost: function(platform) {
            var self = this;
            var content = $('.post-preview-item[data-platform="' + platform + '"] .post-textarea').val();
            var postId = $('#selected-post-id').val() || 0;

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_save_social_post',
                    nonce: writgoaiSocialMedia.nonce,
                    platform: platform,
                    content: content,
                    post_id: postId,
                    hashtags: self.generatedPosts[platform].hashtags || []
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(writgoaiSocialMedia.i18n.saved, 'success');
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
         * Schedule a post
         */
        openScheduleModal: function(platform) {
            var self = this;
            var content = $('.post-preview-item[data-platform="' + platform + '"] .post-textarea').val();
            
            // Simple date/time prompt - in production, use a proper modal
            var dateTime = prompt('Voer datum en tijd in (YYYY-MM-DD HH:MM):', this.getDefaultScheduleTime());
            
            if (dateTime) {
                this.schedulePost(platform, content, dateTime);
            }
        },

        /**
         * Get default schedule time (next best time)
         */
        getDefaultScheduleTime: function() {
            var now = new Date();
            now.setHours(now.getHours() + 2);
            return now.toISOString().slice(0, 16).replace('T', ' ');
        },

        /**
         * Schedule a single post
         */
        schedulePost: function(platform, content, scheduledTime) {
            var self = this;
            var postId = $('#selected-post-id').val() || 0;

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_schedule_social_post',
                    nonce: writgoaiSocialMedia.nonce,
                    platform: platform,
                    content: content,
                    post_id: postId,
                    scheduled_time: scheduledTime,
                    hashtags: self.generatedPosts[platform] ? self.generatedPosts[platform].hashtags : []
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(writgoaiSocialMedia.i18n.scheduled, 'success');
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
         * Schedule all posts
         */
        scheduleAllPosts: function() {
            var self = this;
            var dateTime = prompt('Voer datum en tijd in voor alle posts (YYYY-MM-DD HH:MM):', this.getDefaultScheduleTime());
            
            if (!dateTime) return;

            Object.keys(this.generatedPosts).forEach(function(platform) {
                var content = $('.post-preview-item[data-platform="' + platform + '"] .post-textarea').val();
                self.schedulePost(platform, content, dateTime);
            });
        },

        /**
         * Render calendar
         */
        renderCalendar: function() {
            var $grid = $('#calendar-grid');
            var parts = this.currentMonth.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;

            var firstDay = new Date(year, month, 1);
            var lastDay = new Date(year, month + 1, 0);
            var startDay = (firstDay.getDay() + 6) % 7; // Monday = 0
            var daysInMonth = lastDay.getDate();

            var today = new Date();
            var todayStr = today.toISOString().slice(0, 10);

            var html = '';

            // Empty cells before first day
            for (var i = 0; i < startDay; i++) {
                html += '<div class="calendar-day other-month"></div>';
            }

            // Days of the month
            for (var day = 1; day <= daysInMonth; day++) {
                var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var isToday = dateStr === todayStr;
                
                html += '<div class="calendar-day' + (isToday ? ' today' : '') + '" data-date="' + dateStr + '">';
                html += '<div class="day-number">' + day + '</div>';
                html += '<div class="day-posts" data-date="' + dateStr + '"></div>';
                html += '</div>';
            }

            $grid.html(html);
        },

        /**
         * Navigate calendar month
         */
        navigateMonth: function(direction) {
            var parts = this.currentMonth.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;

            var newDate = new Date(year, month + direction, 1);
            this.currentMonth = newDate.getFullYear() + '-' + String(newDate.getMonth() + 1).padStart(2, '0');

            $('#current-month-label').text(this.formatDutchMonth(this.currentMonth));
            $('#calendar-grid').data('month', this.currentMonth);
            
            this.renderCalendar();
            this.loadScheduledPosts();
        },

        /**
         * Format Dutch month name
         */
        formatDutchMonth: function(month) {
            var dutchMonths = {
                '01': 'Januari', '02': 'Februari', '03': 'Maart', '04': 'April',
                '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Augustus',
                '09': 'September', '10': 'Oktober', '11': 'November', '12': 'December'
            };

            var parts = month.split('-');
            return dutchMonths[parts[1]] + ' ' + parts[0];
        },

        /**
         * Load scheduled posts
         */
        loadScheduledPosts: function() {
            var self = this;
            var $list = $('#scheduled-posts-list');
            var platforms = writgoaiSocialMedia.platforms;

            var parts = this.currentMonth.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10);
            var from = year + '-' + String(month).padStart(2, '0') + '-01 00:00:00';
            var lastDay = new Date(year, month, 0).getDate();
            var to = year + '-' + String(month).padStart(2, '0') + '-' + lastDay + ' 23:59:59';

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_get_scheduled_posts',
                    nonce: writgoaiSocialMedia.nonce,
                    from: from,
                    to: to
                },
                success: function(response) {
                    if (response.success && response.data.posts) {
                        var posts = response.data.posts;
                        
                        if (posts.length === 0) {
                            $list.html('<p class="no-posts">' + writgoaiSocialMedia.i18n.noPostsScheduled + '</p>');
                            return;
                        }

                        var html = '';
                        var calendarPosts = {};

                        posts.forEach(function(post) {
                            var platformInfo = platforms[post.platform] || { icon: '📱', name: post.platform };
                            var scheduleDate = new Date(post.scheduled_time);
                            var dateStr = post.scheduled_time.slice(0, 10);
                            var timeStr = post.scheduled_time.slice(11, 16);

                            // Add to calendar
                            if (!calendarPosts[dateStr]) {
                                calendarPosts[dateStr] = [];
                            }
                            calendarPosts[dateStr].push({
                                platform: post.platform,
                                icon: platformInfo.icon
                            });

                            // Add to list
                            html += '<div class="scheduled-post-item" data-post-id="' + post.id + '">';
                            html += '<span class="post-platform">' + platformInfo.icon + '</span>';
                            html += '<div class="post-info">';
                            html += '<div class="post-time">' + dateStr + ' om ' + timeStr + '</div>';
                            html += '<div class="post-preview">' + self.escapeHtml(post.content.substring(0, 80)) + '...</div>';
                            html += '</div>';
                            html += '<div class="post-actions">';
                            html += '<button type="button" class="button button-small delete-scheduled-btn" data-post-id="' + post.id + '">🗑️</button>';
                            html += '</div>';
                            html += '</div>';
                        });

                        $list.html(html);

                        // Update calendar with posts
                        Object.keys(calendarPosts).forEach(function(date) {
                            var $dayPosts = $('.day-posts[data-date="' + date + '"]');
                            var iconsHtml = '';
                            calendarPosts[date].forEach(function(p) {
                                iconsHtml += '<span class="day-post" title="' + p.platform + '">' + p.icon + '</span>';
                            });
                            $dayPosts.html(iconsHtml);
                        });
                    }
                }
            });
        },

        /**
         * Delete scheduled post
         */
        deleteScheduledPost: function(postId) {
            var self = this;

            if (!confirm(writgoaiSocialMedia.i18n.confirmDelete)) {
                return;
            }

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_delete_scheduled_post',
                    nonce: writgoaiSocialMedia.nonce,
                    social_post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(writgoaiSocialMedia.i18n.deleted, 'success');
                        self.loadScheduledPosts();
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
         * Suggest hashtags
         */
        suggestHashtags: function() {
            var self = this;
            var topic = $('#hashtag-topic').val();
            var platform = $('#hashtag-platform').val();
            var $btn = $('#suggest-hashtags-btn');

            if (!topic) {
                self.showNotification('Voer een onderwerp in', 'error');
                return;
            }

            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Zoeken...');

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_suggest_hashtags',
                    nonce: writgoaiSocialMedia.nonce,
                    content: topic,
                    platform: platform,
                    count: 15
                },
                success: function(response) {
                    if (response.success && response.data.hashtags) {
                        var html = '';
                        response.data.hashtags.forEach(function(tag) {
                            html += '<span class="hashtag-tag">#' + self.escapeHtml(tag) + '</span>';
                        });
                        $('#suggested-hashtags .hashtags-list').html(html);
                        $('#suggested-hashtags').show();
                        self.showNotification(response.data.hashtags.length + ' ' + writgoaiSocialMedia.i18n.hashtagsSuggested, 'success');
                    } else {
                        self.showNotification(response.data.message || 'Geen hashtags gevonden', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Verbindingsfout', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('🔍 Zoek Hashtags');
                }
            });
        },

        /**
         * Save hashtags as set
         */
        saveHashtagsAsSet: function() {
            var self = this;
            var selectedHashtags = [];

            $('#suggested-hashtags .hashtag-tag.selected').each(function() {
                selectedHashtags.push($(this).text().replace('#', ''));
            });

            if (selectedHashtags.length === 0) {
                self.showNotification('Selecteer eerst hashtags', 'error');
                return;
            }

            var setName = prompt('Voer een naam in voor deze hashtag set:');
            if (!setName) return;

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_save_hashtag_set',
                    nonce: writgoaiSocialMedia.nonce,
                    name: setName,
                    hashtags: selectedHashtags,
                    category: $('#hashtag-topic').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(writgoaiSocialMedia.i18n.saved, 'success');
                        self.loadHashtagSets();
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
         * Create new hashtag set
         */
        createHashtagSet: function() {
            var self = this;
            var name = $('#new-set-name').val();
            var category = $('#new-set-category').val();
            var hashtagsStr = $('#new-set-hashtags').val();

            if (!name || !hashtagsStr) {
                self.showNotification('Naam en hashtags zijn verplicht', 'error');
                return;
            }

            var hashtags = hashtagsStr.split(',').map(function(tag) {
                return tag.trim().replace('#', '');
            }).filter(function(tag) {
                return tag.length > 0;
            });

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_save_hashtag_set',
                    nonce: writgoaiSocialMedia.nonce,
                    name: name,
                    hashtags: hashtags,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(writgoaiSocialMedia.i18n.saved, 'success');
                        $('#new-set-name').val('');
                        $('#new-set-category').val('');
                        $('#new-set-hashtags').val('');
                        self.loadHashtagSets();
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
         * Load hashtag sets
         */
        loadHashtagSets: function() {
            var self = this;
            var $list = $('#hashtag-sets-list');

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_get_hashtag_sets',
                    nonce: writgoaiSocialMedia.nonce
                },
                success: function(response) {
                    if (response.success && response.data.sets) {
                        var sets = response.data.sets;

                        if (sets.length === 0) {
                            $list.html('<p class="no-sets">Nog geen hashtag sets opgeslagen</p>');
                            return;
                        }

                        var html = '';
                        sets.forEach(function(set) {
                            var hashtags = set.hashtags || [];
                            html += '<div class="hashtag-set-item" data-set-id="' + set.id + '">';
                            html += '<div class="set-header">';
                            html += '<span class="set-name">' + self.escapeHtml(set.name) + '</span>';
                            html += '<span class="set-count">' + hashtags.length + ' hashtags</span>';
                            html += '</div>';
                            html += '<div class="set-hashtags">';
                            hashtags.slice(0, 5).forEach(function(tag) {
                                html += '<span class="hashtag-tag">#' + self.escapeHtml(tag) + '</span>';
                            });
                            if (hashtags.length > 5) {
                                html += '<span class="hashtag-tag">+' + (hashtags.length - 5) + ' meer</span>';
                            }
                            html += '</div>';
                            html += '<div class="set-actions">';
                            html += '<button type="button" class="button button-small use-set-btn" data-set-id="' + set.id + '">Gebruik</button>';
                            html += '</div>';
                            html += '</div>';
                        });

                        $list.html(html);
                    }
                }
            });
        },

        /**
         * Use hashtag set (copy to clipboard)
         */
        useHashtagSet: function(setId) {
            var self = this;
            var $item = $('.hashtag-set-item[data-set-id="' + setId + '"]');
            var hashtags = [];

            $item.find('.hashtag-tag').each(function() {
                var text = $(this).text();
                if (!text.includes('meer')) {
                    hashtags.push(text);
                }
            });

            var hashtagsStr = hashtags.join(' ');

            if (navigator.clipboard) {
                navigator.clipboard.writeText(hashtagsStr).then(function() {
                    self.showNotification(writgoaiSocialMedia.i18n.copied, 'success');
                });
            }
        },

        /**
         * Load analytics
         */
        loadAnalytics: function(days) {
            var self = this;
            var $totals = $('#analytics-totals');
            var $byPlatform = $('#analytics-by-platform');
            var platforms = writgoaiSocialMedia.platforms;

            $.ajax({
                url: writgoaiSocialMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'writgoai_get_social_analytics',
                    nonce: writgoaiSocialMedia.nonce,
                    days: days
                },
                success: function(response) {
                    if (response.success && response.data.analytics) {
                        var analytics = response.data.analytics;

                        // Render totals
                        var totalsHtml = '';
                        totalsHtml += '<div class="stat-item">';
                        totalsHtml += '<span class="stat-number">' + analytics.total_posts + '</span>';
                        totalsHtml += '<span class="stat-label">Posts totaal</span>';
                        totalsHtml += '</div>';
                        totalsHtml += '<div class="stat-item">';
                        totalsHtml += '<span class="stat-number">' + analytics.published_posts + '</span>';
                        totalsHtml += '<span class="stat-label">Gepubliceerd</span>';
                        totalsHtml += '</div>';
                        totalsHtml += '<div class="stat-item">';
                        totalsHtml += '<span class="stat-number">' + analytics.scheduled_posts + '</span>';
                        totalsHtml += '<span class="stat-label">Ingepland</span>';
                        totalsHtml += '</div>';
                        totalsHtml += '<div class="stat-item">';
                        totalsHtml += '<span class="stat-number">' + analytics.draft_posts + '</span>';
                        totalsHtml += '<span class="stat-label">Concepten</span>';
                        totalsHtml += '</div>';

                        $totals.html(totalsHtml);

                        // Render by platform
                        var platformHtml = '';
                        Object.keys(analytics.by_platform).forEach(function(key) {
                            var platformData = analytics.by_platform[key];
                            var total = platformData.published + platformData.scheduled + platformData.draft;
                            
                            if (total > 0 || platforms[key]) {
                                var platformInfo = platforms[key] || { icon: '📱', name: key };
                                platformHtml += '<div class="platform-stat-card">';
                                platformHtml += '<span class="platform-icon">' + platformInfo.icon + '</span>';
                                platformHtml += '<div class="platform-info">';
                                platformHtml += '<div class="platform-name">' + platformInfo.name + '</div>';
                                platformHtml += '<div class="platform-stats">';
                                platformHtml += '<span>✅ ' + platformData.published + ' gepubliceerd</span>';
                                platformHtml += '<span>📅 ' + platformData.scheduled + ' ingepland</span>';
                                platformHtml += '<span>📝 ' + platformData.draft + ' concepten</span>';
                                platformHtml += '</div>';
                                platformHtml += '</div>';
                                platformHtml += '</div>';
                            }
                        });

                        if (!platformHtml) {
                            platformHtml = '<p class="no-data">Nog geen data beschikbaar</p>';
                        }

                        $byPlatform.html(platformHtml);
                    }
                }
            });
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
         * Show notification
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="social-notification ' + type + '">' + this.escapeHtml(message) + '</div>');
            $('body').append($notification);

            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        SocialMediaManager.init();
    });

})(jQuery);
