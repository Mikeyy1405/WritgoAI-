<?php
/**
 * WritgoCMS Theme Functions
 *
 * @package WritgoCMS
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Define theme constants
 */
define( 'WRITGOCMS_VERSION', '1.0.0' );
define( 'WRITGOCMS_DIR', get_template_directory() );
define( 'WRITGOCMS_URI', get_template_directory_uri() );

/**
 * Theme setup
 */
function writgocms_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title.
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails.
    add_theme_support( 'post-thumbnails' );

    // Custom logo support.
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 350,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // HTML5 support.
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Add support for responsive embeds.
    add_theme_support( 'responsive-embeds' );

    // Add support for WooCommerce.
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    // Register navigation menus.
    register_nav_menus( array(
        'primary'   => esc_html__( 'Primary Menu', 'writgocms' ),
        'footer'    => esc_html__( 'Footer Menu', 'writgocms' ),
        'mobile'    => esc_html__( 'Mobile Menu', 'writgocms' ),
    ) );

    // Set content width.
    if ( ! isset( $content_width ) ) {
        $content_width = 1200;
    }
}
add_action( 'after_setup_theme', 'writgocms_setup' );

/**
 * Enqueue scripts and styles
 */
function writgocms_scripts() {
    // Main stylesheet.
    wp_enqueue_style( 'writgocms-style', get_stylesheet_uri(), array(), WRITGOCMS_VERSION );

    // Template specific styles.
    wp_enqueue_style( 'writgocms-templates', WRITGOCMS_URI . '/assets/css/templates.css', array( 'writgocms-style' ), WRITGOCMS_VERSION );

    // Main JavaScript.
    wp_enqueue_script( 'writgocms-main', WRITGOCMS_URI . '/assets/js/main.js', array( 'jquery' ), WRITGOCMS_VERSION, true );

    // Localize script.
    wp_localize_script( 'writgocms-main', 'writgocms', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'writgocms_nonce' ),
    ) );

    // Comment reply script.
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'writgocms_scripts' );

/**
 * Enqueue admin scripts and styles
 */
function writgocms_admin_scripts( $hook ) {
    // Only load on theme settings page.
    if ( 'toplevel_page_writgocms-settings' !== $hook && 'appearance_page_writgocms-settings' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'writgocms-admin', WRITGOCMS_URI . '/assets/css/admin.css', array(), WRITGOCMS_VERSION );
    wp_enqueue_script( 'writgocms-admin', WRITGOCMS_URI . '/assets/js/admin.js', array( 'jquery' ), WRITGOCMS_VERSION, true );

    wp_localize_script( 'writgocms-admin', 'writgocmsAdmin', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'writgocms_admin_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'writgocms_admin_scripts' );

/**
 * Register widget areas
 */
function writgocms_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Main Sidebar', 'writgocms' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here to appear in your sidebar.', 'writgocms' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget 1', 'writgocms' ),
        'id'            => 'footer-1',
        'description'   => esc_html__( 'First footer widget area.', 'writgocms' ),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget 2', 'writgocms' ),
        'id'            => 'footer-2',
        'description'   => esc_html__( 'Second footer widget area.', 'writgocms' ),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget 3', 'writgocms' ),
        'id'            => 'footer-3',
        'description'   => esc_html__( 'Third footer widget area.', 'writgocms' ),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Shop Sidebar', 'writgocms' ),
        'id'            => 'sidebar-shop',
        'description'   => esc_html__( 'Sidebar for shop pages.', 'writgocms' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'writgocms_widgets_init' );

/**
 * Include theme files
 */
require_once WRITGOCMS_DIR . '/inc/class-template-manager.php';
require_once WRITGOCMS_DIR . '/inc/class-bol-api.php';
require_once WRITGOCMS_DIR . '/inc/class-ai-integration.php';
require_once WRITGOCMS_DIR . '/inc/admin-settings.php';

/**
 * Initialize theme classes
 */
function writgocms_init() {
    // Initialize Template Manager.
    WritgoCMS_Template_Manager::get_instance();

    // Initialize Bol.com API if credentials are set.
    $bol_api_key = get_option( 'writgocms_bol_api_key', '' );
    if ( ! empty( $bol_api_key ) ) {
        WritgoCMS_Bol_API::get_instance();
    }

    // Initialize AI Integration if API keys are set.
    $openai_key = get_option( 'writgocms_openai_api_key', '' );
    $claude_key = get_option( 'writgocms_claude_api_key', '' );
    if ( ! empty( $openai_key ) || ! empty( $claude_key ) ) {
        WritgoCMS_AI_Integration::get_instance();
    }
}
add_action( 'init', 'writgocms_init' );

/**
 * Custom excerpt length
 */
function writgocms_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'writgocms_excerpt_length' );

/**
 * Custom excerpt more
 */
function writgocms_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'writgocms_excerpt_more' );

/**
 * Add custom body classes
 */
function writgocms_body_classes( $classes ) {
    // Add class for singular pages.
    if ( is_singular() ) {
        $classes[] = 'singular';
    }

    // Add class for pages with sidebar.
    if ( is_active_sidebar( 'sidebar-1' ) && ! is_page_template( 'templates/template-shop.php' ) ) {
        $classes[] = 'has-sidebar';
    }

    // Add template-specific class.
    if ( is_page_template() ) {
        $template = get_page_template_slug();
        $template_class = str_replace( array( 'templates/', '.php' ), '', $template );
        $classes[] = 'template-' . $template_class;
    }

    return $classes;
}
add_filter( 'body_class', 'writgocms_body_classes' );

/**
 * Custom template tags
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

    echo '<span class="posted-on">' . $time_string . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Posted by author
 */
function writgocms_posted_by() {
    echo '<span class="byline"><span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span></span>';
}

/**
 * Entry categories
 */
function writgocms_entry_categories() {
    if ( 'post' === get_post_type() ) {
        $categories_list = get_the_category_list( esc_html__( ', ', 'writgocms' ) );
        if ( $categories_list ) {
            echo '<span class="cat-links">' . $categories_list . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}

/**
 * Entry tags
 */
function writgocms_entry_tags() {
    if ( 'post' === get_post_type() ) {
        $tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'writgocms' ) );
        if ( $tags_list ) {
            echo '<span class="tags-links">' . $tags_list . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}

/**
 * Custom pagination
 */
function writgocms_pagination() {
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => esc_html__( '&laquo; Previous', 'writgocms' ),
        'next_text' => esc_html__( 'Next &raquo;', 'writgocms' ),
    ) );
}

/**
 * Get theme option
 */
function writgocms_get_option( $option, $default = '' ) {
    return get_option( 'writgocms_' . $option, $default );
}
