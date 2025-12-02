<?php
/**
 * DataForSEO Settings Admin Page
 *
 * Settings interface for DataForSEO API credentials.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_DataForSEO_Settings
 */
class WritgoAI_DataForSEO_Settings {

	/**
	 * Instance
	 *
	 * @var WritgoAI_DataForSEO_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_DataForSEO_Settings
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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_writgoai_test_dataforseo', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'writgoai_dataforseo_settings', 'writgoai_dataforseo_login', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'writgoai_dataforseo_settings', 'writgoai_dataforseo_password', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	/**
	 * Render settings section
	 */
	public function render_settings_section() {
		$login    = get_option( 'writgoai_dataforseo_login', '' );
		$password = get_option( 'writgoai_dataforseo_password', '' );
		$is_configured = ! empty( $login ) && ! empty( $password );
		?>
		<div class="dataforseo-settings-section">
			<h2><?php esc_html_e( 'DataForSEO API Settings', 'writgoai' ); ?></h2>
			<p class="description">
				<?php
				printf(
					/* translators: %s: DataForSEO URL */
					esc_html__( 'Enter your DataForSEO API credentials. Get your credentials from %s', 'writgoai' ),
					'<a href="https://app.dataforseo.com" target="_blank">app.dataforseo.com</a>'
				);
				?>
			</p>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="writgoai_dataforseo_login"><?php esc_html_e( 'Login', 'writgoai' ); ?></label>
					</th>
					<td>
						<input 
							type="text" 
							id="writgoai_dataforseo_login" 
							name="writgoai_dataforseo_login" 
							value="<?php echo esc_attr( $login ); ?>" 
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="writgoai_dataforseo_password"><?php esc_html_e( 'Password', 'writgoai' ); ?></label>
					</th>
					<td>
						<input 
							type="password" 
							id="writgoai_dataforseo_password" 
							name="writgoai_dataforseo_password" 
							value="<?php echo esc_attr( $password ); ?>" 
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Connection Status', 'writgoai' ); ?></th>
					<td>
						<div id="dataforseo-status">
							<?php if ( $is_configured ) : ?>
								<span class="status-indicator status-connected">✅ <?php esc_html_e( 'Configured', 'writgoai' ); ?></span>
							<?php else : ?>
								<span class="status-indicator status-not-connected">❌ <?php esc_html_e( 'Not Configured', 'writgoai' ); ?></span>
							<?php endif; ?>
						</div>
						<button type="button" id="test-dataforseo-btn" class="button button-secondary" <?php echo ! $is_configured ? 'disabled' : ''; ?>>
							<?php esc_html_e( 'Test Connection', 'writgoai' ); ?>
						</button>
						<div id="dataforseo-test-result" style="margin-top: 10px;"></div>
					</td>
				</tr>
			</table>

			<script>
			jQuery(document).ready(function($) {
				$('#test-dataforseo-btn').on('click', function() {
					var $btn = $(this);
					var $result = $('#dataforseo-test-result');
					
					$btn.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'writgoai' ); ?>');
					$result.html('');

					$.ajax({
						url: ajaxurl,
						method: 'POST',
						data: {
							action: 'writgoai_test_dataforseo',
							nonce: '<?php echo esc_js( wp_create_nonce( 'writgoai_dataforseo_test' ) ); ?>',
							login: $('#writgoai_dataforseo_login').val(),
							password: $('#writgoai_dataforseo_password').val()
						},
						success: function(response) {
							if (response.success) {
								$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
								$('#dataforseo-status').html('<span class="status-indicator status-connected">✅ <?php esc_html_e( 'Connected', 'writgoai' ); ?></span>');
							} else {
								$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
							}
						},
						error: function() {
							$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection failed', 'writgoai' ); ?></span>');
						},
						complete: function() {
							$btn.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'writgoai' ); ?>');
						}
					});
				});

				// Enable/disable test button based on input.
				$('#writgoai_dataforseo_login, #writgoai_dataforseo_password').on('input', function() {
					var login = $('#writgoai_dataforseo_login').val();
					var password = $('#writgoai_dataforseo_password').val();
					$('#test-dataforseo-btn').prop('disabled', !login || !password);
				});
			});
			</script>

			<style>
			.status-indicator {
				display: inline-block;
				padding: 5px 10px;
				border-radius: 3px;
				font-weight: 500;
			}
			.status-connected {
				background: #d4edda;
				color: #155724;
			}
			.status-not-connected {
				background: #f8d7da;
				color: #721c24;
			}
			</style>
		</div>
		<?php
	}

	/**
	 * AJAX handler for testing connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'writgoai_dataforseo_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'writgoai' ) ) );
		}

		$login    = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
		$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

		if ( empty( $login ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Login and password are required', 'writgoai' ) ) );
		}

		// Temporarily set credentials for testing.
		$original_login    = get_option( 'writgoai_dataforseo_login' );
		$original_password = get_option( 'writgoai_dataforseo_password' );

		update_option( 'writgoai_dataforseo_login', $login );
		update_option( 'writgoai_dataforseo_password', $password );

		// Test connection.
		$api    = WritgoAI_DataForSEO_API::get_instance();
		$result = $api->test_connection();

		// Restore original credentials.
		update_option( 'writgoai_dataforseo_login', $original_login );
		update_option( 'writgoai_dataforseo_password', $original_password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connection successful!', 'writgoai' ) ) );
	}
}

// Initialize.
WritgoAI_DataForSEO_Settings::get_instance();
