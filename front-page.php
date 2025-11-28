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
    <!-- Hero Section with Gradient Background -->
    <?php if ( is_active_sidebar( 'hero-section' ) ) : ?>
        <section class="hero-section">
            <div class="hero-background"></div>
            <div class="container">
                <?php dynamic_sidebar( 'hero-section' ); ?>
            </div>
        </section>
    <?php else : ?>
        <section class="hero-section">
            <div class="hero-background"></div>
            <div class="container hero-content">
                <span class="hero-badge"><?php esc_html_e( 'AI-Powered CMS', 'writgocms' ); ?></span>
                <h1 class="hero-title"><?php bloginfo( 'name' ); ?></h1>
                <p class="hero-description">
                    <?php
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description ) :
                        echo esc_html( $description );
                    else :
                        esc_html_e( 'Create stunning content with the power of AI. Generate text, images, and more with multiple AI providers.', 'writgocms' );
                    endif;
                    ?>
                </p>
                <div class="hero-buttons">
                    <a href="<?php echo esc_url( admin_url() ); ?>" class="btn btn-white btn-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <?php esc_html_e( 'Get Started', 'writgocms' ); ?>
                    </a>
                    <a href="#features" class="btn btn-outline-white btn-lg">
                        <?php esc_html_e( 'Learn More', 'writgocms' ); ?>
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number" data-count="4">4</span>
                        <span class="hero-stat-label"><?php esc_html_e( 'AI Providers', 'writgocms' ); ?></span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number" data-count="8">8</span>
                        <span class="hero-stat-label"><?php esc_html_e( 'Image APIs', 'writgocms' ); ?></span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number" data-count="100">100</span><span class="hero-stat-suffix">%</span>
                        <span class="hero-stat-label"><?php esc_html_e( 'Responsive', 'writgocms' ); ?></span>
                    </div>
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

    <!-- Features Section -->
    <section id="features" class="section section-alt features-section">
        <div class="container">
            <div class="section-header fade-in-up">
                <span class="section-badge"><?php esc_html_e( 'Features', 'writgocms' ); ?></span>
                <h2 class="section-title"><?php esc_html_e( 'Everything You Need', 'writgocms' ); ?></h2>
                <p class="section-subtitle"><?php esc_html_e( 'Powerful features to supercharge your content creation workflow.', 'writgocms' ); ?></p>
            </div>
            <div class="features-grid">
                <div class="feature-card glass-card fade-in-up">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                    </div>
                    <h3><?php esc_html_e( 'AI-Powered Content', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Generate high-quality content with multiple AI providers including OpenAI, Claude, Gemini, and Mistral.', 'writgocms' ); ?></p>
                </div>
                <div class="feature-card glass-card fade-in-up" style="--delay: 0.1s">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                    </div>
                    <h3><?php esc_html_e( 'Image Generation', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Create stunning images with DALL-E, Stability AI, Leonardo, and Replicate integrations.', 'writgocms' ); ?></p>
                </div>
                <div class="feature-card glass-card fade-in-up" style="--delay: 0.2s">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </div>
                    </div>
                    <h3><?php esc_html_e( 'Fully Responsive', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Beautiful design that works perfectly on all devices - mobile, tablet, and desktop.', 'writgocms' ); ?></p>
                </div>
                <div class="feature-card glass-card fade-in-up" style="--delay: 0.3s">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3><?php esc_html_e( 'Lightning Fast', 'writgocms' ); ?></h3>
                    <p><?php esc_html_e( 'Optimized for performance with lazy loading, minimal CSS, and efficient JavaScript.', 'writgocms' ); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="section services-section">
        <div class="container">
            <div class="services-grid">
                <div class="services-content fade-in-left">
                    <span class="section-badge"><?php esc_html_e( 'Why Choose Us', 'writgocms' ); ?></span>
                    <h2 class="section-title"><?php esc_html_e( 'Built for Modern Content Creators', 'writgocms' ); ?></h2>
                    <p class="section-description"><?php esc_html_e( 'WritgoCMS combines the power of multiple AI providers with a beautiful, intuitive interface. Whether you are creating blog posts, marketing copy, or stunning visuals, we have got you covered.', 'writgocms' ); ?></p>
                    <ul class="services-list">
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php esc_html_e( 'Multiple AI text generation providers', 'writgocms' ); ?>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php esc_html_e( 'AI image generation with multiple APIs', 'writgocms' ); ?>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php esc_html_e( 'Gutenberg block editor integration', 'writgocms' ); ?>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php esc_html_e( 'Classic editor support included', 'writgocms' ); ?>
                        </li>
                    </ul>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-settings' ) ); ?>" class="btn btn-primary">
                        <?php esc_html_e( 'Configure AI Settings', 'writgocms' ); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                </div>
                <div class="services-image fade-in-right">
                    <div class="services-image-wrapper">
                        <div class="services-card services-card-1 glass-card">
                            <div class="services-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <span><?php esc_html_e( 'OpenAI GPT-4', 'writgocms' ); ?></span>
                        </div>
                        <div class="services-card services-card-2 glass-card">
                            <div class="services-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <span><?php esc_html_e( 'Claude AI', 'writgocms' ); ?></span>
                        </div>
                        <div class="services-card services-card-3 glass-card">
                            <div class="services-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                            <span><?php esc_html_e( 'DALL-E 3', 'writgocms' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section section-gradient stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item fade-in-up">
                    <div class="stat-number" data-count="4">0</div>
                    <div class="stat-label"><?php esc_html_e( 'Text AI Providers', 'writgocms' ); ?></div>
                    <p class="stat-description"><?php esc_html_e( 'OpenAI, Claude, Gemini, Mistral', 'writgocms' ); ?></p>
                </div>
                <div class="stat-item fade-in-up" style="--delay: 0.1s">
                    <div class="stat-number" data-count="4">0</div>
                    <div class="stat-label"><?php esc_html_e( 'Image AI Providers', 'writgocms' ); ?></div>
                    <p class="stat-description"><?php esc_html_e( 'DALL-E, Stability, Leonardo, Replicate', 'writgocms' ); ?></p>
                </div>
                <div class="stat-item fade-in-up" style="--delay: 0.2s">
                    <div class="stat-number" data-count="2">0</div>
                    <div class="stat-label"><?php esc_html_e( 'Editor Integrations', 'writgocms' ); ?></div>
                    <p class="stat-description"><?php esc_html_e( 'Gutenberg & Classic Editor', 'writgocms' ); ?></p>
                </div>
                <div class="stat-item fade-in-up" style="--delay: 0.3s">
                    <div class="stat-number" data-count="100">0</div>
                    <div class="stat-suffix">%</div>
                    <div class="stat-label"><?php esc_html_e( 'Open Source', 'writgocms' ); ?></div>
                    <p class="stat-description"><?php esc_html_e( 'Free & customizable', 'writgocms' ); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section testimonials-section">
        <div class="container">
            <div class="section-header fade-in-up">
                <span class="section-badge"><?php esc_html_e( 'Testimonials', 'writgocms' ); ?></span>
                <h2 class="section-title"><?php esc_html_e( 'What Creators Say', 'writgocms' ); ?></h2>
                <p class="section-subtitle"><?php esc_html_e( 'Trusted by content creators and developers worldwide.', 'writgocms' ); ?></p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card glass-card fade-in-up">
                    <div class="testimonial-rating">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <blockquote class="testimonial-content">
                        <?php esc_html_e( '"WritgoCMS has revolutionized my content workflow. The AI integration is seamless and the generated content is always high quality."', 'writgocms' ); ?>
                    </blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            <span>JD</span>
                        </div>
                        <div class="testimonial-info">
                            <strong><?php esc_html_e( 'John Doe', 'writgocms' ); ?></strong>
                            <span><?php esc_html_e( 'Content Creator', 'writgocms' ); ?></span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card glass-card fade-in-up" style="--delay: 0.1s">
                    <div class="testimonial-rating">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <blockquote class="testimonial-content">
                        <?php esc_html_e( '"The image generation feature is incredible! I can create custom visuals for my blog posts in seconds. Highly recommended!"', 'writgocms' ); ?>
                    </blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            <span>JS</span>
                        </div>
                        <div class="testimonial-info">
                            <strong><?php esc_html_e( 'Jane Smith', 'writgocms' ); ?></strong>
                            <span><?php esc_html_e( 'Blogger', 'writgocms' ); ?></span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card glass-card fade-in-up" style="--delay: 0.2s">
                    <div class="testimonial-rating">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <blockquote class="testimonial-content">
                        <?php esc_html_e( '"As a developer, I appreciate the clean code and flexibility. The theme is easy to customize and extend for client projects."', 'writgocms' ); ?>
                    </blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            <span>MJ</span>
                        </div>
                        <div class="testimonial-info">
                            <strong><?php esc_html_e( 'Mike Johnson', 'writgocms' ); ?></strong>
                            <span><?php esc_html_e( 'Web Developer', 'writgocms' ); ?></span>
                        </div>
                    </div>
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
        <section class="section section-alt latest-posts">
            <div class="container">
                <div class="section-header fade-in-up">
                    <span class="section-badge"><?php esc_html_e( 'Blog', 'writgocms' ); ?></span>
                    <h2 class="section-title"><?php esc_html_e( 'Latest Posts', 'writgocms' ); ?></h2>
                    <p class="section-subtitle"><?php esc_html_e( 'Check out our most recent articles and updates.', 'writgocms' ); ?></p>
                </div>
                <div class="blog-posts grid-view">
                    <?php
                    $delay = 0;
                    while ( $recent_posts->have_posts() ) :
                        $recent_posts->the_post();
                        ?>
                        <article class="post-card glass-card fade-in-up" style="--delay: <?php echo esc_attr( $delay ); ?>s">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="entry-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'writgocms-card', array( 'loading' => 'lazy' ) ); ?>
                                    </a>
                                </div>
                            <?php else : ?>
                                <div class="entry-thumbnail entry-thumbnail-placeholder">
                                    <a href="<?php the_permalink(); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
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
                        $delay += 0.1;
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                <div class="text-center mt-2">
                    <a href="<?php echo esc_url( writgocms_get_blog_url() ); ?>" class="btn btn-primary btn-lg">
                        <?php esc_html_e( 'View All Posts', 'writgocms' ); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                </div>
            </div>
        </section>
        <?php
    endif;
    ?>

    <!-- CTA Section -->
    <section class="section cta-section">
        <div class="cta-background"></div>
        <div class="container">
            <div class="cta-content fade-in-up">
                <h2><?php esc_html_e( 'Ready to Transform Your Content?', 'writgocms' ); ?></h2>
                <p><?php esc_html_e( 'Start creating amazing content with AI-powered tools today. Setup takes just minutes.', 'writgocms' ); ?></p>
                <div class="cta-buttons">
                    <a href="<?php echo esc_url( admin_url() ); ?>" class="btn btn-white btn-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <?php esc_html_e( 'Get Started Free', 'writgocms' ); ?>
                    </a>
                    <a href="https://github.com/Mikeyy1405/WritgoCMS" class="btn btn-outline-white btn-lg" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        <?php esc_html_e( 'View on GitHub', 'writgocms' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main><!-- #primary -->

<?php
get_footer();
