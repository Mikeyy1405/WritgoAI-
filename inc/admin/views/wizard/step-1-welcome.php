<?php
/**
 * Setup Wizard - Step 1: Welcome
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="writgo-wizard-step writgo-wizard-step-1">
	<div class="writgo-card wizard-welcome">
		<div class="wizard-hero">
			<div class="hero-icon">🎉</div>
			<h1><?php esc_html_e( 'Welkom bij WritgoAI!', 'writgoai' ); ?></h1>
			<p class="hero-subtitle">
				<?php esc_html_e( 'We helpen je in 5 eenvoudige stappen om je website te optimaliseren met AI.', 'writgoai' ); ?>
			</p>
		</div>

		<div class="wizard-content">
			<div class="features-grid">
				<div class="feature-item">
					<span class="feature-icon">🤖</span>
					<h3><?php esc_html_e( 'AI-Aangedreven Content', 'writgoai' ); ?></h3>
					<p><?php esc_html_e( 'Genereer hoogwaardige content met geavanceerde AI-modellen', 'writgoai' ); ?></p>
				</div>
				<div class="feature-item">
					<span class="feature-icon">📊</span>
					<h3><?php esc_html_e( 'Website Analyse', 'writgoai' ); ?></h3>
					<p><?php esc_html_e( 'Ontdek verbeterpunten en groei-kansen voor je website', 'writgoai' ); ?></p>
				</div>
				<div class="feature-item">
					<span class="feature-icon">📝</span>
					<h3><?php esc_html_e( 'Contentplanning', 'writgoai' ); ?></h3>
					<p><?php esc_html_e( 'Plan je contentstrategie met slimme suggesties', 'writgoai' ); ?></p>
				</div>
				<div class="feature-item">
					<span class="feature-icon">🚀</span>
					<h3><?php esc_html_e( 'SEO Optimalisatie', 'writgoai' ); ?></h3>
					<p><?php esc_html_e( 'Verbeter je ranking met SEO-geoptimaliseerde content', 'writgoai' ); ?></p>
				</div>
			</div>

			<?php
			// Get WordPress user.
			$current_user = wp_get_current_user();
			?>

			<!-- WordPress User Welcome -->
			<div class="auth-section auth-logged-in">
				<div class="user-welcome">
					<span class="welcome-icon">👋</span>
					<div class="welcome-text">
						<h3>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: user display name */
									__( 'Welkom %s!', 'writgoai' ),
									$current_user->display_name
								)
							);
							?>
						</h3>
						<p class="user-email">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: user email address */
									__( 'Je bent ingelogd als %s.', 'writgoai' ),
									$current_user->user_email
								)
							);
							?>
						</p>
						<p class="description">
							<?php esc_html_e( 'Laten we WritgoAI instellen voor je website.', 'writgoai' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="wizard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgoai' ) ); ?>" class="button button-link wizard-skip">
				<?php esc_html_e( 'Setup overslaan', 'writgoai' ); ?>
			</a>
			<button type="button" class="button button-primary button-hero wizard-next" data-step="1">
				<?php esc_html_e( 'Aan de slag!', 'writgoai' ); ?> →
			</button>
		</div>
	</div>
</div>
