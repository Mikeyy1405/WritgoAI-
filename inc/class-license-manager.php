<?php
/**
 * License Manager Class
 *
 * Handles license verification, user authentication, and subscription management.
 * This is the foundation for the SaaS licensing system:
 * - Users log in (no API keys required)
 * - Subscription verification
 * - Automatic plugin updates
 * - API key injection at download
 * - Stop paying = stop working
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_License_Manager
 */
class WritgoCMS_License_Manager {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_License_Manager
	 */
	private static $instance = null;

	/**
	 * License API base URL
	 *
	 * @var string
	 */
	private $api_base_url = 'https://api.writgoai.com/v1';

	/**
	 * License status cache key
	 *
	 * @var string
	 */
	private $cache_key = 'writgocms_license_status';

	/**
	 * License cache expiration in seconds (12 hours)
	 *
	 * @var int
	 */
	private $cache_expiration = 43200;

	/**
	 * License status options
	 *
	 * @var array
	 */
	private $status_labels = array(
		'valid'     => 'Actief',
		'expired'   => 'Verlopen',
		'invalid'   => 'Ongeldig',
		'suspended' => 'Opgeschort',
		'trial'     => 'Proefperiode',
	);

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_License_Manager
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
		// AJAX handlers for license management.
		add_action( 'wp_ajax_writgocms_activate_license', array( $this, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_writgocms_deactivate_license', array( $this, 'ajax_deactivate_license' ) );
		add_action( 'wp_ajax_writgocms_check_license', array( $this, 'ajax_check_license' ) );
		add_action( 'wp_ajax_writgocms_refresh_license', array( $this, 'ajax_refresh_license' ) );

		// Schedule daily license check.
		add_action( 'writgocms_daily_license_check', array( $this, 'daily_license_check' ) );

		// Admin notices for license status.
		add_action( 'admin_notices', array( $this, 'display_license_notices' ) );

		// Filter to check license before AI operations.
		add_filter( 'writgocms_can_use_ai', array( $this, 'check_can_use_ai' ), 10, 1 );
	}

	/**
	 * Get license key
	 *
	 * @return string
	 */
	public function get_license_key() {
		return get_option( 'writgocms_license_key', '' );
	}

	/**
	 * Get license email
	 *
	 * @return string
	 */
	public function get_license_email() {
		return get_option( 'writgocms_license_email', '' );
	}

	/**
	 * Get license status from cache or server
	 *
	 * @param bool $force_refresh Force refresh from server.
	 * @return array License status data.
	 */
	public function get_license_status( $force_refresh = false ) {
		$cached = get_transient( $this->cache_key );

		if ( ! $force_refresh && false !== $cached ) {
			return $cached;
		}

		$license_key = $this->get_license_key();

		if ( empty( $license_key ) ) {
			return array(
				'status'      => 'invalid',
				'message'     => 'Geen licentie geactiveerd.',
				'is_valid'    => false,
				'expires'     => null,
				'plan'        => null,
				'features'    => array(),
				'api_key'     => '',
				'checked_at'  => current_time( 'mysql' ),
			);
		}

		$status = $this->verify_license( $license_key );
		set_transient( $this->cache_key, $status, $this->cache_expiration );

		return $status;
	}

	/**
	 * Check if license is valid
	 *
	 * @return bool
	 */
	public function is_license_valid() {
		// WordPress admin users with manage_options capability always have access
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$status = $this->get_license_status();
		return isset( $status['is_valid'] ) && $status['is_valid'];
	}

	/**
	 * Check if user can use AI features
	 *
	 * @param bool $can_use Current state.
	 * @return bool
	 */
	public function check_can_use_ai( $can_use ) {
		// WordPress admin users with manage_options capability always have access
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ! $this->is_license_valid() ) {
			return false;
		}
		return $can_use;
	}

	/**
	 * Get injected API key from license server
	 *
	 * @return string|WP_Error API key or error.
	 */
	public function get_injected_api_key() {
		$status = $this->get_license_status();

		if ( ! $status['is_valid'] ) {
			return new WP_Error( 'license_invalid', 'Geen geldige licentie.' );
		}

		if ( empty( $status['api_key'] ) ) {
			// Refresh to get the API key.
			$status = $this->get_license_status( true );
		}

		if ( ! empty( $status['api_key'] ) ) {
			return $status['api_key'];
		}

		return new WP_Error( 'no_api_key', 'Geen API sleutel beschikbaar.' );
	}

	/**
	 * Verify license with the licensing server
	 *
	 * @param string $license_key License key to verify.
	 * @return array License status.
	 */
	public function verify_license( $license_key ) {
		$site_url = home_url();
		$email    = $this->get_license_email();

		$response = wp_remote_post(
			$this->api_base_url . '/license/verify',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'license_key' => $license_key,
						'site_url'    => $site_url,
						'email'       => $email,
						'product'     => 'writgoai',
						'version'     => WRITGOCMS_VERSION,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Return cached status if available, otherwise invalid.
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				$cached['error'] = $response->get_error_message();
				return $cached;
			}

			return array(
				'status'      => 'error',
				'message'     => 'Kon geen verbinding maken met licentieserver.',
				'is_valid'    => false,
				'expires'     => null,
				'plan'        => null,
				'features'    => array(),
				'api_key'     => '',
				'error'       => $response->get_error_message(),
				'checked_at'  => current_time( 'mysql' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code || ! isset( $body['status'] ) ) {
			$error_message = isset( $body['message'] ) ? $body['message'] : 'Onbekende fout.';

			return array(
				'status'      => 'invalid',
				'message'     => $error_message,
				'is_valid'    => false,
				'expires'     => null,
				'plan'        => null,
				'features'    => array(),
				'api_key'     => '',
				'checked_at'  => current_time( 'mysql' ),
			);
		}

		$is_valid = in_array( $body['status'], array( 'valid', 'trial' ), true );

		return array(
			'status'      => sanitize_text_field( $body['status'] ),
			'message'     => isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : '',
			'is_valid'    => $is_valid,
			'expires'     => isset( $body['expires'] ) ? sanitize_text_field( $body['expires'] ) : null,
			'plan'        => isset( $body['plan'] ) ? sanitize_text_field( $body['plan'] ) : null,
			'plan_name'   => isset( $body['plan_name'] ) ? sanitize_text_field( $body['plan_name'] ) : null,
			'features'    => isset( $body['features'] ) ? array_map( 'sanitize_text_field', (array) $body['features'] ) : array(),
			'api_key'     => isset( $body['api_key'] ) ? sanitize_text_field( $body['api_key'] ) : '',
			'usage'       => isset( $body['usage'] ) ? $body['usage'] : array(),
			'limits'      => isset( $body['limits'] ) ? $body['limits'] : array(),
			'checked_at'  => current_time( 'mysql' ),
		);
	}

	/**
	 * Activate license
	 *
	 * @param string $license_key License key.
	 * @param string $email       User email.
	 * @return array|WP_Error Activation result.
	 */
	public function activate_license( $license_key, $email ) {
		$site_url = home_url();

		$response = wp_remote_post(
			$this->api_base_url . '/license/activate',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'license_key' => $license_key,
						'email'       => $email,
						'site_url'    => $site_url,
						'site_name'   => get_bloginfo( 'name' ),
						'product'     => 'writgoai',
						'version'     => WRITGOCMS_VERSION,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$error_message = isset( $body['message'] ) ? $body['message'] : 'Activatie mislukt.';
			return new WP_Error( 'activation_failed', $error_message );
		}

		// Save license details.
		update_option( 'writgocms_license_key', sanitize_text_field( $license_key ) );
		update_option( 'writgocms_license_email', sanitize_email( $email ) );
		update_option( 'writgocms_license_activated_at', current_time( 'mysql' ) );

		// If API key is provided, save it.
		if ( isset( $body['api_key'] ) && ! empty( $body['api_key'] ) ) {
			update_option( 'writgocms_aimlapi_key', sanitize_text_field( $body['api_key'] ) );
		}

		// Clear cache and refresh status.
		delete_transient( $this->cache_key );
		$status = $this->get_license_status( true );

		return array(
			'success' => true,
			'message' => isset( $body['message'] ) ? $body['message'] : 'Licentie succesvol geactiveerd!',
			'status'  => $status,
		);
	}

	/**
	 * Deactivate license
	 *
	 * @return array|WP_Error Deactivation result.
	 */
	public function deactivate_license() {
		$license_key = $this->get_license_key();
		$site_url    = home_url();

		if ( empty( $license_key ) ) {
			return new WP_Error( 'no_license', 'Geen licentie om te deactiveren.' );
		}

		$response = wp_remote_post(
			$this->api_base_url . '/license/deactivate',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'license_key' => $license_key,
						'site_url'    => $site_url,
						'product'     => 'writgoai',
					)
				),
			)
		);

		// Even if server request fails, clear local license data.
		delete_option( 'writgocms_license_key' );
		delete_option( 'writgocms_license_email' );
		delete_option( 'writgocms_license_activated_at' );
		delete_option( 'writgocms_aimlapi_key' );
		delete_transient( $this->cache_key );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => true,
				'message' => 'Licentie lokaal gedeactiveerd.',
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return array(
			'success' => true,
			'message' => isset( $body['message'] ) ? $body['message'] : 'Licentie gedeactiveerd.',
		);
	}

	/**
	 * Daily license check cron job
	 */
	public function daily_license_check() {
		$license_key = $this->get_license_key();

		if ( empty( $license_key ) ) {
			return;
		}

		// Force refresh license status.
		$this->get_license_status( true );
	}

	/**
	 * Schedule daily license check
	 */
	public function schedule_daily_check() {
		if ( ! wp_next_scheduled( 'writgocms_daily_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'writgocms_daily_license_check' );
		}
	}

	/**
	 * Unschedule daily license check
	 */
	public function unschedule_daily_check() {
		$timestamp = wp_next_scheduled( 'writgocms_daily_license_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'writgocms_daily_license_check' );
		}
	}

	/**
	 * Display license notices in admin
	 */
	public function display_license_notices() {
		// Don't show license notices to WordPress admin users with manage_options capability
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show on plugin pages.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'writgocms' ) === false ) {
			return;
		}

		$license_key = $this->get_license_key();

		if ( empty( $license_key ) ) {
			echo '<div class="notice notice-warning is-dismissible">';
			echo '<p><strong>WritgoAI:</strong> ';
			echo esc_html__( 'Activeer je licentie om WritgoAI te gebruiken.', 'writgocms' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=writgocms-license' ) ) . '">';
			echo esc_html__( 'Licentie activeren', 'writgocms' );
			echo '</a></p></div>';
			return;
		}

		$status = $this->get_license_status();

		if ( isset( $status['status'] ) && 'expired' === $status['status'] ) {
			echo '<div class="notice notice-error">';
			echo '<p><strong>WritgoAI:</strong> ';
			echo esc_html__( 'Je licentie is verlopen. Verleng je abonnement om te blijven gebruiken.', 'writgocms' );
			echo ' <a href="https://writgoai.com/account" target="_blank">';
			echo esc_html__( 'Abonnement verlengen', 'writgocms' );
			echo '</a></p></div>';
		} elseif ( isset( $status['status'] ) && 'trial' === $status['status'] ) {
			$expires = isset( $status['expires'] ) ? $status['expires'] : '';
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p><strong>WritgoAI:</strong> ';
			/* translators: %s: expiration date */
			echo esc_html( sprintf( __( 'Je proefperiode loopt af op %s.', 'writgocms' ), $expires ) );
			echo ' <a href="https://writgoai.com/pricing" target="_blank">';
			echo esc_html__( 'Upgrade naar betaald plan', 'writgocms' );
			echo '</a></p></div>';
		}
	}

	/**
	 * Get status label
	 *
	 * @param string $status Status key.
	 * @return string Status label.
	 */
	public function get_status_label( $status ) {
		return isset( $this->status_labels[ $status ] ) ? $this->status_labels[ $status ] : $status;
	}

	/**
	 * Get remaining days until expiration
	 *
	 * @return int|null Days remaining or null if no expiration.
	 */
	public function get_days_remaining() {
		$status = $this->get_license_status();

		if ( ! isset( $status['expires'] ) || empty( $status['expires'] ) ) {
			return null;
		}

		$expires = strtotime( $status['expires'] );
		$now     = time();

		if ( $expires <= $now ) {
			return 0;
		}

		return (int) ceil( ( $expires - $now ) / DAY_IN_SECONDS );
	}

	/**
	 * AJAX handler for license activation
	 */
	public function ajax_activate_license() {
		check_ajax_referer( 'writgocms_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => 'Licentiesleutel is verplicht.' ) );
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Geldig e-mailadres is verplicht.' ) );
		}

		$result = $this->activate_license( $license_key, $email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for license deactivation
	 */
	public function ajax_deactivate_license() {
		check_ajax_referer( 'writgocms_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$result = $this->deactivate_license();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for checking license
	 */
	public function ajax_check_license() {
		check_ajax_referer( 'writgocms_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$status = $this->get_license_status();

		wp_send_json_success( array( 'status' => $status ) );
	}

	/**
	 * AJAX handler for refreshing license
	 */
	public function ajax_refresh_license() {
		check_ajax_referer( 'writgocms_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$status = $this->get_license_status( true );

		wp_send_json_success( array(
			'message' => 'Licentiestatus bijgewerkt.',
			'status'  => $status,
		) );
	}

	/**
	 * Set custom API base URL (for testing or custom servers)
	 *
	 * @param string $url API base URL.
	 */
	public function set_api_base_url( $url ) {
		$this->api_base_url = esc_url_raw( $url );
	}

	/**
	 * Get API base URL
	 *
	 * @return string
	 */
	public function get_api_base_url() {
		return apply_filters( 'writgocms_license_api_url', $this->api_base_url );
	}
}

// Initialize.
WritgoCMS_License_Manager::get_instance();
