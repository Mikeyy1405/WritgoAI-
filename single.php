<?php
/**
 * The template for displaying all single posts
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">
    <div class="content-area">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

                    <div class="entry-meta">
                        <?php
                        printf(
                            '<span class="posted-on">%1$s <time datetime="%2$s">%3$s</time></span>',
                            esc_html__( 'Posted on', 'writgocms' ),
                            esc_attr( get_the_date( DATE_W3C ) ),
                            esc_html( get_the_date() )
                        );

                        printf(
                            '<span class="byline"> %1$s <span class="author vcard"><a href="%2$s">%3$s</a></span></span>',
                            esc_html__( 'by', 'writgocms' ),
                            esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
                            esc_html( get_the_author() )
                        );

                        $categories_list = get_the_category_list( esc_html__( ', ', 'writgocms' ) );
                        if ( $categories_list ) {
                            printf(
                                '<span class="cat-links"> %1$s %2$s</span>',
                                esc_html__( 'in', 'writgocms' ),
                                $categories_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            );
                        }
                        ?>
                    </div><!-- .entry-meta -->
                </header><!-- .entry-header -->

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="entry-thumbnail">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    the_content();

                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'writgocms' ),
                            'after'  => '</div>',
                        )
                    );
                    ?>
                </div><!-- .entry-content -->

                <footer class="entry-footer">
                    <?php
                    $tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'writgocms' ) );
                    if ( $tags_list ) {
                        printf(
                            '<span class="tags-links">%1$s %2$s</span>',
                            esc_html__( 'Tagged:', 'writgocms' ),
                            $tags_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        );
                    }

                    edit_post_link(
                        sprintf(
                            wp_kses(
                                /* translators: %s: Name of current post. Only visible to screen readers */
                                __( 'Edit <span class="screen-reader-text">%s</span>', 'writgocms' ),
                                array(
                                    'span' => array(
                                        'class' => array(),
                                    ),
                                )
                            ),
                            wp_kses_post( get_the_title() )
                        ),
                        '<span class="edit-link">',
                        '</span>'
                    );
                    ?>
                </footer><!-- .entry-footer -->
            </article><!-- #post-<?php the_ID(); ?> -->

            <?php
            // Post navigation.
            the_post_navigation(
                array(
                    'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous:', 'writgocms' ) . '</span> <span class="nav-title">%title</span>',
                    'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'writgocms' ) . '</span> <span class="nav-title">%title</span>',
                )
            );

            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;

        endwhile;
        ?>
    </div><!-- .content-area -->
</main><!-- #primary -->

<?php
get_sidebar();
get_footer();
