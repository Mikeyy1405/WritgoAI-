<?php
/**
 * Settings Tab - Advanced Options
 *
 * Contains advanced technical settings for power users.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_url = get_option( 'writgocms_api_url', 'https://api.writgo.ai' );
$notifications = get_option( 'writgocms_notifications', 1 );
$image_size = get_option( 'writgocms_image_size', '1024x1024' );
$image_quality = get_option( 'writgocms_image_quality', 'standard' );
?>

<div class="settings-tab-content" id="tab-advanced" style="display: none;">
	<h2><?php esc_html_e( 'Geavanceerde Opties', 'writgocms' ); ?></h2>
	
	<div class="writgo-card card-warning">
		<p>
			<span class="dashicons dashicons-warning"></span>
			<strong><?php esc_html_e( 'Let op:', 'writgocms' ); ?></strong>
			<?php esc_html_e( 'Deze instellingen zijn voor gevorderde gebruikers. Wijzig alleen als je weet wat je doet.', 'writgocms' ); ?>
		</p>
	</div>

	<!-- API Configuration -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'API Configuratie', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Verbinding met de WritgoAI API server', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_api_url">
				<?php esc_html_e( 'API Server URL', 'writgocms' ); ?>
			</label>
			<input 
				type="url" 
				id="writgocms_api_url" 
				name="writgocms_api_url" 
				value="<?php echo esc_url( $api_url ); ?>" 
				class="regular-text"
				placeholder="https://api.writgo.ai"
			/>
			<p class="description">
				<?php esc_html_e( 'De URL van de WritgoAI API server. Wijzig dit alleen als instructie van support.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="api-status">
			<button type="button" id="test-api-connection" class="button">
				<span class="dashicons dashicons-admin-tools"></span>
				<?php esc_html_e( 'Test Verbinding', 'writgocms' ); ?>
			</button>
			<span id="api-status-result"></span>
		</div>
	</div>

	<!-- Image Generation Settings -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Afbeelding Generatie', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Instellingen voor AI-gegenereerde afbeeldingen', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_image_size">
				<?php esc_html_e( 'Standaard afbeeldingsgrootte', 'writgocms' ); ?>
			</label>
			<select id="writgocms_image_size" name="writgocms_image_size" class="regular-text">
				<option value="256x256" <?php selected( $image_size, '256x256' ); ?>>256x256 (Klein)</option>
				<option value="512x512" <?php selected( $image_size, '512x512' ); ?>>512x512 (Gemiddeld)</option>
				<option value="1024x1024" <?php selected( $image_size, '1024x1024' ); ?>>1024x1024 (Groot)</option>
				<option value="1792x1024" <?php selected( $image_size, '1792x1024' ); ?>>1792x1024 (Widescreen)</option>
				<option value="1024x1792" <?php selected( $image_size, '1024x1792' ); ?>>1024x1792 (Portret)</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Grotere afbeeldingen kosten meer credits maar hebben betere kwaliteit.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="form-field">
			<label for="writgocms_image_quality">
				<?php esc_html_e( 'Afbeeldingskwaliteit', 'writgocms' ); ?>
			</label>
			<select id="writgocms_image_quality" name="writgocms_image_quality" class="regular-text">
				<option value="standard" <?php selected( $image_quality, 'standard' ); ?>><?php esc_html_e( 'Standaard', 'writgocms' ); ?></option>
				<option value="hd" <?php selected( $image_quality, 'hd' ); ?>><?php esc_html_e( 'HD (Hoge kwaliteit)', 'writgocms' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'HD kwaliteit kost meer credits maar levert scherpere afbeeldingen.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Notifications -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Notificaties', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Meldingen over belangrijke events', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label>
				<input 
					type="checkbox" 
					name="writgocms_notifications" 
					value="1" 
					<?php checked( $notifications, 1 ); ?>
				/>
				<?php esc_html_e( 'E-mail notificaties inschakelen', 'writgocms' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Ontvang meldingen bij belangrijke events zoals: analyses voltooid, licentie verlopen, etc.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Database & Cache -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Database & Cache', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Beheer opgeslagen data', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="maintenance-actions">
			<button type="button" id="clear-cache" class="button">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Cache Legen', 'writgocms' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Verwijder tijdelijk opgeslagen data. Gebruik dit als je problemen ervaart.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="maintenance-actions">
			<button type="button" id="reset-wizard" class="button">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Setup Wizard Opnieuw Starten', 'writgocms' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Start de setup wizard opnieuw. Je huidige instellingen blijven behouden.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Debug Information -->
	<div class="writgo-card">
		<h3><?php esc_html_e( 'Debug Informatie', 'writgocms' ); ?></h3>
		
		<div class="debug-info">
			<table class="widefat">
				<tr>
					<td><strong><?php esc_html_e( 'Plugin Versie:', 'writgocms' ); ?></strong></td>
					<td><?php echo esc_html( WRITGOCMS_VERSION ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WordPress Versie:', 'writgocms' ); ?></strong></td>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP Versie:', 'writgocms' ); ?></strong></td>
					<td><?php echo esc_html( phpversion() ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'API Server:', 'writgocms' ); ?></strong></td>
					<td><?php echo esc_url( $api_url ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Wizard Voltooid:', 'writgocms' ); ?></strong></td>
					<td>
						<?php
						$wizard_completed = get_option( 'writgocms_wizard_completed', false );
						echo $wizard_completed ? '✅ ' . esc_html__( 'Ja', 'writgocms' ) : '❌ ' . esc_html__( 'Nee', 'writgocms' );
						?>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgocms' ) ); ?>
</div>

<style>
.card-warning {
	background: var(--writgo-warning-light);
	border: 2px solid var(--writgo-warning);
}

.card-warning p {
	margin: 0;
	display: flex;
	align-items: center;
	gap: 8px;
}

.card-warning .dashicons {
	color: var(--writgo-warning);
	font-size: 20px;
	width: 20px;
	height: 20px;
}

.api-status {
	margin-top: 16px;
	display: flex;
	align-items: center;
	gap: 12px;
}

#api-status-result {
	font-weight: 600;
}

#api-status-result.success {
	color: var(--writgo-success);
}

#api-status-result.error {
	color: var(--writgo-error);
}

.maintenance-actions {
	padding: 12px 0;
	border-bottom: 1px solid var(--writgo-border);
}

.maintenance-actions:last-child {
	border-bottom: none;
}

.maintenance-actions button {
	margin-bottom: 8px;
}

.debug-info table {
	margin-top: 12px;
}

.debug-info table td {
	padding: 8px 12px;
}

.debug-info table tr:nth-child(even) {
	background: var(--writgo-bg);
}
</style>

<script>
jQuery(document).ready(function($) {
	// Test API Connection
	$('#test-api-connection').on('click', function() {
		var $button = $(this);
		var $result = $('#api-status-result');
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testen...', 'writgocms' ); ?>');
		$result.text('').removeClass('success error');
		
		$.ajax({
			url: writgocmsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'writgocms_test_api_connection',
				nonce: writgocmsAdmin.nonce,
				api_url: $('#writgocms_api_url').val()
			},
			success: function(response) {
				if (response.success) {
					$result.text('✓ ' + response.data.message).addClass('success');
				} else {
					$result.text('✗ ' + response.data.message).addClass('error');
				}
			},
			error: function() {
				$result.text('✗ <?php esc_html_e( 'Verbinding mislukt', 'writgocms' ); ?>').addClass('error');
			},
			complete: function() {
				$button.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Test Verbinding', 'writgocms' ); ?>');
			}
		});
	});

	// Clear Cache
	$('#clear-cache').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Weet je zeker dat je de cache wilt legen?', 'writgocms' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		$button.prop('disabled', true).text('<?php esc_html_e( 'Bezig...', 'writgocms' ); ?>');
		
		$.ajax({
			url: writgocmsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'writgocms_clear_cache',
				nonce: writgocmsAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Cache succesvol geleegd', 'writgocms' ); ?>');
				} else {
					alert('<?php esc_html_e( 'Fout bij legen cache', 'writgocms' ); ?>');
				}
			},
			complete: function() {
				$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Cache Legen', 'writgocms' ); ?>');
			}
		});
	});

	// Reset Wizard
	$('#reset-wizard').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Weet je zeker dat je de wizard opnieuw wilt starten?', 'writgocms' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		$button.prop('disabled', true).text('<?php esc_html_e( 'Bezig...', 'writgocms' ); ?>');
		
		$.ajax({
			url: writgocmsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'writgocms_reset_wizard',
				nonce: writgocmsAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=writgocms-setup-wizard' ) ); ?>';
				}
			},
			complete: function() {
				$button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Setup Wizard Opnieuw Starten', 'writgocms' ); ?>');
			}
		});
	});
});
</script>
