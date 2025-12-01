<?php
/**
 * Settings Tab - Basic Settings
 *
 * Contains license, website theme, and audience settings.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_key = get_option( 'writgocms_license_key', '' );
$license_status = get_option( 'writgocms_license_status', '' );
$website_theme = get_option( 'writgocms_website_theme', '' );
$target_audience = get_option( 'writgocms_target_audience', '' );
$content_tone = get_option( 'writgocms_content_tone', 'professional' );
?>

<div class="settings-tab-content" id="tab-basic">
	<h2><?php esc_html_e( 'Basis Instellingen', 'writgocms' ); ?></h2>

	<!-- License Section -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Activatiecode', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Voer je WritgoAI licentie code in om alle functies te gebruiken', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_license_key">
				<?php esc_html_e( 'Licentie Sleutel', 'writgocms' ); ?>
			</label>
			<div class="license-input-group">
				<input 
					type="text" 
					id="writgocms_license_key" 
					name="writgocms_license_key" 
					value="<?php echo esc_attr( $license_key ); ?>" 
					class="regular-text"
					placeholder="<?php esc_attr_e( 'XXXX-XXXX-XXXX-XXXX', 'writgocms' ); ?>"
				/>
				<button type="button" id="validate-license" class="button">
					<?php esc_html_e( 'Valideren', 'writgocms' ); ?>
				</button>
			</div>
			<?php if ( $license_status === 'valid' ) : ?>
				<p class="license-status license-valid">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Licentie actief en geldig', 'writgocms' ); ?>
				</p>
			<?php elseif ( $license_status === 'invalid' ) : ?>
				<p class="license-status license-invalid">
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Licentie ongeldig', 'writgocms' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Website Theme/Niche -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Website Thema', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Het hoofdthema of de niche van je website', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_website_theme">
				<?php esc_html_e( 'Kies je niche/thema', 'writgocms' ); ?>
			</label>
			<select id="writgocms_website_theme" name="writgocms_website_theme" class="regular-text">
				<option value=""><?php esc_html_e( '-- Selecteer een thema --', 'writgocms' ); ?></option>
				<option value="gezondheid" <?php selected( $website_theme, 'gezondheid' ); ?>><?php esc_html_e( 'Gezondheid & Wellness', 'writgocms' ); ?></option>
				<option value="technologie" <?php selected( $website_theme, 'technologie' ); ?>><?php esc_html_e( 'Technologie & Gadgets', 'writgocms' ); ?></option>
				<option value="reizen" <?php selected( $website_theme, 'reizen' ); ?>><?php esc_html_e( 'Reizen & Toerisme', 'writgocms' ); ?></option>
				<option value="sport" <?php selected( $website_theme, 'sport' ); ?>><?php esc_html_e( 'Sport & Fitness', 'writgocms' ); ?></option>
				<option value="mode" <?php selected( $website_theme, 'mode' ); ?>><?php esc_html_e( 'Mode & Lifestyle', 'writgocms' ); ?></option>
				<option value="food" <?php selected( $website_theme, 'food' ); ?>><?php esc_html_e( 'Eten & Drinken', 'writgocms' ); ?></option>
				<option value="finance" <?php selected( $website_theme, 'finance' ); ?>><?php esc_html_e( 'Financiën & Beleggen', 'writgocms' ); ?></option>
				<option value="wonen" <?php selected( $website_theme, 'wonen' ); ?>><?php esc_html_e( 'Wonen & Tuin', 'writgocms' ); ?></option>
				<option value="onderwijs" <?php selected( $website_theme, 'onderwijs' ); ?>><?php esc_html_e( 'Onderwijs & Leren', 'writgocms' ); ?></option>
				<option value="business" <?php selected( $website_theme, 'business' ); ?>><?php esc_html_e( 'Business & Marketing', 'writgocms' ); ?></option>
				<option value="entertainment" <?php selected( $website_theme, 'entertainment' ); ?>><?php esc_html_e( 'Entertainment & Media', 'writgocms' ); ?></option>
				<option value="anders" <?php selected( $website_theme, 'anders' ); ?>><?php esc_html_e( 'Anders', 'writgocms' ); ?></option>
			</select>
		</div>
	</div>

	<!-- Target Audience -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Doelgroep', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Beschrijf je ideale lezers', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_target_audience">
				<?php esc_html_e( 'Wie zijn je lezers?', 'writgocms' ); ?>
			</label>
			<textarea 
				id="writgocms_target_audience" 
				name="writgocms_target_audience" 
				rows="4" 
				class="large-text"
				placeholder="<?php esc_attr_e( 'bijv. Jonge professionals tussen 25-40 jaar die geïnteresseerd zijn in gezonde levensstijl', 'writgocms' ); ?>"
			><?php echo esc_textarea( $target_audience ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'Dit helpt WritgoAI om content te maken die beter aansluit bij je publiek.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Content Tone -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Schrijfstijl', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'De toon waarin je content wordt geschreven', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_content_tone">
				<?php esc_html_e( 'Kies je schrijfstijl', 'writgocms' ); ?>
			</label>
			<select id="writgocms_content_tone" name="writgocms_content_tone" class="regular-text">
				<option value="professional" <?php selected( $content_tone, 'professional' ); ?>><?php esc_html_e( 'Professioneel', 'writgocms' ); ?></option>
				<option value="casual" <?php selected( $content_tone, 'casual' ); ?>><?php esc_html_e( 'Casual & Vriendelijk', 'writgocms' ); ?></option>
				<option value="formal" <?php selected( $content_tone, 'formal' ); ?>><?php esc_html_e( 'Formeel', 'writgocms' ); ?></option>
				<option value="inspirational" <?php selected( $content_tone, 'inspirational' ); ?>><?php esc_html_e( 'Inspirerend', 'writgocms' ); ?></option>
				<option value="educational" <?php selected( $content_tone, 'educational' ); ?>><?php esc_html_e( 'Educatief', 'writgocms' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Dit bepaalt hoe AI je content schrijft.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgocms' ) ); ?>
</div>
