<?php
/**
 * Title: Hero with Gradient Background
 * Slug: writgocms/hero-gradient
 * Categories: writgocms-hero, featured
 * Keywords: hero, gradient, header, cta
 * Block Types: core/cover
 * Viewport Width: 1400
 *
 * @package WritgoCMS
 */

?>
<!-- wp:cover {"overlayColor":"primary","minHeight":100,"minHeightUnit":"vh","isDark":false,"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"className":"hero-gradient-pattern"} -->
<div class="wp-block-cover is-light hero-gradient-pattern" style="min-height:100vh;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","contentSize":"800px"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.1em","fontWeight":"500"}},"textColor":"background","fontSize":"small"} -->
			<p class="has-text-align-center has-background-color has-text-color has-small-font-size" style="font-weight:500;letter-spacing:0.1em;text-transform:uppercase">✨ Your Badge Here</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"800","lineHeight":"1.1"}},"textColor":"background","fontSize":"xx-large"} -->
			<h1 class="wp-block-heading has-text-align-center has-background-color has-text-color has-xx-large-font-size" style="font-weight:800;line-height:1.1">Your Compelling Headline Goes Here</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","style":{"typography":{"lineHeight":"1.7"}},"textColor":"background","fontSize":"large"} -->
			<p class="has-text-align-center has-background-color has-text-color has-large-font-size" style="line-height:1.7">Add a brief, engaging description that explains your value proposition and encourages visitors to take action.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button {"backgroundColor":"background","textColor":"primary","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background wp-element-button" style="border-radius:8px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">Get Started →</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"backgroundColor":"transparent","textColor":"background","style":{"border":{"radius":"8px","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"borderColor":"background","className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-background-color has-transparent-background-color has-text-color has-background has-border-color has-background-border-color wp-element-button" style="border-width:2px;border-radius:8px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">Learn More</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
