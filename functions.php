<?php
/**
 * WritgoCMS Theme Functions
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define theme constants
define( 'WRITGOCMS_VERSION', '1.0.0' );
define( 'WRITGOCMS_DIR', get_template_directory() );
define( 'WRITGOCMS_URI', get_template_directory_uri() );

/**
 * Theme Setup
 */
function writgocms_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails
    add_theme_support( 'post-thumbnails' );

    // Add custom image sizes
    add_image_size( 'writgocms-featured', 1200, 600, true );
    add_image_size( 'writgocms-card', 600, 400, true );

    // Register Navigation Menus
    register_nav_menus(
        array(
            'primary' => esc_html__( 'Primary Menu', 'writgocms' ),
            'footer'  => esc_html__( 'Footer Menu', 'writgocms' ),
            'social'  => esc_html__( 'Social Links Menu', 'writgocms' ),
        )
    );

    // Add theme support for HTML5
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );

    // Add theme support for selective refresh for widgets
    add_theme_support( 'customize-selective-refresh-widgets' );

    // Add support for custom logo
    add_theme_support(
        'custom-logo',
        array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        )
    );

    // Add support for custom header
    add_theme_support(
        'custom-header',
        array(
            'default-image'      => '',
            'width'              => 1920,
            'height'             => 600,
            'flex-width'         => true,
            'flex-height'        => true,
            'header-text'        => true,
            'default-text-color' => 'ffffff',
        )
    );

    // Add support for custom background
    add_theme_support(
        'custom-background',
        array(
            'default-color' => 'ffffff',
        )
    );

    // Add support for editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // Add support for Block Styles
    add_theme_support( 'wp-block-styles' );

    // Add support for responsive embedded content
    add_theme_support( 'responsive-embeds' );

    // Add support for wide alignment
    add_theme_support( 'align-wide' );

    // Add support for editor color palette
    add_theme_support(
        'editor-color-palette',
        array(
            array(
                'name'  => esc_html__( 'Primary', 'writgocms' ),
                'slug'  => 'primary',
                'color' => '#1877F2',
            ),
            array(
                'name'  => esc_html__( 'Secondary', 'writgocms' ),
                'slug'  => 'secondary',
                'color' => '#f97316',
            ),
            array(
                'name'  => esc_html__( 'Accent', 'writgocms' ),
                'slug'  => 'accent',
                'color' => '#fb923c',
            ),
            array(
                'name'  => esc_html__( 'Dark', 'writgocms' ),
                'slug'  => 'dark',
                'color' => '#1f2937',
            ),
            array(
                'name'  => esc_html__( 'Light', 'writgocms' ),
                'slug'  => 'light',
                'color' => '#f9fafb',
            ),
        )
    );
}
add_action( 'after_setup_theme', 'writgocms_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 */
function writgocms_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'writgocms_content_width', 1200 );
}
add_action( 'after_setup_theme', 'writgocms_content_width', 0 );

/**
 * Register widget areas.
 */
function writgocms_widgets_init() {
    // Main Sidebar
    register_sidebar(
        array(
            'name'          => esc_html__( 'Sidebar', 'writgocms' ),
            'id'            => 'sidebar-1',
            'description'   => esc_html__( 'Add widgets here to appear in the sidebar.', 'writgocms' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );

    // Footer Widget Area 1
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer 1', 'writgocms' ),
            'id'            => 'footer-1',
            'description'   => esc_html__( 'Add widgets here to appear in the first footer column.', 'writgocms' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );

    // Footer Widget Area 2
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer 2', 'writgocms' ),
            'id'            => 'footer-2',
            'description'   => esc_html__( 'Add widgets here to appear in the second footer column.', 'writgocms' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );

    // Footer Widget Area 3
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer 3', 'writgocms' ),
            'id'            => 'footer-3',
            'description'   => esc_html__( 'Add widgets here to appear in the third footer column.', 'writgocms' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );

    // Hero Widget Area (for homepage)
    register_sidebar(
        array(
            'name'          => esc_html__( 'Hero Section', 'writgocms' ),
            'id'            => 'hero-section',
            'description'   => esc_html__( 'Add widgets here to appear in the homepage hero section.', 'writgocms' ),
            'before_widget' => '<div id="%1$s" class="hero-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h1 class="hero-title">',
            'after_title'   => '</h1>',
        )
    );
}
add_action( 'widgets_init', 'writgocms_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function writgocms_scripts() {
    // Main stylesheet
    wp_enqueue_style( 'writgocms-style', get_stylesheet_uri(), array(), WRITGOCMS_VERSION );

    // Theme JavaScript
    wp_enqueue_script(
        'writgocms-theme',
        WRITGOCMS_URI . '/assets/js/theme.js',
        array(),
        WRITGOCMS_VERSION,
        true
    );

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'writgocms_scripts' );

/**
 * Add preconnect for Google Fonts (if used).
 *
 * @param array  $urls           URLs to print for resource hints.
 * @param string $relation_type  The relation type the URLs are printed.
 * @return array Modified URLs.
 */
function writgocms_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }

    return $urls;
}
add_filter( 'wp_resource_hints', 'writgocms_resource_hints', 10, 2 );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function writgocms_pingback_header() {
    if ( is_singular() && pings_open() ) {
        printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
    }
}
add_action( 'wp_head', 'writgocms_pingback_header' );

/**
 * Custom template tags for this theme.
 */
function writgocms_posted_on() {
    $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
    if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
        $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
    }

    $time_string = sprintf(
        $time_string,
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date() ),
        esc_attr( get_the_modified_date( DATE_W3C ) ),
        esc_html( get_the_modified_date() )
    );

    printf(
        '<span class="posted-on">%1$s <a href="%2$s" rel="bookmark">%3$s</a></span>',
        esc_html_x( 'Posted on', 'post date', 'writgocms' ),
        esc_url( get_permalink() ),
        $time_string
    );
}

/**
 * Display the post author.
 */
function writgocms_posted_by() {
    printf(
        '<span class="byline">%1$s <span class="author vcard"><a class="url fn n" href="%2$s">%3$s</a></span></span>',
        esc_html_x( 'by', 'post author', 'writgocms' ),
        esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
        esc_html( get_the_author() )
    );
}

/**
 * Add custom body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array Modified classes.
 */
function writgocms_body_classes( $classes ) {
    // Add class if sidebar is active
    if ( is_active_sidebar( 'sidebar-1' ) && ! is_page_template( 'page-templates/full-width.php' ) ) {
        $classes[] = 'has-sidebar';
    }

    // Add class for singular pages
    if ( is_singular() ) {
        $classes[] = 'singular';
    }

    // Add class for homepage
    if ( is_front_page() ) {
        $classes[] = 'is-front-page';
    }

    return $classes;
}
add_filter( 'body_class', 'writgocms_body_classes' );

/**
 * Customize the excerpt length.
 *
 * @param int $length Excerpt length.
 * @return int Modified excerpt length.
 */
function writgocms_excerpt_length( $length ) {
    if ( is_admin() ) {
        return $length;
    }
    return 30;
}
add_filter( 'excerpt_length', 'writgocms_excerpt_length' );

/**
 * Customize the excerpt more string.
 *
 * @param string $more Excerpt more string.
 * @return string Modified excerpt more string.
 */
function writgocms_excerpt_more( $more ) {
    if ( is_admin() ) {
        return $more;
    }
    return '&hellip;';
}
add_filter( 'excerpt_more', 'writgocms_excerpt_more' );

/**
 * Fallback menu if no menu is assigned.
 */
function writgocms_fallback_menu() {
    ?>
    <ul id="primary-menu" class="primary-menu">
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'writgocms' ); ?></a></li>
        <?php
        $blog_page_id = get_option( 'page_for_posts' );
        if ( $blog_page_id ) :
            ?>
            <li><a href="<?php echo esc_url( get_permalink( $blog_page_id ) ); ?>"><?php esc_html_e( 'Blog', 'writgocms' ); ?></a></li>
        <?php endif; ?>
    </ul>
    <?php
}

/**
 * Get the blog page URL with fallback.
 *
 * @return string The blog page URL.
 */
function writgocms_get_blog_url() {
    $blog_page_id = get_option( 'page_for_posts' );
    if ( $blog_page_id ) {
        $url = get_permalink( $blog_page_id );
        if ( $url ) {
            return $url;
        }
    }
    // Fallback to home URL with blog path
    return home_url( '/' );
}

// AIML Integration
require_once WRITGOCMS_DIR . '/inc/class-aiml-provider.php';
require_once WRITGOCMS_DIR . '/inc/admin-aiml-settings.php';
require_once WRITGOCMS_DIR . '/inc/gutenberg-aiml-block.php';
require_once WRITGOCMS_DIR . '/inc/classic-editor-button.php';
