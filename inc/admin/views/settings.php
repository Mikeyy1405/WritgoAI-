<?php
/**
 * Unified Settings Page
 *
 * Tabbed interface for all WritgoAI settings.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap writgo-settings-container">
	<h1><?php esc_html_e( 'WritgoAI Instellingen', 'writgoai' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Configureer WritgoAI naar jouw wensen. Alle instellingen zijn logisch gegroepeerd.', 'writgoai' ); ?>
	</p>

	<div class="writgo-card">
		<!-- Tab Navigation -->
		<div class="settings-tabs">
			<button type="button" class="settings-tab active" data-tab="tab-basic">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Basis', 'writgoai' ); ?>
			</button>
			<button type="button" class="settings-tab" data-tab="tab-ai-models">
				<span class="dashicons dashicons-chart-area"></span>
				<?php esc_html_e( 'AI Modellen', 'writgoai' ); ?>
			</button>
			<button type="button" class="settings-tab" data-tab="tab-content">
				<span class="dashicons dashicons-edit"></span>
				<?php esc_html_e( 'Content', 'writgoai' ); ?>
			</button>
			<button type="button" class="settings-tab" data-tab="tab-advanced">
				<span class="dashicons dashicons-admin-tools"></span>
				<?php esc_html_e( 'Geavanceerd', 'writgoai' ); ?>
			</button>
		</div>

		<!-- Form Container -->
		<form method="post" action="options.php" class="settings-form">
			<?php settings_fields( 'writgoai_ai_settings' ); ?>
			
			<!-- Tab Content Panels -->
			<?php
			// Include each tab.
			include WRITGOAI_DIR . 'inc/admin/views/settings/tab-basic.php';
			include WRITGOAI_DIR . 'inc/admin/views/settings/tab-ai-models.php';
			include WRITGOAI_DIR . 'inc/admin/views/settings/tab-content.php';
			include WRITGOAI_DIR . 'inc/admin/views/settings/tab-advanced.php';
			?>
		</form>
	</div>
</div>

<style>
.writgo-settings-container {
	max-width: 1200px;
	margin: 20px auto;
}

.writgo-settings-container > h1 {
	font-size: 28px;
	font-weight: 700;
	margin-bottom: 8px;
}

.writgo-settings-container > .description {
	font-size: 16px;
	color: var(--writgo-text-light);
	margin-bottom: 24px;
}

.settings-tabs {
	display: flex;
	gap: 8px;
	margin-bottom: 32px;
	border-bottom: 2px solid var(--writgo-border);
	padding-bottom: 0;
}

.settings-tab {
	background: none;
	border: none;
	padding: 12px 24px;
	font-size: 14px;
	font-weight: 500;
	color: var(--writgo-text-light);
	cursor: pointer;
	display: flex;
	align-items: center;
	gap: 8px;
	border-bottom: 3px solid transparent;
	margin-bottom: -2px;
	transition: var(--writgo-transition);
	border-radius: var(--writgo-radius-sm) var(--writgo-radius-sm) 0 0;
}

.settings-tab:hover {
	background: var(--writgo-bg);
	color: var(--writgo-text);
}

.settings-tab.active {
	color: var(--writgo-primary);
	border-bottom-color: var(--writgo-primary);
	background: var(--writgo-primary-light);
}

.settings-tab .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

.settings-tab-content {
	animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
	from {
		opacity: 0;
		transform: translateY(10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.settings-tab-content h2 {
	font-size: 24px;
	font-weight: 700;
	color: var(--writgo-text);
	margin-bottom: 24px;
	padding-bottom: 12px;
	border-bottom: 2px solid var(--writgo-border);
}

.settings-tab-content h3 {
	font-size: 18px;
	font-weight: 600;
	color: var(--writgo-text);
	margin-bottom: 16px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.settings-tab-content .writgo-card {
	margin-bottom: 24px;
}

.settings-tab-content .writgo-card:last-of-type {
	margin-bottom: 32px;
}

/* Responsive Design */
@media (max-width: 768px) {
	.settings-tabs {
		flex-wrap: wrap;
	}
	
	.settings-tab {
		flex: 1 1 45%;
		justify-content: center;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.settings-tab').on('click', function() {
		var tabId = $(this).data('tab');
		
		// Update active tab
		$('.settings-tab').removeClass('active');
		$(this).addClass('active');
		
		// Show corresponding content
		$('.settings-tab-content').hide();
		$('#' + tabId).fadeIn(300);
	});
	
	// Show first tab by default
	$('.settings-tab-content').hide();
	$('#tab-basic').show();
});
</script>
