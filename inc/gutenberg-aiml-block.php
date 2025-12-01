<?php
/**
 * Gutenberg AIML Block Registration
 *
 * Register Gutenberg block for AI generation.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoCMS_Gutenberg_AIML_Block
 */
class WritgoCMS_Gutenberg_AIML_Block {

    /**
     * Instance
     *
     * @var WritgoCMS_Gutenberg_AIML_Block
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WritgoCMS_Gutenberg_AIML_Block
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
        add_action( 'init', array( $this, 'register_block' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
    }

    /**
     * Add custom block category
     *
     * @param array                   $categories Block categories.
     * @param WP_Block_Editor_Context $context    Block editor context.
     * @return array
     */
    public function add_block_category( $categories, $context ) {
        return array_merge(
            array(
                array(
                    'slug'  => 'writgocms-ai',
                    'title' => __( 'WritgoCMS AI', 'writgocms' ),
                    'icon'  => 'admin-customizer',
                ),
            ),
            $categories
        );
    }

    /**
     * Register the block
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type(
            'writgocms/ai-generator',
            array(
                'editor_script'   => 'writgocms-aiml-block',
                'editor_style'    => 'writgocms-aiml-block-style',
                'render_callback' => array( $this, 'render_block' ),
                'attributes'      => array(
                    'content'   => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'imageUrl'  => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'imageId'   => array(
                        'type'    => 'number',
                        'default' => 0,
                    ),
                    'blockType' => array(
                        'type'    => 'string',
                        'default' => 'text',
                    ),
                ),
            )
        );
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        $provider = WritgoCMS_AIML_Provider::get_instance();

        wp_enqueue_script(
            'writgocms-aiml-block',
            WRITGOCMS_URL . 'assets/js/gutenberg-aiml-block.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-compose', 'wp-data' ),
            WRITGOCMS_VERSION,
            true
        );

        wp_enqueue_style(
            'writgocms-aiml-block-style',
            WRITGOCMS_URL . 'assets/css/gutenberg-aiml-block.css',
            array(),
            WRITGOCMS_VERSION
        );

        wp_localize_script(
            'writgocms-aiml-block',
            'writgocmsAimlBlock',
            array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'writgocms_aiml_nonce' ),
                'isAdmin'      => current_user_can( 'manage_options' ),
                'defaultModel' => $provider->get_default_text_model(),
                'defaultImageModel' => $provider->get_default_image_model(),
                'textModels'   => $provider->get_text_models(),
                'imageModels'  => $provider->get_image_models(),
                'i18n'         => array(
                    'blockTitle'       => __( 'AI Content Generator', 'writgocms' ),
                    'blockDescription' => __( 'Generate text or images using AIMLAPI', 'writgocms' ),
                    'textMode'         => __( 'Text', 'writgocms' ),
                    'imageMode'        => __( 'Image', 'writgocms' ),
                    'promptLabel'      => __( 'Enter your prompt', 'writgocms' ),
                    'generateBtn'      => __( 'Generate', 'writgocms' ),
                    'generating'       => __( 'Generating...', 'writgocms' ),
                    'insertBtn'        => __( 'Insert as Block', 'writgocms' ),
                    'clearBtn'         => __( 'Clear', 'writgocms' ),
                    'previewTitle'     => __( 'Preview', 'writgocms' ),
                    'errorTitle'       => __( 'Error', 'writgocms' ),
                    'noPrompt'         => __( 'Please enter a prompt', 'writgocms' ),
                ),
            )
        );
    }

    /**
     * Render block callback
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public function render_block( $attributes ) {
        $block_type = isset( $attributes['blockType'] ) ? $attributes['blockType'] : 'text';
        $content    = isset( $attributes['content'] ) ? $attributes['content'] : '';
        $image_url  = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';

        if ( 'image' === $block_type && ! empty( $image_url ) ) {
            return sprintf(
                '<figure class="wp-block-image"><img src="%s" alt="%s" /></figure>',
                esc_url( $image_url ),
                esc_attr__( 'AI Generated Image', 'writgocms' )
            );
        }

        if ( ! empty( $content ) ) {
            return sprintf( '<div class="writgocms-ai-content">%s</div>', wp_kses_post( $content ) );
        }

        return '';
    }
}

// Initialize
WritgoCMS_Gutenberg_AIML_Block::get_instance();
