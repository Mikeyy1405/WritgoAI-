<?php
/**
 * Social Media Admin Settings Panel
 *
 * Admin interface for Social Media Manager.
 * Nederlandse versie - Dutch interface for WritgoAI.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Social_Media_Admin
 */
class WritgoAI_Social_Media_Admin {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Social_Media_Admin
	 */
	private static $instance = null;

	/**
	 * Social Media Manager instance
	 *
	 * @var WritgoAI_Social_Media_Manager
	 */
	private $manager;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Social_Media_Admin
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
		$this->manager = WritgoAI_Social_Media_Manager::get_instance();
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add admin menu - Social Media submenu under WritgoAI
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'writgoai',
			'Social Media',
			'📱 Social Media',
			'manage_options',
			'writgocms-social-media',
			array( $this, 'render_social_media_page' )
		);
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'writgocms-social-media' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'writgocms-social-media',
			WRITGOAI_URL . 'assets/css/social-media.css',
			array(),
			WRITGOAI_VERSION
		);

		wp_enqueue_script(
			'writgocms-social-media',
			WRITGOAI_URL . 'assets/js/social-media.js',
			array( 'jquery' ),
			WRITGOAI_VERSION,
			true
		);

		wp_localize_script(
			'writgocms-social-media',
			'writgocmsSocialMedia',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'writgoai_ai_nonce' ),
				'platforms' => $this->manager->get_platforms(),
				'tones'     => $this->manager->get_content_tones(),
				'templates' => $this->manager->get_template_types(),
				'bestTimes' => $this->manager->get_best_posting_times(),
				'i18n'      => array(
					'generating'       => 'Posts genereren...',
					'success'          => 'Gelukt!',
					'error'            => 'Fout',
					'saving'           => 'Opslaan...',
					'saved'            => 'Opgeslagen!',
					'scheduling'       => 'Inplannen...',
					'scheduled'        => 'Ingepland!',
					'deleting'         => 'Verwijderen...',
					'deleted'          => 'Verwijderd!',
					'confirmDelete'    => 'Weet je zeker dat je deze post wilt verwijderen?',
					'selectPlatform'   => 'Selecteer minimaal één platform',
					'enterContent'     => 'Voer content in',
					'noPostsScheduled' => 'Geen posts ingepland',
					'copyToClipboard'  => 'Kopiëren naar klembord',
					'copied'           => 'Gekopieerd!',
					'characters'       => 'karakters',
					'hashtagsSuggested' => 'hashtags voorgesteld',
				),
			)
		);
	}

	/**
	 * Render social media page
	 */
	public function render_social_media_page() {
		// Whitelist valid tab values to prevent URL manipulation issues.
		$valid_tabs = array( 'dashboard', 'create', 'calendar', 'hashtags', 'analytics' );
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
		$active_tab = in_array( $active_tab, $valid_tabs, true ) ? $active_tab : 'dashboard';

		$analytics  = $this->manager->get_analytics_summary( 7 );
		$platforms  = $this->manager->get_platforms();
		$tones      = $this->manager->get_content_tones();
		$templates  = $this->manager->get_template_types();
		$best_times = $this->manager->get_best_posting_times();
		?>
		<div class="wrap writgocms-social-media">
			<h1 class="social-media-header">
				<span class="social-media-logo">📱</span>
				Social Media Manager
				<span class="header-subtitle">Deel je content op alle kanalen tegelijk</span>
			</h1>

			<!-- Navigation Tabs -->
			<nav class="social-media-nav">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=dashboard' ) ); ?>" 
				   class="nav-tab <?php echo 'dashboard' === $active_tab ? 'active' : ''; ?>">
					📊 Dashboard
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=create' ) ); ?>" 
				   class="nav-tab <?php echo 'create' === $active_tab ? 'active' : ''; ?>">
					✍️ Nieuwe Post
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=calendar' ) ); ?>" 
				   class="nav-tab <?php echo 'calendar' === $active_tab ? 'active' : ''; ?>">
					📅 Kalender
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=hashtags' ) ); ?>" 
				   class="nav-tab <?php echo 'hashtags' === $active_tab ? 'active' : ''; ?>">
					#️⃣ Hashtags
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=analytics' ) ); ?>" 
				   class="nav-tab <?php echo 'analytics' === $active_tab ? 'active' : ''; ?>">
					📈 Analytics
				</a>
			</nav>

			<div class="social-media-content">
				<?php
				switch ( $active_tab ) {
					case 'create':
						$this->render_create_tab( $platforms, $tones, $templates );
						break;
					case 'calendar':
						$this->render_calendar_tab( $platforms, $best_times );
						break;
					case 'hashtags':
						$this->render_hashtags_tab();
						break;
					case 'analytics':
						$this->render_analytics_tab( $platforms );
						break;
					default:
						$this->render_dashboard_tab( $analytics, $platforms );
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dashboard tab
	 *
	 * @param array $analytics Analytics data.
	 * @param array $platforms Available platforms.
	 */
	private function render_dashboard_tab( $analytics, $platforms ) {
		?>
		<div class="dashboard-tab">
			<!-- Connected Accounts Section -->
			<div class="social-card accounts-card">
				<h3>🔗 Gekoppelde Accounts</h3>
				<div class="accounts-list">
					<?php foreach ( $platforms as $key => $platform ) : ?>
					<div class="account-item" data-platform="<?php echo esc_attr( $key ); ?>">
						<span class="account-icon"><?php echo esc_html( $platform['icon'] ); ?></span>
						<span class="account-name"><?php echo esc_html( $platform['name'] ); ?></span>
						<span class="account-status not-connected">
							<button type="button" class="button button-small connect-account-btn">➕ Koppelen</button>
						</span>
					</div>
					<?php endforeach; ?>
				</div>
				<p class="accounts-note">
					<em>💡 Account koppeling wordt binnenkort beschikbaar. Momenteel kun je posts handmatig kopiëren.</em>
				</p>
			</div>

			<!-- Quick Stats -->
			<div class="social-card stats-card">
				<h3>📊 Recente Posts (<?php echo esc_html( $analytics['period_days'] ); ?> dagen)</h3>
				<div class="stats-grid">
					<div class="stat-item">
						<span class="stat-number"><?php echo esc_html( $analytics['total_posts'] ); ?></span>
						<span class="stat-label">Posts totaal</span>
					</div>
					<div class="stat-item">
						<span class="stat-number"><?php echo esc_html( $analytics['published_posts'] ); ?></span>
						<span class="stat-label">Gepubliceerd</span>
					</div>
					<div class="stat-item">
						<span class="stat-number"><?php echo esc_html( $analytics['scheduled_posts'] ); ?></span>
						<span class="stat-label">Ingepland</span>
					</div>
					<div class="stat-item">
						<span class="stat-number"><?php echo esc_html( $analytics['draft_posts'] ); ?></span>
						<span class="stat-label">Concepten</span>
					</div>
				</div>
			</div>

			<!-- Quick Actions -->
			<div class="social-card actions-card">
				<h3>⚡ Snelle Acties</h3>
				<div class="quick-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=create' ) ); ?>" class="action-button primary">
						📝 Nieuwe Post Maken
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-social-media&tab=calendar' ) ); ?>" class="action-button secondary">
						📅 Planning Kalender
					</a>
				</div>
			</div>

			<!-- Best Posting Times -->
			<div class="social-card times-card">
				<h3>⏰ Beste Post Tijden</h3>
				<div class="times-list">
					<?php
					$best_times = $this->manager->get_best_posting_times();
					foreach ( $best_times as $key => $time_data ) :
						if ( ! isset( $platforms[ $key ] ) ) {
							continue;
						}
						?>
					<div class="time-item">
						<span class="time-platform"><?php echo esc_html( $platforms[ $key ]['icon'] . ' ' . $platforms[ $key ]['name'] ); ?></span>
						<span class="time-value"><?php echo esc_html( $time_data['description'] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render create tab
	 *
	 * @param array $platforms Available platforms.
	 * @param array $tones     Content tones.
	 * @param array $templates Template types.
	 */
	private function render_create_tab( $platforms, $tones, $templates ) {
		?>
		<div class="create-tab">
			<div class="create-form-container">
				<div class="social-card create-form-card">
					<h3>✍️ Nieuwe Social Media Post</h3>

					<!-- Source Content Selection -->
					<div class="form-section">
						<h4>📄 Selecteer Bron Content</h4>
						<div class="source-options">
							<label class="source-option">
								<input type="radio" name="content_source" value="blog" checked>
								<span class="option-label">🔵 Vanaf blog post</span>
							</label>
							<label class="source-option">
								<input type="radio" name="content_source" value="manual">
								<span class="option-label">⚪ Nieuwe post (handmatig)</span>
							</label>
						</div>

						<!-- Blog Post Selector -->
						<div class="blog-selector" id="blog-selector">
							<label for="blog-post-search">Zoek blog post:</label>
							<input type="text" id="blog-post-search" class="regular-text" placeholder="Typ om te zoeken...">
							<div id="blog-posts-list" class="blog-posts-list"></div>
							<input type="hidden" id="selected-post-id" value="">
							<input type="hidden" id="selected-post-url" value="">
						</div>

						<!-- Manual Content -->
						<div class="manual-content" id="manual-content" style="display: none;">
							<div class="form-field">
								<label for="manual-title">Titel:</label>
								<input type="text" id="manual-title" class="regular-text" placeholder="Post titel">
							</div>
							<div class="form-field">
								<label for="manual-text">Content:</label>
								<textarea id="manual-text" rows="5" placeholder="Schrijf hier je content..."></textarea>
							</div>
							<div class="form-field">
								<label for="manual-link">Link (optioneel):</label>
								<input type="url" id="manual-link" class="regular-text" placeholder="https://...">
							</div>
						</div>
					</div>

					<!-- Platform Selection -->
					<div class="form-section">
						<h4>📱 Selecteer Platforms</h4>
						<div class="platform-checkboxes">
							<?php foreach ( $platforms as $key => $platform ) : ?>
							<label class="platform-checkbox">
								<input type="checkbox" name="platforms[]" value="<?php echo esc_attr( $key ); ?>" 
									<?php echo in_array( $key, array( 'facebook', 'instagram', 'twitter', 'linkedin' ), true ) ? 'checked' : ''; ?>>
								<span class="platform-icon"><?php echo esc_html( $platform['icon'] ); ?></span>
								<span class="platform-name"><?php echo esc_html( $platform['name'] ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- AI Generation Options -->
					<div class="form-section">
						<h4>🤖 AI Generatie Opties</h4>
						<div class="generation-options">
							<div class="option-row">
								<div class="form-field">
									<label for="content-tone">Toon:</label>
									<select id="content-tone">
										<?php foreach ( $tones as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( 'professioneel', $key ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-field">
									<label>
										<input type="checkbox" id="use-hashtags" checked>
										Automatische hashtags
									</label>
								</div>
								<div class="form-field">
									<label>
										<input type="checkbox" id="use-emojis" checked>
										Gebruik emoji's
									</label>
								</div>
							</div>
						</div>
					</div>

					<!-- Image Options -->
					<div class="form-section">
						<h4>🎨 Afbeelding Opties</h4>
						<div class="image-options">
							<label class="image-option">
								<input type="radio" name="image_source" value="ai" checked>
								<span class="option-label">🤖 AI genereert nieuwe afbeelding</span>
							</label>
							<label class="image-option">
								<input type="radio" name="image_source" value="blog">
								<span class="option-label">📷 Gebruik blog afbeelding</span>
							</label>
							<label class="image-option">
								<input type="radio" name="image_source" value="none">
								<span class="option-label">❌ Geen afbeelding</span>
							</label>
						</div>
						<div class="template-selector" id="template-selector">
							<label for="image-template">Template:</label>
							<select id="image-template">
								<?php foreach ( $templates as $key => $template ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $template['icon'] . ' ' . $template['label'] . ' - ' . $template['description'] ); ?>
								</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<!-- Generate Button -->
					<div class="form-actions">
						<button type="button" id="generate-posts-btn" class="button button-primary button-hero">
							🤖 Genereer Posts + Afbeeldingen
						</button>
						<span class="generate-status"></span>
					</div>
				</div>
			</div>

			<!-- Generated Posts Preview -->
			<div class="generated-posts-container" id="generated-posts" style="display: none;">
				<div class="social-card">
					<div class="generated-header">
						<h3>✨ Gegenereerde Posts</h3>
						<div class="generated-actions">
							<button type="button" id="schedule-all-btn" class="button">📅 Alle Inplannen</button>
							<button type="button" id="copy-all-btn" class="button">📋 Alle Kopiëren</button>
						</div>
					</div>
					<div id="posts-preview-list" class="posts-preview-list">
						<!-- Posts will be inserted here -->
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render calendar tab
	 *
	 * @param array $platforms  Available platforms.
	 * @param array $best_times Best posting times.
	 */
	private function render_calendar_tab( $platforms, $best_times ) {
		$current_month = gmdate( 'Y-m' );
		if ( isset( $_GET['month'] ) ) {
			$month_input = sanitize_text_field( wp_unslash( $_GET['month'] ) );
			// Validate month format (Y-m, e.g., 2025-11).
			if ( preg_match( '/^\d{4}-(?:0[1-9]|1[0-2])$/', $month_input ) ) {
				$current_month = $month_input;
			}
		}
		?>
		<div class="calendar-tab">
			<div class="social-card calendar-card">
				<div class="calendar-header">
					<h3>📅 Social Media Planning Kalender</h3>
					<div class="calendar-nav">
						<button type="button" id="prev-month" class="button">◀ Vorige</button>
						<span id="current-month-label"><?php echo esc_html( $this->format_dutch_month( $current_month ) ); ?></span>
						<button type="button" id="next-month" class="button">Volgende ▶</button>
					</div>
				</div>

				<div class="calendar-container">
					<div class="calendar-weekdays">
						<div class="weekday">Ma</div>
						<div class="weekday">Di</div>
						<div class="weekday">Wo</div>
						<div class="weekday">Do</div>
						<div class="weekday">Vr</div>
						<div class="weekday">Za</div>
						<div class="weekday">Zo</div>
					</div>
					<div id="calendar-grid" class="calendar-grid" data-month="<?php echo esc_attr( $current_month ); ?>">
						<!-- Calendar days will be generated by JavaScript -->
					</div>
				</div>

				<!-- Legend -->
				<div class="calendar-legend">
					<h4>📱 Legenda:</h4>
					<div class="legend-items">
						<?php foreach ( $platforms as $key => $platform ) : ?>
						<span class="legend-item">
							<?php echo esc_html( $platform['icon'] . ' ' . $platform['name'] ); ?>
						</span>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Best Times Reference -->
				<div class="best-times-reference">
					<h4>⏰ Aanbevolen Post Tijden:</h4>
					<div class="times-grid">
						<?php foreach ( $best_times as $key => $time_data ) : 
							if ( ! isset( $platforms[ $key ] ) ) {
								continue;
							}
							?>
						<div class="time-ref">
							<span class="time-platform"><?php echo esc_html( $platforms[ $key ]['icon'] ); ?></span>
							<span class="time-desc"><?php echo esc_html( $time_data['description'] ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Scheduled Posts List -->
			<div class="social-card scheduled-posts-card">
				<h3>📋 Ingeplande Posts</h3>
				<div id="scheduled-posts-list" class="scheduled-posts-list">
					<p class="loading">Laden...</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render hashtags tab
	 */
	private function render_hashtags_tab() {
		?>
		<div class="hashtags-tab">
			<!-- Hashtag Research -->
			<div class="social-card research-card">
				<h3>#️⃣ Hashtag Research</h3>
				<div class="research-form">
					<div class="form-field">
						<label for="hashtag-topic">Onderwerp:</label>
						<input type="text" id="hashtag-topic" class="regular-text" placeholder="bijv. elektrische auto">
					</div>
					<div class="form-field">
						<label for="hashtag-platform">Platform:</label>
						<select id="hashtag-platform">
							<option value="instagram">Instagram</option>
							<option value="twitter">Twitter/X</option>
							<option value="linkedin">LinkedIn</option>
							<option value="facebook">Facebook</option>
						</select>
					</div>
					<button type="button" id="suggest-hashtags-btn" class="button button-primary">
						🔍 Zoek Hashtags
					</button>
				</div>
				<div id="suggested-hashtags" class="suggested-hashtags" style="display: none;">
					<h4>Suggesties:</h4>
					<div class="hashtags-list"></div>
					<button type="button" id="save-as-set-btn" class="button">💾 Opslaan als Set</button>
				</div>
			</div>

			<!-- Saved Hashtag Sets -->
			<div class="social-card sets-card">
				<h3>📂 Opgeslagen Hashtag Sets</h3>
				<div id="hashtag-sets-list" class="hashtag-sets-list">
					<p class="loading">Laden...</p>
				</div>
			</div>

			<!-- Create New Set -->
			<div class="social-card create-set-card">
				<h3>➕ Nieuwe Hashtag Set</h3>
				<div class="create-set-form">
					<div class="form-field">
						<label for="new-set-name">Set Naam:</label>
						<input type="text" id="new-set-name" class="regular-text" placeholder="bijv. Auto Content">
					</div>
					<div class="form-field">
						<label for="new-set-category">Categorie:</label>
						<input type="text" id="new-set-category" class="regular-text" placeholder="bijv. Automotive">
					</div>
					<div class="form-field">
						<label for="new-set-hashtags">Hashtags (komma gescheiden):</label>
						<textarea id="new-set-hashtags" rows="3" placeholder="hashtag1, hashtag2, hashtag3"></textarea>
					</div>
					<button type="button" id="create-set-btn" class="button button-primary">
						💾 Set Opslaan
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render analytics tab
	 *
	 * @param array $platforms Available platforms.
	 */
	private function render_analytics_tab( $platforms ) {
		?>
		<div class="analytics-tab">
			<div class="social-card analytics-overview-card">
				<div class="analytics-header">
					<h3>📊 Social Media Analytics</h3>
					<div class="period-selector">
						<label for="analytics-period">Periode:</label>
						<select id="analytics-period">
							<option value="7">Laatste 7 dagen</option>
							<option value="30" selected>Laatste 30 dagen</option>
							<option value="90">Laatste 90 dagen</option>
						</select>
					</div>
				</div>

				<!-- Total Stats -->
				<div id="analytics-totals" class="analytics-totals">
					<p class="loading">Laden...</p>
				</div>

				<!-- Per Platform Stats -->
				<div id="analytics-by-platform" class="analytics-by-platform">
					<!-- Will be populated by JavaScript -->
				</div>
			</div>

			<!-- Performance Tips -->
			<div class="social-card tips-card">
				<h3>💡 Performance Tips</h3>
				<div class="tips-list">
					<div class="tip-item">
						<span class="tip-icon">📊</span>
						<div class="tip-content">
							<strong>Infographics</strong>
							<p>+45% engagement - Visuele content presteert het beste</p>
						</div>
					</div>
					<div class="tip-item">
						<span class="tip-icon">❓</span>
						<div class="tip-content">
							<strong>Polls & Vragen</strong>
							<p>+32% engagement - Vraag je publiek om input</p>
						</div>
					</div>
					<div class="tip-item">
						<span class="tip-icon">🎥</span>
						<div class="tip-content">
							<strong>Video Content</strong>
							<p>+28% bereik - Video krijgt prioriteit in algoritmes</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Format Dutch month name
	 *
	 * @param string $month Month in Y-m format.
	 * @return string Formatted month name.
	 */
	private function format_dutch_month( $month ) {
		$dutch_months = array(
			'01' => 'Januari',
			'02' => 'Februari',
			'03' => 'Maart',
			'04' => 'April',
			'05' => 'Mei',
			'06' => 'Juni',
			'07' => 'Juli',
			'08' => 'Augustus',
			'09' => 'September',
			'10' => 'Oktober',
			'11' => 'November',
			'12' => 'December',
		);

		$parts = explode( '-', $month );
		if ( count( $parts ) === 2 ) {
			$month_num = $parts[1];
			$year      = $parts[0];
			return ( isset( $dutch_months[ $month_num ] ) ? $dutch_months[ $month_num ] : '' ) . ' ' . $year;
		}

		return $month;
	}
}

// Initialize admin.
WritgoAI_Social_Media_Admin::get_instance();
