<?php
/**
 * GSC Admin Settings Panel
 *
 * Admin interface for Google Search Console integration.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_GSC_Admin_Settings
 */
class WritgoCMS_GSC_Admin_Settings {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_GSC_Admin_Settings
	 */
	private static $instance = null;

	/**
	 * GSC Provider instance
	 *
	 * @var WritgoCMS_GSC_Provider
	 */
	private $provider;

	/**
	 * GSC Data Handler instance
	 *
	 * @var WritgoCMS_GSC_Data_Handler
	 */
	private $data_handler;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_GSC_Admin_Settings
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
		$this->provider     = WritgoCMS_GSC_Provider::get_instance();
		$this->data_handler = WritgoCMS_GSC_Data_Handler::get_instance();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Add GSC Dashboard submenu under WritgoAI.
		add_submenu_page(
			'writgocms-aiml',
			__( 'Search Console', 'writgocms' ),
			__( 'Search Console', 'writgocms' ),
			'manage_options',
			'writgocms-gsc',
			array( $this, 'render_dashboard_page' )
		);

		// Add CTR Optimizer submenu.
		add_submenu_page(
			'writgocms-aiml',
			__( 'CTR Optimalisatie', 'writgocms' ),
			__( 'CTR Optimalisatie', 'writgocms' ),
			'manage_options',
			'writgocms-ctr-optimizer',
			array( $this, 'render_ctr_optimizer_page' )
		);

		// Add GSC Settings submenu.
		add_submenu_page(
			'writgocms-aiml',
			__( 'GSC Instellingen', 'writgocms' ),
			__( 'GSC Instellingen', 'writgocms' ),
			'manage_options',
			'writgocms-gsc-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'writgocms_gsc_settings', 'writgocms_gsc_client_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'writgocms_gsc_settings', 'writgocms_gsc_client_secret', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$allowed_hooks = array(
			'writgoai_page_writgocms-gsc',
			'writgoai_page_writgocms-ctr-optimizer',
			'writgoai_page_writgocms-gsc-settings',
		);

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'writgocms-gsc-admin',
			WRITGOCMS_URL . 'assets/css/gsc-admin.css',
			array(),
			WRITGOCMS_VERSION
		);

		wp_enqueue_script(
			'writgocms-gsc-admin',
			WRITGOCMS_URL . 'assets/js/gsc-admin.js',
			array( 'jquery' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_localize_script(
			'writgocms-gsc-admin',
			'writgocmsGsc',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'writgocms_gsc_nonce' ),
				'isConnected'  => $this->provider->is_connected(),
				'selectedSite' => $this->provider->get_selected_site(),
				'i18n'         => array(
					'connecting'         => __( 'Verbinden...', 'writgocms' ),
					'connected'          => __( 'Verbonden', 'writgocms' ),
					'disconnected'       => __( 'Niet verbonden', 'writgocms' ),
					'syncing'            => __( 'Synchroniseren...', 'writgocms' ),
					'syncComplete'       => __( 'Synchronisatie voltooid', 'writgocms' ),
					'error'              => __( 'Fout', 'writgocms' ),
					'loading'            => __( 'Laden...', 'writgocms' ),
					'noData'             => __( 'Geen data beschikbaar', 'writgocms' ),
					'quickWins'          => __( 'Quick Wins', 'writgocms' ),
					'lowCtr'             => __( 'Lage CTR', 'writgocms' ),
					'declining'          => __( 'Dalende Rankings', 'writgocms' ),
					'contentGaps'        => __( 'Content Gaps', 'writgocms' ),
					'analyzing'          => __( 'Analyseren...', 'writgocms' ),
					'generating'         => __( 'Suggesties genereren...', 'writgocms' ),
					'selectSite'         => __( 'Selecteer een site', 'writgocms' ),
					'confirmDisconnect'  => __( 'Weet je zeker dat je de verbinding wilt verbreken?', 'writgocms' ),
				),
			)
		);
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard_page() {
		$is_connected  = $this->provider->is_connected();
		$selected_site = $this->provider->get_selected_site();
		$last_sync     = get_option( 'writgocms_gsc_last_sync', '' );
		?>
		<div class="wrap writgocms-aiml-settings writgocms-gsc-dashboard">
			<h1 class="aiml-header">
				<span class="aiml-logo">📊</span>
				<?php esc_html_e( 'Google Search Console Dashboard', 'writgocms' ); ?>
			</h1>

			<div class="aiml-tab-content">
				<?php if ( ! $is_connected ) : ?>
					<div class="gsc-not-connected">
						<div class="gsc-icon">🔗</div>
						<h2><?php esc_html_e( 'Verbind met Google Search Console', 'writgocms' ); ?></h2>
						<p><?php esc_html_e( 'Verbind je Google Search Console account om search data te bekijken en keyword opportuniteiten te ontdekken.', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-gsc-settings' ) ); ?>" class="button button-primary button-hero">
							⚙️ <?php esc_html_e( 'Configureer GSC Verbinding', 'writgocms' ); ?>
						</a>
					</div>
				<?php elseif ( empty( $selected_site ) ) : ?>
					<div class="gsc-not-connected">
						<div class="gsc-icon">🌐</div>
						<h2><?php esc_html_e( 'Selecteer een Site', 'writgocms' ); ?></h2>
						<p><?php esc_html_e( 'Je bent verbonden met Google Search Console. Selecteer nu een site om data te bekijken.', 'writgocms' ); ?></p>
						<div id="gsc-site-selector">
							<button type="button" id="load-sites-btn" class="button button-secondary">
								<?php esc_html_e( 'Laad beschikbare sites', 'writgocms' ); ?>
							</button>
							<div id="sites-list" style="display:none; margin-top: 15px;"></div>
						</div>
					</div>
				<?php else : ?>
					<!-- Connected Dashboard -->
					<div class="gsc-status-bar">
						<div class="status-item">
							<span class="status-label"><?php esc_html_e( 'Verbonden site:', 'writgocms' ); ?></span>
							<span class="status-value"><?php echo esc_html( $selected_site ); ?></span>
						</div>
						<div class="status-item">
							<span class="status-label"><?php esc_html_e( 'Laatste sync:', 'writgocms' ); ?></span>
							<span class="status-value"><?php echo esc_html( $last_sync ? $last_sync : __( 'Nog niet gesynchroniseerd', 'writgocms' ) ); ?></span>
						</div>
						<div class="status-actions">
							<button type="button" id="sync-now-btn" class="button button-secondary">
								🔄 <?php esc_html_e( 'Synchroniseer Nu', 'writgocms' ); ?>
							</button>
						</div>
					</div>

					<!-- Metrics Overview -->
					<div class="gsc-metrics-grid" id="gsc-metrics">
						<div class="gsc-metric-card">
							<span class="metric-icon">👆</span>
							<div class="metric-content">
								<span class="metric-value" id="total-clicks">-</span>
								<span class="metric-label"><?php esc_html_e( 'Clicks', 'writgocms' ); ?></span>
							</div>
						</div>
						<div class="gsc-metric-card">
							<span class="metric-icon">👁️</span>
							<div class="metric-content">
								<span class="metric-value" id="total-impressions">-</span>
								<span class="metric-label"><?php esc_html_e( 'Impressies', 'writgocms' ); ?></span>
							</div>
						</div>
						<div class="gsc-metric-card">
							<span class="metric-icon">📈</span>
							<div class="metric-content">
								<span class="metric-value" id="avg-ctr">-</span>
								<span class="metric-label"><?php esc_html_e( 'Gemiddelde CTR', 'writgocms' ); ?></span>
							</div>
						</div>
						<div class="gsc-metric-card">
							<span class="metric-icon">🎯</span>
							<div class="metric-content">
								<span class="metric-value" id="avg-position">-</span>
								<span class="metric-label"><?php esc_html_e( 'Gemiddelde Positie', 'writgocms' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Opportunities Section -->
					<div class="gsc-opportunities-section">
						<h2>🎯 <?php esc_html_e( 'Keyword Opportuniteiten', 'writgocms' ); ?></h2>
						
						<div class="opportunity-tabs">
							<button type="button" class="opportunity-tab active" data-type="quick_win">
								🚀 <?php esc_html_e( 'Quick Wins', 'writgocms' ); ?>
								<span class="tab-count" id="count-quick_win">0</span>
							</button>
							<button type="button" class="opportunity-tab" data-type="low_ctr">
								📉 <?php esc_html_e( 'Lage CTR', 'writgocms' ); ?>
								<span class="tab-count" id="count-low_ctr">0</span>
							</button>
							<button type="button" class="opportunity-tab" data-type="declining">
								⬇️ <?php esc_html_e( 'Dalende Rankings', 'writgocms' ); ?>
								<span class="tab-count" id="count-declining">0</span>
							</button>
							<button type="button" class="opportunity-tab" data-type="content_gap">
								📝 <?php esc_html_e( 'Content Gaps', 'writgocms' ); ?>
								<span class="tab-count" id="count-content_gap">0</span>
							</button>
						</div>

						<div id="opportunities-list" class="opportunities-list">
							<p class="loading-text"><?php esc_html_e( 'Laden...', 'writgocms' ); ?></p>
						</div>
					</div>

					<!-- Top Content Section -->
					<div class="gsc-content-section">
						<div class="content-column">
							<h3>🔍 <?php esc_html_e( 'Top Keywords', 'writgocms' ); ?></h3>
							<div id="top-queries" class="data-table-container">
								<p class="loading-text"><?php esc_html_e( 'Laden...', 'writgocms' ); ?></p>
							</div>
						</div>
						<div class="content-column">
							<h3>📄 <?php esc_html_e( 'Top Pagina\'s', 'writgocms' ); ?></h3>
							<div id="top-pages" class="data-table-container">
								<p class="loading-text"><?php esc_html_e( 'Laden...', 'writgocms' ); ?></p>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render CTR optimizer page
	 */
	public function render_ctr_optimizer_page() {
		$posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		?>
		<div class="wrap writgocms-aiml-settings writgocms-ctr-optimizer">
			<h1 class="aiml-header">
				<span class="aiml-logo">✨</span>
				<?php esc_html_e( 'CTR Optimalisatie Tool', 'writgocms' ); ?>
			</h1>

			<div class="aiml-tab-content">
				<div class="ctr-optimizer-intro">
					<h2><?php esc_html_e( 'Verbeter je Click-Through Rate', 'writgocms' ); ?></h2>
					<p><?php esc_html_e( 'Analyseer je meta titles en descriptions en krijg AI-gegenereerde suggesties voor betere CTR.', 'writgocms' ); ?></p>
				</div>

				<div class="ctr-optimizer-grid">
					<div class="ctr-selector-panel">
						<h3>📄 <?php esc_html_e( 'Selecteer een Post', 'writgocms' ); ?></h3>
						<div class="post-search">
							<input type="text" id="post-search-input" placeholder="<?php esc_attr_e( 'Zoek posts...', 'writgocms' ); ?>">
						</div>
						<div class="post-list" id="post-list">
							<?php foreach ( $posts as $post ) : ?>
								<div class="post-item" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
									<span class="post-title"><?php echo esc_html( $post->post_title ); ?></span>
									<span class="post-type"><?php echo esc_html( $post->post_type ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ctr-analysis-panel" id="ctr-analysis-panel" style="display: none;">
						<h3>📊 <?php esc_html_e( 'CTR Analyse', 'writgocms' ); ?></h3>
						<div id="ctr-analysis-content">
							<!-- Dynamic content -->
						</div>

						<div class="ctr-actions">
							<div class="keyword-input">
								<label for="target-keyword"><?php esc_html_e( 'Target Keyword (optioneel):', 'writgocms' ); ?></label>
								<input type="text" id="target-keyword" placeholder="<?php esc_attr_e( 'Voer target keyword in', 'writgocms' ); ?>">
							</div>
							<button type="button" id="generate-suggestions-btn" class="button button-primary button-hero">
								✨ <?php esc_html_e( 'Genereer AI Suggesties', 'writgocms' ); ?>
							</button>
						</div>

						<div id="ctr-suggestions" style="display: none;">
							<h3>💡 <?php esc_html_e( 'AI Suggesties', 'writgocms' ); ?></h3>
							<div id="suggestions-content">
								<!-- Dynamic content -->
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		$is_connected = $this->provider->is_connected();
		?>
		<div class="wrap writgocms-aiml-settings">
			<h1 class="aiml-header">
				<span class="aiml-logo">⚙️</span>
				<?php esc_html_e( 'Google Search Console Instellingen', 'writgocms' ); ?>
			</h1>

			<div class="aiml-tab-content">
				<form method="post" action="options.php">
					<?php settings_fields( 'writgocms_gsc_settings' ); ?>

					<div class="aiml-settings-section">
						<h2><?php esc_html_e( 'OAuth 2.0 Configuratie', 'writgocms' ); ?></h2>
						<p class="description">
							<?php
							printf(
								/* translators: %s: Google Cloud Console URL */
								esc_html__( 'Maak een OAuth 2.0 Client ID aan in de %s. Voeg de redirect URI toe aan de authorized redirect URIs.', 'writgocms' ),
								'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>'
							);
							?>
						</p>
						<p class="description">
							<strong><?php esc_html_e( 'Redirect URI:', 'writgocms' ); ?></strong>
							<code><?php echo esc_html( $this->provider->get_redirect_uri() ); ?></code>
						</p>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="writgocms_gsc_client_id"><?php esc_html_e( 'Client ID', 'writgocms' ); ?></label>
								</th>
								<td>
									<input type="text" id="writgocms_gsc_client_id" name="writgocms_gsc_client_id" value="<?php echo esc_attr( get_option( 'writgocms_gsc_client_id' ) ); ?>" class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="writgocms_gsc_client_secret"><?php esc_html_e( 'Client Secret', 'writgocms' ); ?></label>
								</th>
								<td>
									<div class="api-key-field">
										<input type="password" id="writgocms_gsc_client_secret" name="writgocms_gsc_client_secret" value="<?php echo esc_attr( get_option( 'writgocms_gsc_client_secret' ) ); ?>" class="regular-text">
										<button type="button" class="button toggle-password">👁️</button>
									</div>
								</td>
							</tr>
						</table>
					</div>

					<?php submit_button( __( 'Instellingen Opslaan', 'writgocms' ) ); ?>
				</form>

				<div class="aiml-settings-section">
					<h2><?php esc_html_e( 'Verbindingsstatus', 'writgocms' ); ?></h2>
					
					<?php if ( $is_connected ) : ?>
						<div class="gsc-connection-status connected">
							<span class="status-icon">✅</span>
							<span class="status-text"><?php esc_html_e( 'Verbonden met Google Search Console', 'writgocms' ); ?></span>
						</div>
						<p>
							<strong><?php esc_html_e( 'Geselecteerde site:', 'writgocms' ); ?></strong>
							<?php echo esc_html( $this->provider->get_selected_site() ?: __( 'Geen site geselecteerd', 'writgocms' ) ); ?>
						</p>
						<button type="button" id="disconnect-gsc-btn" class="button button-secondary">
							🔌 <?php esc_html_e( 'Verbinding Verbreken', 'writgocms' ); ?>
						</button>
					<?php else : ?>
						<div class="gsc-connection-status disconnected">
							<span class="status-icon">❌</span>
							<span class="status-text"><?php esc_html_e( 'Niet verbonden', 'writgocms' ); ?></span>
						</div>
						<?php
						$auth_url = $this->provider->get_auth_url();
						if ( ! empty( $auth_url ) ) :
							?>
							<p><?php esc_html_e( 'Klik op de knop hieronder om te verbinden met Google Search Console.', 'writgocms' ); ?></p>
							<a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary button-hero">
								🔗 <?php esc_html_e( 'Verbind met Google', 'writgocms' ); ?>
							</a>
						<?php else : ?>
							<p class="notice notice-warning"><?php esc_html_e( 'Configureer eerst de Client ID en Client Secret hierboven.', 'writgocms' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php if ( $is_connected ) : ?>
					<div class="aiml-settings-section">
						<h2><?php esc_html_e( 'Site Selectie', 'writgocms' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Selecteer de site waarvan je data wilt bekijken.', 'writgocms' ); ?></p>
						
						<button type="button" id="load-sites-btn" class="button button-secondary">
							🔄 <?php esc_html_e( 'Laad Sites', 'writgocms' ); ?>
						</button>
						<div id="sites-list" style="margin-top: 15px;"></div>
					</div>

					<div class="aiml-settings-section">
						<h2><?php esc_html_e( 'Data Synchronisatie', 'writgocms' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Data wordt automatisch dagelijks gesynchroniseerd. Je kunt ook handmatig synchroniseren.', 'writgocms' ); ?></p>
						
						<p>
							<strong><?php esc_html_e( 'Laatste synchronisatie:', 'writgocms' ); ?></strong>
							<?php echo esc_html( get_option( 'writgocms_gsc_last_sync', __( 'Nog niet gesynchroniseerd', 'writgocms' ) ) ); ?>
						</p>
						
						<button type="button" id="sync-now-btn" class="button button-primary">
							🔄 <?php esc_html_e( 'Nu Synchroniseren', 'writgocms' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

// Initialize admin settings.
WritgoCMS_GSC_Admin_Settings::get_instance();
