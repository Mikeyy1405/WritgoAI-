<?php
/**
 * Settings Tab - AI Model Preferences
 *
 * Contains AI model selection and parameters.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$provider = WritgoCMS_AIML_Provider::get_instance();
$text_models = $provider->get_text_models();
$image_models = $provider->get_image_models();

$default_model = get_option( 'writgocms_default_model', 'gpt-4o-mini' );
$default_image_model = get_option( 'writgocms_default_image_model', 'dall-e-3' );
$temperature = get_option( 'writgocms_text_temperature', 0.7 );
$max_tokens = get_option( 'writgocms_text_max_tokens', 2000 );
?>

<div class="settings-tab-content" id="tab-ai-models" style="display: none;">
	<h2><?php esc_html_e( 'AI Model Voorkeuren', 'writgocms' ); ?></h2>

	<!-- Text AI Model -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Tekst AI Model', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Het AI model dat gebruikt wordt voor tekstgeneratie', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_default_model">
				<?php esc_html_e( 'Standaard Model', 'writgocms' ); ?>
			</label>
			<select id="writgocms_default_model" name="writgocms_default_model" class="regular-text">
				<?php foreach ( $text_models as $model_key => $model_info ) : ?>
					<option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( $default_model, $model_key ); ?>>
						<?php echo esc_html( $model_info['name'] ); ?>
						<?php if ( isset( $model_info['context'] ) ) : ?>
							(<?php echo esc_html( number_format( $model_info['context'] ) ); ?> tokens)
						<?php endif; ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'Snellere modellen zijn goedkoper maar minder geavanceerd. Krachtigere modellen leveren betere resultaten.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="model-recommendations">
			<h4><?php esc_html_e( 'Aanbevelingen:', 'writgocms' ); ?></h4>
			<ul>
				<li><strong>GPT-4o Mini:</strong> <?php esc_html_e( 'Snel en betaalbaar voor dagelijks gebruik', 'writgocms' ); ?></li>
				<li><strong>GPT-4o:</strong> <?php esc_html_e( 'Beste balans tussen kwaliteit en snelheid', 'writgocms' ); ?></li>
				<li><strong>Claude 3 Opus:</strong> <?php esc_html_e( 'Hoogste kwaliteit voor belangrijke content', 'writgocms' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Image AI Model -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Afbeelding AI Model', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Het AI model voor het genereren van afbeeldingen', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_default_image_model">
				<?php esc_html_e( 'Standaard Afbeelding Model', 'writgocms' ); ?>
			</label>
			<select id="writgocms_default_image_model" name="writgocms_default_image_model" class="regular-text">
				<?php foreach ( $image_models as $model_key => $model_info ) : ?>
					<option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( $default_image_model, $model_key ); ?>>
						<?php echo esc_html( $model_info['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'Kies het model voor afbeeldinggeneratie.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Creativity Level (Temperature) -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Creativiteit Niveau', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Hoe creatief en gevarieerd de AI schrijft', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_text_temperature">
				<?php esc_html_e( 'Creativiteit (0.0 = voorspelbaar, 1.0 = creatief)', 'writgocms' ); ?>
			</label>
			<div class="range-input-group">
				<input 
					type="range" 
					id="writgocms_text_temperature" 
					name="writgocms_text_temperature" 
					min="0" 
					max="1" 
					step="0.1" 
					value="<?php echo esc_attr( $temperature ); ?>"
					class="temperature-slider"
				/>
				<span class="range-value"><?php echo esc_html( $temperature ); ?></span>
			</div>
			<p class="description">
				<?php esc_html_e( 'Een lager getal (0.3-0.5) is beter voor feitelijke content. Een hoger getal (0.7-0.9) is beter voor creatieve content.', 'writgocms' ); ?>
			</p>
		</div>
	</div>

	<!-- Max Text Length -->
	<div class="writgo-card">
		<h3>
			<?php esc_html_e( 'Maximale Tekstlengte', 'writgocms' ); ?>
			<span class="writgo-tooltip" data-tooltip="<?php esc_attr_e( 'Het maximale aantal woorden dat AI in één keer genereert', 'writgocms' ); ?>">
				<span class="dashicons dashicons-info"></span>
			</span>
		</h3>
		
		<div class="form-field">
			<label for="writgocms_text_max_tokens">
				<?php esc_html_e( 'Maximum aantal woorden', 'writgocms' ); ?>
			</label>
			<input 
				type="number" 
				id="writgocms_text_max_tokens" 
				name="writgocms_text_max_tokens" 
				value="<?php echo esc_attr( $max_tokens ); ?>" 
				min="100" 
				max="4000" 
				step="100"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Hogere waarden genereren langere teksten, maar kosten meer credits.', 'writgocms' ); ?>
			</p>
		</div>

		<div class="tokens-info">
			<p><strong><?php esc_html_e( 'Richtlijn:', 'writgocms' ); ?></strong></p>
			<ul>
				<li>500-1000: <?php esc_html_e( 'Korte paragrafen', 'writgocms' ); ?></li>
				<li>1000-2000: <?php esc_html_e( 'Gemiddelde secties', 'writgocms' ); ?></li>
				<li>2000-4000: <?php esc_html_e( 'Lange artikelen', 'writgocms' ); ?></li>
			</ul>
		</div>
	</div>

	<?php submit_button( __( 'Instellingen Opslaan', 'writgocms' ) ); ?>
</div>

<script>
// Update temperature slider value display
jQuery(document).ready(function($) {
	$('.temperature-slider').on('input', function() {
		$(this).next('.range-value').text($(this).val());
	});
});
</script>

<style>
.range-input-group {
	display: flex;
	align-items: center;
	gap: 12px;
}

.temperature-slider {
	flex: 1;
	max-width: 300px;
}

.range-value {
	min-width: 40px;
	font-weight: 600;
	color: var(--writgo-primary);
}

.model-recommendations,
.tokens-info {
	background: var(--writgo-bg);
	padding: 16px;
	border-radius: var(--writgo-radius-sm);
	margin-top: 16px;
}

.model-recommendations h4,
.tokens-info p strong {
	margin-bottom: 8px;
	font-size: 14px;
}

.model-recommendations ul,
.tokens-info ul {
	list-style: none;
	padding: 0;
	margin: 0;
}

.model-recommendations ul li,
.tokens-info ul li {
	padding: 6px 0;
	font-size: 14px;
}

.model-recommendations ul li strong {
	color: var(--writgo-primary);
}
</style>
