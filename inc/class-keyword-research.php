<?php
/**
 * Keyword Research Class
 *
 * Handles keyword research operations using DataForSEO API.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Keyword_Research
 */
class WritgoAI_Keyword_Research {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Keyword_Research
	 */
	private static $instance = null;

	/**
	 * DataForSEO API instance
	 *
	 * @var WritgoAI_DataForSEO_API
	 */
	private $api;

	/**
	 * Credit manager instance
	 *
	 * @var WritgoAI_Credit_Manager
	 */
	private $credit_manager;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Keyword_Research
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
		$this->api            = WritgoAI_DataForSEO_API::get_instance();
		$this->credit_manager = WritgoAI_Credit_Manager::get_instance();

		add_action( 'wp_ajax_writgoai_search_keyword', array( $this, 'ajax_search_keyword' ) );
		add_action( 'wp_ajax_writgoai_get_related_keywords', array( $this, 'ajax_get_related_keywords' ) );
		add_action( 'wp_ajax_writgoai_save_keyword', array( $this, 'ajax_save_keyword' ) );
		add_action( 'wp_ajax_writgoai_get_serp_data', array( $this, 'ajax_get_serp_data' ) );
	}

	/**
	 * Search for keyword data
	 *
	 * @param string $keyword Keyword to search.
	 * @return array|WP_Error
	 */
	public function search_keyword( $keyword ) {
		// Check cache first.
		$cached = $this->get_cached_keyword( $keyword );
		if ( $cached ) {
			return $cached;
		}

		// Get keyword data.
		$keyword_data = $this->api->get_keyword_data( $keyword );
		if ( is_wp_error( $keyword_data ) ) {
			return $keyword_data;
		}

		// Get difficulty.
		$difficulty_data = $this->api->get_keyword_difficulty( $keyword );
		if ( ! is_wp_error( $difficulty_data ) ) {
			$keyword_data['difficulty'] = $difficulty_data['difficulty'];
		} else {
			$keyword_data['difficulty'] = 'N/A';
		}

		// Save to database.
		$this->save_keyword( $keyword_data );

		return $keyword_data;
	}

	/**
	 * Get related keywords
	 *
	 * @param string $keyword Base keyword.
	 * @param int    $limit Number of results.
	 * @return array|WP_Error
	 */
	public function get_related_keywords( $keyword, $limit = 10 ) {
		return $this->api->get_related_keywords( $keyword, '2840', 'en', $limit );
	}

	/**
	 * Get SERP data
	 *
	 * @param string $keyword Keyword.
	 * @return array|WP_Error
	 */
	public function get_serp_data( $keyword ) {
		return $this->api->get_serp_data( $keyword );
	}

	/**
	 * Save keyword to database
	 *
	 * @param array $keyword_data Keyword data.
	 * @return bool
	 */
	public function save_keyword( $keyword_data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_keywords';

		$related_keywords = isset( $keyword_data['related_keywords'] ) ? wp_json_encode( $keyword_data['related_keywords'] ) : '';

		// Check if exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE keyword = %s",
				$keyword_data['keyword']
			)
		);

		if ( $existing ) {
			// Update.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$table_name,
				array(
					'search_volume'     => $keyword_data['search_volume'],
					'difficulty'        => is_numeric( $keyword_data['difficulty'] ) ? $keyword_data['difficulty'] : 0,
					'cpc'               => $keyword_data['cpc'],
					'competition'       => $keyword_data['competition'],
					'related_keywords'  => $related_keywords,
				),
				array( 'keyword' => $keyword_data['keyword'] ),
				array( '%d', '%d', '%f', '%s', '%s' ),
				array( '%s' )
			);
		} else {
			// Insert.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$table_name,
				array(
					'keyword'          => $keyword_data['keyword'],
					'search_volume'    => $keyword_data['search_volume'],
					'difficulty'       => is_numeric( $keyword_data['difficulty'] ) ? $keyword_data['difficulty'] : 0,
					'cpc'              => $keyword_data['cpc'],
					'competition'      => $keyword_data['competition'],
					'related_keywords' => $related_keywords,
					'created_at'       => current_time( 'mysql' ),
				),
				array( '%s', '%d', '%d', '%f', '%s', '%s', '%s' )
			);
		}

		return $result !== false;
	}

	/**
	 * Get cached keyword data
	 *
	 * @param string $keyword Keyword.
	 * @return array|null
	 */
	private function get_cached_keyword( $keyword ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_keywords';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cached = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE keyword = %s AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
				$keyword
			),
			ARRAY_A
		);

		if ( $cached ) {
			$cached['related_keywords'] = json_decode( $cached['related_keywords'], true );
			return $cached;
		}

		return null;
	}

	/**
	 * AJAX handler for keyword search
	 */
	public function ajax_search_keyword() {
		check_ajax_referer( 'writgoai_keyword_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		if ( empty( $keyword ) ) {
			wp_send_json_error( array( 'message' => 'Keyword is required' ) );
		}

		// Check credits.
		$has_credits = $this->credit_manager->check_credits( 'keyword_research' );
		if ( ! $has_credits ) {
			wp_send_json_error( array( 'message' => 'Insufficient credits' ) );
		}

		// Search keyword.
		$result = $this->search_keyword( $keyword );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Consume credits.
		$this->credit_manager->consume_credits( 'keyword_research' );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for related keywords
	 */
	public function ajax_get_related_keywords() {
		check_ajax_referer( 'writgoai_keyword_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		if ( empty( $keyword ) ) {
			wp_send_json_error( array( 'message' => 'Keyword is required' ) );
		}

		// Check credits (related keywords cost 5 credits).
		$has_credits = $this->credit_manager->check_credits( 'internal_links' );
		if ( ! $has_credits ) {
			wp_send_json_error( array( 'message' => 'Insufficient credits' ) );
		}

		// Get related keywords.
		$result = $this->get_related_keywords( $keyword );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Consume credits.
		$this->credit_manager->consume_credits( 'internal_links' );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for saving keyword
	 */
	public function ajax_save_keyword() {
		check_ajax_referer( 'writgoai_keyword_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$keyword_data = isset( $_POST['keyword_data'] ) ? wp_unslash( $_POST['keyword_data'] ) : array();
		if ( empty( $keyword_data ) ) {
			wp_send_json_error( array( 'message' => 'Keyword data is required' ) );
		}

		// Sanitize data.
		$keyword_data = array(
			'keyword'        => sanitize_text_field( $keyword_data['keyword'] ),
			'search_volume'  => absint( $keyword_data['search_volume'] ),
			'difficulty'     => absint( $keyword_data['difficulty'] ),
			'cpc'            => floatval( $keyword_data['cpc'] ),
			'competition'    => sanitize_text_field( $keyword_data['competition'] ),
		);

		$result = $this->save_keyword( $keyword_data );
		if ( ! $result ) {
			wp_send_json_error( array( 'message' => 'Failed to save keyword' ) );
		}

		wp_send_json_success( array( 'message' => 'Keyword saved successfully' ) );
	}

	/**
	 * AJAX handler for SERP data
	 */
	public function ajax_get_serp_data() {
		check_ajax_referer( 'writgoai_keyword_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		if ( empty( $keyword ) ) {
			wp_send_json_error( array( 'message' => 'Keyword is required' ) );
		}

		// Check credits (SERP analysis costs 10 credits).
		$has_credits = $this->credit_manager->check_credits( 'text_generation' );
		if ( ! $has_credits ) {
			wp_send_json_error( array( 'message' => 'Insufficient credits' ) );
		}

		// Get SERP data.
		$result = $this->get_serp_data( $keyword );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Consume credits.
		$this->credit_manager->consume_credits( 'text_generation' );

		wp_send_json_success( $result );
	}
}

// Initialize.
WritgoAI_Keyword_Research::get_instance();
