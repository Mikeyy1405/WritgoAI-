<?php
/**
 * Title: Blog Grid (3 Columns)
 * Slug: writgocms/blog-grid-3col
 * Categories: writgocms-blog, posts
 * Keywords: blog, grid, posts, query
 * Block Types: core/query
 * Viewport Width: 1400
 *
 * @package WritgoCMS
 */

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"dark","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-dark-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--50)">
		<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.1em","fontWeight":"600"}},"textColor":"primary","fontSize":"small"} -->
		<p class="has-text-align-center has-primary-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase">Blog</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}},"textColor":"text-primary"} -->
		<h2 class="wp-block-heading has-text-align-center has-text-primary-color has-text-color" style="font-weight:700">Latest Articles</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"align":"center","textColor":"text-secondary"} -->
		<p class="has-text-align-center has-text-secondary-color has-text-color">Stay updated with our latest insights, tutorials, and news.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":10,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false},"layout":{"type":"default"}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","columnCount":3}} -->
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}},"border":{"radius":"12px"}},"backgroundColor":"card","className":"post-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group post-card has-card-background-color has-background" style="border-radius:12px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px","bottomLeft":"0px","bottomRight":"0px"}}}} /-->
				<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
					<!-- wp:post-terms {"term":"category","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"textColor":"primary","fontSize":"small"} /-->
					<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontWeight":"600"},"elements":{"link":{"color":{"text":"var:preset|color|text-primary"},":hover":{"color":{"text":"var:preset|color|primary"}}}}},"textColor":"text-primary","fontSize":"large"} /-->
					<!-- wp:post-date {"textColor":"text-secondary","fontSize":"small"} /-->
					<!-- wp:post-excerpt {"moreText":"Read More →","excerptLength":15,"style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"textColor":"text-secondary"} /-->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:button {"backgroundColor":"primary","textColor":"background","style":{"border":{"radius":"8px"}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color has-background wp-element-button" href="/blog" style="border-radius:8px">View All Posts →</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
