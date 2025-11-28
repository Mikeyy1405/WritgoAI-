<?php
/**
 * The template for displaying pages
 *
 * @package WritgoCMS
 * @version 1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="site-container">
        <div class="content-area">
            <div class="main-content">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                        </header>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="post-thumbnail">
                                <?php the_post_thumbnail( 'large' ); ?>
                            </div>
                        <?php endif; ?>

                        <div class="entry-content">
                            <?php
                            the_content();

                            wp_link_pages( array(
                                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'writgocms' ),
                                'after'  => '</div>',
                            ) );
                            ?>
                        </div>

                        <?php if ( get_edit_post_link() ) : ?>
                            <footer class="entry-footer">
                                <?php
                                edit_post_link(
                                    sprintf(
                                        wp_kses(
                                            /* translators: %s: Name of current post */
                                            __( 'Edit <span class="screen-reader-text">%s</span>', 'writgocms' ),
                                            array( 'span' => array( 'class' => array() ) )
                                        ),
                                        get_the_title()
                                    ),
                                    '<span class="edit-link">',
                                    '</span>'
                                );
                                ?>
                            </footer>
                        <?php endif; ?>
                    </article>

                    <?php
                    // If comments are open or we have at least one comment, load up the comment template.
                    if ( comments_open() || get_comments_number() ) :
                        comments_template();
                    endif;

                endwhile;
                ?>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
