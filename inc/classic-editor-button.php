<?php
/**
 * Classic Editor AIML Button
 *
 * TinyMCE button registration for AI generation.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoCMS_Classic_Editor_Button
 */
class WritgoCMS_Classic_Editor_Button {

    /**
     * Instance
     *
     * @var WritgoCMS_Classic_Editor_Button
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WritgoCMS_Classic_Editor_Button
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_init', array( $this, 'init_tinymce' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Initialize TinyMCE integration
     */
    public function init_tinymce() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        if ( 'true' === get_user_option( 'rich_editing' ) ) {
            add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );
            add_filter( 'mce_buttons', array( $this, 'register_tinymce_button' ) );
        }
    }

    /**
     * Add TinyMCE plugin
     *
     * @param array $plugins TinyMCE plugins.
     * @return array
     */
    public function add_tinymce_plugin( $plugins ) {
        $plugins['writgocms_aiml'] = WRITGOCMS_URI . '/assets/js/tinymce-aiml-plugin.js';
        return $plugins;
    }

    /**
     * Register TinyMCE button
     *
     * @param array $buttons TinyMCE buttons.
     * @return array
     */
    public function register_tinymce_button( $buttons ) {
        $buttons[] = 'writgocms_aiml';
        return $buttons;
    }

    /**
     * Enqueue scripts for classic editor
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        // Only load for classic editor
        $post = get_post();
        if ( null !== $post && function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) {
            return;
        }

        wp_enqueue_style(
            'writgocms-tinymce-aiml',
            WRITGOCMS_URI . '/assets/css/tinymce-aiml.css',
            array(),
            WRITGOCMS_VERSION
        );

        wp_localize_script(
            'jquery',
            'writgocmsTinymceAiml',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'writgocms_aiml_nonce' ),
                'i18n'    => array(
                    'title'          => __( 'AI Content Generator', 'writgocms' ),
                    'promptLabel'    => __( 'Enter your prompt:', 'writgocms' ),
                    'textBtn'        => __( 'Generate Text', 'writgocms' ),
                    'imageBtn'       => __( 'Generate Image', 'writgocms' ),
                    'insertBtn'      => __( 'Insert', 'writgocms' ),
                    'cancelBtn'      => __( 'Cancel', 'writgocms' ),
                    'generating'     => __( 'Generating...', 'writgocms' ),
                    'success'        => __( 'Generated successfully!', 'writgocms' ),
                    'error'          => __( 'Error:', 'writgocms' ),
                    'noPrompt'       => __( 'Please enter a prompt', 'writgocms' ),
                    'previewTitle'   => __( 'Preview:', 'writgocms' ),
                ),
            )
        );
    }
}

// Initialize
WritgoCMS_Classic_Editor_Button::get_instance();
