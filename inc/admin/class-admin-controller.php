<?php
/**
 * Admin Controller
 *
 * Central controller for admin interface management.
 * Handles routing, enqueuing assets, and coordinating admin components.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Admin_Controller
 */
class WritgoCMS_Admin_Controller {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Admin_Controller
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Admin_Controller
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
		add_action( 'wp_ajax_writgocms_test_api_connection', array( $this, 'ajax_test_api_connection' ) );
		add_action( 'wp_ajax_writgocms_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_writgocms_reset_wizard', array( $this, 'ajax_reset_wizard' ) );
	}

	/**
	 * Enqueue beginner-friendly admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_beginner_assets( $hook ) {
		// Only load on WritgoAI admin pages.
		if ( strpos( $hook, 'writgocms' ) === false ) {
			return;
		}

		// Enqueue beginner CSS.
		wp_enqueue_style(
			'writgocms-admin-beginner',
			WRITGOCMS_URL . 'assets/css/admin-beginner.css',
			array(),
			WRITGOCMS_VERSION
		);

		// Enqueue beginner JS.
		wp_enqueue_script(
			'writgocms-admin-beginner',
			WRITGOCMS_URL . 'assets/js/admin-beginner.js',
			array( 'jquery' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_localize_script(
			'writgocms-admin-beginner',
			'writgocmsAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'writgocms_admin_nonce' ),
				'i18n'    => array(
					'saving'      => __( 'Opslaan...', 'writgocms' ),
					'saved'       => __( 'Opgeslagen!', 'writgocms' ),
					'error'       => __( 'Er is een fout opgetreden', 'writgocms' ),
					'loading'     => __( 'Laden...', 'writgocms' ),
					'validating'  => __( 'Valideren...', 'writgocms' ),
					'success'     => __( 'Gelukt!', 'writgocms' ),
					'nextStep'    => __( 'Volgende Stap', 'writgocms' ),
					'previousStep' => __( 'Vorige Stap', 'writgocms' ),
					'skip'        => __( 'Overslaan', 'writgocms' ),
					'finish'      => __( 'Voltooien', 'writgocms' ),
				),
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
		$partial_path = WRITGOCMS_DIR . 'inc/admin/views/partials/' . $partial_name . '.php';
		
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
		return (bool) get_option( 'writgocms_wizard_completed', false );
	}

	/**
	 * Mark wizard as completed
	 */
	public function mark_wizard_completed() {
		update_option( 'writgocms_wizard_completed', true );
		update_option( 'writgocms_wizard_completed_at', current_time( 'mysql' ) );
	}

	/**
	 * AJAX handler to test API connection
	 */
	public function ajax_test_api_connection() {
		check_ajax_referer( 'writgocms_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgocms' ) ) );
		}

		$api_url = isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '';

		if ( empty( $api_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen API URL opgegeven', 'writgocms' ) ) );
		}

		// Test connection by making a simple request.
		$response = wp_remote_get( $api_url . '/health', array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Verbinding mislukt: %s', 'writgocms' ),
					$response->get_error_message()
				),
			) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code === 200 ) {
			wp_send_json_success( array( 'message' => __( 'Verbinding succesvol!', 'writgocms' ) ) );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Server antwoordde met status: %d', 'writgocms' ),
					$status_code
				),
			) );
		}
	}

	/**
	 * AJAX handler to clear cache
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'writgocms_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgocms' ) ) );
		}

		// Clear WordPress transients using safe method.
		global $wpdb;
		// Get all WritgoCMS transient names.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_writgocms_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_writgocms_' ) . '%'
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

		wp_send_json_success( array( 'message' => __( 'Cache geleegd', 'writgocms' ) ) );
	}

	/**
	 * AJAX handler to reset wizard
	 */
	public function ajax_reset_wizard() {
		check_ajax_referer( 'writgocms_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgocms' ) ) );
		}

		// Reset wizard completion status.
		delete_option( 'writgocms_wizard_completed' );
		delete_option( 'writgocms_wizard_completed_at' );

		// Clear wizard step data.
		for ( $i = 1; $i <= 5; $i++ ) {
			delete_option( 'writgocms_wizard_step_' . $i );
		}

		wp_send_json_success( array( 'message' => __( 'Wizard gereset', 'writgocms' ) ) );
	}
}
