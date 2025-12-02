<?php
/**
 * Gutenberg AI Block Registration
 *
 * Register Gutenberg block for AI generation.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoAI_Gutenberg_AI_Block
 */
class WritgoAI_Gutenberg_AI_Block {

    /**
     * Instance
     *
     * @var WritgoAI_Gutenberg_AI_Block
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WritgoAI_Gutenberg_AI_Block
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
                    'slug'  => 'writgoai',
                    'title' => __( 'WritgoAI AI', 'writgoai' ),
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
            'writgoai/ai-generator',
            array(
                'editor_script'   => 'writgoai-block',
                'editor_style'    => 'writgoai-block-style',
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
        $provider = WritgoAI_AI_Provider::get_instance();

        wp_enqueue_script(
            'writgoai-block',
            WRITGOAI_URL . 'assets/js/gutenberg-ai-block.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-compose', 'wp-data' ),
            WRITGOAI_VERSION,
            true
        );

        wp_enqueue_style(
            'writgoai-block-style',
            WRITGOAI_URL . 'assets/css/gutenberg-ai-block.css',
            array(),
            WRITGOAI_VERSION
        );

        wp_localize_script(
            'writgoai-block',
            'writgoaiAimlBlock',
            array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'writgoai_ai_nonce' ),
                'isAdmin'      => current_user_can( 'manage_options' ),
                'defaultModel' => $provider->get_default_text_model(),
                'defaultImageModel' => $provider->get_default_image_model(),
                'textModels'   => $provider->get_text_models(),
                'imageModels'  => $provider->get_image_models(),
                'i18n'         => array(
                    'blockTitle'       => __( 'AI Content Generator', 'writgoai' ),
                    'blockDescription' => __( 'Generate text or images using WritgoAI', 'writgoai' ),
                    'textMode'         => __( 'Text', 'writgoai' ),
                    'imageMode'        => __( 'Image', 'writgoai' ),
                    'promptLabel'      => __( 'Enter your prompt', 'writgoai' ),
                    'generateBtn'      => __( 'Generate', 'writgoai' ),
                    'generating'       => __( 'Generating...', 'writgoai' ),
                    'insertBtn'        => __( 'Insert as Block', 'writgoai' ),
                    'clearBtn'         => __( 'Clear', 'writgoai' ),
                    'previewTitle'     => __( 'Preview', 'writgoai' ),
                    'errorTitle'       => __( 'Error', 'writgoai' ),
                    'noPrompt'         => __( 'Please enter a prompt', 'writgoai' ),
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
                esc_attr__( 'AI Generated Image', 'writgoai' )
            );
        }

        if ( ! empty( $content ) ) {
            return sprintf( '<div class="writgoai-ai-content">%s</div>', wp_kses_post( $content ) );
        }

        return '';
    }
}

// Initialize
WritgoAI_Gutenberg_AI_Block::get_instance();
