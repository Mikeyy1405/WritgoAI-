<?php
/**
 * Setup Wizard - Step 2: Website Theme/Niche
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wizard = WritgoAI_Setup_Wizard::get_instance();
$step_data = $wizard->get_step_data( 2 );
?>

<div class="writgo-wizard-step writgo-wizard-step-2">
	<div class="writgo-card">
		<div class="wizard-header">
			<h2><?php esc_html_e( 'Wat is het thema van je website?', 'writgoai' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Dit helpt ons om relevantere content en suggesties te genereren.', 'writgoai' ); ?>
			</p>
		</div>

		<div class="wizard-content">
			<div class="form-field">
				<label for="website-theme">
					<?php esc_html_e( 'Kies je niche/thema', 'writgoai' ); ?>
					<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Bijvoorbeeld: gezondheid, technologie, reizen, sport, etc.', 'writgoai' ); ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				</label>
				<select id="website-theme" name="website_theme" class="regular-text">
					<option value=""><?php esc_html_e( '-- Selecteer een thema --', 'writgoai' ); ?></option>
					<option value="gezondheid" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'gezondheid' ); ?>><?php esc_html_e( 'Gezondheid & Wellness', 'writgoai' ); ?></option>
					<option value="technologie" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'technologie' ); ?>><?php esc_html_e( 'Technologie & Gadgets', 'writgoai' ); ?></option>
					<option value="reizen" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'reizen' ); ?>><?php esc_html_e( 'Reizen & Toerisme', 'writgoai' ); ?></option>
					<option value="sport" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'sport' ); ?>><?php esc_html_e( 'Sport & Fitness', 'writgoai' ); ?></option>
					<option value="mode" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'mode' ); ?>><?php esc_html_e( 'Mode & Lifestyle', 'writgoai' ); ?></option>
					<option value="food" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'food' ); ?>><?php esc_html_e( 'Eten & Drinken', 'writgoai' ); ?></option>
					<option value="finance" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'finance' ); ?>><?php esc_html_e( 'Financiën & Beleggen', 'writgoai' ); ?></option>
					<option value="wonen" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'wonen' ); ?>><?php esc_html_e( 'Wonen & Tuin', 'writgoai' ); ?></option>
					<option value="onderwijs" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'onderwijs' ); ?>><?php esc_html_e( 'Onderwijs & Leren', 'writgoai' ); ?></option>
					<option value="business" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'business' ); ?>><?php esc_html_e( 'Business & Marketing', 'writgoai' ); ?></option>
					<option value="entertainment" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'entertainment' ); ?>><?php esc_html_e( 'Entertainment & Media', 'writgoai' ); ?></option>
					<option value="anders" <?php selected( isset( $step_data['website_theme'] ) ? $step_data['website_theme'] : '', 'anders' ); ?>><?php esc_html_e( 'Anders', 'writgoai' ); ?></option>
				</select>
			</div>

			<div class="form-field">
				<label for="custom-theme">
					<?php esc_html_e( 'Of beschrijf je eigen thema', 'writgoai' ); ?>
				</label>
				<input 
					type="text" 
					id="custom-theme" 
					name="custom_theme" 
					class="regular-text"
					placeholder="<?php esc_attr_e( 'bijv. Duurzaamheid en milieu', 'writgoai' ); ?>"
					value="<?php echo esc_attr( isset( $step_data['custom_theme'] ) ? $step_data['custom_theme'] : '' ); ?>"
				/>
			</div>

			<div class="form-field">
				<label for="content-tone">
					<?php esc_html_e( 'Schrijfstijl', 'writgoai' ); ?>
					<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'De toon waarin je content wordt geschreven', 'writgoai' ); ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				</label>
				<select id="content-tone" name="content_tone" class="regular-text">
					<option value="professional" <?php selected( isset( $step_data['content_tone'] ) ? $step_data['content_tone'] : '', 'professional' ); ?>><?php esc_html_e( 'Professioneel', 'writgoai' ); ?></option>
					<option value="casual" <?php selected( isset( $step_data['content_tone'] ) ? $step_data['content_tone'] : '', 'casual' ); ?>><?php esc_html_e( 'Casual & Vriendelijk', 'writgoai' ); ?></option>
					<option value="formal" <?php selected( isset( $step_data['content_tone'] ) ? $step_data['content_tone'] : '', 'formal' ); ?>><?php esc_html_e( 'Formeel', 'writgoai' ); ?></option>
					<option value="inspirational" <?php selected( isset( $step_data['content_tone'] ) ? $step_data['content_tone'] : '', 'inspirational' ); ?>><?php esc_html_e( 'Inspirerend', 'writgoai' ); ?></option>
					<option value="educational" <?php selected( isset( $step_data['content_tone'] ) ? $step_data['content_tone'] : '', 'educational' ); ?>><?php esc_html_e( 'Educatief', 'writgoai' ); ?></option>
				</select>
			</div>
		</div>

		<div class="wizard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=writgoai-setup-wizard&step=1' ) ); ?>" class="button wizard-back">
				← <?php esc_html_e( 'Terug', 'writgoai' ); ?>
			</a>
			<button type="button" class="button button-primary button-hero wizard-next" data-step="2">
				<?php esc_html_e( 'Volgende Stap', 'writgoai' ); ?> →
			</button>
		</div>
	</div>
</div>
