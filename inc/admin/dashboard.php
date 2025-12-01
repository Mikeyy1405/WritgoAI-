<?php
/**
 * Dashboard Admin Page
 *
 * Main dashboard for WritgoAI with website health score, stats, and workflow progress.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Dashboard
 */
class WritgoCMS_Dashboard {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Dashboard
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Dashboard
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
		// Dashboard is rendered through admin-aiml-settings.php.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_writgocms_get_dashboard_stats', array( $this, 'ajax_get_dashboard_stats' ) );
	}

	/**
	 * Enqueue dashboard scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'writgocms-aiml' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'writgocms-dashboard',
			WRITGOCMS_URL . 'assets/css/dashboard.css',
			array(),
			WRITGOCMS_VERSION
		);

		wp_enqueue_script(
			'writgocms-dashboard',
			WRITGOCMS_URL . 'assets/js/dashboard.js',
			array( 'jquery' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_localize_script(
			'writgocms-dashboard',
			'writgocmsDashboard',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'writgocms_dashboard_nonce' ),
			)
		);
	}

	/**
	 * Render dashboard page
	 */
	public function render() {
		$stats = $this->get_dashboard_stats();
		?>
		<div class="wrap writgocms-dashboard">
			<h1><?php esc_html_e( 'WritgoAI Dashboard', 'writgocms' ); ?></h1>

			<!-- Health Score Section -->
			<div class="dashboard-section health-score-section">
				<h2><?php esc_html_e( 'Website Health Score', 'writgocms' ); ?></h2>
				<div class="health-score-container">
					<div class="health-score-circle">
						<div class="score-value <?php echo esc_attr( $this->get_score_class( $stats['health_score'] ) ); ?>">
							<?php echo esc_html( $stats['health_score'] ); ?>
							<span class="score-label">/100</span>
						</div>
					</div>
					<div class="health-metrics">
						<div class="metric">
							<span class="metric-label"><?php esc_html_e( 'Content Coverage', 'writgocms' ); ?></span>
							<span class="metric-value"><?php echo esc_html( $stats['content_coverage'] ); ?>%</span>
						</div>
						<div class="metric">
							<span class="metric-label"><?php esc_html_e( 'Topical Authority', 'writgocms' ); ?></span>
							<span class="metric-value"><?php echo esc_html( $stats['topical_authority'] ); ?>%</span>
						</div>
						<div class="metric">
							<span class="metric-label"><?php esc_html_e( 'Internal Links Score', 'writgocms' ); ?></span>
							<span class="metric-value"><?php echo esc_html( $stats['internal_links_score'] ); ?>%</span>
						</div>
						<div class="metric">
							<span class="metric-label"><?php esc_html_e( 'Technical SEO Score', 'writgocms' ); ?></span>
							<span class="metric-value"><?php echo esc_html( $stats['technical_seo_score'] ); ?>%</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Quick Stats Cards -->
			<div class="dashboard-section quick-stats-section">
				<h2><?php esc_html_e( 'Quick Stats', 'writgocms' ); ?></h2>
				<div class="stats-cards">
					<div class="stat-card">
						<div class="stat-icon">📝</div>
						<div class="stat-content">
							<div class="stat-value"><?php echo esc_html( $stats['total_posts'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Total Posts', 'writgocms' ); ?></div>
						</div>
					</div>
					<div class="stat-card">
						<div class="stat-icon">✅</div>
						<div class="stat-content">
							<div class="stat-value"><?php echo esc_html( $stats['optimized_posts'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Optimized Posts', 'writgocms' ); ?></div>
						</div>
					</div>
					<div class="stat-card">
						<div class="stat-icon">📊</div>
						<div class="stat-content">
							<div class="stat-value"><?php echo esc_html( $stats['avg_ranking'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Average Ranking', 'writgocms' ); ?></div>
						</div>
					</div>
					<div class="stat-card">
						<div class="stat-icon">👥</div>
						<div class="stat-content">
							<div class="stat-value"><?php echo esc_html( number_format( $stats['monthly_traffic'] ) ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Monthly Traffic', 'writgocms' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Workflow Progress -->
			<div class="dashboard-section workflow-section">
				<h2><?php esc_html_e( 'Workflow Progress', 'writgocms' ); ?></h2>
				<div class="workflow-steps">
					<div class="workflow-step <?php echo esc_attr( $stats['workflow_step_1']['status'] ); ?>">
						<div class="step-number">1</div>
						<div class="step-content">
							<h3><?php esc_html_e( 'Website Analyse', 'writgocms' ); ?></h3>
							<p class="step-description"><?php esc_html_e( 'Analyze your website content and SEO performance', 'writgocms' ); ?></p>
							<?php if ( $stats['workflow_step_1']['status'] === 'completed' ) : ?>
								<p class="step-status completed">
									✓ <?php esc_html_e( 'Completed', 'writgocms' ); ?>
									<?php if ( $stats['workflow_step_1']['completed_at'] ) : ?>
										- <?php echo esc_html( $stats['workflow_step_1']['completed_at'] ); ?>
									<?php endif; ?>
								</p>
							<?php elseif ( $stats['workflow_step_1']['status'] === 'in-progress' ) : ?>
								<p class="step-status in-progress">⏳ <?php esc_html_e( 'In Progress', 'writgocms' ); ?></p>
							<?php else : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="button button-primary">
									<?php esc_html_e( 'Start Analysis', 'writgocms' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="workflow-step <?php echo esc_attr( $stats['workflow_step_2']['status'] ); ?>">
						<div class="step-number">2</div>
						<div class="step-content">
							<h3><?php esc_html_e( 'Content Strategie', 'writgocms' ); ?></h3>
							<p class="step-description"><?php esc_html_e( 'Create your content strategy and plan', 'writgocms' ); ?></p>
							<?php if ( $stats['workflow_step_1']['status'] !== 'completed' ) : ?>
								<p class="step-status locked">🔒 <?php esc_html_e( 'Locked - Complete Step 1 first', 'writgocms' ); ?></p>
							<?php elseif ( $stats['workflow_step_2']['status'] === 'completed' ) : ?>
								<p class="step-status completed">✓ <?php esc_html_e( 'Completed', 'writgocms' ); ?></p>
							<?php else : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="button button-primary">
									<?php esc_html_e( 'Create Strategy', 'writgocms' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="workflow-step locked">
						<div class="step-number">3-6</div>
						<div class="step-content">
							<h3><?php esc_html_e( 'Coming Soon', 'writgocms' ); ?></h3>
							<p class="step-description"><?php esc_html_e( 'More workflow steps will be added in future updates', 'writgocms' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Recent Activity -->
			<div class="dashboard-section recent-activity-section">
				<h2><?php esc_html_e( 'Recent Activity', 'writgocms' ); ?></h2>
				<div class="activity-list">
					<?php if ( ! empty( $stats['recent_activity'] ) ) : ?>
						<?php foreach ( $stats['recent_activity'] as $activity ) : ?>
							<div class="activity-item">
								<span class="activity-icon"><?php echo esc_html( $activity['icon'] ); ?></span>
								<span class="activity-text"><?php echo esc_html( $activity['text'] ); ?></span>
								<span class="activity-time"><?php echo esc_html( $activity['time'] ); ?></span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'No recent activity', 'writgocms' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get dashboard statistics
	 *
	 * @return array
	 */
	public function get_dashboard_stats() {
		global $wpdb;

		// Get site analysis data.
		$analysis_table = $wpdb->prefix . 'writgo_site_analysis';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$analysis = $wpdb->get_row(
			"SELECT * FROM {$analysis_table} ORDER BY analyzed_at DESC LIMIT 1",
			ARRAY_A
		);

		// Get GSC data.
		$gsc_table = $wpdb->prefix . 'writgoai_gsc_queries';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$gsc_stats = $wpdb->get_row(
			"SELECT 
				SUM(clicks) as clicks,
				AVG(position) as avg_position
			FROM {$gsc_table}
			WHERE date > DATE_SUB(NOW(), INTERVAL 30 DAY)",
			ARRAY_A
		);

		$health_score          = $analysis['health_score'] ?? 0;
		$total_posts           = $analysis['total_posts'] ?? 0;
		$optimized_posts       = $analysis['optimized_posts'] ?? 0;
		$monthly_traffic       = $gsc_stats['clicks'] ?? 0;
		$avg_ranking           = round( $gsc_stats['avg_position'] ?? 0, 1 );

		// Calculate component scores.
		$content_coverage      = $total_posts > 0 ? min( 100, ( $total_posts / 50 ) * 100 ) : 0;
		$topical_authority     = $total_posts > 0 ? ( $optimized_posts / $total_posts ) * 100 : 0;
		$internal_links_score  = $this->calculate_internal_links_score();
		$technical_seo_score   = $this->calculate_technical_seo_score();

		// Workflow status.
		$workflow_step_1 = array(
			'status'       => $analysis ? 'completed' : 'pending',
			'completed_at' => $analysis ? human_time_diff( strtotime( $analysis['analyzed_at'] ) ) . ' ago' : null,
		);

		$workflow_step_2 = array(
			'status' => 'pending',
		);

		// Recent activity.
		$recent_activity = $this->get_recent_activity();

		return array(
			'health_score'          => $health_score,
			'content_coverage'      => round( $content_coverage ),
			'topical_authority'     => round( $topical_authority ),
			'internal_links_score'  => round( $internal_links_score ),
			'technical_seo_score'   => round( $technical_seo_score ),
			'total_posts'           => $total_posts,
			'optimized_posts'       => $optimized_posts,
			'avg_ranking'           => $avg_ranking,
			'monthly_traffic'       => $monthly_traffic,
			'workflow_step_1'       => $workflow_step_1,
			'workflow_step_2'       => $workflow_step_2,
			'recent_activity'       => $recent_activity,
		);
	}

	/**
	 * Calculate internal links score
	 *
	 * @return float
	 */
	private function calculate_internal_links_score() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_post_scores';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg_internal_links = $wpdb->get_var(
			"SELECT AVG(internal_links) FROM {$table_name}"
		);

		// Score based on average (5+ internal links = 100%).
		return min( 100, ( $avg_internal_links / 5 ) * 100 );
	}

	/**
	 * Calculate technical SEO score
	 *
	 * @return float
	 */
	private function calculate_technical_seo_score() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_post_scores';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_desc_percentage = $wpdb->get_var(
			"SELECT (SUM(has_meta_description) / COUNT(*)) * 100 FROM {$table_name}"
		);

		return $meta_desc_percentage ?? 0;
	}

	/**
	 * Get recent activity
	 *
	 * @return array
	 */
	private function get_recent_activity() {
		$activity = array();

		// Get last sync time.
		$last_sync = get_option( 'writgocms_last_daily_sync' );
		if ( $last_sync ) {
			$activity[] = array(
				'icon' => '🔄',
				'text' => __( 'Daily sync completed', 'writgocms' ),
				'time' => human_time_diff( strtotime( $last_sync ) ) . ' ' . __( 'ago', 'writgocms' ),
			);
		}

		// Get last analysis.
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_site_analysis';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_analysis = $wpdb->get_var(
			"SELECT analyzed_at FROM {$table_name} ORDER BY analyzed_at DESC LIMIT 1"
		);
		if ( $last_analysis ) {
			$activity[] = array(
				'icon' => '🔍',
				'text' => __( 'Site analysis completed', 'writgocms' ),
				'time' => human_time_diff( strtotime( $last_analysis ) ) . ' ' . __( 'ago', 'writgocms' ),
			);
		}

		return $activity;
	}

	/**
	 * Get score CSS class
	 *
	 * @param int $score Score value.
	 * @return string
	 */
	private function get_score_class( $score ) {
		if ( $score >= 71 ) {
			return 'score-good';
		} elseif ( $score >= 41 ) {
			return 'score-warning';
		} else {
			return 'score-poor';
		}
	}

	/**
	 * AJAX handler for getting dashboard stats
	 */
	public function ajax_get_dashboard_stats() {
		check_ajax_referer( 'writgocms_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$stats = $this->get_dashboard_stats();
		wp_send_json_success( $stats );
	}
}

// Initialize.
WritgoCMS_Dashboard::get_instance();
