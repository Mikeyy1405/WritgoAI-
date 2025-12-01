<?php
/**
 * Settings Tab - Content Settings
 *
 * Contains content planning and Gutenberg toolbar settings.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$content_categories = get_option( 'writgocms_content_categories', array( 'informatief', 'reviews', 'top_lijstjes', 'vergelijkingen' ) );
$items_per_analysis = get_option( 'writgocms_items_per_analysis', 20 );
$weekly_updates = get_option( 'writgocms_weekly_updates', 1 );
$toolbar_enabled = get_option( 'writgocms_toolbar_enabled', 1 );
$toolbar_buttons = get_option( 'writgocms_toolbar_buttons', array(
	'rewrite'     => true,
	'links'       => true,
	'image'       => true,
	'rewrite_all' => true,
) );
$toolbar_rewrite_tone = get_option( 'writgocms_toolbar_rewrite_tone', 'same' );
$toolbar_links_limit = get_option( 'writgocms_toolbar_links_limit', 5 );
?>

<div class="settings-tab-content" id="tab-content" style="display: none;">
	<h2><?php esc_html_e( 'Content Instellingen', 'writgocms' ); ?></h2>

	<!-- Content Categories -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Content Categorieën', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Kies welke soorten content je wilt maken', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label><?php esc_html_e( 'Selecteer content types', 'writgocms' ); ?></label>
			<div class="checkbox-group">
				<label>
					<input type="checkbox" name="writgocms_content_categories[]" value="informatief" <?php checked( in_array( 'informatief', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '📚 Informatieve Content', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Educatieve artikelen en handleidingen', 'writgocms' ); ?></span>
					</span>
				</label>
				<label>
					<input type="checkbox" name="writgocms_content_categories[]" value="reviews" <?php checked( in_array( 'reviews', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '⭐ Reviews', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Product en dienst beoordelingen', 'writgocms' ); ?></span>
					</span>
				</label>
				<label>
					<input type="checkbox" name="writgocms_content_categories[]" value="top_lijstjes" <?php checked( in_array( 'top_lijstjes', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '🏆 Top Lijstjes', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( '"Top 10" en ranglijsten', 'writgocms' ); ?></span>
					</span>
				</label>
				<label>
					<input type="checkbox" name="writgocms_content_categories[]" value="vergelijkingen" <?php checked( in_array( 'vergelijkingen', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '⚖️ Vergelijkingen', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Productvergelijkingen en alternatieven', 'writgocms' ); ?></span>
					</span>
				</label>
			</div>
		</div>
	</div>

	<!-- Content Planning -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Content Planning', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Hoe AI je contentplan genereert', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_items_per_analysis">
				<?php esc_html_e( 'Aantal artikelen per analyse', 'writgocms' ); ?>
			</label>
			<input 
				type="number" 
				id="writgocms_items_per_analysis" 
				name="writgocms_items_per_analysis" 
				value="<?php echo esc_attr( $items_per_analysis ); ?>" 
				min="5" 
				max="50" 
				step="5"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Hoeveel artikel suggesties wil je krijgen per contentplan?', 'writgocms' ); ?>
			</p>
		</div>

		<div class="form-field">
			<label for="writgocms_weekly_updates">
				<?php esc_html_e( 'Publicatie ritme', 'writgocms' ); ?>
			</label>
			<select id="writgocms_weekly_updates" name="writgocms_weekly_updates" class="regular-text">
				<option value="1" <?php selected( $weekly_updates, 1 ); ?>><?php esc_html_e( '1 artikel per week', 'writgocms' ); ?></option>
				<option value="2" <?php selected( $weekly_updates, 2 ); ?>><?php esc_html_e( '2 artikelen per week', 'writgocms' ); ?></option>
				<option value="3" <?php selected( $weekly_updates, 3 ); ?>><?php esc_html_e( '3 artikelen per week', 'writgocms' ); ?></option>
				<option value="5" <?php selected( $weekly_updates, 5 ); ?>><?php esc_html_e( '5 artikelen per week (dagelijks)', 'writgocms' ); ?></option>
				<option value="7" <?php selected( $weekly_updates, 7 ); ?>><?php esc_html_e( '7+ artikelen per week', 'writgocms' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Hoe vaak publiceer je nieuwe content?', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Gutenberg Toolbar -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Editor Werkbalk', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'AI functionaliteit in de Gutenberg editor', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label>
				<input 
					type="checkbox" 
					name="writgocms_toolbar_enabled" 
					value="1" 
					<?php checked( $toolbar_enabled, 1 ); ?>
				/>
				<?php esc_html_e( 'AI werkbalk inschakelen in de editor', 'writgocms' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Voegt AI-knoppen toe aan de Gutenberg tekstselectie werkbalk.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="toolbar-buttons-section" <?php echo $toolbar_enabled ? '' : 'style="opacity: 0.5; pointer-events: none;"'; ?>>
			<label><?php esc_html_e( 'Beschikbare functies', 'writgocms' ); ?></label>
			<div class="checkbox-group">
				<label>
					<input 
						type="checkbox" 
						name="writgocms_toolbar_buttons[rewrite]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['rewrite'] ) ? $toolbar_buttons['rewrite'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '✏️ Herschrijven', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Geselecteerde tekst opnieuw schrijven', 'writgocms' ); ?></span>
					</span>
				</label>
				<label>
					<input 
						type="checkbox" 
						name="writgocms_toolbar_buttons[links]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['links'] ) ? $toolbar_buttons['links'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '🔗 Interne Links', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Automatisch interne links toevoegen', 'writgocms' ); ?></span>
					</span>
				</label>
				<label>
					<input 
						type="checkbox" 
						name="writgocms_toolbar_buttons[image]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['image'] ) ? $toolbar_buttons['image'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '🖼️ AI Afbeelding', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Afbeelding genereren voor tekst', 'writgocms' ); ?></span>
					</span>
				</label>
				<label>
					<input 
						type="checkbox" 
						name="writgocms_toolbar_buttons[rewrite_all]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['rewrite_all'] ) ? $toolbar_buttons['rewrite_all'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '📝 Heel artikel herschrijven', 'writgocms' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Volledig artikel opnieuw schrijven', 'writgocms' ); ?></span>
					</span>
				</label>
			</div>
		</div>

		<div class="form-field">
			<label for="writgocms_toolbar_rewrite_tone">
				<?php esc_html_e( 'Herschrijf stijl', 'writgocms' ); ?>
			</label>
			<select id="writgocms_toolbar_rewrite_tone" name="writgocms_toolbar_rewrite_tone" class="regular-text">
				<option value="same" <?php selected( $toolbar_rewrite_tone, 'same' ); ?>><?php esc_html_e( 'Behoud huidige stijl', 'writgocms' ); ?></option>
				<option value="simpler" <?php selected( $toolbar_rewrite_tone, 'simpler' ); ?>><?php esc_html_e( 'Eenvoudiger maken', 'writgocms' ); ?></option>
				<option value="professional" <?php selected( $toolbar_rewrite_tone, 'professional' ); ?>><?php esc_html_e( 'Professioneler maken', 'writgocms' ); ?></option>
				<option value="casual" <?php selected( $toolbar_rewrite_tone, 'casual' ); ?>><?php esc_html_e( 'Informeler maken', 'writgocms' ); ?></option>
			</select>
		</div>

		<div class="form-field">
			<label for="writgocms_toolbar_links_limit">
				<?php esc_html_e( 'Maximum aantal interne links', 'writgocms' ); ?>
			</label>
			<input 
				type="number" 
				id="writgocms_toolbar_links_limit" 
				name="writgocms_toolbar_links_limit" 
				value="<?php echo esc_attr( $toolbar_links_limit ); ?>" 
				min="1" 
				max="20"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Maximaal aantal interne links om toe te voegen per sectie.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgocms' ) ); ?>
</div>

<style>
.checkbox-label {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.checkbox-desc {
	font-size: 13px;
	color: var(--writgo-text-light);
	font-weight: normal;
}
</style>
