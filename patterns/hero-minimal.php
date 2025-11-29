<?php
/**
 * Title: Minimal Hero
 * Slug: writgocms/hero-minimal
 * Categories: writgocms-hero, featured
 * Keywords: hero, minimal, clean, simple
 * Block Types: core/group
 * Viewport Width: 1400
 *
 * @package WritgoCMS
 */

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"dark","layout":{"type":"constrained","contentSize":"700px"}} -->
<div class="wp-block-group has-dark-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"700","lineHeight":"1.2"}},"textColor":"text-primary"} -->
	<h1 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color" style="font-weight:700;line-height:1.2">Simple. Clean. Effective.</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"text-secondary","fontSize":"large"} -->
	<p class="has-text-align-center has-text-secondary-color has-text-color has-large-font-size">A minimal hero section that lets your message speak for itself. Perfect for landing pages focused on clarity.</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
		<!-- wp:button {"backgroundColor":"primary","textColor":"background","style":{"border":{"radius":"8px"}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color has-background wp-element-button" style="border-radius:8px">Get Started</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
