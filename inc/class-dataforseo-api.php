<?php
/**
 * DataForSEO API Client
 *
 * Handles API communication with DataForSEO for keyword research.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_DataForSEO_API
 */
class WritgoAI_DataForSEO_API {

	/**
	 * Instance
	 *
	 * @var WritgoAI_DataForSEO_API
	 */
	private static $instance = null;

	/**
	 * API base URL
	 *
	 * @var string
	 */
	private $api_base_url = 'https://api.dataforseo.com/v3';

	/**
	 * Get instance
	 *
	 * @return WritgoAI_DataForSEO_API
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
		// Constructor.
	}

	/**
	 * Get API credentials
	 *
	 * @return array
	 */
	private function get_credentials() {
		return array(
			'login'    => get_option( 'writgoai_dataforseo_login', '' ),
			'password' => get_option( 'writgoai_dataforseo_password', '' ),
		);
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $data Request data.
	 * @return array|WP_Error
	 */
	private function make_request( $endpoint, $data = array() ) {
		$credentials = $this->get_credentials();

		if ( empty( $credentials['login'] ) || empty( $credentials['password'] ) ) {
			return new WP_Error( 'missing_credentials', 'DataForSEO credentials not configured' );
		}

		$url = $this->api_base_url . $endpoint;

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $credentials['login'] . ':' . $credentials['password'] ),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $data ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || isset( $data['status_code'] ) && $data['status_code'] !== 20000 ) {
			return new WP_Error(
				'api_error',
				isset( $data['status_message'] ) ? $data['status_message'] : 'API request failed'
			);
		}

		return $data;
	}

	/**
	 * Test connection
	 *
	 * @return bool|WP_Error
	 */
	public function test_connection() {
		$credentials = $this->get_credentials();

		if ( empty( $credentials['login'] ) || empty( $credentials['password'] ) ) {
			return new WP_Error( 'missing_credentials', 'DataForSEO credentials not configured' );
		}

		$url = $this->api_base_url . '/appendix/user_data';

		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $credentials['login'] . ':' . $credentials['password'] ),
			),
			'timeout' => 15,
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || isset( $data['status_code'] ) && $data['status_code'] !== 20000 ) {
			return new WP_Error(
				'api_error',
				isset( $data['status_message'] ) ? $data['status_message'] : 'Connection test failed'
			);
		}

		return true;
	}

	/**
	 * Get keyword data
	 *
	 * @param string $keyword Keyword to search.
	 * @param string $location_code Location code (default: 2840 for USA).
	 * @param string $language_code Language code (default: en).
	 * @return array|WP_Error
	 */
	public function get_keyword_data( $keyword, $location_code = '2840', $language_code = 'en' ) {
		$data = array(
			array(
				'keyword'       => $keyword,
				'location_code' => $location_code,
				'language_code' => $language_code,
			),
		);

		$result = $this->make_request( '/keywords_data/google/search_volume/live', $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! isset( $result['tasks'][0]['result'][0] ) ) {
			return new WP_Error( 'no_data', 'No keyword data returned' );
		}

		$keyword_data = $result['tasks'][0]['result'][0];

		return array(
			'keyword'        => $keyword_data['keyword'] ?? $keyword,
			'search_volume'  => $keyword_data['search_volume'] ?? 0,
			'competition'    => $keyword_data['competition'] ?? 'N/A',
			'cpc'            => $keyword_data['cpc'] ?? 0,
		);
	}

	/**
	 * Get keyword difficulty
	 *
	 * @param string $keyword Keyword to check.
	 * @param string $location_code Location code.
	 * @param string $language_code Language code.
	 * @return array|WP_Error
	 */
	public function get_keyword_difficulty( $keyword, $location_code = '2840', $language_code = 'en' ) {
		$data = array(
			array(
				'keyword'       => $keyword,
				'location_code' => $location_code,
				'language_code' => $language_code,
			),
		);

		$result = $this->make_request( '/dataforseo_labs/google/keyword_ideas/live', $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! isset( $result['tasks'][0]['result'][0] ) ) {
			return new WP_Error( 'no_data', 'No difficulty data returned' );
		}

		$items = $result['tasks'][0]['result'][0]['items'] ?? array();

		// Find exact match.
		foreach ( $items as $item ) {
			if ( strtolower( $item['keyword'] ) === strtolower( $keyword ) ) {
				return array(
					'keyword'    => $item['keyword'],
					'difficulty' => $item['keyword_info']['keyword_difficulty'] ?? 'N/A',
				);
			}
		}

		return array(
			'keyword'    => $keyword,
			'difficulty' => 'N/A',
		);
	}

	/**
	 * Get related keywords
	 *
	 * @param string $keyword Base keyword.
	 * @param string $location_code Location code.
	 * @param string $language_code Language code.
	 * @param int    $limit Number of results to return.
	 * @return array|WP_Error
	 */
	public function get_related_keywords( $keyword, $location_code = '2840', $language_code = 'en', $limit = 10 ) {
		$data = array(
			array(
				'keyword'       => $keyword,
				'location_code' => $location_code,
				'language_code' => $language_code,
				'limit'         => $limit,
			),
		);

		$result = $this->make_request( '/dataforseo_labs/google/related_keywords/live', $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! isset( $result['tasks'][0]['result'][0]['items'] ) ) {
			return array();
		}

		$items            = $result['tasks'][0]['result'][0]['items'];
		$related_keywords = array();

		foreach ( $items as $item ) {
			$keyword_data = $item['keyword_data'] ?? array();
			$related_keywords[] = array(
				'keyword'        => $item['keyword'] ?? '',
				'search_volume'  => $keyword_data['keyword_info']['search_volume'] ?? 0,
				'competition'    => $keyword_data['keyword_info']['competition'] ?? 'N/A',
				'cpc'            => $keyword_data['keyword_info']['cpc'] ?? 0,
			);
		}

		return $related_keywords;
	}

	/**
	 * Get SERP data
	 *
	 * @param string $keyword Keyword to search.
	 * @param string $location_code Location code.
	 * @param string $language_code Language code.
	 * @return array|WP_Error
	 */
	public function get_serp_data( $keyword, $location_code = '2840', $language_code = 'en' ) {
		$data = array(
			array(
				'keyword'       => $keyword,
				'location_code' => $location_code,
				'language_code' => $language_code,
				'device'        => 'desktop',
				'os'            => 'windows',
			),
		);

		$result = $this->make_request( '/serp/google/organic/live/advanced', $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! isset( $result['tasks'][0]['result'][0]['items'] ) ) {
			return array();
		}

		$items       = $result['tasks'][0]['result'][0]['items'];
		$serp_results = array();

		foreach ( $items as $item ) {
			if ( $item['type'] === 'organic' ) {
				$serp_results[] = array(
					'position'    => $item['rank_group'] ?? 0,
					'title'       => $item['title'] ?? '',
					'url'         => $item['url'] ?? '',
					'description' => $item['description'] ?? '',
					'domain'      => $item['domain'] ?? '',
				);
			}
		}

		return $serp_results;
	}
}

// Initialize.
WritgoAI_DataForSEO_API::get_instance();
