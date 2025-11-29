<?php
/**
 * Title: CTA Full Width with Gradient
 * Slug: writgocms/cta-fullwidth
 * Categories: writgocms-cta, call-to-action
 * Keywords: cta, call to action, fullwidth, gradient
 * Block Types: core/cover
 * Viewport Width: 1400
 *
 * @package WritgoCMS
 */

?>
<!-- wp:cover {"overlayColor":"primary","minHeight":400,"isDark":false,"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
<div class="wp-block-cover is-light" style="min-height:400px;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"layout":{"type":"constrained","contentSize":"700px"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}},"textColor":"background"} -->
			<h2 class="wp-block-heading has-text-align-center has-background-color has-text-color" style="font-weight:700">Transform Your Workflow Today</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"align":"center","textColor":"background"} -->
			<p class="has-text-align-center has-background-color has-text-color">Start using our AI-powered tools to create amazing content in minutes, not hours.</p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button {"backgroundColor":"background","textColor":"primary","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background wp-element-button" style="border-radius:8px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">Start Free Trial →</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"backgroundColor":"transparent","textColor":"background","style":{"border":{"radius":"8px","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"borderColor":"background","className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-background-color has-transparent-background-color has-text-color has-background has-border-color has-background-border-color wp-element-button" style="border-width:2px;border-radius:8px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">View Demo</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
