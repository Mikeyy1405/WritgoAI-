<?php
/**
 * Authentication Manager Class
 *
 * Handles user authentication using WordPress logged-in user.
 * Automatically authenticates with API server using WordPress credentials.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Auth_Manager
 */
class WritgoAI_Auth_Manager {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Auth_Manager
	 */
	private static $instance = null;

	/**
	 * API Base URL
	 *
	 * @var string
	 */
	private $api_base_url;

	/**
	 * API Token option key
	 *
	 * @var string
	 */
	private $token_option = 'writgoai_api_token';

	/**
	 * License data option key
	 *
	 * @var string
	 */
	private $license_option = 'writgoai_license_data';

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Auth_Manager
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
		$this->api_base_url = get_option( 'writgoai_api_url', 'https://api.writgo.nl' );

		// AJAX handlers for backward compatibility.
		add_action( 'wp_ajax_writgoai_check_auth', array( $this, 'ajax_check_auth' ) );
		
		// Auto-authenticate superuser on admin init.
		add_action( 'admin_init', array( $this, 'maybe_auto_authenticate' ) );
	}

	/**
	 * Set API base URL
	 *
	 * @param string $url API base URL.
	 * @return void
	 */
	public function set_api_base_url( $url ) {
		$this->api_base_url = rtrim( $url, '/' );
	}

	/**
	 * Check if user is authenticated (logged into WordPress)
	 *
	 * @return bool True if user is logged into WordPress and has manage_options capability.
	 */
	public function is_authenticated() {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/**
	 * Get current WordPress user
	 *
	 * @return array|null User data or null if not authenticated.
	 */
	public function get_current_user() {
		if ( ! $this->is_authenticated() ) {
			return null;
		}

		$wp_user = wp_get_current_user();
		return array(
			'id'    => $wp_user->ID,
			'email' => $wp_user->user_email,
			'name'  => $wp_user->display_name,
		);
	}

	/**
	 * Get authentication data for API calls
	 * Uses WordPress user email + site URL as identifier
	 *
	 * @return array|null Authentication data or null if not authenticated.
	 */
	public function get_api_auth() {
		$user = $this->get_current_user();
		if ( ! $user ) {
			return null;
		}

		return array(
			'wp_user_id'    => $user['id'],
			'wp_user_email' => $user['email'],
			'wp_site_url'   => home_url(),
			'wp_site_name'  => get_bloginfo( 'name' ),
		);
	}

	/**
	 * Generate a secure token for API authentication
	 * Based on WordPress user + site + secret
	 *
	 * @return string|null Token or null if not authenticated.
	 */
	public function get_api_token() {
		$user = $this->get_current_user();
		if ( ! $user ) {
			return null;
		}

		// Create a hash based on user email, site URL, and WordPress auth salt.
		// This is a deterministic token that can be validated.
		$data  = $user['email'] . '|' . home_url();
		$token = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );

		return $token;
	}

	/**
	 * Get stored API token from WordPress options
	 *
	 * @return string|null Token or null if not found.
	 */
	public function get_token() {
		if ( ! $this->is_authenticated() ) {
			return null;
		}

		return get_option( $this->token_option, null );
	}

	/**
	 * Get API key from stored token (alias for get_token for compatibility)
	 *
	 * @return string|null API key or null if not found.
	 */
	public function get_api_key() {
		return $this->get_token();
	}

	/**
	 * Check if current user is superuser
	 *
	 * Superuser is determined by email address matching the configured superuser email.
	 * Default superuser email is 'info@writgo.nl' but can be overridden via constant or filter.
	 *
	 * @return bool True if current user is superuser.
	 */
	public function is_superuser() {
		if ( ! $this->is_authenticated() ) {
			return false;
		}

		$user = $this->get_current_user();
		if ( ! $user ) {
			return false;
		}

		// Allow configuration via constant.
		$superuser_email = defined( 'WRITGOAI_SUPERUSER_EMAIL' ) ? WRITGOAI_SUPERUSER_EMAIL : 'info@writgo.nl';

		// Allow filtering the superuser email.
		$superuser_email = apply_filters( 'writgoai_superuser_email', $superuser_email );

		return strtolower( $user['email'] ) === strtolower( $superuser_email );
	}

	/**
	 * Check if we have a valid API session
	 *
	 * @return bool True if we have a valid API token stored.
	 */
	public function has_valid_session() {
		$token = get_option( $this->token_option, '' );
		return ! empty( $token );
	}

	/**
	 * Authenticate with API using WordPress credentials
	 *
	 * @return bool True if authentication succeeded, false otherwise.
	 */
	public function authenticate_with_api() {
		if ( ! $this->is_authenticated() ) {
			return false;
		}

		return $this->ensure_admin_has_license();
	}

	/**
	 * Ensure admin has license in API
	 * Automatically creates/links a user and license in the API
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function ensure_admin_has_license() {
		$user = $this->get_current_user();
		if ( ! $user ) {
			return false;
		}

		// Superuser bypass - no external API call needed.
		if ( $this->is_superuser() ) {
			// Generate a local token for superuser.
			$token = $this->generate_superuser_token();
			update_option( $this->token_option, $token, false );
			update_option( $this->license_option, array(
				'status' => 'active',
				'plan'   => 'superuser',
				'email'  => $user['email'],
			), false );
			return true;
		}

		// Call API to ensure user has license.
		$response = wp_remote_post(
			$this->api_base_url . '/v1/auth/wordpress',
			array(
				'timeout' => 30,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'wp_user_id'    => $user['id'],
						'wp_user_email' => $user['email'],
						'wp_user_name'  => $user['name'],
						'wp_site_url'   => home_url(),
						'wp_site_name'  => get_bloginfo( 'name' ),
						'is_admin'      => current_user_can( 'manage_options' ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Log error for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WritgoAI Auth: API authentication failed - ' . $response->get_error_message() );
			}
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code && 201 !== $code ) {
			// Log error for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$error_message = isset( $body['message'] ) ? $body['message'] : 'Unknown error';
				error_log( 'WritgoAI Auth: API authentication failed - HTTP ' . $code . ': ' . $error_message );
			}
			return false;
		}

		if ( isset( $body['success'] ) && $body['success'] ) {
			// Store the token for future API calls.
			if ( isset( $body['token'] ) ) {
				update_option( $this->token_option, $body['token'], false );
			}
			// Store license data.
			if ( isset( $body['license'] ) ) {
				update_option( $this->license_option, $body['license'], false );
			}
			return true;
		}

		return false;
	}

	/**
	 * Generate a superuser token
	 *
	 * @return string|null Superuser token or null if user not found.
	 */
	private function generate_superuser_token() {
		$user = $this->get_current_user();
		if ( ! $user ) {
			return null;
		}
		return hash_hmac( 'sha256', $user['email'] . '|' . home_url() . '|superuser', wp_salt( 'auth' ) );
	}

	/**
	 * Maybe auto-authenticate superuser on admin init
	 *
	 * @return void
	 */
	public function maybe_auto_authenticate() {
		// Check if user is logged into WordPress with manage_options capability.
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// If superuser and no valid session, auto-authenticate.
		$user = $this->get_current_user();
		if ( $user && $this->is_superuser() && ! $this->has_valid_session() ) {
			$this->ensure_admin_has_license();
		}
	}

	/**
	 * Get authorization header for API requests
	 *
	 * @return array|WP_Error Authorization header or error.
	 */
	public function get_auth_header() {
		$token = $this->get_token();

		if ( empty( $token ) ) {
			return new WP_Error( 'not_authenticated', __( 'Niet ingelogd. Log in om door te gaan.', 'writgoai' ) );
		}

		return array(
			'Authorization' => 'Bearer ' . $token,
		);
	}

	/**
	 * AJAX handler for checking authentication status
	 */
	public function ajax_check_auth() {
		check_ajax_referer( 'writgoai_auth_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgoai' ) ) );
		}

		$is_authenticated = $this->is_authenticated();
		$user             = $this->get_current_user();

		wp_send_json_success(
			array(
				'authenticated' => $is_authenticated,
				'user'          => $user,
			)
		);
	}
}

// Initialize.
WritgoAI_Auth_Manager::get_instance();
