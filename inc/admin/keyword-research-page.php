<?php
/**
 * Keyword Research Admin Page
 *
 * Interface for keyword research using DataForSEO.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Keyword_Research_Page
 */
class WritgoAI_Keyword_Research_Page {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Keyword_Research_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Keyword_Research_Page
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'writgoai',
			__( 'Keyword Research', 'writgoai' ),
			__( '🔑 Keyword Research', 'writgoai' ),
			'edit_posts',
			'writgoai-keyword-research',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'writgoai_page_writgoai-keyword-research' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'writgoai-keyword-research',
			WRITGOAI_URL . 'assets/css/keyword-research.css',
			array(),
			WRITGOAI_VERSION
		);

		wp_enqueue_script(
			'writgoai-keyword-research',
			WRITGOAI_URL . 'assets/js/keyword-research.js',
			array( 'jquery' ),
			WRITGOAI_VERSION,
			true
		);

		wp_localize_script(
			'writgoai-keyword-research',
			'writgoaiKeywordResearch',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'writgoai_keyword_nonce' ),
				'i18n'    => array(
					'searching'  => __( 'Searching...', 'writgoai' ),
					'error'      => __( 'Error', 'writgoai' ),
					'success'    => __( 'Success', 'writgoai' ),
					'noResults'  => __( 'No results found', 'writgoai' ),
					'saved'      => __( 'Keyword saved successfully', 'writgoai' ),
				),
			)
		);
	}

	/**
	 * Render keyword research page
	 */
	public function render_page() {
		?>
		<div class="wrap writgoai-keyword-research">
			<h1><?php esc_html_e( 'Keyword Research', 'writgoai' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Search for keywords to analyze their search volume, difficulty, and competition.', 'writgoai' ); ?>
			</p>

			<!-- Search Form -->
			<div class="keyword-search-form">
				<div class="search-input-wrapper">
					<input 
						type="text" 
						id="keyword-search-input" 
						class="regular-text" 
						placeholder="<?php esc_attr_e( 'Enter keyword...', 'writgoai' ); ?>"
					/>
					<button type="button" id="keyword-search-btn" class="button button-primary">
						🔍 <?php esc_html_e( 'Search', 'writgoai' ); ?>
					</button>
				</div>
				<p class="credits-info">
					<?php esc_html_e( 'Cost: 15 credits per search', 'writgoai' ); ?>
				</p>
			</div>

			<!-- Loading State -->
			<div id="keyword-loading" class="keyword-loading" style="display: none;">
				<div class="spinner is-active"></div>
				<p><?php esc_html_e( 'Searching...', 'writgoai' ); ?></p>
			</div>

			<!-- Results Container -->
			<div id="keyword-results" class="keyword-results" style="display: none;">
				<div class="keyword-card">
					<div class="keyword-header">
						<h2 class="keyword-title" id="result-keyword"></h2>
						<button type="button" id="save-keyword-btn" class="button button-secondary">
							💾 <?php esc_html_e( 'Save to Plan', 'writgoai' ); ?>
						</button>
					</div>

					<div class="keyword-metrics">
						<div class="metric-item">
							<span class="metric-label"><?php esc_html_e( 'Search Volume', 'writgoai' ); ?></span>
							<span class="metric-value" id="result-volume">-</span>
							<span class="metric-unit">/month</span>
						</div>
						<div class="metric-item">
							<span class="metric-label"><?php esc_html_e( 'Difficulty', 'writgoai' ); ?></span>
							<span class="metric-value" id="result-difficulty">-</span>
							<span class="metric-badge" id="result-difficulty-badge"></span>
						</div>
						<div class="metric-item">
							<span class="metric-label"><?php esc_html_e( 'CPC', 'writgoai' ); ?></span>
							<span class="metric-value" id="result-cpc">-</span>
						</div>
						<div class="metric-item">
							<span class="metric-label"><?php esc_html_e( 'Competition', 'writgoai' ); ?></span>
							<span class="metric-value" id="result-competition">-</span>
						</div>
					</div>

					<!-- Related Keywords Section -->
					<div class="related-keywords-section">
						<div class="section-header">
							<h3><?php esc_html_e( 'Related Keywords', 'writgoai' ); ?></h3>
							<button type="button" id="load-related-btn" class="button button-small">
								<?php esc_html_e( 'Load Related Keywords', 'writgoai' ); ?> (5 credits)
							</button>
						</div>
						<div id="related-keywords-list" class="related-keywords-list">
							<!-- Related keywords will be inserted here -->
						</div>
					</div>

					<!-- Actions -->
					<div class="keyword-actions">
						<button type="button" id="view-serp-btn" class="button">
							📊 <?php esc_html_e( 'View SERP', 'writgoai' ); ?> (10 credits)
						</button>
					</div>
				</div>
			</div>

			<!-- SERP Results -->
			<div id="serp-results" class="serp-results" style="display: none;">
				<h3><?php esc_html_e( 'Top 10 SERP Results', 'writgoai' ); ?></h3>
				<div id="serp-list" class="serp-list">
					<!-- SERP results will be inserted here -->
				</div>
			</div>

			<!-- Saved Keywords -->
			<div class="saved-keywords-section">
				<h2><?php esc_html_e( 'Saved Keywords', 'writgoai' ); ?></h2>
				<div id="saved-keywords-list">
					<?php $this->render_saved_keywords(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render saved keywords
	 */
	private function render_saved_keywords() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_keywords';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$keywords = $wpdb->get_results(
			"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 20",
			ARRAY_A
		);

		if ( empty( $keywords ) ) {
			echo '<p>' . esc_html__( 'No saved keywords yet.', 'writgoai' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Keyword', 'writgoai' ) . '</th>';
		echo '<th>' . esc_html__( 'Volume', 'writgoai' ) . '</th>';
		echo '<th>' . esc_html__( 'Difficulty', 'writgoai' ) . '</th>';
		echo '<th>' . esc_html__( 'CPC', 'writgoai' ) . '</th>';
		echo '<th>' . esc_html__( 'Competition', 'writgoai' ) . '</th>';
		echo '<th>' . esc_html__( 'Saved', 'writgoai' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $keywords as $keyword ) {
			echo '<tr>';
			echo '<td><strong>' . esc_html( $keyword['keyword'] ) . '</strong></td>';
			echo '<td>' . esc_html( number_format( $keyword['search_volume'] ) ) . '</td>';
			echo '<td>' . esc_html( $keyword['difficulty'] ) . '</td>';
			echo '<td>€' . esc_html( number_format( $keyword['cpc'], 2 ) ) . '</td>';
			echo '<td>' . esc_html( $keyword['competition'] ) . '</td>';
			echo '<td>' . esc_html( human_time_diff( strtotime( $keyword['created_at'] ) ) ) . ' ' . esc_html__( 'ago', 'writgoai' ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
	}
}

// Initialize.
WritgoAI_Keyword_Research_Page::get_instance();
