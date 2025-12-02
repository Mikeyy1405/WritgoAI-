<?php
/**
 * Settings Tab - Content Settings
 *
 * Contains content planning and Gutenberg toolbar settings.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$content_categories = get_option( 'writgoai_content_categories', array( 'informatief', 'reviews', 'top_lijstjes', 'vergelijkingen' ) );
$items_per_analysis = get_option( 'writgoai_items_per_analysis', 20 );
$weekly_updates = get_option( 'writgoai_weekly_updates', 1 );
$toolbar_enabled = get_option( 'writgoai_toolbar_enabled', 1 );
$toolbar_buttons = get_option( 'writgoai_toolbar_buttons', array(
	'rewrite'     => true,
	'links'       => true,
	'image'       => true,
	'rewrite_all' => true,
) );
$toolbar_rewrite_tone = get_option( 'writgoai_toolbar_rewrite_tone', 'same' );
$toolbar_links_limit = get_option( 'writgoai_toolbar_links_limit', 5 );
?>

<div class="settings-tab-content" id="tab-content" style="display: none;">
	<h2><?php esc_html_e( 'Content Instellingen', 'writgoai' ); ?></h2>

	<!-- Content Categories -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Content Categorieën', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Kies welke soorten content je wilt maken', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label><?php esc_html_e( 'Selecteer content types', 'writgoai' ); ?></label>
			<div class="checkbox-group">
				<label>
					<input type="checkbox" name="writgoai_content_categories[]" value="informatief" <?php checked( in_array( 'informatief', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '📚 Informatieve Content', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Educatieve artikelen en handleidingen', 'writgoai' ); ?></span>
					</span>
				</label>
				<label>
					<input type="checkbox" name="writgoai_content_categories[]" value="reviews" <?php checked( in_array( 'reviews', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '⭐ Reviews', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Product en dienst beoordelingen', 'writgoai' ); ?></span>
					</span>
				</label>
				<label>
					<input type="checkbox" name="writgoai_content_categories[]" value="top_lijstjes" <?php checked( in_array( 'top_lijstjes', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '🏆 Top Lijstjes', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( '"Top 10" en ranglijsten', 'writgoai' ); ?></span>
					</span>
				</label>
				<label>
					<input type="checkbox" name="writgoai_content_categories[]" value="vergelijkingen" <?php checked( in_array( 'vergelijkingen', $content_categories, true ) ); ?> />
					<span class="checkbox-label">
						<strong><?php esc_html_e( '⚖️ Vergelijkingen', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Productvergelijkingen en alternatieven', 'writgoai' ); ?></span>
					</span>
				</label>
			</div>
		</div>
	</div>

	<!-- Content Planning -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Content Planning', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Hoe AI je contentplan genereert', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_items_per_analysis">
				<?php esc_html_e( 'Aantal artikelen per analyse', 'writgoai' ); ?>
			</label>
			<input 
				type="number" 
				id="writgoai_items_per_analysis" 
				name="writgoai_items_per_analysis" 
				value="<?php echo esc_attr( $items_per_analysis ); ?>" 
				min="5" 
				max="50" 
				step="5"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Hoeveel artikel suggesties wil je krijgen per contentplan?', 'writgoai' ); ?>
			</p>
		</div>

		<div class="form-field">
			<label for="writgoai_weekly_updates">
				<?php esc_html_e( 'Publicatie ritme', 'writgoai' ); ?>
			</label>
			<select id="writgoai_weekly_updates" name="writgoai_weekly_updates" class="regular-text">
				<option value="1" <?php selected( $weekly_updates, 1 ); ?>><?php esc_html_e( '1 artikel per week', 'writgoai' ); ?></option>
				<option value="2" <?php selected( $weekly_updates, 2 ); ?>><?php esc_html_e( '2 artikelen per week', 'writgoai' ); ?></option>
				<option value="3" <?php selected( $weekly_updates, 3 ); ?>><?php esc_html_e( '3 artikelen per week', 'writgoai' ); ?></option>
				<option value="5" <?php selected( $weekly_updates, 5 ); ?>><?php esc_html_e( '5 artikelen per week (dagelijks)', 'writgoai' ); ?></option>
				<option value="7" <?php selected( $weekly_updates, 7 ); ?>><?php esc_html_e( '7+ artikelen per week', 'writgoai' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Hoe vaak publiceer je nieuwe content?', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<!-- Gutenberg Toolbar -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Editor Werkbalk', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'AI functionaliteit in de Gutenberg editor', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label>
				<input 
					type="checkbox" 
					name="writgoai_toolbar_enabled" 
					value="1" 
					<?php checked( $toolbar_enabled, 1 ); ?>
				/>
				<?php esc_html_e( 'AI werkbalk inschakelen in de editor', 'writgoai' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Voegt AI-knoppen toe aan de Gutenberg tekstselectie werkbalk.', 'writgoai' ); ?>
			</p>
		</div>

		<div class="toolbar-buttons-section" <?php echo $toolbar_enabled ? '' : 'style="opacity: 0.5; pointer-events: none;"'; ?>>
			<label><?php esc_html_e( 'Beschikbare functies', 'writgoai' ); ?></label>
			<div class="checkbox-group">
				<label>
					<input 
						type="checkbox" 
						name="writgoai_toolbar_buttons[rewrite]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['rewrite'] ) ? $toolbar_buttons['rewrite'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '✏️ Herschrijven', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Geselecteerde tekst opnieuw schrijven', 'writgoai' ); ?></span>
					</span>
				</label>
				<label>
					<input 
						type="checkbox" 
						name="writgoai_toolbar_buttons[links]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['links'] ) ? $toolbar_buttons['links'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '🔗 Interne Links', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Automatisch interne links toevoegen', 'writgoai' ); ?></span>
					</span>
				</label>
				<label>
					<input 
						type="checkbox" 
						name="writgoai_toolbar_buttons[image]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['image'] ) ? $toolbar_buttons['image'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '🖼️ AI Afbeelding', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Afbeelding genereren voor tekst', 'writgoai' ); ?></span>
					</span>
				</label>
				<label>
					<input 
						type="checkbox" 
						name="writgoai_toolbar_buttons[rewrite_all]" 
						value="1" 
						<?php checked( isset( $toolbar_buttons['rewrite_all'] ) ? $toolbar_buttons['rewrite_all'] : false, true ); ?>
					/>
					<span class="checkbox-label">
						<strong><?php esc_html_e( '📝 Heel artikel herschrijven', 'writgoai' ); ?></strong>
						<span class="checkbox-desc"><?php esc_html_e( 'Volledig artikel opnieuw schrijven', 'writgoai' ); ?></span>
					</span>
				</label>
			</div>
		</div>

		<div class="form-field">
			<label for="writgoai_toolbar_rewrite_tone">
				<?php esc_html_e( 'Herschrijf stijl', 'writgoai' ); ?>
			</label>
			<select id="writgoai_toolbar_rewrite_tone" name="writgoai_toolbar_rewrite_tone" class="regular-text">
				<option value="same" <?php selected( $toolbar_rewrite_tone, 'same' ); ?>><?php esc_html_e( 'Behoud huidige stijl', 'writgoai' ); ?></option>
				<option value="simpler" <?php selected( $toolbar_rewrite_tone, 'simpler' ); ?>><?php esc_html_e( 'Eenvoudiger maken', 'writgoai' ); ?></option>
				<option value="professional" <?php selected( $toolbar_rewrite_tone, 'professional' ); ?>><?php esc_html_e( 'Professioneler maken', 'writgoai' ); ?></option>
				<option value="casual" <?php selected( $toolbar_rewrite_tone, 'casual' ); ?>><?php esc_html_e( 'Informeler maken', 'writgoai' ); ?></option>
			</select>
		</div>

		<div class="form-field">
			<label for="writgoai_toolbar_links_limit">
				<?php esc_html_e( 'Maximum aantal interne links', 'writgoai' ); ?>
			</label>
			<input 
				type="number" 
				id="writgoai_toolbar_links_limit" 
				name="writgoai_toolbar_links_limit" 
				value="<?php echo esc_attr( $toolbar_links_limit ); ?>" 
				min="1" 
				max="20"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Maximaal aantal interne links om toe te voegen per sectie.', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgoai' ) ); ?>
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
