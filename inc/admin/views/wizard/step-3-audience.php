<?php
/**
 * Setup Wizard - Step 3: Define Target Audience
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wizard = WritgoCMS_Setup_Wizard::get_instance();
$step_data = $wizard->get_step_data( 3 );
?>

<div class="writgo-wizard-step writgo-wizard-step-3">
	<div class="writgo-card">
		<div class="wizard-header">
			<h2><?php esc_html_e( 'Wie is je doelgroep?', 'writgocms' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Vertel ons over je ideale lezers, zodat we content kunnen maken die hen aanspreekt.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="wizard-content">
			<div class="form-field">
				<label for="target-audience">
					<?php esc_html_e( 'Beschrijf je doelgroep', 'writgocms' ); ?>
					<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Denk aan leeftijd, interesses, en wat ze zoeken', 'writgocms' ); ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				</label>
				<textarea 
					id="target-audience" 
					name="target_audience" 
					rows="4" 
					class="large-text"
					placeholder="<?php esc_attr_e( 'bijv. Jonge professionals tussen 25-40 jaar die geïnteresseerd zijn in gezonde levensstijl en fitness', 'writgocms' ); ?>"
				><?php echo esc_textarea( isset( $step_data['target_audience'] ) ? $step_data['target_audience'] : '' ); ?></textarea>
			</div>

			<div class="audience-examples">
				<h4><?php esc_html_e( 'Kies een voorbeeld:', 'writgocms' ); ?></h4>
				<div class="example-cards">
					<div class="example-card" data-example="professionals">
						<span class="example-icon">💼</span>
						<h5><?php esc_html_e( 'Professionals', 'writgocms' ); ?></h5>
						<p><?php esc_html_e( 'Werknemers en ondernemers op zoek naar carrière-advies', 'writgocms' ); ?></p>
					</div>
					<div class="example-card" data-example="parents">
						<span class="example-icon">👨‍👩‍👧‍👦</span>
						<h5><?php esc_html_e( 'Ouders', 'writgocms' ); ?></h5>
						<p><?php esc_html_e( 'Ouders die tips zoeken voor opvoeding en gezinsleven', 'writgocms' ); ?></p>
					</div>
					<div class="example-card" data-example="students">
						<span class="example-icon">🎓</span>
						<h5><?php esc_html_e( 'Studenten', 'writgocms' ); ?></h5>
						<p><?php esc_html_e( 'Studenten die leren en studiehulp zoeken', 'writgocms' ); ?></p>
					</div>
					<div class="example-card" data-example="hobbyists">
						<span class="example-icon">🎨</span>
						<h5><?php esc_html_e( 'Hobbyisten', 'writgocms' ); ?></h5>
						<p><?php esc_html_e( 'Mensen met specifieke interesses en hobby\'s', 'writgocms' ); ?></p>
					</div>
				</div>
			</div>

			<div class="form-field">
				<label>
					<?php esc_html_e( 'Belangrijkste doelen', 'writgocms' ); ?>
				</label>
				<div class="checkbox-group">
					<label>
						<input type="checkbox" name="goals[]" value="informeren" <?php checked( in_array( 'informeren', isset( $step_data['goals'] ) ? (array) $step_data['goals'] : array(), true ) ); ?> />
						<?php esc_html_e( 'Informeren en opleiden', 'writgocms' ); ?>
					</label>
					<label>
						<input type="checkbox" name="goals[]" value="verkopen" <?php checked( in_array( 'verkopen', isset( $step_data['goals'] ) ? (array) $step_data['goals'] : array(), true ) ); ?> />
						<?php esc_html_e( 'Producten verkopen', 'writgocms' ); ?>
					</label>
					<label>
						<input type="checkbox" name="goals[]" value="leads" <?php checked( in_array( 'leads', isset( $step_data['goals'] ) ? (array) $step_data['goals'] : array(), true ) ); ?> />
						<?php esc_html_e( 'Leads genereren', 'writgocms' ); ?>
					</label>
					<label>
						<input type="checkbox" name="goals[]" value="community" <?php checked( in_array( 'community', isset( $step_data['goals'] ) ? (array) $step_data['goals'] : array(), true ) ); ?> />
						<?php esc_html_e( 'Community opbouwen', 'writgocms' ); ?>
					</label>
					<label>
						<input type="checkbox" name="goals[]" value="traffic" <?php checked( in_array( 'traffic', isset( $step_data['goals'] ) ? (array) $step_data['goals'] : array(), true ) ); ?> />
						<?php esc_html_e( 'Traffic verhogen', 'writgocms' ); ?>
					</label>
				</div>
			</div>
		</div>

		<div class="wizard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-setup-wizard&step=2' ) ); ?>" class="button wizard-back">
				← <?php esc_html_e( 'Terug', 'writgocms' ); ?>
			</a>
			<button type="button" class="button button-primary button-hero wizard-next" data-step="3">
				<?php esc_html_e( 'Volgende Stap', 'writgocms' ); ?> →
			</button>
		</div>
	</div>
</div>
