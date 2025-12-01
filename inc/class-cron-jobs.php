<?php
/**
 * Cron Jobs Class
 *
 * Handles automated tasks for WritgoAI plugin.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Cron_Jobs
 */
class WritgoCMS_Cron_Jobs {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Cron_Jobs
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Cron_Jobs
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
		// Register cron hooks.
		add_action( 'writgocms_daily_sync', array( $this, 'daily_sync' ) );
		add_action( 'writgocms_weekly_analysis', array( $this, 'weekly_analysis' ) );

		// Schedule events on init if not already scheduled.
		add_action( 'init', array( $this, 'maybe_schedule_events' ) );
	}

	/**
	 * Maybe schedule cron events
	 */
	public function maybe_schedule_events() {
		if ( ! wp_next_scheduled( 'writgocms_daily_sync' ) ) {
			wp_schedule_event( strtotime( '03:00:00' ), 'daily', 'writgocms_daily_sync' );
		}

		if ( ! wp_next_scheduled( 'writgocms_weekly_analysis' ) ) {
			wp_schedule_event( strtotime( 'next Sunday 03:00:00' ), 'weekly', 'writgocms_weekly_analysis' );
		}
	}

	/**
	 * Unschedule all events
	 */
	public function unschedule_events() {
		$timestamp = wp_next_scheduled( 'writgocms_daily_sync' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'writgocms_daily_sync' );
		}

		$timestamp = wp_next_scheduled( 'writgocms_weekly_analysis' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'writgocms_weekly_analysis' );
		}
	}

	/**
	 * Daily sync job
	 * - Sync Search Console data
	 * - Update rankings
	 * - Check for declining posts
	 * - Calculate new health scores
	 */
	public function daily_sync() {
		// Sync GSC data.
		if ( class_exists( 'WritgoCMS_GSC_Data_Handler' ) ) {
			$gsc_handler = WritgoCMS_GSC_Data_Handler::get_instance();
			$gsc_handler->sync_data();
		}

		// Update post rankings.
		$this->update_rankings();

		// Detect declining posts.
		$this->detect_declining_posts();

		// Calculate health scores.
		$this->calculate_health_scores();

		// Log completion.
		update_option( 'writgocms_last_daily_sync', current_time( 'mysql' ) );
	}

	/**
	 * Weekly analysis job
	 * - Full site re-analysis
	 * - Send performance email
	 */
	public function weekly_analysis() {
		// Run full site analysis.
		if ( class_exists( 'WritgoCMS_Site_Analyzer' ) ) {
			$analyzer = WritgoCMS_Site_Analyzer::get_instance();
			$analyzer->analyze_site();
		}

		// Send performance email.
		$this->send_performance_email();

		// Log completion.
		update_option( 'writgocms_last_weekly_analysis', current_time( 'mysql' ) );
	}

	/**
	 * Update post rankings from GSC data
	 */
	private function update_rankings() {
		global $wpdb;

		$gsc_table = $wpdb->prefix . 'writgoai_gsc_pages';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pages = $wpdb->get_results(
			"SELECT post_id, AVG(position) as avg_position 
			FROM {$gsc_table} 
			WHERE post_id > 0 
			AND date > DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY post_id",
			ARRAY_A
		);

		foreach ( $pages as $page ) {
			update_post_meta( $page['post_id'], '_writgocms_avg_ranking', round( $page['avg_position'], 1 ) );
		}
	}

	/**
	 * Detect declining posts
	 */
	private function detect_declining_posts() {
		global $wpdb;

		$gsc_table = $wpdb->prefix . 'writgoai_gsc_pages';

		// Get posts with position change > -3 in last 30 days.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$declining = $wpdb->get_results(
			"SELECT 
				post_id,
				AVG(CASE WHEN date > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN position END) as recent_position,
				AVG(CASE WHEN date BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY) THEN position END) as past_position
			FROM {$gsc_table}
			WHERE post_id > 0
			GROUP BY post_id
			HAVING (recent_position - past_position) >= 3",
			ARRAY_A
		);

		// Store declining posts.
		update_option( 'writgocms_declining_posts', wp_list_pluck( $declining, 'post_id' ) );
	}

	/**
	 * Calculate health scores for all posts
	 */
	private function calculate_health_scores() {
		if ( ! class_exists( 'WritgoCMS_Site_Analyzer' ) ) {
			return;
		}

		$analyzer = WritgoCMS_Site_Analyzer::get_instance();

		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
			)
		);

		foreach ( $posts as $post ) {
			$analyzer->analyze_post( $post->ID );
		}
	}

	/**
	 * Send performance email to admin
	 */
	private function send_performance_email() {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		// Get latest analysis.
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_site_analysis';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$analysis = $wpdb->get_row(
			"SELECT * FROM {$table_name} ORDER BY analyzed_at DESC LIMIT 1",
			ARRAY_A
		);

		if ( ! $analysis ) {
			return;
		}

		// Get GSC stats.
		$gsc_stats = $this->get_gsc_weekly_stats();

		$subject = sprintf( 'Weekly SEO Performance Report - %s', $site_name );

		$message = sprintf(
			"Weekly SEO Performance Report for %s\n\n" .
			"Site Health Score: %d/100\n" .
			"Total Posts: %d\n" .
			"Optimized Posts: %d\n" .
			"Niche: %s\n\n" .
			"Search Console Stats (Last 7 Days):\n" .
			"Total Clicks: %d\n" .
			"Total Impressions: %d\n" .
			"Average CTR: %.2f%%\n" .
			"Average Position: %.1f\n\n" .
			"View full dashboard: %s",
			$site_name,
			$analysis['health_score'],
			$analysis['total_posts'],
			$analysis['optimized_posts'],
			$analysis['niche'],
			$gsc_stats['clicks'],
			$gsc_stats['impressions'],
			$gsc_stats['ctr'],
			$gsc_stats['position'],
			admin_url( 'admin.php?page=writgocms-aiml' )
		);

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Get GSC weekly stats
	 *
	 * @return array
	 */
	private function get_gsc_weekly_stats() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgoai_gsc_queries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			"SELECT 
				SUM(clicks) as clicks,
				SUM(impressions) as impressions,
				AVG(ctr) as ctr,
				AVG(position) as position
			FROM {$table_name}
			WHERE date > DATE_SUB(NOW(), INTERVAL 7 DAY)",
			ARRAY_A
		);

		return array(
			'clicks'      => (int) ( $stats['clicks'] ?? 0 ),
			'impressions' => (int) ( $stats['impressions'] ?? 0 ),
			'ctr'         => (float) ( $stats['ctr'] ?? 0 ),
			'position'    => (float) ( $stats['position'] ?? 0 ),
		);
	}
}

// Initialize.
WritgoCMS_Cron_Jobs::get_instance();
