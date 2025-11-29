<?php
/**
 * Title: Hero with Background Image
 * Slug: writgocms/hero-image
 * Categories: writgocms-hero, featured
 * Keywords: hero, image, background, cta
 * Block Types: core/cover
 * Viewport Width: 1400
 *
 * @package WritgoCMS
 */

?>
<!-- wp:cover {"dimRatio":70,"overlayColor":"dark","minHeight":100,"minHeightUnit":"vh","isDark":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"className":"hero-image-pattern"} -->
<div class="wp-block-cover is-dark hero-image-pattern" style="min-height:100vh;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<span aria-hidden="true" class="wp-block-cover__background has-dark-background-color has-background-dim-70 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","contentSize":"800px"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"800","lineHeight":"1.1"}},"textColor":"text-primary","fontSize":"xx-large"} -->
			<h1 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color has-xx-large-font-size" style="font-weight:800;line-height:1.1">Your Stunning Headline</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","style":{"typography":{"lineHeight":"1.7"}},"textColor":"text-secondary","fontSize":"large"} -->
			<p class="has-text-align-center has-text-secondary-color has-text-color has-large-font-size" style="line-height:1.7">Add your image to the cover block background, then customize this text to match your brand message.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button {"backgroundColor":"primary","textColor":"background","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color has-background wp-element-button" style="border-radius:8px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">Get Started →</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"backgroundColor":"transparent","textColor":"text-primary","style":{"border":{"radius":"8px","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"borderColor":"text-primary","className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-primary-color has-transparent-background-color has-text-color has-background has-border-color has-text-primary-border-color wp-element-button" style="border-width:2px;border-radius:8px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">Learn More</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
