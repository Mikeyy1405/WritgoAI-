<?php
/**
 * Admin Page Header Partial
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title = isset( $page_title ) ? $page_title : __( 'WritgoAI', 'writgoai' );
$page_description = isset( $page_description ) ? $page_description : '';
?>

<div class="writgo-page-header">
	<div class="page-header-content">
		<h1 class="page-title"><?php echo esc_html( $page_title ); ?></h1>
		<?php if ( $page_description ) : ?>
			<p class="page-description"><?php echo esc_html( $page_description ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( isset( $header_actions ) && ! empty( $header_actions ) ) : ?>
		<div class="page-header-actions">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $header_actions;
			?>
		</div>
	<?php endif; ?>
</div>
