<?php
/**
 * The template for displaying 404 pages (not found)
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
        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title"><?php esc_html_e( '404', 'writgocms' ); ?></h1>
            </header>

            <div class="page-content">
                <h2><?php esc_html_e( 'Oops! Page not found.', 'writgocms' ); ?></h2>
                <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try searching for what you are looking for?', 'writgocms' ); ?></p>

                <?php get_search_form(); ?>

                <div class="error-404-links mt-2">
                    <p><?php esc_html_e( 'Or you can try these links:', 'writgocms' ); ?></p>
                    <ul>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php esc_html_e( 'Go to Homepage', 'writgocms' ); ?>
                            </a>
                        </li>
                        <?php if ( get_option( 'page_for_posts' ) ) : ?>
                            <li>
                                <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">
                                    <?php esc_html_e( 'Visit Blog', 'writgocms' ); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
get_footer();
