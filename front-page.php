<?php
/**
 * The template for displaying the front page
 *
 * This template displays the homepage with hero section and content areas.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main front-page">
    <?php if ( is_active_sidebar( 'hero-section' ) ) : ?>
        <section class="hero-section">
            <div class="container">
                <?php dynamic_sidebar( 'hero-section' ); ?>
            </div>
        </section>
    <?php else : ?>
        <section class="hero-section">
            <div class="container">
                <h1><?php bloginfo( 'name' ); ?></h1>
                <p>
                    <?php
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description ) :
                        echo esc_html( $description );
                    else :
                        esc_html_e( 'Welcome to our website. Discover amazing content and features.', 'writgocms' );
                    endif;
                    ?>
                </p>
                <div class="hero-buttons">
                    <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="btn btn-white">
                        <?php esc_html_e( 'Read Blog', 'writgocms' ); ?>
                    </a>
                    <a href="#features" class="btn btn-outline-white">
                        <?php esc_html_e( 'Learn More', 'writgocms' ); ?>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    // Display page content if there is any
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            $content = get_the_content();
            if ( ! empty( trim( $content ) ) ) :
                ?>
                <section class="section page-content">
                    <div class="container">
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </section>
                <?php
            endif;
        endwhile;
    endif;
    ?>

    <section id="features" class="section section-alt">
        <div class="container">
            <div class="section-title">
                <h2><?php esc_html_e( 'Features', 'writgocms' ); ?></h2>
                <p><?php esc_html_e( 'Discover what makes WritgoCMS special.', 'writgocms' ); ?></p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h3><?php esc_html_e( 'AI-Powered Content', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Generate high-quality content with multiple AI providers including OpenAI, Claude, Gemini, and Mistral.', 'writgocms' ); ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <h3><?php esc_html_e( 'Image Generation', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Create stunning images with DALL-E, Stability AI, Leonardo, and Replicate integrations.', 'writgocms' ); ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <h3><?php esc_html_e( 'Fully Responsive', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Beautiful design that works perfectly on all devices - mobile, tablet, and desktop.', 'writgocms' ); ?></p>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Display latest posts section
    $recent_posts = new WP_Query(
        array(
            'posts_per_page'      => 3,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
        )
    );

    if ( $recent_posts->have_posts() ) :
        ?>
        <section class="section latest-posts">
            <div class="container">
                <div class="section-title">
                    <h2><?php esc_html_e( 'Latest Posts', 'writgocms' ); ?></h2>
                    <p><?php esc_html_e( 'Check out our most recent articles and updates.', 'writgocms' ); ?></p>
                </div>
                <div class="blog-posts grid-view">
                    <?php
                    while ( $recent_posts->have_posts() ) :
                        $recent_posts->the_post();
                        ?>
                        <article class="post-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="entry-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'writgocms-card' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="entry-content-wrapper">
                                <header class="entry-header">
                                    <?php the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h3>' ); ?>
                                    <div class="entry-meta">
                                        <span class="posted-on">
                                            <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
                                                <?php echo esc_html( get_the_date() ); ?>
                                            </time>
                                        </span>
                                    </div>
                                </header>
                                <div class="entry-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                <a href="<?php the_permalink(); ?>" class="read-more">
                                    <?php esc_html_e( 'Read More', 'writgocms' ); ?>
                                </a>
                            </div>
                        </article>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                <div class="text-center mt-2">
                    <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="btn btn-primary">
                        <?php esc_html_e( 'View All Posts', 'writgocms' ); ?>
                    </a>
                </div>
            </div>
        </section>
        <?php
    endif;
    ?>

    <section class="section section-alt cta-section">
        <div class="container text-center">
            <h2><?php esc_html_e( 'Ready to Get Started?', 'writgocms' ); ?></h2>
            <p><?php esc_html_e( 'Start creating amazing content with AI-powered tools today.', 'writgocms' ); ?></p>
            <div class="hero-buttons">
                <a href="<?php echo esc_url( admin_url() ); ?>" class="btn btn-primary">
                    <?php esc_html_e( 'Go to Dashboard', 'writgocms' ); ?>
                </a>
            </div>
        </div>
    </section>
</main><!-- #primary -->

<?php
get_footer();
