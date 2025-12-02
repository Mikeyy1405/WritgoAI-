<?php
/**
 * Admin Controller
 *
 * Central controller for admin interface management.
 * Handles routing, enqueuing assets, and coordinating admin components.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Admin_Controller
 */
class WritgoAI_Admin_Controller {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Admin_Controller
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Admin_Controller
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_beginner_assets' ) );
		
		// AJAX handlers for advanced settings.
		add_action( 'wp_ajax_writgoai_test_api_connection', array( $this, 'ajax_test_api_connection' ) );
		add_action( 'wp_ajax_writgoai_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_writgoai_reset_wizard', array( $this, 'ajax_reset_wizard' ) );
	}

	/**
	 * Enqueue beginner-friendly admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_beginner_assets( $hook ) {
		// Load on all WritgoAI admin pages (both writgoai and legacy writgocms slugs).
		if ( strpos( $hook, 'writgoai' ) === false && strpos( $hook, 'writgocms' ) === false ) {
			return;
		}

		// Enqueue beginner CSS.
		wp_enqueue_style(
			'writgoai-admin-beginner',
			WRITGOAI_URL . 'assets/css/admin-beginner.css',
			array(),
			WRITGOAI_VERSION
		);

		// Enqueue beginner JS.
		wp_enqueue_script(
			'writgoai-admin-beginner',
			WRITGOAI_URL . 'assets/js/admin-beginner.js',
			array( 'jquery' ),
			WRITGOAI_VERSION,
			true
		);

		wp_localize_script(
			'writgoai-admin-beginner',
			'writgoaiAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'writgoai_admin_nonce' ),
				'i18n'    => array(
					'saving'       => __( 'Opslaan...', 'writgoai' ),
					'saved'        => __( 'Opgeslagen!', 'writgoai' ),
					'error'        => __( 'Er is een fout opgetreden', 'writgoai' ),
					'loading'      => __( 'Laden...', 'writgoai' ),
					'validating'   => __( 'Valideren...', 'writgoai' ),
					'success'      => __( 'Gelukt!', 'writgoai' ),
					'nextStep'     => __( 'Volgende Stap', 'writgoai' ),
					'previousStep' => __( 'Vorige Stap', 'writgoai' ),
					'skip'         => __( 'Overslaan', 'writgoai' ),
					'finish'       => __( 'Voltooien', 'writgoai' ),
					'skipConfirm'  => __( 'Weet je zeker dat je de setup wilt overslaan?', 'writgoai' ),
				),
			)
		);

		// Add auth nonce for authentication endpoints.
		wp_localize_script(
			'writgoai-admin-beginner',
			'writgoaiAuth',
			array(
				'nonce' => wp_create_nonce( 'writgoai_auth_nonce' ),
			)
		);
	}

	/**
	 * Render partial template
	 *
	 * @param string $partial_name Name of the partial file (without .php).
	 * @param array  $data Data to pass to the partial.
	 */
	public function render_partial( $partial_name, $data = array() ) {
		$partial_path = WRITGOAI_DIR . 'inc/admin/views/partials/' . $partial_name . '.php';
		
		if ( file_exists( $partial_path ) ) {
			// Extract data to make variables available in the partial.
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $data, EXTR_SKIP );
			include $partial_path;
		}
	}

	/**
	 * Check if user has completed setup wizard
	 *
	 * @return bool
	 */
	public function is_wizard_completed() {
		return (bool) get_option( 'writgoai_wizard_completed', false );
	}

	/**
	 * Mark wizard as completed
	 */
	public function mark_wizard_completed() {
		update_option( 'writgoai_wizard_completed', true );
		update_option( 'writgoai_wizard_completed_at', current_time( 'mysql' ) );
	}

	/**
	 * AJAX handler to test API connection
	 */
	public function ajax_test_api_connection() {
		check_ajax_referer( 'writgoai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgoai' ) ) );
		}

		$api_url = isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '';

		if ( empty( $api_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen API URL opgegeven', 'writgoai' ) ) );
		}

		// Test connection by making a simple request.
		$response = wp_remote_get( $api_url . '/health', array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Verbinding mislukt: %s', 'writgoai' ),
					$response->get_error_message()
				),
			) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code === 200 ) {
			wp_send_json_success( array( 'message' => __( 'Verbinding succesvol!', 'writgoai' ) ) );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Server antwoordde met status: %d', 'writgoai' ),
					$status_code
				),
			) );
		}
	}

	/**
	 * AJAX handler to clear cache
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'writgoai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgoai' ) ) );
		}

		// Clear WordPress transients using safe method.
		global $wpdb;
		// Get all WritgoAI transient names.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_writgoai_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_writgoai_' ) . '%'
			)
		);

		// Delete transients using WordPress functions.
		foreach ( $transients as $transient ) {
			if ( strpos( $transient, '_transient_timeout_' ) === 0 ) {
				// Skip timeout entries, they'll be deleted with the transient.
				continue;
			}
			$transient_name = str_replace( '_transient_', '', $transient );
			delete_transient( $transient_name );
		}

		wp_send_json_success( array( 'message' => __( 'Cache geleegd', 'writgoai' ) ) );
	}

	/**
	 * AJAX handler to reset wizard
	 */
	public function ajax_reset_wizard() {
		check_ajax_referer( 'writgoai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgoai' ) ) );
		}

		// Reset wizard completion status.
		delete_option( 'writgoai_wizard_completed' );
		delete_option( 'writgoai_wizard_completed_at' );

		// Clear wizard step data.
		for ( $i = 1; $i <= 5; $i++ ) {
			delete_option( 'writgoai_wizard_step_' . $i );
		}

		wp_send_json_success( array( 'message' => __( 'Wizard gereset', 'writgoai' ) ) );
	}
}
