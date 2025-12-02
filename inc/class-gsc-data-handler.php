<?php
/**
 * Google Search Console Data Handler
 *
 * Handles data storage, sync, and opportunity detection for GSC data.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_GSC_Data_Handler
 */
class WritgoAI_GSC_Data_Handler {

	/**
	 * Instance
	 *
	 * @var WritgoAI_GSC_Data_Handler
	 */
	private static $instance = null;

	/**
	 * GSC Provider instance
	 *
	 * @var WritgoAI_GSC_Provider
	 */
	private $provider;

	/**
	 * Table names
	 *
	 * @var array
	 */
	private $tables = array();

	/**
	 * CTR benchmarks by position
	 *
	 * @var array
	 */
	private $ctr_benchmarks = array(
		1  => 0.28,
		2  => 0.15,
		3  => 0.11,
		4  => 0.08,
		5  => 0.06,
		6  => 0.05,
		7  => 0.04,
		8  => 0.03,
		9  => 0.03,
		10 => 0.02,
	);

	/**
	 * Get instance
	 *
	 * @return WritgoAI_GSC_Data_Handler
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
		global $wpdb;

		$this->tables = array(
			'queries'       => $wpdb->prefix . 'writgoai_gsc_queries',
			'pages'         => $wpdb->prefix . 'writgoai_gsc_pages',
			'opportunities' => $wpdb->prefix . 'writgoai_gsc_opportunities',
		);

		$this->provider = WritgoAI_GSC_Provider::get_instance();

		add_action( 'writgoai_gsc_daily_sync', array( $this, 'run_daily_sync' ) );
		add_action( 'wp_ajax_writgoai_gsc_sync_now', array( $this, 'ajax_sync_now' ) );
		add_action( 'wp_ajax_writgoai_gsc_get_opportunities', array( $this, 'ajax_get_opportunities' ) );
		add_action( 'wp_ajax_writgoai_gsc_get_dashboard_data', array( $this, 'ajax_get_dashboard_data' ) );
		add_action( 'wp_ajax_writgoai_gsc_get_post_data', array( $this, 'ajax_get_post_data' ) );
	}

	/**
	 * Create database tables
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql_queries = "CREATE TABLE IF NOT EXISTS {$this->tables['queries']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			query varchar(500) NOT NULL,
			clicks int(11) NOT NULL DEFAULT 0,
			impressions int(11) NOT NULL DEFAULT 0,
			ctr float NOT NULL DEFAULT 0,
			position float NOT NULL DEFAULT 0,
			date date NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY query_date (query(191), date),
			KEY query_idx (query(191)),
			KEY date_idx (date),
			KEY position_idx (position)
		) $charset_collate;";

		$sql_pages = "CREATE TABLE IF NOT EXISTS {$this->tables['pages']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url varchar(500) NOT NULL,
			post_id bigint(20) unsigned DEFAULT NULL,
			clicks int(11) NOT NULL DEFAULT 0,
			impressions int(11) NOT NULL DEFAULT 0,
			ctr float NOT NULL DEFAULT 0,
			position float NOT NULL DEFAULT 0,
			date date NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY url_date (url(191), date),
			KEY url_idx (url(191)),
			KEY post_id_idx (post_id),
			KEY date_idx (date)
		) $charset_collate;";

		$sql_opportunities = "CREATE TABLE IF NOT EXISTS {$this->tables['opportunities']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			keyword varchar(500) NOT NULL,
			page_url varchar(500) DEFAULT NULL,
			post_id bigint(20) unsigned DEFAULT NULL,
			opportunity_type varchar(50) NOT NULL,
			score float NOT NULL DEFAULT 0,
			current_position float DEFAULT NULL,
			current_ctr float DEFAULT NULL,
			impressions int(11) DEFAULT 0,
			clicks int(11) DEFAULT 0,
			position_change float DEFAULT NULL,
			suggested_action text,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY keyword_type (keyword(191), opportunity_type),
			KEY opportunity_type_idx (opportunity_type),
			KEY status_idx (status),
			KEY score_idx (score)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_queries );
		dbDelta( $sql_pages );
		dbDelta( $sql_opportunities );
	}

	/**
	 * Schedule daily sync
	 */
	public function schedule_sync() {
		if ( ! wp_next_scheduled( 'writgoai_gsc_daily_sync' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 03:00:00' ), 'daily', 'writgoai_gsc_daily_sync' );
		}
	}

	/**
	 * Unschedule sync
	 */
	public function unschedule_sync() {
		$timestamp = wp_next_scheduled( 'writgoai_gsc_daily_sync' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'writgoai_gsc_daily_sync' );
		}
	}

	/**
	 * Run daily sync
	 */
	public function run_daily_sync() {
		if ( ! $this->provider->is_connected() ) {
			return;
		}

		$site_url = $this->provider->get_selected_site();
		if ( empty( $site_url ) ) {
			return;
		}

		// Sync last 28 days of data.
		$end_date   = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$start_date = gmdate( 'Y-m-d', strtotime( '-28 days' ) );

		$this->sync_query_data( $site_url, $start_date, $end_date );
		$this->sync_page_data( $site_url, $start_date, $end_date );
		$this->detect_opportunities();
		$this->cleanup_old_data();

		update_option( 'writgoai_gsc_last_sync', current_time( 'mysql' ) );
	}

	/**
	 * Sync query data
	 *
	 * @param string $site_url   Site URL.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return bool|WP_Error
	 */
	public function sync_query_data( $site_url, $start_date, $end_date ) {
		global $wpdb;

		$data = $this->provider->get_search_analytics(
			$site_url,
			$start_date,
			$end_date,
			array( 'query', 'date' ),
			5000
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['rows'] ) ) {
			return true;
		}

		foreach ( $data['rows'] as $row ) {
			$query = $row['keys'][0];
			$date  = $row['keys'][1];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->replace(
				$this->tables['queries'],
				array(
					'query'       => $query,
					'clicks'      => (int) $row['clicks'],
					'impressions' => (int) $row['impressions'],
					'ctr'         => (float) $row['ctr'],
					'position'    => (float) $row['position'],
					'date'        => $date,
				),
				array( '%s', '%d', '%d', '%f', '%f', '%s' )
			);
		}

		return true;
	}

	/**
	 * Sync page data
	 *
	 * @param string $site_url   Site URL.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return bool|WP_Error
	 */
	public function sync_page_data( $site_url, $start_date, $end_date ) {
		global $wpdb;

		$data = $this->provider->get_search_analytics(
			$site_url,
			$start_date,
			$end_date,
			array( 'page', 'date' ),
			5000
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['rows'] ) ) {
			return true;
		}

		foreach ( $data['rows'] as $row ) {
			$page_url = $row['keys'][0];
			$date     = $row['keys'][1];

			// Try to find matching post.
			$post_id = url_to_postid( $page_url );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->replace(
				$this->tables['pages'],
				array(
					'url'         => $page_url,
					'post_id'     => $post_id > 0 ? $post_id : null,
					'clicks'      => (int) $row['clicks'],
					'impressions' => (int) $row['impressions'],
					'ctr'         => (float) $row['ctr'],
					'position'    => (float) $row['position'],
					'date'        => $date,
				),
				array( '%s', '%d', '%d', '%d', '%f', '%f', '%s' )
			);
		}

		return true;
	}

	/**
	 * Detect opportunities
	 */
	public function detect_opportunities() {
		$this->detect_quick_wins();
		$this->detect_low_ctr();
		$this->detect_declining_rankings();
		$this->detect_content_gaps();
	}

	/**
	 * Detect quick wins (position 11-20)
	 */
	private function detect_quick_wins() {
		global $wpdb;

		// Get queries on page 2 (position 11-20) with decent impressions.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query, 
					AVG(position) as avg_position,
					AVG(ctr) as avg_ctr,
					SUM(impressions) as total_impressions,
					SUM(clicks) as total_clicks
				FROM {$this->tables['queries']}
				WHERE date >= %s
				GROUP BY query
				HAVING avg_position >= 11 AND avg_position <= 20 AND total_impressions >= 50
				ORDER BY total_impressions DESC
				LIMIT 50",
				gmdate( 'Y-m-d', strtotime( '-28 days' ) )
			)
		);

		foreach ( $results as $row ) {
			$score = $this->calculate_quick_win_score( $row );

			$this->save_opportunity(
				$row->query,
				'quick_win',
				$score,
				array(
					'current_position' => $row->avg_position,
					'current_ctr'      => $row->avg_ctr,
					'impressions'      => $row->total_impressions,
					'clicks'           => $row->total_clicks,
					'suggested_action' => sprintf(
						/* translators: %d: current position */
						__( 'Dit keyword staat op positie %d. Met content optimalisatie kun je naar pagina 1.', 'writgoai' ),
						round( $row->avg_position )
					),
				)
			);
		}
	}

	/**
	 * Calculate quick win score
	 *
	 * @param object $row Data row.
	 * @return float
	 */
	private function calculate_quick_win_score( $row ) {
		// Higher score for positions closer to 11 and more impressions.
		$position_score   = ( 21 - $row->avg_position ) / 10; // 0-1 scale.
		$impression_score = min( $row->total_impressions / 1000, 1 ); // 0-1 scale.

		return ( $position_score * 0.6 + $impression_score * 0.4 ) * 100;
	}

	/**
	 * Detect low CTR opportunities
	 */
	private function detect_low_ctr() {
		global $wpdb;

		// Get queries with position 1-10 but below benchmark CTR.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query, 
					AVG(position) as avg_position,
					AVG(ctr) as avg_ctr,
					SUM(impressions) as total_impressions,
					SUM(clicks) as total_clicks
				FROM {$this->tables['queries']}
				WHERE date >= %s
				GROUP BY query
				HAVING avg_position <= 10 AND total_impressions >= 100
				ORDER BY total_impressions DESC
				LIMIT 50",
				gmdate( 'Y-m-d', strtotime( '-28 days' ) )
			)
		);

		foreach ( $results as $row ) {
			$position_rounded = max( 1, min( 10, round( $row->avg_position ) ) );
			$benchmark_ctr    = $this->ctr_benchmarks[ $position_rounded ];

			// Only flag if CTR is significantly below benchmark.
			if ( $row->avg_ctr < $benchmark_ctr * 0.7 ) {
				$score = $this->calculate_low_ctr_score( $row, $benchmark_ctr );

				$this->save_opportunity(
					$row->query,
					'low_ctr',
					$score,
					array(
						'current_position' => $row->avg_position,
						'current_ctr'      => $row->avg_ctr,
						'impressions'      => $row->total_impressions,
						'clicks'           => $row->total_clicks,
						'suggested_action' => sprintf(
							/* translators: 1: current CTR percentage, 2: benchmark CTR percentage */
							__( 'CTR is %.1f%% terwijl benchmark %.1f%% is. Verbeter je meta title en description.', 'writgoai' ),
							$row->avg_ctr * 100,
							$benchmark_ctr * 100
						),
					)
				);
			}
		}
	}

	/**
	 * Calculate low CTR score
	 *
	 * @param object $row           Data row.
	 * @param float  $benchmark_ctr Benchmark CTR.
	 * @return float
	 */
	private function calculate_low_ctr_score( $row, $benchmark_ctr ) {
		$ctr_gap          = $benchmark_ctr - $row->avg_ctr;
		$potential_clicks = $row->total_impressions * $ctr_gap;

		return min( $potential_clicks, 100 );
	}

	/**
	 * Detect declining rankings
	 */
	private function detect_declining_rankings() {
		global $wpdb;

		$recent_start = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$recent_end   = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$old_start    = gmdate( 'Y-m-d', strtotime( '-28 days' ) );
		$old_end      = gmdate( 'Y-m-d', strtotime( '-8 days' ) );

		// Compare recent week to previous 3 weeks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					r.query,
					r.avg_position as recent_position,
					o.avg_position as old_position,
					r.total_impressions as recent_impressions,
					r.total_clicks as recent_clicks,
					(r.avg_position - o.avg_position) as position_change
				FROM (
					SELECT query, AVG(position) as avg_position, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks
					FROM {$this->tables['queries']}
					WHERE date BETWEEN %s AND %s
					GROUP BY query
				) r
				INNER JOIN (
					SELECT query, AVG(position) as avg_position
					FROM {$this->tables['queries']}
					WHERE date BETWEEN %s AND %s
					GROUP BY query
				) o ON r.query = o.query
				WHERE (r.avg_position - o.avg_position) >= 3
				ORDER BY (r.avg_position - o.avg_position) DESC
				LIMIT 50",
				$recent_start,
				$recent_end,
				$old_start,
				$old_end
			)
		);

		foreach ( $results as $row ) {
			$score = min( $row->position_change * 10, 100 );

			$this->save_opportunity(
				$row->query,
				'declining',
				$score,
				array(
					'current_position' => $row->recent_position,
					'position_change'  => $row->position_change,
					'impressions'      => $row->recent_impressions,
					'clicks'           => $row->recent_clicks,
					'suggested_action' => sprintf(
						/* translators: 1: position change, 2: old position, 3: new position */
						__( 'Positie daalde met %.1f plaatsen (van %.1f naar %.1f). Update je content.', 'writgoai' ),
						$row->position_change,
						$row->old_position,
						$row->recent_position
					),
				)
			);
		}
	}

	/**
	 * Detect content gaps
	 */
	private function detect_content_gaps() {
		global $wpdb;

		// Find keywords with high impressions but no clicks (or very low CTR).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query, 
					AVG(position) as avg_position,
					AVG(ctr) as avg_ctr,
					SUM(impressions) as total_impressions,
					SUM(clicks) as total_clicks
				FROM {$this->tables['queries']}
				WHERE date >= %s
				GROUP BY query
				HAVING avg_position > 20 AND total_impressions >= 200
				ORDER BY total_impressions DESC
				LIMIT 50",
				gmdate( 'Y-m-d', strtotime( '-28 days' ) )
			)
		);

		foreach ( $results as $row ) {
			$score = min( $row->total_impressions / 50, 100 );

			$this->save_opportunity(
				$row->query,
				'content_gap',
				$score,
				array(
					'current_position' => $row->avg_position,
					'current_ctr'      => $row->avg_ctr,
					'impressions'      => $row->total_impressions,
					'clicks'           => $row->total_clicks,
					'suggested_action' => sprintf(
						/* translators: %d: number of impressions */
						__( '%d impressies maar nog niet op pagina 1. Maak gerichte content voor dit keyword.', 'writgoai' ),
						$row->total_impressions
					),
				)
			);
		}
	}

	/**
	 * Save opportunity to database
	 *
	 * @param string $keyword Type.
	 * @param string $type    Opportunity type.
	 * @param float  $score   Score.
	 * @param array  $data    Additional data.
	 */
	private function save_opportunity( $keyword, $type, $score, $data = array() ) {
		global $wpdb;

		$insert_data = array(
			'keyword'          => $keyword,
			'opportunity_type' => $type,
			'score'            => $score,
			'current_position' => isset( $data['current_position'] ) ? $data['current_position'] : null,
			'current_ctr'      => isset( $data['current_ctr'] ) ? $data['current_ctr'] : null,
			'impressions'      => isset( $data['impressions'] ) ? $data['impressions'] : 0,
			'clicks'           => isset( $data['clicks'] ) ? $data['clicks'] : 0,
			'position_change'  => isset( $data['position_change'] ) ? $data['position_change'] : null,
			'suggested_action' => isset( $data['suggested_action'] ) ? $data['suggested_action'] : null,
			'status'           => 'active',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->replace(
			$this->tables['opportunities'],
			$insert_data,
			array( '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%f', '%s', '%s' )
		);
	}

	/**
	 * Cleanup old data (older than 6 months)
	 */
	public function cleanup_old_data() {
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d', strtotime( '-6 months' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->tables['queries']} WHERE date < %s",
				$cutoff_date
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->tables['pages']} WHERE date < %s",
				$cutoff_date
			)
		);
	}

	/**
	 * Get dashboard data
	 *
	 * @param int $days Number of days.
	 * @return array
	 */
	public function get_dashboard_data( $days = 28 ) {
		global $wpdb;

		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Get totals.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					SUM(clicks) as total_clicks,
					SUM(impressions) as total_impressions,
					AVG(ctr) as avg_ctr,
					AVG(position) as avg_position
				FROM {$this->tables['queries']}
				WHERE date >= %s",
				$start_date
			)
		);

		// Get top queries.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$top_queries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position
				FROM {$this->tables['queries']}
				WHERE date >= %s
				GROUP BY query
				ORDER BY clicks DESC
				LIMIT 10",
				$start_date
			)
		);

		// Get top pages.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$top_pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT url, post_id, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position
				FROM {$this->tables['pages']}
				WHERE date >= %s
				GROUP BY url, post_id
				ORDER BY clicks DESC
				LIMIT 10",
				$start_date
			)
		);

		// Get opportunity counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$opportunity_counts = $wpdb->get_results(
			"SELECT opportunity_type, COUNT(*) as count
			FROM {$this->tables['opportunities']}
			WHERE status = 'active'
			GROUP BY opportunity_type"
		);

		return array(
			'totals'              => $totals,
			'top_queries'         => $top_queries,
			'top_pages'           => $top_pages,
			'opportunity_counts'  => $opportunity_counts,
			'last_sync'           => get_option( 'writgoai_gsc_last_sync', '' ),
		);
	}

	/**
	 * Get opportunities
	 *
	 * @param string $type   Opportunity type (optional).
	 * @param int    $limit  Limit.
	 * @param int    $offset Offset.
	 * @return array
	 */
	public function get_opportunities( $type = '', $limit = 20, $offset = 0 ) {
		global $wpdb;

		$where = "WHERE status = 'active'";
		if ( ! empty( $type ) ) {
			$where .= $wpdb->prepare( ' AND opportunity_type = %s', $type );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->tables['opportunities']}
				{$where}
				ORDER BY score DESC
				LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		return $results;
	}

	/**
	 * Get post GSC data
	 *
	 * @param int $post_id Post ID.
	 * @param int $days    Number of days.
	 * @return array
	 */
	public function get_post_data( $post_id, $days = 28 ) {
		global $wpdb;

		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Get page data.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$page_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT url, SUM(clicks) as total_clicks, SUM(impressions) as total_impressions, AVG(ctr) as avg_ctr, AVG(position) as avg_position
				FROM {$this->tables['pages']}
				WHERE post_id = %d AND date >= %s
				GROUP BY url",
				$post_id,
				$start_date
			)
		);

		if ( ! $page_data ) {
			return null;
		}

		// Get keywords for this page.
		$post_url = get_permalink( $post_id );
		$site_url = $this->provider->get_selected_site();

		if ( empty( $site_url ) || empty( $post_url ) ) {
			return array(
				'page_data' => $page_data,
				'keywords'  => array(),
				'trend'     => 'stable',
			);
		}

		// Get trend by comparing recent to older data.
		$recent_start = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$old_start    = gmdate( 'Y-m-d', strtotime( '-28 days' ) );
		$old_end      = gmdate( 'Y-m-d', strtotime( '-8 days' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$recent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(position) FROM {$this->tables['pages']} WHERE post_id = %d AND date >= %s",
				$post_id,
				$recent_start
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(position) FROM {$this->tables['pages']} WHERE post_id = %d AND date BETWEEN %s AND %s",
				$post_id,
				$old_start,
				$old_end
			)
		);

		$trend = 'stable';
		if ( $recent && $old ) {
			$diff = $old - $recent; // Positive = improving (lower position number = better).
			if ( $diff > 2 ) {
				$trend = 'rising';
			} elseif ( $diff < -2 ) {
				$trend = 'declining';
			}
		}

		return array(
			'page_data' => $page_data,
			'trend'     => $trend,
		);
	}

	/**
	 * Get CTR benchmark for position
	 *
	 * @param float $position Position.
	 * @return float
	 */
	public function get_ctr_benchmark( $position ) {
		$position_rounded = max( 1, min( 10, round( $position ) ) );
		return isset( $this->ctr_benchmarks[ $position_rounded ] ) ? $this->ctr_benchmarks[ $position_rounded ] : 0.01;
	}

	/**
	 * AJAX handler for sync now
	 */
	public function ajax_sync_now() {
		check_ajax_referer( 'writgoai_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
		}

		$this->run_daily_sync();

		wp_send_json_success( array(
			'message'   => __( 'Synchronisatie voltooid.', 'writgoai' ),
			'last_sync' => get_option( 'writgoai_gsc_last_sync', '' ),
		) );
	}

	/**
	 * AJAX handler for get opportunities
	 */
	public function ajax_get_opportunities() {
		check_ajax_referer( 'writgoai_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
		}

		$type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$limit  = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20;
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

		$opportunities = $this->get_opportunities( $type, $limit, $offset );

		wp_send_json_success( array( 'opportunities' => $opportunities ) );
	}

	/**
	 * AJAX handler for get dashboard data
	 */
	public function ajax_get_dashboard_data() {
		check_ajax_referer( 'writgoai_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
		}

		$days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 28;
		$data = $this->get_dashboard_data( $days );

		wp_send_json_success( $data );
	}

	/**
	 * AJAX handler for get post data
	 */
	public function ajax_get_post_data() {
		check_ajax_referer( 'writgoai_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Post ID is vereist.', 'writgoai' ) ) );
		}

		$data = $this->get_post_data( $post_id );

		if ( ! $data ) {
			wp_send_json_error( array( 'message' => __( 'Geen data gevonden voor deze post.', 'writgoai' ) ) );
		}

		wp_send_json_success( $data );
	}
}

// Initialize.
WritgoAI_GSC_Data_Handler::get_instance();
