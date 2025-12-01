<?php
/**
 * DataForSEO Settings Admin Page
 *
 * Settings interface for DataForSEO API credentials.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_DataForSEO_Settings
 */
class WritgoCMS_DataForSEO_Settings {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_DataForSEO_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_DataForSEO_Settings
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
		add_action( 'wp_ajax_writgocms_test_dataforseo', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'writgocms_dataforseo_settings', 'writgocms_dataforseo_login', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'writgocms_dataforseo_settings', 'writgocms_dataforseo_password', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	/**
	 * Render settings section
	 */
	public function render_settings_section() {
		$login    = get_option( 'writgocms_dataforseo_login', '' );
		$password = get_option( 'writgocms_dataforseo_password', '' );
		$is_configured = ! empty( $login ) && ! empty( $password );
		?>
		<div class="dataforseo-settings-section">
			<h2><?php esc_html_e( 'DataForSEO API Settings', 'writgocms' ); ?></h2>
			<p class="description">
				<?php
				printf(
					/* translators: %s: DataForSEO URL */
					esc_html__( 'Enter your DataForSEO API credentials. Get your credentials from %s', 'writgocms' ),
					'<a href="https://app.dataforseo.com" target="_blank">app.dataforseo.com</a>'
				);
				?>
			</p>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="writgocms_dataforseo_login"><?php esc_html_e( 'Login', 'writgocms' ); ?></label>
					</th>
					<td>
						<input 
							type="text" 
							id="writgocms_dataforseo_login" 
							name="writgocms_dataforseo_login" 
							value="<?php echo esc_attr( $login ); ?>" 
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="writgocms_dataforseo_password"><?php esc_html_e( 'Password', 'writgocms' ); ?></label>
					</th>
					<td>
						<input 
							type="password" 
							id="writgocms_dataforseo_password" 
							name="writgocms_dataforseo_password" 
							value="<?php echo esc_attr( $password ); ?>" 
							class="regular-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Connection Status', 'writgocms' ); ?></th>
					<td>
						<div id="dataforseo-status">
							<?php if ( $is_configured ) : ?>
								<span class="status-indicator status-connected">✅ <?php esc_html_e( 'Configured', 'writgocms' ); ?></span>
							<?php else : ?>
								<span class="status-indicator status-not-connected">❌ <?php esc_html_e( 'Not Configured', 'writgocms' ); ?></span>
							<?php endif; ?>
						</div>
						<button type="button" id="test-dataforseo-btn" class="button button-secondary" <?php echo ! $is_configured ? 'disabled' : ''; ?>>
							<?php esc_html_e( 'Test Connection', 'writgocms' ); ?>
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
					
					$btn.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'writgocms' ); ?>');
					$result.html('');

					$.ajax({
						url: ajaxurl,
						method: 'POST',
						data: {
							action: 'writgocms_test_dataforseo',
							nonce: '<?php echo esc_js( wp_create_nonce( 'writgocms_dataforseo_test' ) ); ?>',
							login: $('#writgocms_dataforseo_login').val(),
							password: $('#writgocms_dataforseo_password').val()
						},
						success: function(response) {
							if (response.success) {
								$result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
								$('#dataforseo-status').html('<span class="status-indicator status-connected">✅ <?php esc_html_e( 'Connected', 'writgocms' ); ?></span>');
							} else {
								$result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
							}
						},
						error: function() {
							$result.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection failed', 'writgocms' ); ?></span>');
						},
						complete: function() {
							$btn.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'writgocms' ); ?>');
						}
					});
				});

				// Enable/disable test button based on input.
				$('#writgocms_dataforseo_login, #writgocms_dataforseo_password').on('input', function() {
					var login = $('#writgocms_dataforseo_login').val();
					var password = $('#writgocms_dataforseo_password').val();
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
		check_ajax_referer( 'writgocms_dataforseo_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'writgocms' ) ) );
		}

		$login    = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
		$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

		if ( empty( $login ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Login and password are required', 'writgocms' ) ) );
		}

		// Temporarily set credentials for testing.
		$original_login    = get_option( 'writgocms_dataforseo_login' );
		$original_password = get_option( 'writgocms_dataforseo_password' );

		update_option( 'writgocms_dataforseo_login', $login );
		update_option( 'writgocms_dataforseo_password', $password );

		// Test connection.
		$api    = WritgoCMS_DataForSEO_API::get_instance();
		$result = $api->test_connection();

		// Restore original credentials.
		update_option( 'writgocms_dataforseo_login', $original_login );
		update_option( 'writgocms_dataforseo_password', $original_password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connection successful!', 'writgocms' ) ) );
	}
}

// Initialize.
WritgoCMS_DataForSEO_Settings::get_instance();
