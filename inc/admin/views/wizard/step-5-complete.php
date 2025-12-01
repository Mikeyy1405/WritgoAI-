<?php
/**
 * Setup Wizard - Step 5: Complete
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="writgo-wizard-step writgo-wizard-step-5">
	<div class="writgo-card wizard-complete">
		<div class="wizard-hero">
			<div class="hero-icon success">🎉</div>
			<h1><?php esc_html_e( 'Gefeliciteerd!', 'writgocms' ); ?></h1>
			<p class="hero-subtitle">
				<?php esc_html_e( 'Je bent helemaal klaar om te beginnen met WritgoAI.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="wizard-content">
			<div class="next-steps">
				<h3><?php esc_html_e( 'Wat kun je nu doen?', 'writgocms' ); ?></h3>
				
				<div class="next-step-cards">
					<div class="next-step-card">
						<span class="step-icon">📊</span>
						<h4><?php esc_html_e( 'Dashboard bekijken', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'Zie je website health score en krijg aanbevelingen', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Naar Dashboard', 'writgocms' ); ?> →
						</a>
					</div>

					<div class="next-step-card">
						<span class="step-icon">📝</span>
						<h4><?php esc_html_e( 'Contentplan maken', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'Laat AI een complete contentstrategie voor je maken', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="button">
							<?php esc_html_e( 'Start Planning', 'writgocms' ); ?> →
						</a>
					</div>

					<div class="next-step-card">
						<span class="step-icon">✍️</span>
						<h4><?php esc_html_e( 'Content genereren', 'writgocms' ); ?></h4>
						<p><?php esc_html_e( 'Begin direct met het maken van je eerste AI-content', 'writgocms' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="button">
							<?php esc_html_e( 'Nieuwe Post', 'writgocms' ); ?> →
						</a>
					</div>
				</div>
			</div>

			<div class="resources">
				<h4><?php esc_html_e( 'Hulpbronnen', 'writgocms' ); ?></h4>
				<ul class="resource-links">
					<li>
						<span class="dashicons dashicons-media-document"></span>
						<a href="https://writgo.ai/docs" target="_blank">
							<?php esc_html_e( 'Documentatie & Handleidingen', 'writgocms' ); ?>
						</a>
					</li>
					<li>
						<span class="dashicons dashicons-video-alt3"></span>
						<a href="https://writgo.ai/tutorials" target="_blank">
							<?php esc_html_e( 'Video Tutorials', 'writgocms' ); ?>
						</a>
					</li>
					<li>
						<span class="dashicons dashicons-groups"></span>
						<a href="https://writgo.ai/community" target="_blank">
							<?php esc_html_e( 'Community Forum', 'writgocms' ); ?>
						</a>
					</li>
					<li>
						<span class="dashicons dashicons-sos"></span>
						<a href="https://writgo.ai/support" target="_blank">
							<?php esc_html_e( 'Support & Hulp', 'writgocms' ); ?>
						</a>
					</li>
				</ul>
			</div>

			<div class="completion-badge">
				<div class="badge-content">
					<span class="badge-icon">🏆</span>
					<div class="badge-text">
						<strong><?php esc_html_e( 'Setup Voltooid!', 'writgocms' ); ?></strong>
						<p><?php esc_html_e( 'Je bent nu klaar om AI te gebruiken voor je website', 'writgocms' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="wizard-actions">
			<button type="button" class="button button-primary button-hero wizard-complete" data-step="5">
				<?php esc_html_e( 'Naar Dashboard', 'writgocms' ); ?> →
			</button>
		</div>
	</div>
</div>
