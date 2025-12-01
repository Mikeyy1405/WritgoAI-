<?php
/**
 * Setup Wizard - Step 1: Welcome
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="writgo-wizard-step writgo-wizard-step-1">
	<div class="writgo-card wizard-welcome">
		<div class="wizard-hero">
			<div class="hero-icon">🎉</div>
			<h1><?php esc_html_e( 'Welkom bij WritgoAI!', 'writgocms' ); ?></h1>
			<p class="hero-subtitle">
				<?php esc_html_e( 'We helpen je in 5 eenvoudige stappen om je website te optimaliseren met AI.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="wizard-content">
			<div class="features-grid">
				<div class="feature-item">
					<span class="feature-icon">🤖</span>
					<h3><?php esc_html_e( 'AI-Aangedreven Content', 'writgocms' ); ?></h3>
					<p><?php esc_html_e( 'Genereer hoogwaardige content met geavanceerde AI-modellen', 'writgocms' ); ?></p>
				</div>
				<div class="feature-item">
					<span class="feature-icon">📊</span>
					<h3><?php esc_html_e( 'Website Analyse', 'writgocms' ); ?></h3>
					<p><?php esc_html_e( 'Ontdek verbeterpunten en groei-kansen voor je website', 'writgocms' ); ?></p>
				</div>
				<div class="feature-item">
					<span class="feature-icon">📝</span>
					<h3><?php esc_html_e( 'Contentplanning', 'writgocms' ); ?></h3>
					<p><?php esc_html_e( 'Plan je contentstrategie met slimme suggesties', 'writgocms' ); ?></p>
				</div>
				<div class="feature-item">
					<span class="feature-icon">🚀</span>
					<h3><?php esc_html_e( 'SEO Optimalisatie', 'writgocms' ); ?></h3>
					<p><?php esc_html_e( 'Verbeter je ranking met SEO-geoptimaliseerde content', 'writgocms' ); ?></p>
				</div>
			</div>

			<div class="license-section">
				<h3><?php esc_html_e( 'Activeer je Licentie', 'writgocms' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Voer je activatiecode in om toegang te krijgen tot alle functies.', 'writgocms' ); ?>
				</p>
				
				<?php
				$license_key = get_option( 'writgocms_license_key', '' );
				$license_status = get_option( 'writgocms_license_status', '' );
				?>

				<div class="license-input-group">
					<input 
						type="text" 
						id="wizard-license-key" 
						class="regular-text" 
						placeholder="<?php esc_attr_e( 'bijv. XXXX-XXXX-XXXX-XXXX', 'writgocms' ); ?>"
						value="<?php echo esc_attr( $license_key ); ?>"
					/>
					<button type="button" id="validate-license-btn" class="button button-primary">
						<?php esc_html_e( 'Valideren', 'writgocms' ); ?>
					</button>
				</div>

				<?php if ( $license_status === 'valid' ) : ?>
					<div class="license-status license-valid">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Licentie is geldig en actief!', 'writgocms' ); ?>
					</div>
				<?php endif; ?>

				<p class="license-help">
					<a href="https://writgo.ai/licentie" target="_blank">
						<?php esc_html_e( 'Nog geen licentie? Koop er hier een', 'writgocms' ); ?> →
					</a>
				</p>
			</div>
		</div>

		<div class="wizard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml' ) ); ?>" class="button button-link wizard-skip">
				<?php esc_html_e( 'Setup overslaan', 'writgocms' ); ?>
			</a>
			<button type="button" class="button button-primary button-hero wizard-next" data-step="1">
				<?php esc_html_e( 'Volgende Stap', 'writgocms' ); ?> →
			</button>
		</div>
	</div>
</div>
