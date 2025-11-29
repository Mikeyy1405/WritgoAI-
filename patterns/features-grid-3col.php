<?php
/**
 * Title: Features Grid (3 Columns)
 * Slug: writgocms/features-grid-3col
 * Categories: writgocms-features, featured
 * Keywords: features, grid, icons, services
 * Block Types: core/columns
 * Viewport Width: 1400
 *
 * @package WritgoCMS
 */

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"card","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-card-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--50)">
		<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.1em","fontWeight":"600"}},"textColor":"primary","fontSize":"small"} -->
		<p class="has-text-align-center has-primary-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase">Features</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}},"textColor":"text-primary"} -->
		<h2 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color" style="font-weight:700">Everything You Need</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"align":"center","textColor":"text-secondary"} -->
		<p class="has-text-align-center has-text-secondary-color has-text-color">Powerful features to help you achieve your goals faster and more efficiently.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"12px"}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-dark-background-color has-background" style="border-radius:12px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem"}}} -->
				<p style="font-size:2.5rem">🚀</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"level":3,"textColor":"text-primary","fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-primary-color has-text-color has-large-font-size">Feature One</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"text-secondary","fontSize":"small"} -->
				<p class="has-text-secondary-color has-text-color has-small-font-size">Describe your first feature here. Explain how it helps users solve their problems.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"12px"}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-dark-background-color has-background" style="border-radius:12px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem"}}} -->
				<p style="font-size:2.5rem">⚡</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"level":3,"textColor":"text-primary","fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-primary-color has-text-color has-large-font-size">Feature Two</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"text-secondary","fontSize":"small"} -->
				<p class="has-text-secondary-color has-text-color has-small-font-size">Describe your second feature here. Highlight the benefits and advantages.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"12px"}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-dark-background-color has-background" style="border-radius:12px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem"}}} -->
				<p style="font-size:2.5rem">🎯</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"level":3,"textColor":"text-primary","fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-primary-color has-text-color has-large-font-size">Feature Three</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"text-secondary","fontSize":"small"} -->
				<p class="has-text-secondary-color has-text-color has-small-font-size">Describe your third feature here. Show how it differentiates your offering.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
