<?php
/**
 * The template for displaying single posts
 *
 * @package WritgoCMS
 * @version 1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="site-container">
        <div class="content-area <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : ''; ?>">
            <div class="main-content">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

                            <div class="entry-meta">
                                <?php
                                writgocms_posted_on();
                                writgocms_posted_by();
                                writgocms_entry_categories();
                                ?>
                            </div>
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

                        <footer class="entry-footer">
                            <?php writgocms_entry_tags(); ?>

                            <?php
                            if ( get_edit_post_link() ) :
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
                            endif;
                            ?>
                        </footer>
                    </article>

                    <?php
                    // Post navigation.
                    the_post_navigation( array(
                        'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous:', 'writgocms' ) . '</span> <span class="nav-title">%title</span>',
                        'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'writgocms' ) . '</span> <span class="nav-title">%title</span>',
                    ) );

                    // If comments are open or we have at least one comment, load up the comment template.
                    if ( comments_open() || get_comments_number() ) :
                        comments_template();
                    endif;

                endwhile;
                ?>
            </div>

            <?php get_sidebar(); ?>
        </div>
    </div>
</main>

<?php
get_footer();
