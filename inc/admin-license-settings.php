<?php
/**
 * License Admin Settings
 *
 * Admin interface for license management.
 * Features:
 * - License activation/deactivation
 * - License status display
 * - Subscription management
 * - Update checking
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_License_Admin
 */
class WritgoCMS_License_Admin {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_License_Admin
	 */
	private static $instance = null;

	/**
	 * License manager instance
	 *
	 * @var WritgoCMS_License_Manager
	 */
	private $license_manager;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_License_Admin
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
		$this->license_manager = WritgoCMS_License_Manager::get_instance();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'writgocms-aiml',
			'Licentie',
			'🔑 Licentie',
			'manage_options',
			'writgocms-license',
			array( $this, 'render_license_page' )
		);
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'writgocms-license' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'writgocms-license-admin',
			WRITGOCMS_URL . 'assets/css/admin-aiml.css',
			array(),
			WRITGOCMS_VERSION
		);

		wp_enqueue_script(
			'writgocms-license-admin',
			WRITGOCMS_URL . 'assets/js/admin-aiml.js',
			array( 'jquery' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_localize_script(
			'writgocms-license-admin',
			'writgocmsLicense',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'writgocms_auth_nonce' ), // Changed to auth_nonce for auth actions.
				'i18n'    => array(
					'activating'    => 'Activeren...',
					'deactivating'  => 'Deactiveren...',
					'checking'      => 'Controleren...',
					'refreshing'    => 'Bijwerken...',
					'success'       => 'Gelukt!',
					'error'         => 'Fout',
					'confirmDeactivate' => 'Weet je zeker dat je de licentie wilt deactiveren?',
				),
				'updateUrl' => admin_url( 'update-core.php' ),
			)
		);
	}

	/**
	 * Render license page
	 */
	public function render_license_page() {
		// Get WordPress user (always authenticated for admin users with manage_options).
		$auth_manager = WritgoCMS_Auth_Manager::get_instance();
		$current_user = $auth_manager->get_current_user();

		// Legacy license support.
		$license_key   = $this->license_manager->get_license_key();
		$license_email = $this->license_manager->get_license_email();
		$license_status = $this->license_manager->get_license_status();
		$is_valid      = $this->license_manager->is_license_valid();
		$days_remaining = $this->license_manager->get_days_remaining();
		?>
		<div class="wrap writgocms-aiml-settings writgocms-license-page">
			<h1 class="aiml-header">
				<span class="aiml-logo">🔑</span>
				WritgoAI Licentie
			</h1>

			<div class="aiml-tab-content">
				<!-- WordPress User View (always authenticated) -->
				<div class="auth-user-panel">
					<div class="planner-card">
						<h2>👤 Je WordPress Account</h2>
						
						<div class="auth-user-info">
							<div class="user-avatar">
								<span class="user-icon">👤</span>
							</div>
							<div class="user-details">
								<h3 class="user-name"><?php echo esc_html( $current_user['name'] ); ?></h3>
								<p class="user-email"><?php echo esc_html( $current_user['email'] ); ?></p>
								<p class="description">
									<?php esc_html_e( 'Je bent ingelogd met je WordPress account. De plugin gebruikt automatisch je WordPress inloggegevens voor authenticatie met de WritgoAI API.', 'writgocms' ); ?>
								</p>
							</div>
						</div>

						<div class="auth-actions">
							<a href="https://writgoai.com/account" target="_blank" class="button button-secondary">
								⚙️ Bekijk WritgoAI Account
							</a>
						</div>
					</div>
				</div>

				<?php if ( ! empty( $license_key ) ) : ?>
					<!-- License activated -->
					<div class="license-status-panel">
						<div class="planner-card">
							<h2>📋 Licentie Status</h2>

							<div class="license-info-grid">
								<div class="license-info-card <?php echo $is_valid ? 'status-valid' : 'status-invalid'; ?>">
									<div class="info-icon">
										<?php echo $is_valid ? '✅' : '❌'; ?>
									</div>
									<div class="info-content">
										<span class="info-label">Status</span>
										<span class="info-value">
											<?php echo esc_html( $this->license_manager->get_status_label( $license_status['status'] ) ); ?>
										</span>
									</div>
								</div>

								<?php if ( isset( $license_status['plan_name'] ) && ! empty( $license_status['plan_name'] ) ) : ?>
								<div class="license-info-card">
									<div class="info-icon">📦</div>
									<div class="info-content">
										<span class="info-label">Abonnement</span>
										<span class="info-value"><?php echo esc_html( $license_status['plan_name'] ); ?></span>
									</div>
								</div>
								<?php endif; ?>

								<?php if ( isset( $license_status['expires'] ) && ! empty( $license_status['expires'] ) ) : ?>
								<div class="license-info-card">
									<div class="info-icon">📅</div>
									<div class="info-content">
										<span class="info-label">Verloopt op</span>
										<span class="info-value"><?php echo esc_html( $license_status['expires'] ); ?></span>
										<?php if ( null !== $days_remaining ) : ?>
										<span class="info-subtext">
											<?php
											if ( $days_remaining > 0 ) {
												/* translators: %d: number of days */
												echo esc_html( sprintf( __( 'Nog %d dagen', 'writgocms' ), $days_remaining ) );
											} else {
												echo esc_html__( 'Verlopen', 'writgocms' );
											}
											?>
										</span>
										<?php endif; ?>
									</div>
								</div>
								<?php endif; ?>

								<div class="license-info-card">
									<div class="info-icon">📧</div>
									<div class="info-content">
										<span class="info-label">E-mail</span>
										<span class="info-value"><?php echo esc_html( $license_email ); ?></span>
									</div>
								</div>

								<div class="license-info-card">
									<div class="info-icon">🔑</div>
									<div class="info-content">
										<span class="info-label">Licentiesleutel</span>
										<span class="info-value license-key-masked">
											<?php echo esc_html( substr( $license_key, 0, 4 ) . '-****-****-' . substr( $license_key, -4 ) ); ?>
										</span>
									</div>
								</div>

								<?php if ( isset( $license_status['checked_at'] ) ) : ?>
								<div class="license-info-card">
									<div class="info-icon">🕐</div>
									<div class="info-content">
										<span class="info-label">Laatst gecontroleerd</span>
										<span class="info-value"><?php echo esc_html( $license_status['checked_at'] ); ?></span>
									</div>
								</div>
								<?php endif; ?>
							</div>

							<!-- Credit Usage Section -->
							<?php
							// Get credit info from Credit Manager.
							$credit_info = null;
							if ( class_exists( 'WritgoCMS_Credit_Manager' ) ) {
								$credit_manager = WritgoCMS_Credit_Manager::get_instance();
								$credit_info = $credit_manager->get_credit_info();
							}
							?>
							<?php if ( $credit_info && $credit_info['credits_total'] > 0 ) : ?>
							<div class="license-credits-section">
								<h3>🪙 <?php esc_html_e( 'Credit Balance', 'writgocms' ); ?></h3>
								<?php
								$remaining = $credit_info['credits_remaining'];
								$total = $credit_info['credits_total'];
								$used = $credit_info['credits_used'];
								$percentage = $total > 0 ? ( $remaining / $total ) * 100 : 0;
								$bar_color = $percentage > 50 ? '#28a745' : ( $percentage > 20 ? '#ffc107' : '#dc3545' );
								?>
								<div class="credits-display-large">
									<div class="credits-number"><?php echo number_format( $remaining ); ?></div>
									<div class="credits-label"><?php esc_html_e( 'credits remaining', 'writgocms' ); ?></div>
								</div>
								<div class="credits-bar-large">
									<div class="credits-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%; background: <?php echo esc_attr( $bar_color ); ?>;"></div>
								</div>
								<div class="credits-details">
									<div class="credits-detail-item">
										<span class="credits-detail-label"><?php esc_html_e( 'Used', 'writgocms' ); ?></span>
										<span class="credits-detail-value"><?php echo number_format( $used ); ?></span>
									</div>
									<div class="credits-detail-item">
										<span class="credits-detail-label"><?php esc_html_e( 'Total', 'writgocms' ); ?></span>
										<span class="credits-detail-value"><?php echo number_format( $total ); ?></span>
									</div>
									<?php if ( ! empty( $credit_info['period_end'] ) ) : ?>
									<div class="credits-detail-item">
										<span class="credits-detail-label"><?php esc_html_e( 'Resets On', 'writgocms' ); ?></span>
										<span class="credits-detail-value"><?php echo esc_html( $credit_info['period_end'] ); ?></span>
									</div>
									<?php endif; ?>
								</div>
								<div class="credits-cost-table">
									<h4><?php esc_html_e( 'Credit Costs per Action', 'writgocms' ); ?></h4>
									<table class="widefat">
										<tbody>
											<tr><td><?php esc_html_e( 'AI Rewrite (small)', 'writgocms' ); ?></td><td><strong>10</strong></td></tr>
											<tr><td><?php esc_html_e( 'AI Rewrite (paragraph)', 'writgocms' ); ?></td><td><strong>25</strong></td></tr>
											<tr><td><?php esc_html_e( 'AI Rewrite (full)', 'writgocms' ); ?></td><td><strong>50</strong></td></tr>
											<tr><td><?php esc_html_e( 'AI Image', 'writgocms' ); ?></td><td><strong>100</strong></td></tr>
											<tr><td><?php esc_html_e( 'SEO Analysis', 'writgocms' ); ?></td><td><strong>20</strong></td></tr>
											<tr><td><?php esc_html_e( 'Internal Links', 'writgocms' ); ?></td><td><strong>5</strong></td></tr>
											<tr><td><?php esc_html_e( 'Keyword Research', 'writgocms' ); ?></td><td><strong>15</strong></td></tr>
										</tbody>
									</table>
								</div>
							</div>
							<?php endif; ?>

							<!-- Usage limits if available -->
							<?php if ( isset( $license_status['usage'] ) && ! empty( $license_status['usage'] ) ) : ?>
							<div class="license-usage-section">
								<h3>📊 Gebruik</h3>
								<div class="usage-grid">
									<?php if ( isset( $license_status['usage']['text_generations'] ) ) : ?>
									<div class="usage-item">
										<span class="usage-label">Tekst Generaties</span>
										<div class="usage-bar">
											<?php
											$used  = $license_status['usage']['text_generations'];
											$limit = isset( $license_status['limits']['text_generations'] ) ? $license_status['limits']['text_generations'] : 'onbeperkt';
											$percentage = is_numeric( $limit ) && $limit > 0 ? min( 100, ( $used / $limit ) * 100 ) : 0;
											?>
											<div class="usage-progress" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
										</div>
										<span class="usage-value">
											<?php echo esc_html( $used ); ?> / <?php echo esc_html( $limit ); ?>
										</span>
									</div>
									<?php endif; ?>

									<?php if ( isset( $license_status['usage']['image_generations'] ) ) : ?>
									<div class="usage-item">
										<span class="usage-label">Afbeelding Generaties</span>
										<div class="usage-bar">
											<?php
											$used  = $license_status['usage']['image_generations'];
											$limit = isset( $license_status['limits']['image_generations'] ) ? $license_status['limits']['image_generations'] : 'onbeperkt';
											$percentage = is_numeric( $limit ) && $limit > 0 ? min( 100, ( $used / $limit ) * 100 ) : 0;
											?>
											<div class="usage-progress" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
										</div>
										<span class="usage-value">
											<?php echo esc_html( $used ); ?> / <?php echo esc_html( $limit ); ?>
										</span>
									</div>
									<?php endif; ?>
								</div>
							</div>
							<?php endif; ?>

							<!-- Features if available -->
							<?php if ( isset( $license_status['features'] ) && ! empty( $license_status['features'] ) ) : ?>
							<div class="license-features-section">
								<h3>⭐ Inbegrepen Features</h3>
								<ul class="features-list">
									<?php foreach ( $license_status['features'] as $feature ) : ?>
									<li>✅ <?php echo esc_html( $feature ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<?php endif; ?>

							<!-- License actions -->
							<div class="license-actions">
								<button type="button" id="refresh-license-btn" class="button button-secondary">
									🔄 Status Vernieuwen
								</button>
								<button type="button" id="check-updates-btn" class="button button-secondary">
									⬆️ Controleer Updates
								</button>
								<a href="https://writgoai.com/account" target="_blank" class="button button-secondary">
									👤 Mijn Account
								</a>
								<button type="button" id="deactivate-license-btn" class="button button-link-delete">
									❌ Licentie Deactiveren
								</button>
							</div>

							<div class="license-status-message"></div>
						</div>

						<!-- Update info section -->
						<div class="planner-card">
							<h2>⬆️ Plugin Updates</h2>
							<div class="update-info">
								<p>
									<strong>Huidige versie:</strong> <?php echo esc_html( WRITGOCMS_VERSION ); ?>
								</p>
								<p class="description">
									Met een actieve licentie ontvang je automatisch updates via het WordPress update systeem.
								</p>
								<div id="update-check-result"></div>
							</div>
						</div>

						<!-- Quick links -->
						<div class="planner-card">
							<h2>🔗 Snelle Links</h2>
							<div class="quick-links-grid">
								<a href="https://writgoai.com/docs" target="_blank" class="quick-link">
									📚 Documentatie
								</a>
								<a href="https://writgoai.com/support" target="_blank" class="quick-link">
									🆘 Support
								</a>
								<a href="https://writgoai.com/changelog" target="_blank" class="quick-link">
									📝 Changelog
								</a>
								<a href="https://writgoai.com/pricing" target="_blank" class="quick-link">
									💰 Prijzen & Abonnementen
								</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<style>
			/* Consistent Card Grid */
			.writgocms-license-page .license-info-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 15px;
				margin-bottom: 25px;
			}

			/* License Info Cards - Consistent Styling */
			.writgocms-license-page .license-info-card {
				background: #fff;
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				padding: 16px;
				display: flex;
				align-items: flex-start;
				gap: 12px;
				transition: all 0.2s ease;
			}

			.writgocms-license-page .license-info-card:hover {
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
				transform: translateY(-1px);
			}

			.writgocms-license-page .license-info-card.status-valid {
				background: #f0fdf4;
				border-color: #86efac;
			}

			.writgocms-license-page .license-info-card.status-invalid {
				background: #fef2f2;
				border-color: #fca5a5;
			}

			.writgocms-license-page .info-icon {
				font-size: 24px;
				line-height: 1;
			}

			.writgocms-license-page .info-content {
				display: flex;
				flex-direction: column;
			}

			.writgocms-license-page .info-label {
				font-size: 12px;
				color: #6c757d;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.writgocms-license-page .info-value {
				font-size: 16px;
				font-weight: 600;
				color: #212529;
			}

			.writgocms-license-page .info-subtext {
				font-size: 12px;
				color: #6c757d;
			}

			.writgocms-license-page .license-key-masked {
				font-family: monospace;
				letter-spacing: 1px;
			}

			.writgocms-license-page .license-actions {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				padding-top: 20px;
				border-top: 1px solid #e9ecef;
				margin-top: 20px;
			}

			/* Better button styling */
			.writgocms-license-page .license-actions .button {
				padding: 8px 16px;
				min-height: 36px;
				display: inline-flex;
				align-items: center;
				gap: 6px;
				border-radius: 6px;
				transition: all 0.2s ease;
			}

			.writgocms-license-page .license-actions .button:hover {
				transform: translateY(-1px);
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
			}

			.writgocms-license-page .button-primary {
				background: linear-gradient(135deg, #1877F2 0%, #3b82f6 100%);
				border: none;
				color: #fff;
			}

			.writgocms-license-page .button-secondary {
				background: #fff;
				border: 1px solid #e2e8f0;
				color: #1e293b;
			}

			.writgocms-license-page .button-secondary:hover {
				border-color: #1877F2;
				color: #1877F2;
			}

			.writgocms-license-page .button-link-delete {
				color: #dc2626;
			}

			.writgocms-license-page .button-link-delete:hover {
				color: #991b1b;
				background: #fef2f2;
			}

			.writgocms-license-page .license-usage-section,
			.writgocms-license-page .license-features-section {
				margin-top: 25px;
				padding-top: 20px;
				border-top: 1px solid #e9ecef;
			}

			.writgocms-license-page .usage-grid {
				display: grid;
				gap: 15px;
			}

			.writgocms-license-page .usage-item {
				display: flex;
				flex-direction: column;
				gap: 5px;
			}

			.writgocms-license-page .usage-bar {
				height: 8px;
				background: #e9ecef;
				border-radius: 4px;
				overflow: hidden;
			}

			.writgocms-license-page .usage-progress {
				height: 100%;
				background: linear-gradient(90deg, #007bff, #28a745);
				border-radius: 4px;
				transition: width 0.3s ease;
			}

			.writgocms-license-page .usage-value {
				font-size: 12px;
				color: #6c757d;
			}

			.writgocms-license-page .features-list {
				columns: 2;
				column-gap: 20px;
				list-style: none;
				margin: 0;
				padding: 0;
			}

			.writgocms-license-page .features-list li {
				padding: 5px 0;
			}

			.writgocms-license-page .quick-links-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
				gap: 12px;
			}

			.writgocms-license-page .quick-link {
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 16px;
				background: #fff;
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				text-decoration: none;
				color: #1e293b;
				font-weight: 500;
				transition: all 0.2s ease;
			}

			.writgocms-license-page .quick-link:hover {
				background: #f8fafc;
				border-color: #1877F2;
				color: #1877F2;
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			}

			.writgocms-license-page .license-help-section {
				margin-top: 30px;
				padding-top: 25px;
				border-top: 1px solid #e9ecef;
			}

			.writgocms-license-page .license-help-section h3 {
				color: #0f172a;
				font-size: 18px;
				margin-bottom: 12px;
			}

			.writgocms-license-page .license-help-section ul {
				list-style: none;
				padding: 0;
				margin: 15px 0;
			}

			.writgocms-license-page .license-help-section li {
				padding: 8px 0;
				font-size: 14px;
				color: #475569;
			}

			/* License activation form styling */
			.writgocms-license-page .license-activation-form .button-primary.button-hero,
			.writgocms-license-page .button-hero {
				padding: 12px 24px;
				font-size: 16px;
				min-height: 44px;
			}

			.writgocms-license-page .license-status-message {
				margin-left: 15px;
				font-weight: 500;
			}

			.writgocms-license-page .license-status-message.success {
				color: #28a745;
			}

			.writgocms-license-page .license-status-message.error {
				color: #dc3545;
			}

			#update-check-result {
				margin-top: 15px;
				padding: 15px;
				background: #f8f9fa;
				border-radius: 8px;
				display: none;
			}

			#update-check-result.visible {
				display: block;
			}

			#update-check-result.has-update {
				background: #d4edda;
				border: 1px solid #c3e6cb;
			}

			/* Consistent Planner Cards */
			.writgocms-license-page .planner-card {
				background: #fff;
				border: 1px solid #e2e8f0;
				border-radius: 12px;
				padding: 24px;
				margin-bottom: 20px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
				transition: all 0.2s ease;
			}

			.writgocms-license-page .planner-card:hover {
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
			}

			.writgocms-license-page .planner-card h2 {
				margin: 0 0 20px 0;
				padding-bottom: 15px;
				border-bottom: 2px solid #e2e8f0;
				font-size: 20px;
				font-weight: 600;
				color: #0f172a;
			}

			/* Credit Display Styles */
			.writgocms-license-page .license-credits-section {
				margin-top: 25px;
				padding-top: 20px;
				border-top: 1px solid #e9ecef;
			}

			.writgocms-license-page .credits-display-large {
				text-align: center;
				padding: 20px;
				background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
				border-radius: 10px;
				margin-bottom: 15px;
			}

			.writgocms-license-page .credits-number {
				font-size: 48px;
				font-weight: bold;
				color: #0369a1;
				line-height: 1;
			}

			.writgocms-license-page .credits-label {
				font-size: 14px;
				color: #64748b;
				margin-top: 5px;
			}

			.writgocms-license-page .credits-bar-large {
				height: 12px;
				background: #e2e8f0;
				border-radius: 6px;
				overflow: hidden;
				margin-bottom: 15px;
			}

			.writgocms-license-page .credits-bar-fill {
				height: 100%;
				border-radius: 6px;
				transition: width 0.3s ease;
			}

			.writgocms-license-page .credits-details {
				display: grid;
				grid-template-columns: repeat(3, 1fr);
				gap: 15px;
				margin-bottom: 20px;
			}

			.writgocms-license-page .credits-detail-item {
				text-align: center;
				padding: 10px;
				background: #f8f9fa;
				border-radius: 8px;
			}

			.writgocms-license-page .credits-detail-label {
				display: block;
				font-size: 12px;
				color: #64748b;
				text-transform: uppercase;
			}

			.writgocms-license-page .credits-detail-value {
				display: block;
				font-size: 18px;
				font-weight: 600;
				color: #1e293b;
			}

			.writgocms-license-page .credits-cost-table {
				margin-top: 20px;
			}

			.writgocms-license-page .credits-cost-table h4 {
				margin: 0 0 10px 0;
				font-size: 14px;
				color: #475569;
			}

			.writgocms-license-page .credits-cost-table table {
				max-width: 400px;
			}

			.writgocms-license-page .credits-cost-table td {
				padding: 8px 12px;
			}

			.writgocms-license-page .credits-cost-table td:last-child {
				text-align: right;
				color: #0369a1;
			}

			/* Mobile Responsiveness */
			@media screen and (max-width: 782px) {
				.writgocms-license-page .license-info-grid {
					grid-template-columns: 1fr;
					gap: 12px;
				}

				.writgocms-license-page .license-info-card {
					padding: 14px;
				}

				.writgocms-license-page .quick-links-grid {
					grid-template-columns: 1fr;
					gap: 10px;
				}

				.writgocms-license-page .license-actions {
					flex-direction: column;
					gap: 8px;
				}

				.writgocms-license-page .license-actions .button {
					width: 100%;
					text-align: center;
				}

				.writgocms-license-page .credits-details {
					grid-template-columns: 1fr;
					gap: 10px;
				}

				.writgocms-license-page .features-list {
					columns: 1;
				}
			}

			@media screen and (max-width: 480px) {
				.writgocms-license-page .info-value {
					font-size: 14px;
				}

				.writgocms-license-page .credits-number {
					font-size: 36px;
				}
			}

			/* Auth User Panel Styles */
			.writgocms-license-page .auth-user-info {
				display: flex;
				align-items: center;
				gap: 24px;
				padding: 24px;
				background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
				border-radius: 12px;
				margin-bottom: 24px;
			}

			.writgocms-license-page .user-avatar {
				flex-shrink: 0;
			}

			.writgocms-license-page .user-icon {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 80px;
				height: 80px;
				background: white;
				border-radius: 50%;
				font-size: 40px;
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			}

			.writgocms-license-page .user-details {
				flex: 1;
			}

			.writgocms-license-page .user-name {
				font-size: 24px;
				font-weight: 600;
				color: #0f172a;
				margin: 0 0 8px 0;
			}

			.writgocms-license-page .user-email {
				font-size: 14px;
				color: #64748b;
				margin: 4px 0;
			}

			.writgocms-license-page .user-company {
				font-size: 14px;
				color: #94a3b8;
				margin: 4px 0;
			}

			.writgocms-license-page .auth-actions {
				display: flex;
				flex-wrap: wrap;
				gap: 12px;
				padding-top: 20px;
				border-top: 1px solid #e2e8f0;
			}

			.writgocms-license-page .auth-status-message-settings {
				margin-top: 16px;
				padding: 12px 16px;
				border-radius: 8px;
				font-size: 14px;
				display: none;
			}

			.writgocms-license-page .auth-status-message-settings.success {
				background: #d1fae5;
				color: #065f46;
				border: 1px solid #10b981;
				display: block;
			}

			.writgocms-license-page .auth-status-message-settings.error {
				background: #fee2e2;
				color: #991b1b;
				border: 1px solid #ef4444;
				display: block;
			}

			.writgocms-license-page .auth-help-section {
				margin-top: 24px;
				padding-top: 24px;
				border-top: 1px solid #e9ecef;
			}

			@media screen and (max-width: 782px) {
				.writgocms-license-page .auth-user-info {
					flex-direction: column;
					text-align: center;
				}

				.writgocms-license-page .auth-actions {
					flex-direction: column;
				}

				.writgocms-license-page .auth-actions .button {
					width: 100%;
					text-align: center;
				}
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// License activation form.
			$('#license-activation-form').on('submit', function(e) {
				e.preventDefault();

				var $btn = $('#activate-license-btn');
				var $message = $('.license-status-message');
				var email = $('#license-email').val();
				var licenseKey = $('#license-key').val();

				$btn.prop('disabled', true).text('Activeren...');
				$message.removeClass('success error').text('');

				$.ajax({
					url: writgocmsLicense.ajaxUrl,
					type: 'POST',
					data: {
						action: 'writgocms_activate_license',
						nonce: writgocmsLicense.nonce,
						email: email,
						license_key: licenseKey
					},
					success: function(response) {
						if (response.success) {
							$message.addClass('success').text(response.data.message);
							setTimeout(function() {
								location.reload();
							}, 1500);
						} else {
							$message.addClass('error').text(response.data.message);
							$btn.prop('disabled', false).text('✅ Licentie Activeren');
						}
					},
					error: function() {
						$message.addClass('error').text('Er is een fout opgetreden.');
						$btn.prop('disabled', false).text('✅ Licentie Activeren');
					}
				});
			});

			// Deactivate license.
			$('#deactivate-license-btn').on('click', function() {
				if (!confirm(writgocmsLicense.i18n.confirmDeactivate)) {
					return;
				}

				var $btn = $(this);
				var $message = $('.license-status-message');

				$btn.prop('disabled', true).text('Deactiveren...');
				$message.removeClass('success error').text('');

				$.ajax({
					url: writgocmsLicense.ajaxUrl,
					type: 'POST',
					data: {
						action: 'writgocms_deactivate_license',
						nonce: writgocmsLicense.nonce
					},
					success: function(response) {
						if (response.success) {
							$message.addClass('success').text(response.data.message);
							setTimeout(function() {
								location.reload();
							}, 1500);
						} else {
							$message.addClass('error').text(response.data.message);
							$btn.prop('disabled', false).text('❌ Licentie Deactiveren');
						}
					},
					error: function() {
						$message.addClass('error').text('Er is een fout opgetreden.');
						$btn.prop('disabled', false).text('❌ Licentie Deactiveren');
					}
				});
			});

			// Refresh license status.
			$('#refresh-license-btn').on('click', function() {
				var $btn = $(this);
				var $message = $('.license-status-message');

				$btn.prop('disabled', true).text('Bijwerken...');
				$message.removeClass('success error').text('');

				$.ajax({
					url: writgocmsLicense.ajaxUrl,
					type: 'POST',
					data: {
						action: 'writgocms_refresh_license',
						nonce: writgocmsLicense.nonce
					},
					success: function(response) {
						if (response.success) {
							$message.addClass('success').text(response.data.message);
							setTimeout(function() {
								location.reload();
							}, 1000);
						} else {
							$message.addClass('error').text(response.data.message);
						}
						$btn.prop('disabled', false).text('🔄 Status Vernieuwen');
					},
					error: function() {
						$message.addClass('error').text('Er is een fout opgetreden.');
						$btn.prop('disabled', false).text('🔄 Status Vernieuwen');
					}
				});
			});

			// Check for updates.
			$('#check-updates-btn').on('click', function() {
				var $btn = $(this);
				var $result = $('#update-check-result');

				$btn.prop('disabled', true).text('Controleren...');
				$result.removeClass('visible has-update').html('');

				$.ajax({
					url: writgocmsLicense.ajaxUrl,
					type: 'POST',
					data: {
						action: 'writgocms_check_updates',
						nonce: writgocmsLicense.nonce
					},
					success: function(response) {
						if (response.success) {
							var html = '<p><strong>' + response.data.message + '</strong></p>';
							html += '<p>Huidige versie: ' + response.data.current_version + '</p>';
							html += '<p>Nieuwste versie: ' + response.data.latest_version + '</p>';

							if (response.data.has_update) {
								$result.addClass('has-update');
								html += '<p><a href="' + writgocmsLicense.updateUrl + '" class="button button-primary">Ga naar Updates</a></p>';
							}

							$result.html(html).addClass('visible');
						} else {
							$result.html('<p class="error">' + response.data.message + '</p>').addClass('visible');
						}
						$btn.prop('disabled', false).text('⬆️ Controleer Updates');
					},
					error: function() {
						$result.html('<p class="error">Er is een fout opgetreden.</p>').addClass('visible');
						$btn.prop('disabled', false).text('⬆️ Controleer Updates');
					}
				});
			});
		});

		// Settings login form.
		$('#settings-login-form').on('submit', function(e) {
			e.preventDefault();

			var $form = $(this);
			var $btn = $('#settings-login-btn');
			var $message = $('.auth-status-message');
			var email = $('#settings-email').val();
			var password = $('#settings-password').val();

			if (!email || !password) {
				$message.removeClass('success').addClass('error').text('Vul alle velden in.');
				return;
			}

			$btn.prop('disabled', true).text('Inloggen...');
			$message.removeClass('success error').text('');

			$.ajax({
				url: writgocmsLicense.ajaxUrl,
				type: 'POST',
				data: {
					action: 'writgocms_login',
					nonce: writgocmsLicense.nonce,
					email: email,
					password: password
				},
				success: function(response) {
					if (response.success) {
						$message.addClass('success').text(response.data.message || 'Login succesvol!');
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						$message.addClass('error').text(response.data.message || 'Login mislukt.');
						$btn.prop('disabled', false).text('🔓 Inloggen');
					}
				},
				error: function() {
					$message.addClass('error').text('Er is een fout opgetreden.');
					$btn.prop('disabled', false).text('🔓 Inloggen');
				}
			});
		});

		// Logout button.
		$('#logout-btn').on('click', function() {
			if (!confirm('Weet je zeker dat je wilt uitloggen?')) {
				return;
			}

			var $btn = $(this);
			var $message = $('.auth-status-message-settings');

			$btn.prop('disabled', true).text('Uitloggen...');
			$message.removeClass('success error').text('');

			$.ajax({
				url: writgocmsLicense.ajaxUrl,
				type: 'POST',
				data: {
					action: 'writgocms_logout',
					nonce: writgocmsLicense.nonce
				},
				success: function(response) {
					if (response.success) {
						$message.addClass('success').text(response.data.message || 'Uitgelogd.');
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						$message.addClass('error').text(response.data.message || 'Uitloggen mislukt.');
						$btn.prop('disabled', false).text('🚪 Uitloggen');
					}
				},
				error: function() {
					$message.addClass('error').text('Er is een fout opgetreden.');
					$btn.prop('disabled', false).text('🚪 Uitloggen');
				}
			});
		});
		</script>
		<?php
	}
}

// Initialize.
add_action( 'plugins_loaded', function() {
	WritgoCMS_License_Admin::get_instance();
}, 15 );
