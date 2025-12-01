<?php
/**
 * Authentication Manager Class
 *
 * Handles user authentication with the WritgoAI API server using email/password.
 * Manages Bearer token storage, refresh, and logout functionality.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Auth_Manager
 */
class WritgoCMS_Auth_Manager {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Auth_Manager
	 */
	private static $instance = null;

	/**
	 * API Base URL
	 *
	 * @var string
	 */
	private $api_base_url;

	/**
	 * Token option key
	 *
	 * @var string
	 */
	private $token_option = 'writgocms_auth_token';

	/**
	 * User option key
	 *
	 * @var string
	 */
	private $user_option = 'writgocms_auth_user';

	/**
	 * Token expiry option key
	 *
	 * @var string
	 */
	private $expiry_option = 'writgocms_token_expiry';

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Auth_Manager
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
		$this->api_base_url = get_option( 'writgocms_api_url', 'https://api.writgoai.com' );

		// AJAX handlers for authentication.
		add_action( 'wp_ajax_writgocms_login', array( $this, 'ajax_login' ) );
		add_action( 'wp_ajax_writgocms_logout', array( $this, 'ajax_logout' ) );
		add_action( 'wp_ajax_writgocms_check_auth', array( $this, 'ajax_check_auth' ) );

		// Admin notices for authentication status.
		add_action( 'admin_notices', array( $this, 'display_auth_notices' ) );

		// Auto-refresh token on admin pages if near expiry.
		add_action( 'admin_init', array( $this, 'maybe_refresh_token' ) );
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
	 * Login with email and password
	 *
	 * @param string $email    User email.
	 * @param string $password User password.
	 * @return array|WP_Error Login result or error.
	 */
	public function login( $email, $password ) {
		// Validate inputs.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Geldig e-mailadres is verplicht.', 'writgocms' ) );
		}

		if ( empty( $password ) ) {
			return new WP_Error( 'invalid_password', __( 'Wachtwoord is verplicht.', 'writgocms' ) );
		}

		// Make login request to API.
		$response = wp_remote_post(
			$this->api_base_url . '/v1/auth/login',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'email'    => $email,
						'password' => $password,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'login_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Login mislukt: %s', 'writgocms' ),
					$response->get_error_message()
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$error_message = isset( $body['message'] ) ? $body['message'] : __( 'Login mislukt. Controleer je inloggegevens.', 'writgocms' );
			return new WP_Error( 'login_failed', $error_message );
		}

		// Validate response data.
		if ( empty( $body['token'] ) || empty( $body['user'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Ongeldige server response.', 'writgocms' ) );
		}

		// Encrypt and store token.
		$encrypted_token = $this->encrypt_token( $body['token'] );
		update_option( $this->token_option, $encrypted_token, false );

		// Store user data.
		$user_data = array(
			'id'      => isset( $body['user']['id'] ) ? $body['user']['id'] : '',
			'email'   => isset( $body['user']['email'] ) ? $body['user']['email'] : $email,
			'name'    => isset( $body['user']['name'] ) ? $body['user']['name'] : '',
			'company' => isset( $body['user']['company'] ) ? $body['user']['company'] : '',
		);
		update_option( $this->user_option, $user_data, false );

		// Store token expiry (default 24 hours if not provided).
		$expiry_timestamp = isset( $body['expires_at'] ) ? strtotime( $body['expires_at'] ) : time() + DAY_IN_SECONDS;
		update_option( $this->expiry_option, $expiry_timestamp, false );

		return array(
			'success' => true,
			'message' => __( 'Login succesvol!', 'writgocms' ),
			'user'    => $user_data,
		);
	}

	/**
	 * Logout user
	 *
	 * @return array|WP_Error Logout result.
	 */
	public function logout() {
		$token = $this->get_token();

		// Call logout endpoint if we have a token.
		if ( ! empty( $token ) ) {
			wp_remote_post(
				$this->api_base_url . '/v1/auth/logout',
				array(
					'timeout' => 10,
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);
		}

		// Clear stored authentication data.
		delete_option( $this->token_option );
		delete_option( $this->user_option );
		delete_option( $this->expiry_option );

		return array(
			'success' => true,
			'message' => __( 'Je bent uitgelogd.', 'writgocms' ),
		);
	}

	/**
	 * Get stored token (decrypted)
	 *
	 * @return string|null Token or null if not found/expired.
	 */
	public function get_token() {
		// Check if token exists and is not expired.
		if ( ! $this->is_authenticated() ) {
			return null;
		}

		$encrypted_token = get_option( $this->token_option, '' );
		if ( empty( $encrypted_token ) ) {
			return null;
		}

		return $this->decrypt_token( $encrypted_token );
	}

	/**
	 * Check if user is authenticated
	 *
	 * @return bool True if authenticated and token is valid.
	 */
	public function is_authenticated() {
		$encrypted_token = get_option( $this->token_option, '' );
		$expiry          = get_option( $this->expiry_option, 0 );

		if ( empty( $encrypted_token ) || empty( $expiry ) ) {
			return false;
		}

		// Check if token has expired.
		if ( time() >= $expiry ) {
			// Token expired, clean up.
			$this->logout();
			return false;
		}

		return true;
	}

	/**
	 * Get current user data
	 *
	 * @return array|null User data or null if not authenticated.
	 */
	public function get_current_user() {
		if ( ! $this->is_authenticated() ) {
			return null;
		}

		return get_option( $this->user_option, null );
	}

	/**
	 * Get authorization header for API requests
	 *
	 * @return array|WP_Error Authorization header or error.
	 */
	public function get_auth_header() {
		$token = $this->get_token();

		if ( empty( $token ) ) {
			return new WP_Error( 'not_authenticated', __( 'Niet ingelogd. Log in om door te gaan.', 'writgocms' ) );
		}

		return array(
			'Authorization' => 'Bearer ' . $token,
		);
	}

	/**
	 * Refresh token if near expiry
	 *
	 * Automatically refreshes the token if it's within 1 hour of expiry.
	 *
	 * @return bool True if refreshed successfully or not needed, false on failure.
	 */
	public function maybe_refresh_token() {
		if ( ! $this->is_authenticated() ) {
			return false;
		}

		$expiry = get_option( $this->expiry_option, 0 );

		// Refresh if within 1 hour of expiry.
		if ( time() < ( $expiry - HOUR_IN_SECONDS ) ) {
			return true; // Not needed yet.
		}

		return $this->refresh_token();
	}

	/**
	 * Refresh authentication token
	 *
	 * @return bool True if refreshed successfully, false on failure.
	 */
	private function refresh_token() {
		$token = $this->get_token();

		if ( empty( $token ) ) {
			return false;
		}

		// Make refresh request to API.
		$response = wp_remote_post(
			$this->api_base_url . '/v1/auth/refresh',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code || empty( $body['token'] ) ) {
			return false;
		}

		// Store new token.
		$encrypted_token = $this->encrypt_token( $body['token'] );
		update_option( $this->token_option, $encrypted_token, false );

		// Update expiry.
		$expiry_timestamp = isset( $body['expires_at'] ) ? strtotime( $body['expires_at'] ) : time() + DAY_IN_SECONDS;
		update_option( $this->expiry_option, $expiry_timestamp, false );

		return true;
	}

	/**
	 * Encrypt token for storage
	 *
	 * @param string $token Token to encrypt.
	 * @return string Encrypted token.
	 */
	private function encrypt_token( $token ) {
		// Use WordPress salts for encryption key.
		$key = wp_salt( 'auth' );

		// Simple encryption using base64 and XOR (WordPress doesn't have built-in encryption).
		// For production, consider using a proper encryption library.
		$encrypted = base64_encode( $token ^ $key );

		return $encrypted;
	}

	/**
	 * Decrypt token from storage
	 *
	 * @param string $encrypted_token Encrypted token.
	 * @return string Decrypted token.
	 */
	private function decrypt_token( $encrypted_token ) {
		// Use WordPress salts for encryption key.
		$key = wp_salt( 'auth' );

		// Decrypt using base64 and XOR.
		$decrypted = base64_decode( $encrypted_token ) ^ $key;

		return $decrypted;
	}

	/**
	 * AJAX handler for login
	 */
	public function ajax_login() {
		check_ajax_referer( 'writgocms_auth_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgocms' ) ) );
		}

		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		$result = $this->login( $email, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for logout
	 */
	public function ajax_logout() {
		check_ajax_referer( 'writgocms_auth_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgocms' ) ) );
		}

		$result = $this->logout();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for checking authentication status
	 */
	public function ajax_check_auth() {
		check_ajax_referer( 'writgocms_auth_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgocms' ) ) );
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

	/**
	 * Display authentication notices in admin
	 */
	public function display_auth_notices() {
		// Only show on plugin pages.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'writgocms' ) === false ) {
			return;
		}

		if ( ! $this->is_authenticated() ) {
			echo '<div class="notice notice-warning is-dismissible">';
			echo '<p><strong>WritgoAI:</strong> ';
			echo esc_html__( 'Log in om WritgoAI te gebruiken.', 'writgocms' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=writgocms-wizard' ) ) . '">';
			echo esc_html__( 'Inloggen', 'writgocms' );
			echo '</a></p></div>';
		}
	}
}

// Initialize.
WritgoCMS_Auth_Manager::get_instance();
