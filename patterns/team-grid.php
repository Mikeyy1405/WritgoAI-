<?php
/**
 * Title: Team Grid
 * Slug: writgocms/team-grid
 * Categories: writgocms-content, featured
 * Keywords: team, grid, members, about
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
		<p class="has-text-align-center has-primary-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase">Our Team</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}},"textColor":"text-primary"} -->
		<h2 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color" style="font-weight:700">Meet the Experts</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"align":"center","textColor":"text-secondary"} -->
		<p class="has-text-align-center has-text-secondary-color has-text-color">Our dedicated team of professionals is here to help you succeed.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-dark-background-color has-background" style="border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"5rem"}}} -->
				<p class="has-text-align-center" style="font-size:5rem">👨‍💼</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"text-primary","fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color has-large-font-size">John Doe</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","textColor":"primary","fontSize":"small"} -->
				<p class="has-text-align-center has-primary-color has-text-color has-small-font-size">CEO &amp; Founder</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"text-secondary","fontSize":"small"} -->
				<p class="has-text-align-center has-text-secondary-color has-text-color has-small-font-size">10+ years of experience leading tech companies to success.</p>
				<!-- /wp:paragraph -->
				<!-- wp:social-links {"iconColor":"text-secondary","iconColorValue":"#94a3b8","size":"has-small-icon-size","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"className":"is-style-logos-only","layout":{"type":"flex","justifyContent":"center"}} -->
				<ul class="wp-block-social-links has-small-icon-size has-icon-color is-style-logos-only">
					<!-- wp:social-link {"url":"#","service":"twitter"} /-->
					<!-- wp:social-link {"url":"#","service":"linkedin"} /-->
				</ul>
				<!-- /wp:social-links -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-dark-background-color has-background" style="border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"5rem"}}} -->
				<p class="has-text-align-center" style="font-size:5rem">👩‍💻</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"text-primary","fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color has-large-font-size">Jane Smith</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","textColor":"primary","fontSize":"small"} -->
				<p class="has-text-align-center has-primary-color has-text-color has-small-font-size">CTO</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"text-secondary","fontSize":"small"} -->
				<p class="has-text-align-center has-text-secondary-color has-text-color has-small-font-size">Expert in AI and machine learning with a passion for innovation.</p>
				<!-- /wp:paragraph -->
				<!-- wp:social-links {"iconColor":"text-secondary","iconColorValue":"#94a3b8","size":"has-small-icon-size","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"className":"is-style-logos-only","layout":{"type":"flex","justifyContent":"center"}} -->
				<ul class="wp-block-social-links has-small-icon-size has-icon-color is-style-logos-only">
					<!-- wp:social-link {"url":"#","service":"twitter"} /-->
					<!-- wp:social-link {"url":"#","service":"github"} /-->
				</ul>
				<!-- /wp:social-links -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-dark-background-color has-background" style="border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"5rem"}}} -->
				<p class="has-text-align-center" style="font-size:5rem">👨‍🎨</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"text-primary","fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color has-large-font-size">Mike Johnson</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","textColor":"primary","fontSize":"small"} -->
				<p class="has-text-align-center has-primary-color has-text-color has-small-font-size">Lead Designer</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"text-secondary","fontSize":"small"} -->
				<p class="has-text-align-center has-text-secondary-color has-text-color has-small-font-size">Creative mind behind our beautiful UI and user experiences.</p>
				<!-- /wp:paragraph -->
				<!-- wp:social-links {"iconColor":"text-secondary","iconColorValue":"#94a3b8","size":"has-small-icon-size","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"className":"is-style-logos-only","layout":{"type":"flex","justifyContent":"center"}} -->
				<ul class="wp-block-social-links has-small-icon-size has-icon-color is-style-logos-only">
					<!-- wp:social-link {"url":"#","service":"dribbble"} /-->
					<!-- wp:social-link {"url":"#","service":"instagram"} /-->
				</ul>
				<!-- /wp:social-links -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
