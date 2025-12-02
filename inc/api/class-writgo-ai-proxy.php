<?php
/**
 * AI Proxy Class
 *
 * Server-side proxy for secure API communication with AI providers.
 * All requests go through this proxy to keep API keys secure.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_AI_Proxy
 */
class WritgoAI_AI_Proxy {

	/**
	 * Instance
	 *
	 * @var WritgoAI_AI_Proxy
	 */
	private static $instance = null;

	/**
	 * AI API Base URL
	 *
	 * @var string
	 */
	private $api_base_url = 'https://api.writgo.nl';

	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	private $namespace = 'writgo/v1';

	/**
	 * Database table name
	 *
	 * @var string
	 */
	private $usage_table;

	/**
	 * Rate limits per license tier (requests per day)
	 *
	 * @var array
	 */
	private $rate_limits = array(
		'trial'        => 50,
		'starter'      => 100,
		'professional' => 500,
		'business'     => 2000,
		'enterprise'   => 10000,
		'admin'        => PHP_INT_MAX, // Unlimited for admins
	);

	/**
	 * Get instance
	 *
	 * @return WritgoAI_AI_Proxy
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
		$this->usage_table = $wpdb->prefix . 'writgo_api_usage';

		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->namespace,
			'/aiml-proxy',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_proxy_request' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'action' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'generate_text', 'generate_image' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'prompt' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'model'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/usage',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_usage_stats' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Check if user has permission to use the API
	 *
	 * @return bool|WP_Error
	 */
	public function check_permissions() {
		// User must be logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				__( 'Je moet ingelogd zijn om deze functie te gebruiken.', 'writgoai' ),
				array( 'status' => 401 )
			);
		}

		// User must have edit_posts capability.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'forbidden',
				__( 'Je hebt geen toestemming om deze functie te gebruiken.', 'writgoai' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handle proxy request
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_proxy_request( $request ) {
		// Validate license.
		$license_check = $this->validate_license();
		if ( is_wp_error( $license_check ) ) {
			return $license_check;
		}

		// Check rate limit.
		$rate_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		// Get API key from server configuration.
		$api_key = $this->get_server_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error(
				'configuration_error',
				__( 'De AI service is niet correct geconfigureerd. Neem contact op met de beheerder.', 'writgoai' ),
				array( 'status' => 500 )
			);
		}

		$action = $request->get_param( 'action' );
		$prompt = $request->get_param( 'prompt' );
		$model  = $request->get_param( 'model' );

		// Process the request based on action type.
		if ( 'generate_text' === $action ) {
			$result = $this->generate_text( $api_key, $prompt, $model, $request );
		} elseif ( 'generate_image' === $action ) {
			$result = $this->generate_image( $api_key, $prompt, $model, $request );
		} else {
			return new WP_Error(
				'invalid_action',
				__( 'Ongeldige actie opgegeven.', 'writgoai' ),
				array( 'status' => 400 )
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Track usage after successful request.
		$this->track_usage();

		// Get updated usage for headers.
		$usage = $this->get_user_usage();
		$limit = $this->get_user_rate_limit();

		$response = new WP_REST_Response( $result, 200 );
		$response->header( 'X-RateLimit-Limit', $limit );
		$response->header( 'X-RateLimit-Remaining', max( 0, $limit - $usage['request_count'] ) );
		$response->header( 'X-RateLimit-Reset', $this->get_reset_timestamp() );

		return $response;
	}

	/**
	 * Validate user license
	 *
	 * @return bool|WP_Error
	 */
	private function validate_license() {
		// Admin users always have access for testing.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ! class_exists( 'WritgoAI_License_Manager' ) ) {
			return new WP_Error(
				'license_error',
				__( 'Licentiesysteem niet beschikbaar.', 'writgoai' ),
				array( 'status' => 500 )
			);
		}

		$license_manager = WritgoAI_License_Manager::get_instance();

		if ( ! $license_manager->is_license_valid() ) {
			return new WP_Error(
				'invalid_license',
				__( 'Je licentie is niet geldig. Neem contact op met support.', 'writgoai' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if user has exceeded rate limit
	 *
	 * @return bool|WP_Error
	 */
	private function check_rate_limit() {
		$usage = $this->get_user_usage();
		$limit = $this->get_user_rate_limit();

		// Check if we need to reset (new day).
		if ( $usage && $this->should_reset_usage( $usage['last_reset_date'] ) ) {
			$this->reset_user_usage();
			$usage = $this->get_user_usage();
		}

		$current_count = $usage ? (int) $usage['request_count'] : 0;

		if ( $current_count >= $limit ) {
			$reset_time = $this->get_reset_timestamp();

			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Je hebt je dagelijkse limiet bereikt. Probeer het morgen opnieuw.', 'writgoai' ),
				array(
					'status'      => 429,
					'retry_after' => gmdate( 'c', $reset_time ),
				)
			);
		}

		return true;
	}

	/**
	 * Get user's current usage stats
	 *
	 * @return array|null
	 */
	private function get_user_usage() {
		global $wpdb;
		$user_id = get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$usage = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->usage_table} WHERE user_id = %d",
				$user_id
			),
			ARRAY_A
		);

		return $usage;
	}

	/**
	 * Get user's rate limit based on license tier
	 *
	 * @return int
	 */
	private function get_user_rate_limit() {
		// Admin users have unlimited access.
		if ( current_user_can( 'manage_options' ) ) {
			return $this->rate_limits['admin'];
		}

		// Get license tier from license manager.
		if ( class_exists( 'WritgoAI_License_Manager' ) ) {
			$license_manager = WritgoAI_License_Manager::get_instance();
			$status          = $license_manager->get_license_status();

			$plan = isset( $status['plan'] ) ? strtolower( $status['plan'] ) : 'starter';

			if ( isset( $this->rate_limits[ $plan ] ) ) {
				return $this->rate_limits[ $plan ];
			}
		}

		// Default to starter limit.
		return $this->rate_limits['starter'];
	}

	/**
	 * Check if usage should be reset (new day)
	 *
	 * @param string $last_reset_date Last reset date.
	 * @return bool
	 */
	private function should_reset_usage( $last_reset_date ) {
		if ( empty( $last_reset_date ) ) {
			return true;
		}

		$last_reset = strtotime( $last_reset_date );
		$today      = strtotime( gmdate( 'Y-m-d' ) );

		return $last_reset < $today;
	}

	/**
	 * Reset user's daily usage
	 */
	private function reset_user_usage() {
		global $wpdb;
		$user_id = get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$this->usage_table,
			array(
				'request_count'   => 0,
				'last_reset_date' => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'user_id' => $user_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Track API usage for current user
	 */
	private function track_usage() {
		global $wpdb;
		$user_id     = get_current_user_id();
		$license_key = $this->get_user_license_key();

		$usage = $this->get_user_usage();

		if ( $usage ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->usage_table,
				array(
					'request_count' => (int) $usage['request_count'] + 1,
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'user_id' => $user_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->usage_table,
				array(
					'user_id'         => $user_id,
					'license_key'     => $license_key,
					'request_count'   => 1,
					'last_reset_date' => current_time( 'mysql' ),
					'created_at'      => current_time( 'mysql' ),
					'updated_at'      => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Get user's license key
	 *
	 * @return string
	 */
	private function get_user_license_key() {
		if ( class_exists( 'WritgoAI_License_Manager' ) ) {
			$license_manager = WritgoAI_License_Manager::get_instance();
			return $license_manager->get_license_key();
		}
		return '';
	}

	/**
	 * Get reset timestamp (next midnight UTC)
	 *
	 * @return int
	 */
	private function get_reset_timestamp() {
		return strtotime( 'tomorrow midnight', current_time( 'timestamp' ) );
	}

	/**
	 * Get server API key from environment or wp-config
	 *
	 * @return string
	 */
	private function get_server_api_key() {
		// First check wp-config.php constant.
		if ( defined( 'WRITGO_AI_API_KEY' ) && WRITGO_AI_API_KEY ) {
			return WRITGO_AI_API_KEY;
		}

		// Then check environment variable.
		$env_key = getenv( 'WRITGO_AI_API_KEY' );
		if ( $env_key ) {
			return $env_key;
		}

		// Then try to get from license manager (injected key).
		if ( class_exists( 'WritgoAI_License_Manager' ) ) {
			$license_manager = WritgoAI_License_Manager::get_instance();
			$injected_key    = $license_manager->get_injected_api_key();

			if ( ! is_wp_error( $injected_key ) && ! empty( $injected_key ) ) {
				return $injected_key;
			}
		}

		return '';
	}

	/**
	 * Generate text using AI API
	 *
	 * @param string          $api_key API key.
	 * @param string          $prompt  Prompt text.
	 * @param string|null     $model   Model to use.
	 * @param WP_REST_Request $request Original request.
	 * @return array|WP_Error
	 */
	private function generate_text( $api_key, $prompt, $model, $request ) {
		if ( null === $model || empty( $model ) ) {
			$model = get_option( 'writgoai_default_model', 'gpt-4o' );
		}

		$temperature = (float) get_option( 'writgoai_text_temperature', 0.7 );
		$max_tokens  = (int) get_option( 'writgoai_text_max_tokens', 1000 );

		$response = wp_remote_post(
			$this->api_base_url . '/chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'messages'    => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'temperature' => $temperature,
						'max_tokens'  => $max_tokens,
					)
				),
			)
		);

		return $this->process_api_response( $response, 'text', $model );
	}

	/**
	 * Generate image using AI API
	 *
	 * @param string          $api_key API key.
	 * @param string          $prompt  Prompt text.
	 * @param string|null     $model   Model to use.
	 * @param WP_REST_Request $request Original request.
	 * @return array|WP_Error
	 */
	private function generate_image( $api_key, $prompt, $model, $request ) {
		if ( null === $model || empty( $model ) ) {
			$model = get_option( 'writgoai_default_image_model', 'dall-e-3' );
		}

		$size    = get_option( 'writgoai_image_size', '1024x1024' );
		$quality = get_option( 'writgoai_image_quality', 'standard' );

		$body_params = array(
			'model'  => $model,
			'prompt' => $prompt,
			'n'      => 1,
			'size'   => $size,
		);

		if ( 'dall-e-3' === $model ) {
			$body_params['quality'] = $quality;
		}

		$response = wp_remote_post(
			$this->api_base_url . '/images/generations',
			array(
				'timeout' => 120,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body_params ),
			)
		);

		return $this->process_api_response( $response, 'image', $model );
	}

	/**
	 * Process API response
	 *
	 * @param array|WP_Error $response API response.
	 * @param string         $type     Type (text or image).
	 * @param string         $model    Model used.
	 * @return array|WP_Error
	 */
	private function process_api_response( $response, $type, $model ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				__( 'Er is een fout opgetreden bij het genereren van content. Probeer het opnieuw.', 'writgoai' ),
				array( 'status' => 502 )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$error_message = isset( $body['error']['message'] )
				? $body['error']['message']
				: __( 'Er is een fout opgetreden bij de AI service.', 'writgoai' );

			return new WP_Error(
				'api_error',
				$error_message,
				array( 'status' => $code )
			);
		}

		if ( 'text' === $type ) {
			if ( isset( $body['choices'][0]['message']['content'] ) ) {
				$this->track_usage_stats( $type, $model );

				return array(
					'success' => true,
					'content' => $body['choices'][0]['message']['content'],
					'model'   => $model,
					'usage'   => isset( $body['usage'] ) ? $body['usage'] : array(),
				);
			}
		} elseif ( 'image' === $type ) {
			if ( isset( $body['data'][0]['url'] ) ) {
				$image_url = $body['data'][0]['url'];
				$image_title = isset( $body['data'][0]['revised_prompt'] ) ? $body['data'][0]['revised_prompt'] : '';
				$image_title = is_string( $image_title ) ? substr( $image_title, 0, 100 ) : '';
				$saved     = $this->save_image_to_media_library( $image_url, $image_title );

				$this->track_usage_stats( $type, $model );

				if ( is_wp_error( $saved ) ) {
					return array(
						'success'    => true,
						'image_url'  => $image_url,
						'model'      => $model,
						'saved'      => false,
						'save_error' => $saved->get_error_message(),
					);
				}

				return array(
					'success'       => true,
					'image_url'     => wp_get_attachment_url( $saved ),
					'attachment_id' => $saved,
					'model'         => $model,
					'saved'         => true,
				);
			}
		}

		return new WP_Error(
			'invalid_response',
			__( 'Ongeldige respons van de AI service.', 'writgoai' ),
			array( 'status' => 502 )
		);
	}

	/**
	 * Save image to media library
	 *
	 * @param string $image_url Image URL.
	 * @param string $title     Image title.
	 * @return int|WP_Error
	 */
	private function save_image_to_media_library( $image_url, $title = '' ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = array(
			'name'     => 'ai-generated-' . wp_unique_id() . '.png',
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, 0, sanitize_text_field( $title ) );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return $attachment_id;
		}

		return $attachment_id;
	}

	/**
	 * Track usage statistics (for analytics)
	 *
	 * @param string $type  Type (text/image).
	 * @param string $model Model used.
	 */
	private function track_usage_stats( $type, $model ) {
		$stats = get_option( 'writgoai_ai_usage_stats', array() );
		$date  = gmdate( 'Y-m-d' );

		if ( ! isset( $stats[ $date ] ) ) {
			$stats[ $date ] = array();
		}

		if ( ! isset( $stats[ $date ][ $type ] ) ) {
			$stats[ $date ][ $type ] = array();
		}

		if ( ! isset( $stats[ $date ][ $type ][ $model ] ) ) {
			$stats[ $date ][ $type ][ $model ] = 0;
		}

		$stats[ $date ][ $type ][ $model ]++;

		// Keep only last 30 days.
		$cutoff = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		foreach ( array_keys( $stats ) as $stat_date ) {
			if ( $stat_date < $cutoff ) {
				unset( $stats[ $stat_date ] );
			}
		}

		update_option( 'writgoai_ai_usage_stats', $stats );
	}

	/**
	 * Get usage stats REST endpoint handler
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_usage_stats( $request ) {
		$usage = $this->get_user_usage();
		$limit = $this->get_user_rate_limit();

		$request_count = $usage ? (int) $usage['request_count'] : 0;

		// Check if we need to reset.
		if ( $usage && $this->should_reset_usage( $usage['last_reset_date'] ) ) {
			$request_count = 0;
		}

		$response_data = array(
			'requests_used'      => $request_count,
			'requests_remaining' => max( 0, $limit - $request_count ),
			'daily_limit'        => $limit,
			'reset_at'           => gmdate( 'c', $this->get_reset_timestamp() ),
			'service_active'     => ! empty( $this->get_server_api_key() ),
		);

		$response = new WP_REST_Response( $response_data, 200 );
		$response->header( 'X-RateLimit-Limit', $limit );
		$response->header( 'X-RateLimit-Remaining', max( 0, $limit - $request_count ) );
		$response->header( 'X-RateLimit-Reset', $this->get_reset_timestamp() );

		return $response;
	}
}

// Initialize.
WritgoAI_AI_Proxy::get_instance();
