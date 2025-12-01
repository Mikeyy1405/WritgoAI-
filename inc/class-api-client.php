<?php
/**
 * WritgoAI API Client Class
 *
 * Handles communication with WritgoAI API server for credit management.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_API_Client
 */
class WritgoCMS_API_Client {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_API_Client
	 */
	private static $instance = null;

	/**
	 * API Base URL
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * License Key
	 *
	 * @var string
	 */
	private $license_key;

	/**
	 * Cache duration in seconds (5 minutes)
	 *
	 * @var int
	 */
	private $cache_duration = 300;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_API_Client
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
		// Get API URL from options or use default.
		$this->api_url = get_option( 'writgocms_api_url', 'https://api.writgoai.com' );
		
		// Get license key from options.
		$this->license_key = get_option( 'writgocms_license_key', '' );
	}

	/**
	 * Set license key
	 *
	 * @param string $license_key License key.
	 * @return void
	 */
	public function set_license_key( $license_key ) {
		$this->license_key = $license_key;
	}

	/**
	 * Set API URL
	 *
	 * @param string $api_url API URL.
	 * @return void
	 */
	public function set_api_url( $api_url ) {
		$this->api_url = rtrim( $api_url, '/' );
	}

	/**
	 * Get credit balance from API
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array|WP_Error Credit balance data or error.
	 */
	public function get_credit_balance( $force_refresh = false ) {
		// Try to get from cache first unless force refresh.
		if ( ! $force_refresh ) {
			$cached = $this->get_cached_data( 'credit_balance' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Make API request.
		$response = $this->request( 'GET', '/v1/credits/balance' );

		if ( is_wp_error( $response ) ) {
			// Return cached data as fallback if available.
			$cached = get_option( 'writgocms_cached_credit_balance', false );
			if ( false !== $cached ) {
				return $cached;
			}
			return $response;
		}

		// Cache the response.
		$this->cache_data( 'credit_balance', $response );

		return $response;
	}

	/**
	 * Deduct credits for an action
	 *
	 * @param string $action_type Action type.
	 * @param int    $amount      Optional custom amount.
	 * @param array  $metadata    Optional metadata for tracking.
	 * @return array|WP_Error Response data or error.
	 */
	public function deduct_credits( $action_type, $amount = 0, $metadata = array() ) {
		$body = array(
			'action_type' => $action_type,
		);

		if ( $amount > 0 ) {
			$body['amount'] = $amount;
		}

		if ( ! empty( $metadata ) ) {
			$body['metadata'] = $metadata;
		}

		$response = $this->request( 'POST', '/v1/credits/deduct', $body );

		if ( ! is_wp_error( $response ) ) {
			// Invalidate cache after deduction.
			$this->invalidate_cache( 'credit_balance' );
		}

		return $response;
	}

	/**
	 * Get credit history from API
	 *
	 * @param int  $limit  Number of records to retrieve.
	 * @param int  $offset Offset for pagination.
	 * @param bool $force_refresh Force refresh cache.
	 * @return array|WP_Error Credit history data or error.
	 */
	public function get_credit_history( $limit = 50, $offset = 0, $force_refresh = false ) {
		$cache_key = "credit_history_{$limit}_{$offset}";

		// Try to get from cache first unless force refresh.
		if ( ! $force_refresh ) {
			$cached = $this->get_cached_data( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Make API request with query parameters.
		$endpoint = add_query_arg(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			),
			'/v1/credits/history'
		);

		$response = $this->request( 'GET', $endpoint );

		if ( is_wp_error( $response ) ) {
			// Return cached data as fallback if available.
			$cached = get_option( "writgocms_cached_{$cache_key}", false );
			if ( false !== $cached ) {
				return $cached;
			}
			return $response;
		}

		// Cache the response.
		$this->cache_data( $cache_key, $response );

		return $response;
	}

	/**
	 * Get subscription status from API
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array|WP_Error Subscription status data or error.
	 */
	public function get_subscription_status( $force_refresh = false ) {
		// Try to get from cache first unless force refresh.
		if ( ! $force_refresh ) {
			$cached = $this->get_cached_data( 'subscription_status' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Make API request.
		$response = $this->request( 'GET', '/v1/subscriptions/status' );

		if ( is_wp_error( $response ) ) {
			// Return cached data as fallback if available.
			$cached = get_option( 'writgocms_cached_subscription_status', false );
			if ( false !== $cached ) {
				return $cached;
			}
			return $response;
		}

		// Cache the response.
		$this->cache_data( 'subscription_status', $response );

		return $response;
	}

	/**
	 * Make an API request
	 *
	 * @param string $method   HTTP method (GET, POST, PUT, DELETE).
	 * @param string $endpoint API endpoint.
	 * @param array  $body     Request body for POST/PUT requests.
	 * @return array|WP_Error Response data or error.
	 */
	private function request( $method, $endpoint, $body = array() ) {
		// Validate license key.
		if ( empty( $this->license_key ) ) {
			return new WP_Error(
				'INVALID_LICENSE',
				__( 'Licentie sleutel is niet geconfigureerd. Ga naar Instellingen om je licentie te activeren.', 'writgocms' )
			);
		}

		// Build full URL.
		$url = $this->api_url . $endpoint;

		// Prepare headers.
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->license_key,
			'User-Agent'    => 'WritgoCMS/' . WRITGOCMS_VERSION,
		);

		// Prepare arguments.
		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		);

		// Add body for POST/PUT requests.
		if ( in_array( $method, array( 'POST', 'PUT' ), true ) && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		// Make request.
		$response = wp_remote_request( $url, $args );

		// Check for request errors.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'API_ERROR',
				sprintf(
					/* translators: %s: error message */
					__( 'API communicatie fout: %s', 'writgocms' ),
					$response->get_error_message()
				)
			);
		}

		// Get response code and body.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Parse JSON response.
		$data = json_decode( $response_body, true );

		// Handle HTTP errors.
		if ( $response_code >= 400 ) {
			return $this->handle_error_response( $response_code, $data );
		}

		// Return parsed data.
		return $data;
	}

	/**
	 * Handle API error responses
	 *
	 * @param int   $code Response code.
	 * @param array $data Response data.
	 * @return WP_Error
	 */
	private function handle_error_response( $code, $data ) {
		$error_code = isset( $data['error']['code'] ) ? $data['error']['code'] : 'API_ERROR';
		$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : '';

		// Map API error codes to Dutch messages.
		$error_messages = array(
			'INSUFFICIENT_CREDITS'  => __( 'Onvoldoende credits. Upgrade je abonnement of wacht tot je maandelijkse limiet reset.', 'writgocms' ),
			'INVALID_LICENSE'       => __( 'Ongeldige licentie sleutel. Controleer je licentie in de instellingen.', 'writgocms' ),
			'RATE_LIMIT_EXCEEDED'   => __( 'Te veel verzoeken. Probeer het over enkele minuten opnieuw.', 'writgocms' ),
			'SUBSCRIPTION_EXPIRED'  => __( 'Je abonnement is verlopen. Verleng je abonnement om door te gaan.', 'writgocms' ),
			'SUBSCRIPTION_CANCELED' => __( 'Je abonnement is geannuleerd. Heractiveer je abonnement om door te gaan.', 'writgocms' ),
		);

		// Use Dutch message if available, otherwise use API message or generic message.
		if ( isset( $error_messages[ $error_code ] ) ) {
			$message = $error_messages[ $error_code ];
		} elseif ( ! empty( $error_message ) ) {
			$message = $error_message;
		} else {
			$message = sprintf(
				/* translators: %d: HTTP status code */
				__( 'API fout (code: %d). Probeer het later opnieuw.', 'writgocms' ),
				$code
			);
		}

		return new WP_Error( $error_code, $message, array( 'status' => $code ) );
	}

	/**
	 * Cache data in WordPress options
	 *
	 * @param string $key  Cache key.
	 * @param mixed  $data Data to cache.
	 * @return void
	 */
	private function cache_data( $key, $data ) {
		$cache_data = array(
			'data'       => $data,
			'expires_at' => time() + $this->cache_duration,
		);
		update_option( "writgocms_cached_{$key}", $cache_data, false );
	}

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached data or false if not found/expired.
	 */
	private function get_cached_data( $key ) {
		$cache_data = get_option( "writgocms_cached_{$key}", false );

		if ( false === $cache_data ) {
			return false;
		}

		// Check if cache has expired.
		if ( isset( $cache_data['expires_at'] ) && time() > $cache_data['expires_at'] ) {
			return false;
		}

		return isset( $cache_data['data'] ) ? $cache_data['data'] : false;
	}

	/**
	 * Invalidate cache
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	private function invalidate_cache( $key ) {
		delete_option( "writgocms_cached_{$key}" );
	}

	/**
	 * Invalidate all caches
	 *
	 * @return void
	 */
	public function invalidate_all_caches() {
		$cache_keys = array(
			'credit_balance',
			'subscription_status',
		);

		foreach ( $cache_keys as $key ) {
			$this->invalidate_cache( $key );
		}

		// Also clear credit history caches (pattern match).
		// Use WordPress options API with pattern matching.
		global $wpdb;
		$pattern = $wpdb->esc_like( 'writgocms_cached_credit_history_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
	}
}

/**
 * Get API Client instance
 *
 * @return WritgoCMS_API_Client
 */
function writgocms_api_client() {
	return WritgoCMS_API_Client::get_instance();
}
