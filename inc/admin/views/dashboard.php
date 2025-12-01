<?php
/**
 * Beginner-Friendly Dashboard Template
 *
 * Simplified dashboard with clear workflow and quick actions.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get dashboard instance for stats.
if ( ! class_exists( 'WritgoCMS_Dashboard' ) ) {
	return;
}
$dashboard = WritgoCMS_Dashboard::get_instance();
$stats = $dashboard->get_dashboard_stats();

if ( ! class_exists( 'WritgoCMS_Admin_Controller' ) ) {
	return;
}
$controller = WritgoCMS_Admin_Controller::get_instance();
$wizard_completed = $controller->is_wizard_completed();
?>

<div class="wrap writgo-dashboard-container">
	
	<!-- Hero Section with CTA -->
	<div class="writgo-card card-hero">
		<div class="hero-content">
			<h1><?php esc_html_e( 'Welkom bij WritgoAI', 'writgocms' ); ?></h1>
			<p class="hero-subtitle">
				<?php
				if ( ! $wizard_completed ) {
					esc_html_e( 'Laten we beginnen met het optimaliseren van je website met AI.', 'writgocms' );
				} else {
					esc_html_e( 'Je website wordt slimmer met elke stap. Ga door!', 'writgocms' );
				}
				?>
			</p>
			<?php if ( ! $wizard_completed ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-setup-wizard' ) ); ?>" class="button button-primary button-hero">
					<?php esc_html_e( 'Start Setup Wizard', 'writgocms' ); ?> →
				</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Progress Indicator -->
	<?php if ( $wizard_completed ) : ?>
	<div class="writgo-card">
		<h2><?php esc_html_e( 'Je Voortgang', 'writgocms' ); ?></h2>
		<div class="progress-overview">
			<div class="progress-stat">
				<div class="stat-circle <?php echo esc_attr( $dashboard->get_score_class( $stats['health_score'] ) ); ?>">
					<span class="stat-value"><?php echo esc_html( $stats['health_score'] ); ?></span>
					<span class="stat-max">/100</span>
				</div>
				<p class="stat-label"><?php esc_html_e( 'Website Health Score', 'writgocms' ); ?></p>
			</div>
			<div class="progress-details">
				<div class="progress-item">
					<span class="progress-label"><?php esc_html_e( 'Content Dekking', 'writgocms' ); ?></span>
					<div class="progress-bar-wrapper">
						<div class="progress-bar">
							<div class="progress-fill" style="width: <?php echo esc_attr( $stats['content_coverage'] ); ?>%"></div>
						</div>
						<span class="progress-value"><?php echo esc_html( $stats['content_coverage'] ); ?>%</span>
					</div>
				</div>
				<div class="progress-item">
					<span class="progress-label"><?php esc_html_e( 'SEO Optimalisatie', 'writgocms' ); ?></span>
					<div class="progress-bar-wrapper">
						<div class="progress-bar">
							<div class="progress-fill" style="width: <?php echo esc_attr( $stats['topical_authority'] ); ?>%"></div>
						</div>
						<span class="progress-value"><?php echo esc_html( $stats['topical_authority'] ); ?>%</span>
					</div>
				</div>
				<div class="progress-item">
					<span class="progress-label"><?php esc_html_e( 'Interne Links', 'writgocms' ); ?></span>
					<div class="progress-bar-wrapper">
						<div class="progress-bar">
							<div class="progress-fill" style="width: <?php echo esc_attr( $stats['internal_links_score'] ); ?>%"></div>
						</div>
						<span class="progress-value"><?php echo esc_html( $stats['internal_links_score'] ); ?>%</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Quick Actions -->
	<div class="writgo-card">
		<h2><?php esc_html_e( 'Snelle Acties', 'writgocms' ); ?></h2>
		<div class="quick-actions-grid">
			<div class="quick-action-card">
				<span class="action-icon">🔍</span>
				<h3><?php esc_html_e( 'Analyseer Website', 'writgocms' ); ?></h3>
				<p><?php esc_html_e( 'Krijg inzicht in je huidige content en SEO-status', 'writgocms' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Start Analyse', 'writgocms' ); ?>
				</a>
			</div>

			<div class="quick-action-card">
				<span class="action-icon">📝</span>
				<h3><?php esc_html_e( 'Maak Contentplan', 'writgocms' ); ?></h3>
				<p><?php esc_html_e( 'Laat AI een complete contentstrategie maken', 'writgocms' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Plan Content', 'writgocms' ); ?>
				</a>
			</div>

			<div class="quick-action-card">
				<span class="action-icon">✍️</span>
				<h3><?php esc_html_e( 'Schrijf Artikel', 'writgocms' ); ?></h3>
				<p><?php esc_html_e( 'Begin met schrijven met AI-ondersteuning', 'writgocms' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Nieuw Artikel', 'writgocms' ); ?>
				</a>
			</div>

			<div class="quick-action-card">
				<span class="action-icon">⚙️</span>
				<h3><?php esc_html_e( 'Instellingen', 'writgocms' ); ?></h3>
				<p><?php esc_html_e( 'Pas WritgoAI aan naar jouw wensen', 'writgocms' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Wijzig Instellingen', 'writgocms' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Stats Overview (Collapsible) -->
	<div class="writgo-card">
		<div class="card-header-with-toggle">
			<h2><?php esc_html_e( 'Statistieken Overzicht', 'writgocms' ); ?></h2>
			<button type="button" class="toggle-details" data-target="#stats-details">
				<span class="dashicons dashicons-arrow-down-alt2"></span>
				<?php esc_html_e( 'Meer Details', 'writgocms' ); ?>
			</button>
		</div>
		
		<div class="stats-summary">
			<div class="stat-item">
				<span class="stat-icon">📄</span>
				<div class="stat-content">
					<span class="stat-value"><?php echo esc_html( $stats['total_posts'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Artikelen', 'writgocms' ); ?></span>
				</div>
			</div>
			<div class="stat-item">
				<span class="stat-icon">✅</span>
				<div class="stat-content">
					<span class="stat-value"><?php echo esc_html( $stats['optimized_posts'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Geoptimaliseerd', 'writgocms' ); ?></span>
				</div>
			</div>
			<div class="stat-item">
				<span class="stat-icon">📊</span>
				<div class="stat-content">
					<span class="stat-value"><?php echo esc_html( number_format( $stats['monthly_traffic'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Maandelijks Verkeer', 'writgocms' ); ?></span>
				</div>
			</div>
			<div class="stat-item">
				<span class="stat-icon">🎯</span>
				<div class="stat-content">
					<span class="stat-value"><?php echo esc_html( $stats['avg_ranking'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Gem. Positie', 'writgocms' ); ?></span>
				</div>
			</div>
		</div>

		<div id="stats-details" class="stats-details" style="display: none;">
			<div class="detail-section">
				<h4><?php esc_html_e( 'Content Analyse', 'writgocms' ); ?></h4>
				<table class="stats-table">
					<tr>
						<td><?php esc_html_e( 'Content Dekking', 'writgocms' ); ?></td>
						<td><strong><?php echo esc_html( $stats['content_coverage'] ); ?>%</strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Topical Authority', 'writgocms' ); ?></td>
						<td><strong><?php echo esc_html( $stats['topical_authority'] ); ?>%</strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Interne Links Score', 'writgocms' ); ?></td>
						<td><strong><?php echo esc_html( $stats['internal_links_score'] ); ?>%</strong></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Technische SEO Score', 'writgocms' ); ?></td>
						<td><strong><?php echo esc_html( $stats['technical_seo_score'] ); ?>%</strong></td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<!-- Recent Activity -->
	<?php if ( ! empty( $stats['recent_activity'] ) ) : ?>
	<div class="writgo-card">
		<h2><?php esc_html_e( 'Recente Activiteit', 'writgocms' ); ?></h2>
		<div class="activity-feed">
			<?php foreach ( array_slice( $stats['recent_activity'], 0, 5 ) as $activity ) : ?>
				<div class="activity-item">
					<span class="activity-icon"><?php echo esc_html( $activity['icon'] ); ?></span>
					<div class="activity-content">
						<p class="activity-text"><?php echo esc_html( $activity['text'] ); ?></p>
						<span class="activity-time"><?php echo esc_html( $activity['time'] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Next Steps Recommendation -->
	<div class="writgo-card card-recommendation">
		<h2><?php esc_html_e( 'Wat Nu?', 'writgocms' ); ?></h2>
		<div class="recommendation-content">
			<?php
			// Determine next recommended action based on workflow status.
			if ( $stats['workflow_step_1']['status'] !== 'completed' ) :
				?>
				<div class="recommendation-item">
					<span class="rec-icon">🔍</span>
					<div class="rec-content">
						<h4><?php esc_html_e( 'Start met een Website Analyse', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'Laat WritgoAI je website scannen om verbeterpunten te vinden.', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Start Analyse', 'writgocms' ); ?> →
						</a>
					</div>
				</div>
			<?php elseif ( $stats['workflow_step_2']['status'] !== 'completed' ) : ?>
				<div class="recommendation-item">
					<span class="rec-icon">📝</span>
					<div class="rec-content">
						<h4><?php esc_html_e( 'Maak een Contentplan', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'Gebruik AI om een complete contentstrategie te ontwikkelen.', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Maak Plan', 'writgocms' ); ?> →
						</a>
					</div>
				</div>
			<?php else : ?>
				<div class="recommendation-item">
					<span class="rec-icon">✍️</span>
					<div class="rec-content">
						<h4><?php esc_html_e( 'Begin met Schrijven', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'Je hebt alles klaar staan. Tijd om content te maken!', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Nieuw Artikel', 'writgocms' ); ?> →
						</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

</div>

<style>
/* Additional dashboard-specific styles */
.writgo-dashboard-container {
	max-width: 1200px;
	margin: 20px auto;
}

.hero-content {
	text-align: center;
	padding: 20px 0;
}

.hero-content h1 {
	font-size: 32px;
	font-weight: 700;
	margin-bottom: 12px;
	color: white;
}

.hero-content .hero-subtitle {
	font-size: 18px;
	margin-bottom: 24px;
	opacity: 0.95;
}

.progress-overview {
	display: flex;
	gap: 40px;
	align-items: center;
}

.progress-stat {
	text-align: center;
}

.stat-circle {
	width: 120px;
	height: 120px;
	border-radius: 50%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	margin: 0 auto 12px;
	border: 4px solid;
}

.stat-circle.score-good {
	background: var(--writgo-success-light);
	border-color: var(--writgo-success);
	color: var(--writgo-success);
}

.stat-circle.score-warning {
	background: var(--writgo-warning-light);
	border-color: var(--writgo-warning);
	color: var(--writgo-warning);
}

.stat-circle.score-poor {
	background: var(--writgo-error-light);
	border-color: var(--writgo-error);
	color: var(--writgo-error);
}

.stat-circle .stat-value {
	font-size: 36px;
	font-weight: 700;
}

.stat-circle .stat-max {
	font-size: 16px;
	opacity: 0.7;
}

.progress-details {
	flex: 1;
}

.progress-item {
	margin-bottom: 16px;
}

.progress-label {
	display: block;
	font-size: 14px;
	font-weight: 500;
	margin-bottom: 6px;
	color: var(--writgo-text);
}

.progress-bar-wrapper {
	display: flex;
	align-items: center;
	gap: 12px;
}

.progress-bar {
	flex: 1;
	height: 10px;
	background: var(--writgo-border);
	border-radius: 999px;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, var(--writgo-primary) 0%, var(--writgo-info) 100%);
	border-radius: 999px;
	transition: width 0.3s ease;
}

.progress-value {
	font-size: 14px;
	font-weight: 600;
	color: var(--writgo-text);
	min-width: 45px;
}

.quick-actions-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
}

.quick-action-card {
	padding: 24px;
	border: 2px solid var(--writgo-border);
	border-radius: var(--writgo-radius);
	text-align: center;
	transition: var(--writgo-transition);
}

.quick-action-card:hover {
	border-color: var(--writgo-primary);
	box-shadow: var(--writgo-shadow);
	transform: translateY(-2px);
}

.quick-action-card .action-icon {
	font-size: 48px;
	display: block;
	margin-bottom: 16px;
}

.quick-action-card h3 {
	font-size: 18px;
	font-weight: 600;
	color: var(--writgo-text);
	margin-bottom: 8px;
}

.quick-action-card p {
	font-size: 14px;
	color: var(--writgo-text-light);
	margin-bottom: 16px;
}

.card-header-with-toggle {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.toggle-details {
	background: none;
	border: none;
	color: var(--writgo-primary);
	cursor: pointer;
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: 14px;
	padding: 6px 12px;
	border-radius: var(--writgo-radius-sm);
	transition: var(--writgo-transition);
}

.toggle-details:hover {
	background: var(--writgo-primary-light);
}

.toggle-details.open .dashicons {
	transform: rotate(180deg);
}

.stats-summary {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 20px;
	margin-bottom: 20px;
}

.stat-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px;
	background: var(--writgo-bg);
	border-radius: var(--writgo-radius-sm);
}

.stat-item .stat-icon {
	font-size: 32px;
}

.stat-content {
	display: flex;
	flex-direction: column;
}

.stat-content .stat-value {
	font-size: 24px;
	font-weight: 700;
	color: var(--writgo-text);
}

.stat-content .stat-label {
	font-size: 12px;
	color: var(--writgo-text-light);
}

.stats-details {
	padding-top: 20px;
	border-top: 1px solid var(--writgo-border);
}

.stats-table {
	width: 100%;
}

.stats-table tr {
	border-bottom: 1px solid var(--writgo-border);
}

.stats-table td {
	padding: 12px 0;
}

.activity-feed {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.activity-item {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	padding: 12px;
	background: var(--writgo-bg);
	border-radius: var(--writgo-radius-sm);
}

.activity-icon {
	font-size: 24px;
	flex-shrink: 0;
}

.activity-content {
	flex: 1;
}

.activity-text {
	margin: 0 0 4px 0;
	font-size: 14px;
	color: var(--writgo-text);
}

.activity-time {
	font-size: 12px;
	color: var(--writgo-text-light);
}

.card-recommendation {
	background: var(--writgo-primary-light);
	border: 2px solid var(--writgo-primary);
}

.recommendation-item {
	display: flex;
	gap: 20px;
	align-items: center;
}

.recommendation-item .rec-icon {
	font-size: 48px;
	flex-shrink: 0;
}

.rec-content h4 {
	font-size: 18px;
	font-weight: 600;
	color: var(--writgo-text);
	margin-bottom: 8px;
}

.rec-content p {
	font-size: 14px;
	color: var(--writgo-text-light);
	margin-bottom: 12px;
}

@media (max-width: 768px) {
	.progress-overview {
		flex-direction: column;
	}
	
	.quick-actions-grid {
		grid-template-columns: 1fr;
	}
	
	.stats-summary {
		grid-template-columns: 1fr;
	}
	
	.recommendation-item {
		flex-direction: column;
		text-align: center;
	}
}
</style>
