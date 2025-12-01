<?php
/**
 * Setup Wizard
 *
 * Guides new users through initial plugin setup with a step-by-step wizard.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Setup_Wizard
 */
class WritgoCMS_Setup_Wizard {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Setup_Wizard
	 */
	private static $instance = null;

	/**
	 * Current wizard step
	 *
	 * @var int
	 */
	private $current_step = 1;

	/**
	 * Total wizard steps
	 *
	 * @var int
	 */
	private $total_steps = 5;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Setup_Wizard
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
		add_action( 'admin_menu', array( $this, 'add_wizard_page' ) );
		add_action( 'wp_ajax_writgocms_save_wizard_step', array( $this, 'ajax_save_wizard_step' ) );
		add_action( 'wp_ajax_writgocms_skip_wizard', array( $this, 'ajax_skip_wizard' ) );
		
		// Redirect to wizard on first activation.
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_wizard' ) );
	}

	/**
	 * Add wizard page to admin menu (hidden)
	 */
	public function add_wizard_page() {
		add_submenu_page(
			null, // No parent menu - hidden from sidebar.
			__( 'WritgoAI Setup Wizard', 'writgocms' ),
			__( 'Setup Wizard', 'writgocms' ),
			'manage_options',
			'writgocms-setup-wizard',
			array( $this, 'render_wizard' )
		);
	}

	/**
	 * Maybe redirect to wizard on first activation
	 */
	public function maybe_redirect_to_wizard() {
		// Check if we should redirect to wizard.
		if ( get_transient( 'writgocms_activation_redirect' ) ) {
			delete_transient( 'writgocms_activation_redirect' );
			
			// Don't redirect if wizard already completed.
			if ( ! get_option( 'writgocms_wizard_completed', false ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=writgocms-setup-wizard' ) );
				exit;
			}
		}
	}

	/**
	 * Render wizard page
	 */
	public function render_wizard() {
		$this->current_step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="writgo-wizard-container">
			<?php $this->render_step_indicator(); ?>
			<?php $this->render_current_step(); ?>
		</div>
		<?php
	}

	/**
	 * Render step indicator
	 */
	private function render_step_indicator() {
		$steps = array(
			1 => __( 'Welkom', 'writgocms' ),
			2 => __( 'Website Thema', 'writgocms' ),
			3 => __( 'Doelgroep', 'writgocms' ),
			4 => __( 'Eerste Analyse', 'writgocms' ),
			5 => __( 'Klaar!', 'writgocms' ),
		);
		?>
		<div class="writgo-steps">
			<?php foreach ( $steps as $step_num => $step_label ) : ?>
				<div class="writgo-step <?php echo $step_num < $this->current_step ? 'completed' : ''; ?> <?php echo $step_num === $this->current_step ? 'active' : ''; ?>">
					<div class="step-icon">
						<?php if ( $step_num < $this->current_step ) : ?>
							<span class="dashicons dashicons-yes"></span>
						<?php else : ?>
							<span class="step-number"><?php echo esc_html( $step_num ); ?></span>
						<?php endif; ?>
					</div>
					<div class="step-label"><?php echo esc_html( $step_label ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render current wizard step
	 */
	private function render_current_step() {
		$step_file = WRITGOCMS_DIR . 'inc/admin/views/wizard/step-' . $this->current_step . '.php';
		
		if ( file_exists( $step_file ) ) {
			include $step_file;
		}
	}

	/**
	 * Get wizard step data
	 *
	 * @param int $step Step number.
	 * @return array
	 */
	public function get_step_data( $step ) {
		return get_option( 'writgocms_wizard_step_' . $step, array() );
	}

	/**
	 * Save wizard step data
	 *
	 * @param int   $step Step number.
	 * @param array $data Step data.
	 */
	public function save_step_data( $step, $data ) {
		update_option( 'writgocms_wizard_step_' . $step, $data );
	}

	/**
	 * AJAX handler to save wizard step
	 */
	public function ajax_save_wizard_step() {
		check_ajax_referer( 'writgocms_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgocms' ) ) );
		}

		$step = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 0;
		$data = isset( $_POST['data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

		if ( $step > 0 && $step <= $this->total_steps ) {
			$this->save_step_data( $step, $data );
			
			// Mark wizard as completed if this is the last step.
			if ( $step === $this->total_steps ) {
				$controller = WritgoCMS_Admin_Controller::get_instance();
				$controller->mark_wizard_completed();
			}
			
			wp_send_json_success( array(
				'message'   => __( 'Stap opgeslagen', 'writgocms' ),
				'next_step' => $step < $this->total_steps ? $step + 1 : null,
			) );
		}

		wp_send_json_error( array( 'message' => __( 'Ongeldige stap', 'writgocms' ) ) );
	}

	/**
	 * AJAX handler to skip wizard
	 */
	public function ajax_skip_wizard() {
		check_ajax_referer( 'writgocms_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten', 'writgocms' ) ) );
		}

		$controller = WritgoCMS_Admin_Controller::get_instance();
		$controller->mark_wizard_completed();

		wp_send_json_success( array(
			'message'      => __( 'Wizard overgeslagen', 'writgocms' ),
			'redirect_url' => admin_url( 'admin.php?page=writgocms-aiml' ),
		) );
	}
}
