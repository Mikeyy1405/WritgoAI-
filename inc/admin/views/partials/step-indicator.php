<?php
/**
 * Step Indicator Partial
 *
 * Shows progress through a multi-step process.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$steps = isset( $steps ) ? $steps : array();
$current_step = isset( $current_step ) ? $current_step : 1;
?>

<div class="writgo-step-indicator">
	<?php foreach ( $steps as $step_num => $step_label ) : ?>
		<div class="step-item <?php echo $step_num < $current_step ? 'completed' : ''; ?> <?php echo $step_num === $current_step ? 'active' : ''; ?>">
			<div class="step-marker">
				<?php if ( $step_num < $current_step ) : ?>
					<span class="dashicons dashicons-yes"></span>
				<?php else : ?>
					<span class="step-number"><?php echo esc_html( $step_num ); ?></span>
				<?php endif; ?>
			</div>
			<div class="step-label"><?php echo esc_html( $step_label ); ?></div>
		</div>
	<?php endforeach; ?>
</div>
