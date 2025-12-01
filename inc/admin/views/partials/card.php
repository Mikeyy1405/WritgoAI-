<?php
/**
 * Card Component Partial
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_class = isset( $card_class ) ? $card_class : '';
$card_title = isset( $card_title ) ? $card_title : '';
$card_icon = isset( $card_icon ) ? $card_icon : '';
$card_content = isset( $card_content ) ? $card_content : '';
?>

<div class="writgo-card <?php echo esc_attr( $card_class ); ?>">
	<?php if ( $card_title ) : ?>
		<div class="card-header">
			<?php if ( $card_icon ) : ?>
				<span class="card-icon"><?php echo esc_html( $card_icon ); ?></span>
			<?php endif; ?>
			<h3 class="card-title"><?php echo esc_html( $card_title ); ?></h3>
		</div>
	<?php endif; ?>
	<div class="card-content">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $card_content;
		?>
	</div>
</div>
