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

    // Register Navigation Menus
    register_nav_menus(
        array(
            'primary' => esc_html__( 'Primary Menu', 'writgocms' ),
            'footer'  => esc_html__( 'Footer Menu', 'writgocms' ),
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

    // Add support for editor styles
    add_theme_support( 'editor-styles' );

    // Add support for Block Styles
    add_theme_support( 'wp-block-styles' );

    // Add support for responsive embedded content
    add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'writgocms_setup' );

/**
 * Enqueue scripts and styles.
 */
function writgocms_scripts() {
    wp_enqueue_style( 'writgocms-style', get_stylesheet_uri(), array(), WRITGOCMS_VERSION );
}
add_action( 'wp_enqueue_scripts', 'writgocms_scripts' );

// AIML Integration
require_once WRITGOCMS_DIR . '/inc/class-aiml-provider.php';
require_once WRITGOCMS_DIR . '/inc/admin-aiml-settings.php';
require_once WRITGOCMS_DIR . '/inc/gutenberg-aiml-block.php';
require_once WRITGOCMS_DIR . '/inc/classic-editor-button.php';
