<?php
/**
 * Settings Tab - Basic Settings
 *
 * Contains license, website theme, and audience settings.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_key = get_option( 'writgoai_license_key', '' );
$license_status = get_option( 'writgoai_license_status', '' );
$website_theme = get_option( 'writgoai_website_theme', '' );
$target_audience = get_option( 'writgoai_target_audience', '' );
$content_tone = get_option( 'writgoai_content_tone', 'professional' );
?>

<div class="settings-tab-content" id="tab-basic">
	<h2><?php esc_html_e( 'Basis Instellingen', 'writgoai' ); ?></h2>

	<!-- License Section -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Activatiecode', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Voer je WritgoAI licentie code in om alle functies te gebruiken', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_license_key">
				<?php esc_html_e( 'Licentie Sleutel', 'writgoai' ); ?>
			</label>
			<div class="license-input-group">
				<input 
					type="text" 
					id="writgoai_license_key" 
					name="writgoai_license_key" 
					value="<?php echo esc_attr( $license_key ); ?>" 
					class="regular-text"
					placeholder="<?php esc_attr_e( 'XXXX-XXXX-XXXX-XXXX', 'writgoai' ); ?>"
				/>
				<button type="button" id="validate-license" class="button">
					<?php esc_html_e( 'Valideren', 'writgoai' ); ?>
				</button>
			</div>
			<?php if ( $license_status === 'valid' ) : ?>
				<p class="license-status license-valid">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Licentie actief en geldig', 'writgoai' ); ?>
				</p>
			<?php elseif ( $license_status === 'invalid' ) : ?>
				<p class="license-status license-invalid">
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Licentie ongeldig', 'writgoai' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Website Theme/Niche -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Website Thema', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Het hoofdthema of de niche van je website', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_website_theme">
				<?php esc_html_e( 'Kies je niche/thema', 'writgoai' ); ?>
			</label>
			<select id="writgoai_website_theme" name="writgoai_website_theme" class="regular-text">
				<option value=""><?php esc_html_e( '-- Selecteer een thema --', 'writgoai' ); ?></option>
				<option value="gezondheid" <?php selected( $website_theme, 'gezondheid' ); ?>><?php esc_html_e( 'Gezondheid & Wellness', 'writgoai' ); ?></option>
				<option value="technologie" <?php selected( $website_theme, 'technologie' ); ?>><?php esc_html_e( 'Technologie & Gadgets', 'writgoai' ); ?></option>
				<option value="reizen" <?php selected( $website_theme, 'reizen' ); ?>><?php esc_html_e( 'Reizen & Toerisme', 'writgoai' ); ?></option>
				<option value="sport" <?php selected( $website_theme, 'sport' ); ?>><?php esc_html_e( 'Sport & Fitness', 'writgoai' ); ?></option>
				<option value="mode" <?php selected( $website_theme, 'mode' ); ?>><?php esc_html_e( 'Mode & Lifestyle', 'writgoai' ); ?></option>
				<option value="food" <?php selected( $website_theme, 'food' ); ?>><?php esc_html_e( 'Eten & Drinken', 'writgoai' ); ?></option>
				<option value="finance" <?php selected( $website_theme, 'finance' ); ?>><?php esc_html_e( 'Financiën & Beleggen', 'writgoai' ); ?></option>
				<option value="wonen" <?php selected( $website_theme, 'wonen' ); ?>><?php esc_html_e( 'Wonen & Tuin', 'writgoai' ); ?></option>
				<option value="onderwijs" <?php selected( $website_theme, 'onderwijs' ); ?>><?php esc_html_e( 'Onderwijs & Leren', 'writgoai' ); ?></option>
				<option value="business" <?php selected( $website_theme, 'business' ); ?>><?php esc_html_e( 'Business & Marketing', 'writgoai' ); ?></option>
				<option value="entertainment" <?php selected( $website_theme, 'entertainment' ); ?>><?php esc_html_e( 'Entertainment & Media', 'writgoai' ); ?></option>
				<option value="anders" <?php selected( $website_theme, 'anders' ); ?>><?php esc_html_e( 'Anders', 'writgoai' ); ?></option>
			</select>
		</div>
	</div>

	<!-- Target Audience -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Doelgroep', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Beschrijf je ideale lezers', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_target_audience">
				<?php esc_html_e( 'Wie zijn je lezers?', 'writgoai' ); ?>
			</label>
			<textarea 
				id="writgoai_target_audience" 
				name="writgoai_target_audience" 
				rows="4" 
				class="large-text"
				placeholder="<?php esc_attr_e( 'bijv. Jonge professionals tussen 25-40 jaar die geïnteresseerd zijn in gezonde levensstijl', 'writgoai' ); ?>"
			><?php echo esc_textarea( $target_audience ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'Dit helpt WritgoAI om content te maken die beter aansluit bij je publiek.', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<!-- Content Tone -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Schrijfstijl', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'De toon waarin je content wordt geschreven', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_content_tone">
				<?php esc_html_e( 'Kies je schrijfstijl', 'writgoai' ); ?>
			</label>
			<select id="writgoai_content_tone" name="writgoai_content_tone" class="regular-text">
				<option value="professional" <?php selected( $content_tone, 'professional' ); ?>><?php esc_html_e( 'Professioneel', 'writgoai' ); ?></option>
				<option value="casual" <?php selected( $content_tone, 'casual' ); ?>><?php esc_html_e( 'Casual & Vriendelijk', 'writgoai' ); ?></option>
				<option value="formal" <?php selected( $content_tone, 'formal' ); ?>><?php esc_html_e( 'Formeel', 'writgoai' ); ?></option>
				<option value="inspirational" <?php selected( $content_tone, 'inspirational' ); ?>><?php esc_html_e( 'Inspirerend', 'writgoai' ); ?></option>
				<option value="educational" <?php selected( $content_tone, 'educational' ); ?>><?php esc_html_e( 'Educatief', 'writgoai' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Dit bepaalt hoe AI je content schrijft.', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgoai' ) ); ?>
</div>
