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
}
