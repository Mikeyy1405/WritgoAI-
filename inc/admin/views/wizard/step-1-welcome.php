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

			<?php
			// Check if user is authenticated.
			$auth_manager = WritgoCMS_Auth_Manager::get_instance();
			$is_authenticated = $auth_manager->is_authenticated();
			$current_user = $auth_manager->get_current_user();
			?>

			<?php if ( ! $is_authenticated ) : ?>
				<!-- Login Form -->
				<div class="auth-section">
					<h3><?php esc_html_e( 'Log in op je Account', 'writgocms' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Log in met je WritgoAI account om toegang te krijgen tot alle functies.', 'writgocms' ); ?>
					</p>

					<form id="wizard-login-form" class="auth-form">
						<div class="form-field">
							<label for="wizard-email"><?php esc_html_e( 'E-mailadres', 'writgocms' ); ?></label>
							<input 
								type="email" 
								id="wizard-email" 
								name="email"
								class="regular-text" 
								placeholder="<?php esc_attr_e( 'je@email.nl', 'writgocms' ); ?>"
								required
							/>
						</div>

						<div class="form-field">
							<label for="wizard-password"><?php esc_html_e( 'Wachtwoord', 'writgocms' ); ?></label>
							<input 
								type="password" 
								id="wizard-password" 
								name="password"
								class="regular-text" 
								placeholder="<?php esc_attr_e( 'Wachtwoord', 'writgocms' ); ?>"
								required
							/>
						</div>

						<div class="form-actions">
							<button type="submit" id="wizard-login-btn" class="button button-primary button-large">
								<?php esc_html_e( 'Inloggen', 'writgocms' ); ?>
							</button>
							<a href="https://writgo.ai/forgot-password" target="_blank" class="forgot-password-link">
								<?php esc_html_e( 'Wachtwoord vergeten?', 'writgocms' ); ?>
							</a>
						</div>

						<div class="auth-status-message"></div>
					</form>

					<div class="auth-help">
						<p>
							<strong><?php esc_html_e( 'Nog geen account?', 'writgocms' ); ?></strong><br>
							<a href="https://writgo.ai/register" target="_blank" class="button button-secondary">
								<?php esc_html_e( 'Account Aanmaken', 'writgocms' ); ?> →
							</a>
						</p>
					</div>
				</div>
			<?php else : ?>
				<!-- Logged In User Info -->
				<div class="auth-section auth-logged-in">
					<div class="user-welcome">
						<span class="welcome-icon">👋</span>
						<div class="welcome-text">
							<h3><?php echo esc_html( sprintf( __( 'Welkom terug, %s!', 'writgocms' ), $current_user['name'] ? $current_user['name'] : $current_user['email'] ) ); ?></h3>
							<p class="user-email"><?php echo esc_html( $current_user['email'] ); ?></p>
							<?php if ( ! empty( $current_user['company'] ) ) : ?>
								<p class="user-company"><?php echo esc_html( $current_user['company'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<p class="description">
						<?php esc_html_e( 'Je bent ingelogd en klaar om te beginnen met WritgoAI!', 'writgocms' ); ?>
					</p>
				</div>
			<?php endif; ?>
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
