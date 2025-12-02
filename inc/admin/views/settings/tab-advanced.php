<?php
/**
 * Settings Tab - Advanced Options
 *
 * Contains advanced technical settings for power users.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_url = get_option( 'writgoai_api_url', 'https://api.writgo.ai' );
$notifications = get_option( 'writgoai_notifications', 1 );
$image_size = get_option( 'writgoai_image_size', '1024x1024' );
$image_quality = get_option( 'writgoai_image_quality', 'standard' );
?>

<div class="settings-tab-content" id="tab-advanced" style="display: none;">
	<h2><?php esc_html_e( 'Geavanceerde Opties', 'writgoai' ); ?></h2>
	
	<div class="writgo-card card-warning">
		<p>
			<span class="dashicons dashicons-warning"></span>
			<strong><?php esc_html_e( 'Let op:', 'writgoai' ); ?></strong>
			<?php esc_html_e( 'Deze instellingen zijn voor gevorderde gebruikers. Wijzig alleen als je weet wat je doet.', 'writgoai' ); ?>
		</p>
	</div>

	<!-- API Configuration -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'API Configuratie', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Verbinding met de WritgoAI API server', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_api_url">
				<?php esc_html_e( 'API Server URL', 'writgoai' ); ?>
			</label>
			<input 
				type="url" 
				id="writgoai_api_url" 
				name="writgoai_api_url" 
				value="<?php echo esc_url( $api_url ); ?>" 
				class="regular-text"
				placeholder="https://api.writgo.ai"
			/>
			<p class="description">
				<?php esc_html_e( 'De URL van de WritgoAI API server. Wijzig dit alleen als instructie van support.', 'writgoai' ); ?>
			</p>
		</div>

		<div class="api-status">
			<button type="button" id="test-api-connection" class="button">
				<span class="dashicons dashicons-admin-tools"></span>
				<?php esc_html_e( 'Test Verbinding', 'writgoai' ); ?>
			</button>
			<span id="api-status-result"></span>
		</div>
	</div>

	<!-- Image Generation Settings -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Afbeelding Generatie', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Instellingen voor AI-gegenereerde afbeeldingen', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgoai_image_size">
				<?php esc_html_e( 'Standaard afbeeldingsgrootte', 'writgoai' ); ?>
			</label>
			<select id="writgoai_image_size" name="writgoai_image_size" class="regular-text">
				<option value="256x256" <?php selected( $image_size, '256x256' ); ?>>256x256 (Klein)</option>
				<option value="512x512" <?php selected( $image_size, '512x512' ); ?>>512x512 (Gemiddeld)</option>
				<option value="1024x1024" <?php selected( $image_size, '1024x1024' ); ?>>1024x1024 (Groot)</option>
				<option value="1792x1024" <?php selected( $image_size, '1792x1024' ); ?>>1792x1024 (Widescreen)</option>
				<option value="1024x1792" <?php selected( $image_size, '1024x1792' ); ?>>1024x1792 (Portret)</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Grotere afbeeldingen kosten meer credits maar hebben betere kwaliteit.', 'writgoai' ); ?>
			</p>
		</div>

		<div class="form-field">
			<label for="writgoai_image_quality">
				<?php esc_html_e( 'Afbeeldingskwaliteit', 'writgoai' ); ?>
			</label>
			<select id="writgoai_image_quality" name="writgoai_image_quality" class="regular-text">
				<option value="standard" <?php selected( $image_quality, 'standard' ); ?>><?php esc_html_e( 'Standaard', 'writgoai' ); ?></option>
				<option value="hd" <?php selected( $image_quality, 'hd' ); ?>><?php esc_html_e( 'HD (Hoge kwaliteit)', 'writgoai' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'HD kwaliteit kost meer credits maar levert scherpere afbeeldingen.', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<!-- Notifications -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Notificaties', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Meldingen over belangrijke events', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label>
				<input 
					type="checkbox" 
					name="writgoai_notifications" 
					value="1" 
					<?php checked( $notifications, 1 ); ?>
				/>
				<?php esc_html_e( 'E-mail notificaties inschakelen', 'writgoai' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Ontvang meldingen bij belangrijke events zoals: analyses voltooid, licentie verlopen, etc.', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<!-- Database & Cache -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Database & Cache', 'writgoai' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Beheer opgeslagen data', 'writgoai' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="maintenance-actions">
			<button type="button" id="clear-cache" class="button">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Cache Legen', 'writgoai' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Verwijder tijdelijk opgeslagen data. Gebruik dit als je problemen ervaart.', 'writgoai' ); ?>
			</p>
		</div>

		<div class="maintenance-actions">
			<button type="button" id="reset-wizard" class="button">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Setup Wizard Opnieuw Starten', 'writgoai' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Start de setup wizard opnieuw. Je huidige instellingen blijven behouden.', 'writgoai' ); ?>
			</p>
		</div>
	</div>

	<!-- Debug Information -->
	<div class="writgo-card">
		<h3><?php esc_html_e( 'Debug Informatie', 'writgoai' ); ?></h3>
		
		<div class="debug-info">
			<table class="widefat">
				<tr>
					<td><strong><?php esc_html_e( 'Plugin Versie:', 'writgoai' ); ?></strong></td>
					<td><?php echo esc_html( WRITGOAI_VERSION ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WordPress Versie:', 'writgoai' ); ?></strong></td>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP Versie:', 'writgoai' ); ?></strong></td>
					<td><?php echo esc_html( phpversion() ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'API Server:', 'writgoai' ); ?></strong></td>
					<td><?php echo esc_url( $api_url ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Wizard Voltooid:', 'writgoai' ); ?></strong></td>
					<td>
						<?php
						$wizard_completed = get_option( 'writgoai_wizard_completed', false );
						echo $wizard_completed ? '✅ ' . esc_html__( 'Ja', 'writgoai' ) : '❌ ' . esc_html__( 'Nee', 'writgoai' );
						?>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgoai' ) ); ?>
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
		
		$button.prop('disabled', true).text('<?php esc_html_e( 'Testen...', 'writgoai' ); ?>');
		$result.text('').removeClass('success error');
		
		$.ajax({
			url: writgoaiAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'writgoai_test_api_connection',
				nonce: writgoaiAdmin.nonce,
				api_url: $('#writgoai_api_url').val()
			},
			success: function(response) {
				if (response.success) {
					$result.text('✓ ' + response.data.message).addClass('success');
				} else {
					$result.text('✗ ' + response.data.message).addClass('error');
				}
			},
			error: function() {
				$result.text('✗ <?php esc_html_e( 'Verbinding mislukt', 'writgoai' ); ?>').addClass('error');
			},
			complete: function() {
				$button.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Test Verbinding', 'writgoai' ); ?>');
			}
		});
	});

	// Clear Cache
	$('#clear-cache').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Weet je zeker dat je de cache wilt legen?', 'writgoai' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		$button.prop('disabled', true).text('<?php esc_html_e( 'Bezig...', 'writgoai' ); ?>');
		
		$.ajax({
			url: writgoaiAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'writgoai_clear_cache',
				nonce: writgoaiAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					alert('<?php esc_html_e( 'Cache succesvol geleegd', 'writgoai' ); ?>');
				} else {
					alert('<?php esc_html_e( 'Fout bij legen cache', 'writgoai' ); ?>');
				}
			},
			complete: function() {
				$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Cache Legen', 'writgoai' ); ?>');
			}
		});
	});

	// Reset Wizard
	$('#reset-wizard').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Weet je zeker dat je de wizard opnieuw wilt starten?', 'writgoai' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		$button.prop('disabled', true).text('<?php esc_html_e( 'Bezig...', 'writgoai' ); ?>');
		
		$.ajax({
			url: writgoaiAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'writgoai_reset_wizard',
				nonce: writgoaiAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=writgoai-setup-wizard' ) ); ?>';
				}
			},
			complete: function() {
				$button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Setup Wizard Opnieuw Starten', 'writgoai' ); ?>');
			}
		});
	});
});
</script>
