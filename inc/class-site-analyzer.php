<?php
/**
 * Site Analyzer Class
 *
 * Analyzes WordPress site content, calculates SEO scores, and detects niche/topics.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Site_Analyzer
 */
class WritgoCMS_Site_Analyzer {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Site_Analyzer
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Site_Analyzer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_writgocms_analyze_site', array( $this, 'ajax_analyze_site' ) );
		add_action( 'wp_ajax_writgocms_analyze_post', array( $this, 'ajax_analyze_post' ) );
		add_action( 'wp_ajax_writgocms_get_analysis_status', array( $this, 'ajax_get_analysis_status' ) );
	}

	/**
	 * Analyze entire site
	 *
	 * @return array Analysis results.
	 */
	public function analyze_site() {
		global $wpdb;

		$site_url    = get_site_url();
		$posts       = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		$total_posts = count( $posts );

		// Analyze each post.
		$optimized_posts = 0;
		$topics          = array();

		foreach ( $posts as $post ) {
			$score = $this->analyze_post( $post->ID );
			if ( $score['seo_score'] >= 70 ) {
				++$optimized_posts;
			}

			// Extract topics from categories and tags.
			$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
			$topics     = array_merge( $topics, $categories, $tags );
		}

		// Get unique topics.
		$topics = array_unique( $topics );
		$topics = array_values( $topics );

		// Detect niche.
		$niche = $this->detect_niche( $posts, $topics );

		// Calculate health score.
		$health_score = $this->calculate_health_score(
			array(
				'total_posts'     => $total_posts,
				'optimized_posts' => $optimized_posts,
			)
		);

		// Store results.
		$table_name = $wpdb->prefix . 'writgo_site_analysis';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'site_url'        => $site_url,
				'total_posts'     => $total_posts,
				'optimized_posts' => $optimized_posts,
				'health_score'    => $health_score,
				'niche'           => $niche,
				'topics'          => wp_json_encode( $topics ),
				'analyzed_at'     => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		return array(
			'success'         => true,
			'total_posts'     => $total_posts,
			'optimized_posts' => $optimized_posts,
			'health_score'    => $health_score,
			'niche'           => $niche,
			'topics'          => $topics,
		);
	}

	/**
	 * Analyze single post
	 *
	 * @param int $post_id Post ID.
	 * @return array Post analysis scores.
	 */
	public function analyze_post( $post_id ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array( 'error' => 'Post not found' );
		}

		$content = $post->post_content . ' ' . $post->post_title;
		$content = wp_strip_all_tags( $content );

		// Calculate word count.
		$word_count = str_word_count( $content );

		// Count links.
		preg_match_all( '/<a\s+href="([^"]+)"/', $post->post_content, $links );
		$internal_links = 0;
		$external_links = 0;
		$site_url       = get_site_url();

		foreach ( $links[1] as $link ) {
			if ( strpos( $link, $site_url ) !== false || strpos( $link, '/' ) === 0 ) {
				++$internal_links;
			} else {
				++$external_links;
			}
		}

		// Count images.
		preg_match_all( '/<img/', $post->post_content, $images );
		$images_count = count( $images[0] );

		// Check meta description.
		$meta_description     = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		$has_meta_description = ! empty( $meta_description );

		// Calculate keyword density (simplified).
		$keyword_density = $this->calculate_keyword_density( $content );

		// Calculate readability score.
		$readability_score = $this->calculate_readability_score( $content, $word_count );

		// Calculate SEO score.
		$seo_score = $this->calculate_seo_score(
			array(
				'word_count'           => $word_count,
				'internal_links'       => $internal_links,
				'external_links'       => $external_links,
				'images_count'         => $images_count,
				'has_meta_description' => $has_meta_description,
				'readability_score'    => $readability_score,
			)
		);

		// Store results.
		$table_name = $wpdb->prefix . 'writgo_post_scores';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE post_id = %d",
				$post_id
			)
		);

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				array(
					'seo_score'            => $seo_score,
					'readability_score'    => $readability_score,
					'keyword_density'      => $keyword_density,
					'word_count'           => $word_count,
					'internal_links'       => $internal_links,
					'external_links'       => $external_links,
					'images_count'         => $images_count,
					'has_meta_description' => $has_meta_description ? 1 : 0,
					'analyzed_at'          => current_time( 'mysql' ),
				),
				array( 'post_id' => $post_id ),
				array( '%d', '%d', '%f', '%d', '%d', '%d', '%d', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_name,
				array(
					'post_id'              => $post_id,
					'seo_score'            => $seo_score,
					'readability_score'    => $readability_score,
					'keyword_density'      => $keyword_density,
					'word_count'           => $word_count,
					'internal_links'       => $internal_links,
					'external_links'       => $external_links,
					'images_count'         => $images_count,
					'has_meta_description' => $has_meta_description ? 1 : 0,
					'analyzed_at'          => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%f', '%d', '%d', '%d', '%d', '%d', '%s' )
			);
		}

		return array(
			'seo_score'            => $seo_score,
			'readability_score'    => $readability_score,
			'keyword_density'      => $keyword_density,
			'word_count'           => $word_count,
			'internal_links'       => $internal_links,
			'external_links'       => $external_links,
			'images_count'         => $images_count,
			'has_meta_description' => $has_meta_description,
		);
	}

	/**
	 * Calculate health score
	 *
	 * @param array $data Analysis data.
	 * @return int Health score (0-100).
	 */
	public function calculate_health_score( $data ) {
		$score = 0;

		// Content coverage (40 points).
		if ( $data['total_posts'] >= 50 ) {
			$score += 40;
		} elseif ( $data['total_posts'] >= 20 ) {
			$score += 30;
		} elseif ( $data['total_posts'] >= 10 ) {
			$score += 20;
		} elseif ( $data['total_posts'] > 0 ) {
			$score += 10;
		}

		// Optimized posts percentage (60 points).
		if ( $data['total_posts'] > 0 ) {
			$optimization_rate = ( $data['optimized_posts'] / $data['total_posts'] ) * 100;
			$score            += (int) ( ( $optimization_rate / 100 ) * 60 );
		}

		return min( 100, $score );
	}

	/**
	 * Detect site niche
	 *
	 * @param array $posts Array of WP_Post objects.
	 * @param array $topics Array of topics.
	 * @return string Detected niche.
	 */
	public function detect_niche( $posts, $topics ) {
		if ( empty( $topics ) ) {
			return 'General';
		}

		// Use the most common topic as niche.
		$topic_counts = array_count_values( $topics );
		arsort( $topic_counts );
		$top_topic = array_key_first( $topic_counts );

		return $top_topic ? $top_topic : 'General';
	}

	/**
	 * Extract topics from posts
	 *
	 * @return array Array of topics.
	 */
	public function extract_topics() {
		$posts  = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		$topics = array();

		foreach ( $posts as $post ) {
			$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			$tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
			$topics     = array_merge( $topics, $categories, $tags );
		}

		$topics = array_unique( $topics );
		return array_values( $topics );
	}

	/**
	 * Find internal linking opportunities
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of suggested posts to link to.
	 */
	public function find_internal_link_opportunities( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$categories = wp_get_post_categories( $post_id );
		$tags       = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

		// Find related posts.
		$related_posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'post__not_in'   => array( $post_id ),
				'category__in'   => $categories,
				'tag__in'        => $tags,
			)
		);

		$opportunities = array();
		foreach ( $related_posts as $related ) {
			$opportunities[] = array(
				'id'    => $related->ID,
				'title' => $related->post_title,
				'url'   => get_permalink( $related->ID ),
			);
		}

		return $opportunities;
	}

	/**
	 * Calculate keyword density
	 *
	 * @param string $content Post content.
	 * @return float Keyword density.
	 */
	private function calculate_keyword_density( $content ) {
		$words = str_word_count( strtolower( $content ), 1 );
		if ( empty( $words ) ) {
			return 0;
		}

		$word_counts = array_count_values( $words );
		arsort( $word_counts );

		// Get density of most common word.
		$max_count    = reset( $word_counts );
		$total_words  = count( $words );
		$density      = ( $max_count / $total_words ) * 100;

		return round( $density, 2 );
	}

	/**
	 * Calculate readability score
	 *
	 * @param string $content Post content.
	 * @param int    $word_count Word count.
	 * @return int Readability score (0-100).
	 */
	private function calculate_readability_score( $content, $word_count ) {
		if ( $word_count === 0 ) {
			return 0;
		}

		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$sentence_count = count( $sentences );

		if ( $sentence_count === 0 ) {
			return 50;
		}

		// Average words per sentence.
		$avg_words_per_sentence = $word_count / $sentence_count;

		// Simple readability score based on sentence length.
		if ( $avg_words_per_sentence <= 15 ) {
			$score = 100;
		} elseif ( $avg_words_per_sentence <= 20 ) {
			$score = 80;
		} elseif ( $avg_words_per_sentence <= 25 ) {
			$score = 60;
		} elseif ( $avg_words_per_sentence <= 30 ) {
			$score = 40;
		} else {
			$score = 20;
		}

		return $score;
	}

	/**
	 * Calculate SEO score
	 *
	 * @param array $data Post analysis data.
	 * @return int SEO score (0-100).
	 */
	private function calculate_seo_score( $data ) {
		$score = 0;

		// Word count (20 points).
		if ( $data['word_count'] >= 1500 ) {
			$score += 20;
		} elseif ( $data['word_count'] >= 1000 ) {
			$score += 15;
		} elseif ( $data['word_count'] >= 500 ) {
			$score += 10;
		} elseif ( $data['word_count'] >= 300 ) {
			$score += 5;
		}

		// Internal links (20 points).
		if ( $data['internal_links'] >= 5 ) {
			$score += 20;
		} elseif ( $data['internal_links'] >= 3 ) {
			$score += 15;
		} elseif ( $data['internal_links'] >= 1 ) {
			$score += 10;
		}

		// External links (10 points).
		if ( $data['external_links'] >= 2 ) {
			$score += 10;
		} elseif ( $data['external_links'] >= 1 ) {
			$score += 5;
		}

		// Images (15 points).
		if ( $data['images_count'] >= 3 ) {
			$score += 15;
		} elseif ( $data['images_count'] >= 1 ) {
			$score += 10;
		}

		// Meta description (15 points).
		if ( $data['has_meta_description'] ) {
			$score += 15;
		}

		// Readability (20 points).
		$score += (int) ( ( $data['readability_score'] / 100 ) * 20 );

		return min( 100, $score );
	}

	/**
	 * AJAX handler for site analysis
	 */
	public function ajax_analyze_site() {
		check_ajax_referer( 'writgocms_analyzer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$result = $this->analyze_site();
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for post analysis
	 */
	public function ajax_analyze_post() {
		check_ajax_referer( 'writgocms_analyzer_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
		}

		$result = $this->analyze_post( $post_id );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for getting analysis status
	 */
	public function ajax_get_analysis_status() {
		check_ajax_referer( 'writgocms_analyzer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_site_analysis';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$latest = $wpdb->get_row(
			"SELECT * FROM {$table_name} ORDER BY analyzed_at DESC LIMIT 1",
			ARRAY_A
		);

		if ( $latest ) {
			$latest['topics'] = json_decode( $latest['topics'], true );
		}

		wp_send_json_success( $latest );
	}

	/**
	 * Get post score
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Post score data.
	 */
	public function get_post_score( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_post_scores';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);
	}
}

// Initialize.
WritgoCMS_Site_Analyzer::get_instance();
