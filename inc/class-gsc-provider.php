<?php
/**
 * Google Search Console Provider Class
 *
 * Handles OAuth 2.0 authentication and API communication with Google Search Console.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_GSC_Provider
 */
class WritgoCMS_GSC_Provider {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_GSC_Provider
	 */
	private static $instance = null;

	/**
	 * Google API OAuth endpoints
	 *
	 * @var string
	 */
	private $oauth_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';

	/**
	 * Google API token endpoint
	 *
	 * @var string
	 */
	private $oauth_token_url = 'https://oauth2.googleapis.com/token';

	/**
	 * Google Search Console API base URL
	 *
	 * @var string
	 */
	private $api_base_url = 'https://www.googleapis.com/webmasters/v3';

	/**
	 * Required OAuth scopes
	 *
	 * @var array
	 */
	private $scopes = array(
		'https://www.googleapis.com/auth/webmasters.readonly',
	);

	/**
	 * Rate limit option name
	 *
	 * @var string
	 */
	private $rate_limit_option = 'writgocms_gsc_rate_limits';

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_GSC_Provider
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
		add_action( 'wp_ajax_writgocms_gsc_auth', array( $this, 'ajax_handle_auth' ) );
		add_action( 'wp_ajax_writgocms_gsc_disconnect', array( $this, 'ajax_disconnect' ) );
		add_action( 'wp_ajax_writgocms_gsc_fetch_data', array( $this, 'ajax_fetch_data' ) );
		add_action( 'wp_ajax_writgocms_gsc_get_sites', array( $this, 'ajax_get_sites' ) );
		add_action( 'wp_ajax_writgocms_gsc_select_site', array( $this, 'ajax_select_site' ) );
		add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
	}

	/**
	 * Get OAuth client ID
	 *
	 * @return string
	 */
	public function get_client_id() {
		return get_option( 'writgocms_gsc_client_id', '' );
	}

	/**
	 * Get OAuth client secret
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return get_option( 'writgocms_gsc_client_secret', '' );
	}

	/**
	 * Get access token
	 *
	 * @return string
	 */
	public function get_access_token() {
		return get_option( 'writgocms_gsc_access_token', '' );
	}

	/**
	 * Get refresh token
	 *
	 * @return string
	 */
	public function get_refresh_token() {
		return get_option( 'writgocms_gsc_refresh_token', '' );
	}

	/**
	 * Get token expiry time
	 *
	 * @return int
	 */
	public function get_token_expiry() {
		return (int) get_option( 'writgocms_gsc_token_expiry', 0 );
	}

	/**
	 * Get selected site URL
	 *
	 * @return string
	 */
	public function get_selected_site() {
		return get_option( 'writgocms_gsc_selected_site', '' );
	}

	/**
	 * Check if connected to GSC
	 *
	 * @return bool
	 */
	public function is_connected() {
		$access_token  = $this->get_access_token();
		$refresh_token = $this->get_refresh_token();
		return ! empty( $access_token ) && ! empty( $refresh_token );
	}

	/**
	 * Check if token is expired
	 *
	 * @return bool
	 */
	public function is_token_expired() {
		$expiry = $this->get_token_expiry();
		return $expiry > 0 && time() >= $expiry;
	}

	/**
	 * Get OAuth redirect URI
	 *
	 * @return string
	 */
	public function get_redirect_uri() {
		return admin_url( 'admin.php?page=writgocms-gsc' );
	}

	/**
	 * Get OAuth authorization URL
	 *
	 * @return string
	 */
	public function get_auth_url() {
		$client_id = $this->get_client_id();
		if ( empty( $client_id ) ) {
			return '';
		}

		$state = wp_create_nonce( 'writgocms_gsc_oauth' );
		update_option( 'writgocms_gsc_oauth_state', $state );

		$params = array(
			'client_id'     => $client_id,
			'redirect_uri'  => $this->get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => implode( ' ', $this->scopes ),
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => $state,
		);

		return $this->oauth_auth_url . '?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth callback
	 */
	public function handle_oauth_callback() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'writgocms-gsc' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['code'] ) ) {
			return;
		}

		// Verify user has permission to manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// OAuth state parameter serves as CSRF protection.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$saved_state = get_option( 'writgocms_gsc_oauth_state', '' );

		if ( empty( $state ) || $state !== $saved_state ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Ongeldige OAuth state. Probeer opnieuw.', 'writgocms' ) . '</p></div>';
			} );
			return;
		}

		delete_option( 'writgocms_gsc_oauth_state' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		$result = $this->exchange_code_for_tokens( $code );

		if ( is_wp_error( $result ) ) {
			add_action( 'admin_notices', function() use ( $result ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} );
			return;
		}

		// Redirect to remove code from URL.
		wp_safe_redirect( admin_url( 'admin.php?page=writgocms-gsc&connected=1' ) );
		exit;
	}

	/**
	 * Exchange authorization code for tokens
	 *
	 * @param string $code Authorization code.
	 * @return array|WP_Error
	 */
	public function exchange_code_for_tokens( $code ) {
		$response = wp_remote_post(
			$this->oauth_token_url,
			array(
				'timeout' => 30,
				'body'    => array(
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'code'          => $code,
					'grant_type'    => 'authorization_code',
					'redirect_uri'  => $this->get_redirect_uri(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			$error_message = isset( $body['error_description'] ) ? $body['error_description'] : $body['error'];
			return new WP_Error( 'oauth_error', $error_message );
		}

		if ( ! isset( $body['access_token'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Ongeldig antwoord van Google.', 'writgocms' ) );
		}

		// Save tokens.
		update_option( 'writgocms_gsc_access_token', sanitize_text_field( $body['access_token'] ) );

		if ( isset( $body['refresh_token'] ) ) {
			update_option( 'writgocms_gsc_refresh_token', sanitize_text_field( $body['refresh_token'] ) );
		}

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
		update_option( 'writgocms_gsc_token_expiry', time() + $expires_in - 60 );

		return $body;
	}

	/**
	 * Refresh access token
	 *
	 * @return bool|WP_Error
	 */
	public function refresh_access_token() {
		$refresh_token = $this->get_refresh_token();
		if ( empty( $refresh_token ) ) {
			return new WP_Error( 'no_refresh_token', __( 'Geen refresh token beschikbaar.', 'writgocms' ) );
		}

		$response = wp_remote_post(
			$this->oauth_token_url,
			array(
				'timeout' => 30,
				'body'    => array(
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			$error_message = isset( $body['error_description'] ) ? $body['error_description'] : $body['error'];
			return new WP_Error( 'oauth_error', $error_message );
		}

		if ( ! isset( $body['access_token'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Ongeldig antwoord van Google.', 'writgocms' ) );
		}

		update_option( 'writgocms_gsc_access_token', sanitize_text_field( $body['access_token'] ) );

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
		update_option( 'writgocms_gsc_token_expiry', time() + $expires_in - 60 );

		return true;
	}

	/**
	 * Ensure valid access token
	 *
	 * @return string|WP_Error
	 */
	public function ensure_valid_token() {
		if ( ! $this->is_connected() ) {
			return new WP_Error( 'not_connected', __( 'Niet verbonden met Google Search Console.', 'writgocms' ) );
		}

		if ( $this->is_token_expired() ) {
			$result = $this->refresh_access_token();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $this->get_access_token();
	}

	/**
	 * Check rate limit
	 *
	 * @return bool
	 */
	private function check_rate_limit() {
		$limits = get_option( $this->rate_limit_option, array() );
		$now    = time();

		if ( ! isset( $limits['gsc'] ) ) {
			return true;
		}

		$limit_data = $limits['gsc'];
		$window     = 60; // 1 minute window.
		$max_calls  = 30; // Max 30 calls per minute.

		if ( $now - $limit_data['timestamp'] > $window ) {
			return true;
		}

		return $limit_data['count'] < $max_calls;
	}

	/**
	 * Update rate limit
	 */
	private function update_rate_limit() {
		$limits = get_option( $this->rate_limit_option, array() );
		$now    = time();
		$window = 60;

		if ( ! isset( $limits['gsc'] ) || $now - $limits['gsc']['timestamp'] > $window ) {
			$limits['gsc'] = array(
				'timestamp' => $now,
				'count'     => 1,
			);
		} else {
			$limits['gsc']['count']++;
		}

		update_option( $this->rate_limit_option, $limits );
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method   HTTP method.
	 * @param array  $body     Request body.
	 * @return array|WP_Error
	 */
	public function api_request( $endpoint, $method = 'GET', $body = array() ) {
		$token = $this->ensure_valid_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		if ( ! $this->check_rate_limit() ) {
			return new WP_Error( 'rate_limited', __( 'Rate limit bereikt. Probeer later opnieuw.', 'writgocms' ) );
		}

		$url = $this->api_base_url . $endpoint;

		$args = array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'method'  => $method,
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		$this->update_rate_limit();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'API fout opgetreden.', 'writgocms' );
			return new WP_Error( 'api_error', $error_message );
		}

		return $data;
	}

	/**
	 * Get list of sites
	 *
	 * @return array|WP_Error
	 */
	public function get_sites() {
		return $this->api_request( '/sites' );
	}

	/**
	 * Get search analytics data
	 *
	 * @param string $site_url   Site URL.
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @param array  $dimensions Dimensions to request.
	 * @param int    $row_limit  Maximum rows to return.
	 * @return array|WP_Error
	 */
	public function get_search_analytics( $site_url, $start_date, $end_date, $dimensions = array( 'query' ), $row_limit = 1000 ) {
		$encoded_url = rawurlencode( $site_url );

		$body = array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => $dimensions,
			'rowLimit'   => $row_limit,
		);

		return $this->api_request( '/sites/' . $encoded_url . '/searchAnalytics/query', 'POST', $body );
	}

	/**
	 * Get search analytics with filters
	 *
	 * @param string $site_url   Site URL.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param array  $dimensions Dimensions.
	 * @param array  $filters    Filters.
	 * @param int    $row_limit  Row limit.
	 * @return array|WP_Error
	 */
	public function get_search_analytics_filtered( $site_url, $start_date, $end_date, $dimensions = array( 'query' ), $filters = array(), $row_limit = 1000 ) {
		$encoded_url = rawurlencode( $site_url );

		$body = array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => $dimensions,
			'rowLimit'   => $row_limit,
		);

		if ( ! empty( $filters ) ) {
			$body['dimensionFilterGroups'] = array(
				array(
					'filters' => $filters,
				),
			);
		}

		return $this->api_request( '/sites/' . $encoded_url . '/searchAnalytics/query', 'POST', $body );
	}

	/**
	 * Disconnect from Google Search Console
	 */
	public function disconnect() {
		delete_option( 'writgocms_gsc_access_token' );
		delete_option( 'writgocms_gsc_refresh_token' );
		delete_option( 'writgocms_gsc_token_expiry' );
		delete_option( 'writgocms_gsc_selected_site' );
		delete_option( 'writgocms_gsc_oauth_state' );
	}

	/**
	 * AJAX handler for authentication
	 */
	public function ajax_handle_auth() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$auth_url = $this->get_auth_url();
		if ( empty( $auth_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Configureer eerst de OAuth credentials.', 'writgocms' ) ) );
		}

		wp_send_json_success( array( 'auth_url' => $auth_url ) );
	}

	/**
	 * AJAX handler for disconnect
	 */
	public function ajax_disconnect() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$this->disconnect();

		wp_send_json_success( array( 'message' => __( 'Verbinding verbroken.', 'writgocms' ) ) );
	}

	/**
	 * AJAX handler for fetching data
	 */
	public function ajax_fetch_data() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$site_url   = $this->get_selected_site();
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : gmdate( 'Y-m-d', strtotime( '-28 days' ) );
		$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		if ( empty( $site_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Selecteer eerst een site.', 'writgocms' ) ) );
		}

		$data = $this->get_search_analytics( $site_url, $start_date, $end_date );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * AJAX handler for getting sites
	 */
	public function ajax_get_sites() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$sites = $this->get_sites();

		if ( is_wp_error( $sites ) ) {
			wp_send_json_error( array( 'message' => $sites->get_error_message() ) );
		}

		wp_send_json_success( $sites );
	}

	/**
	 * AJAX handler for selecting site
	 */
	public function ajax_select_site() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$site_url = isset( $_POST['site_url'] ) ? sanitize_url( wp_unslash( $_POST['site_url'] ) ) : '';

		if ( empty( $site_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Site URL is vereist.', 'writgocms' ) ) );
		}

		update_option( 'writgocms_gsc_selected_site', $site_url );

		wp_send_json_success( array( 'message' => __( 'Site geselecteerd.', 'writgocms' ) ) );
	}
}

// Initialize.
WritgoCMS_GSC_Provider::get_instance();
