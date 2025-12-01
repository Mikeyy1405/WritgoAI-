<?php
/**
 * Setup Wizard - Step 4: First Website Analysis
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wizard = WritgoCMS_Setup_Wizard::get_instance();
$step_data = $wizard->get_step_data( 4 );
?>

<div class="writgo-wizard-step writgo-wizard-step-4">
	<div class="writgo-card">
		<div class="wizard-header">
			<h2><?php esc_html_e( 'Laten we je website analyseren', 'writgocms' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'We scannen je website om verbeterpunten en kansen te vinden.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="wizard-content">
			<div class="analysis-info">
				<div class="info-item">
					<span class="info-icon">🔍</span>
					<div class="info-content">
						<h4><?php esc_html_e( 'Wat analyseren we?', 'writgocms' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Bestaande content en structuur', 'writgocms' ); ?></li>
							<li><?php esc_html_e( 'SEO-optimalisatie mogelijkheden', 'writgocms' ); ?></li>
							<li><?php esc_html_e( 'Content gaps en kansen', 'writgocms' ); ?></li>
							<li><?php esc_html_e( 'Interne link structuur', 'writgocms' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="info-item">
					<span class="info-icon">⏱️</span>
					<div class="info-content">
						<h4><?php esc_html_e( 'Hoe lang duurt het?', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'De analyse duurt ongeveer 2-5 minuten, afhankelijk van de grootte van je website.', 'writgocms' ); ?></p>
					</div>
				</div>
			</div>

			<div class="analysis-action-box">
				<button type="button" id="start-analysis-btn" class="button button-primary button-hero">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Start Analyse', 'writgocms' ); ?>
				</button>
			</div>

			<div id="analysis-progress" class="analysis-progress" style="display: none;">
				<div class="progress-bar">
					<div class="progress-fill"></div>
				</div>
				<p class="progress-text"><?php esc_html_e( 'Analyse bezig...', 'writgocms' ); ?></p>
			</div>

			<div id="analysis-results" class="analysis-results" style="display: none;">
				<div class="result-success">
					<span class="dashicons dashicons-yes-alt"></span>
					<h3><?php esc_html_e( 'Analyse voltooid!', 'writgocms' ); ?></h3>
					<p><?php esc_html_e( 'We hebben waardevolle inzichten gevonden voor je website.', 'writgocms' ); ?></p>
				</div>

				<div class="quick-insights">
					<div class="insight-card">
						<span class="insight-value" id="insight-posts">--</span>
						<span class="insight-label"><?php esc_html_e( 'Posts geanalyseerd', 'writgocms' ); ?></span>
					</div>
					<div class="insight-card">
						<span class="insight-value" id="insight-score">--</span>
						<span class="insight-label"><?php esc_html_e( 'Health Score', 'writgocms' ); ?></span>
					</div>
					<div class="insight-card">
						<span class="insight-value" id="insight-opportunities">--</span>
						<span class="insight-label"><?php esc_html_e( 'Kansen gevonden', 'writgocms' ); ?></span>
					</div>
				</div>
			</div>

			<div class="skip-analysis">
				<p>
					<button type="button" class="button button-link skip-analysis-btn">
						<?php esc_html_e( 'Analyse overslaan (kan later nog)', 'writgocms' ); ?>
					</button>
				</p>
			</div>
		</div>

		<div class="wizard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-setup-wizard&step=3' ) ); ?>" class="button wizard-back">
				← <?php esc_html_e( 'Terug', 'writgocms' ); ?>
			</a>
			<button type="button" class="button button-primary button-hero wizard-next" data-step="4" disabled>
				<?php esc_html_e( 'Volgende Stap', 'writgocms' ); ?> →
			</button>
		</div>
	</div>
</div>
