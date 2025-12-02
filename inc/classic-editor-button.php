<?php
/**
 * Classic Editor AIML Button
 *
 * TinyMCE button registration for AI generation.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoAI_Classic_Editor_Button
 */
class WritgoAI_Classic_Editor_Button {

    /**
     * Instance
     *
     * @var WritgoAI_Classic_Editor_Button
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WritgoAI_Classic_Editor_Button
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
        $plugins['writgoai_ai'] = WRITGOAI_URL . 'assets/js/tinymce-ai-plugin.js';
        return $plugins;
    }

    /**
     * Register TinyMCE button
     *
     * @param array $buttons TinyMCE buttons.
     * @return array
     */
    public function register_tinymce_button( $buttons ) {
        $buttons[] = 'writgoai_ai';
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
            'writgocms-tinymce-ai',
            WRITGOAI_URL . 'assets/css/tinymce-ai.css',
            array(),
            WRITGOAI_VERSION
        );

        wp_localize_script(
            'jquery',
            'writgocmsTinymceAiml',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'writgoai_ai_nonce' ),
                'isAdmin' => current_user_can( 'manage_options' ),
                'i18n'    => array(
                    'title'          => __( 'AI Content Generator', 'writgoai' ),
                    'promptLabel'    => __( 'Enter your prompt:', 'writgoai' ),
                    'textBtn'        => __( 'Generate Text', 'writgoai' ),
                    'imageBtn'       => __( 'Generate Image', 'writgoai' ),
                    'insertBtn'      => __( 'Insert', 'writgoai' ),
                    'cancelBtn'      => __( 'Cancel', 'writgoai' ),
                    'generating'     => __( 'Generating...', 'writgoai' ),
                    'success'        => __( 'Generated successfully!', 'writgoai' ),
                    'error'          => __( 'Error:', 'writgoai' ),
                    'noPrompt'       => __( 'Please enter a prompt', 'writgoai' ),
                    'previewTitle'   => __( 'Preview:', 'writgoai' ),
                ),
            )
        );
    }
}

// Initialize
WritgoAI_Classic_Editor_Button::get_instance();
