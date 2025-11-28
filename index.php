<?php
/**
 * The main template file
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
                if ( have_posts() ) :

                    if ( is_home() && ! is_front_page() ) :
                        ?>
                        <header class="page-header">
                            <h1 class="page-title"><?php single_post_title(); ?></h1>
                        </header>
                        <?php
                    endif;

                    ?>
                    <div class="posts-grid">
                        <?php
                        while ( have_posts() ) :
                            the_post();
                            ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <div class="post-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'medium_large' ); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <header class="entry-header">
                                    <?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
                                    
                                    <div class="entry-meta">
                                        <?php
                                        writgocms_posted_on();
                                        writgocms_posted_by();
                                        ?>
                                    </div>
                                </header>

                                <div class="entry-summary">
                                    <?php the_excerpt(); ?>
                                </div>

                                <footer class="entry-footer">
                                    <a href="<?php the_permalink(); ?>" class="btn btn-outline">
                                        <?php esc_html_e( 'Read More', 'writgocms' ); ?>
                                    </a>
                                </footer>
                            </article>
                            <?php
                        endwhile;
                        ?>
                    </div>

                    <?php
                    writgocms_pagination();

                else :
                    ?>
                    <section class="no-results not-found">
                        <header class="page-header">
                            <h1 class="page-title"><?php esc_html_e( 'Nothing Found', 'writgocms' ); ?></h1>
                        </header>

                        <div class="page-content">
                            <?php if ( is_search() ) : ?>
                                <p><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'writgocms' ); ?></p>
                                <?php get_search_form(); ?>
                            <?php else : ?>
                                <p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'writgocms' ); ?></p>
                                <?php get_search_form(); ?>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php
                endif;
                ?>
            </div>

            <?php get_sidebar(); ?>
        </div>
    </div>
</main>

<?php
get_footer();
