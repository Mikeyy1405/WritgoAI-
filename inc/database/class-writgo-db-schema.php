<?php
/**
 * Database Schema Class
 *
 * Handles database table creation and migrations for WritgoAI.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_DB_Schema
 */
class WritgoAI_DB_Schema {

	/**
	 * Instance
	 *
	 * @var WritgoAI_DB_Schema
	 */
	private static $instance = null;

	/**
	 * Database version option key
	 *
	 * @var string
	 */
	private $db_version_key = 'writgoai_db_version';

	/**
	 * Current database version
	 *
	 * @var string
	 */
	private $db_version = '1.2.0';

	/**
	 * Get instance
	 *
	 * @return WritgoAI_DB_Schema
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
		// Empty constructor.
	}

	/**
	 * Create all required database tables
	 */
	public function create_tables() {
		$this->create_api_usage_table();
		$this->create_site_analysis_table();
		$this->create_post_scores_table();
		$this->create_keywords_table();
		$this->update_db_version();
	}

	/**
	 * Create API usage tracking table
	 */
	public function create_api_usage_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'writgo_api_usage';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			license_key varchar(255) NOT NULL DEFAULT '',
			request_count int(11) NOT NULL DEFAULT 0,
			last_reset_date datetime NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY license_key (license_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Update database version
	 */
	private function update_db_version() {
		update_option( $this->db_version_key, $this->db_version );
	}

	/**
	 * Check if database needs update
	 *
	 * @return bool
	 */
	public function needs_update() {
		$installed_version = get_option( $this->db_version_key, '0' );
		return version_compare( $installed_version, $this->db_version, '<' );
	}

	/**
	 * Run database migrations if needed
	 */
	public function maybe_update() {
		if ( $this->needs_update() ) {
			$this->create_tables();
		}
	}

	/**
	 * Create site analysis table
	 */
	public function create_site_analysis_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'writgo_site_analysis';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			site_url varchar(255) NOT NULL,
			total_posts int(11) NOT NULL DEFAULT 0,
			optimized_posts int(11) NOT NULL DEFAULT 0,
			health_score int(11) NOT NULL DEFAULT 0,
			niche varchar(100) DEFAULT '',
			topics TEXT,
			analyzed_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY site_url (site_url)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create post scores table
	 */
	public function create_post_scores_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'writgo_post_scores';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			seo_score int(11) NOT NULL DEFAULT 0,
			readability_score int(11) NOT NULL DEFAULT 0,
			keyword_density float NOT NULL DEFAULT 0,
			word_count int(11) NOT NULL DEFAULT 0,
			internal_links int(11) NOT NULL DEFAULT 0,
			external_links int(11) NOT NULL DEFAULT 0,
			images_count int(11) NOT NULL DEFAULT 0,
			has_meta_description tinyint(1) NOT NULL DEFAULT 0,
			analyzed_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create keywords table
	 */
	public function create_keywords_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'writgo_keywords';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			keyword varchar(255) NOT NULL,
			search_volume int(11) NOT NULL DEFAULT 0,
			difficulty int(11) NOT NULL DEFAULT 0,
			cpc float NOT NULL DEFAULT 0,
			competition varchar(20) DEFAULT '',
			related_keywords TEXT,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY keyword (keyword)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop all plugin tables (for uninstall)
	 */
	public function drop_tables() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}writgo_api_usage" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}writgo_site_analysis" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}writgo_post_scores" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}writgo_keywords" );

		delete_option( $this->db_version_key );
	}

	/**
	 * Get usage statistics for a user
	 *
	 * @param int $user_id User ID.
	 * @return array|null
	 */
	public function get_user_usage( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_api_usage';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d",
				$user_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get all usage statistics (for admin dashboard)
	 *
	 * @param int $limit Number of records to return.
	 * @return array
	 */
	public function get_all_usage_stats( $limit = 100 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_api_usage';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.*, wu.display_name, wu.user_email 
				FROM {$table_name} u 
				LEFT JOIN {$wpdb->users} wu ON u.user_id = wu.ID 
				ORDER BY u.request_count DESC 
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get total API requests today
	 *
	 * @return int
	 */
	public function get_total_requests_today() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_api_usage';
		$today      = gmdate( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(request_count) FROM {$table_name} WHERE DATE(last_reset_date) = %s",
				$today
			)
		);

		return (int) $result;
	}

	/**
	 * Reset all daily usage counts (called by cron)
	 */
	public function reset_all_daily_usage() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_api_usage';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name} SET request_count = 0, last_reset_date = %s, updated_at = %s",
				current_time( 'mysql' ),
				current_time( 'mysql' )
			)
		);
	}
}

// Initialize.
WritgoAI_DB_Schema::get_instance();
