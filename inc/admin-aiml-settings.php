<?php
/**
 * AIML Admin Settings Panel
 *
 * Complete admin interface for AIML configuration.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoCMS_AIML_Admin_Settings
 */
class WritgoCMS_AIML_Admin_Settings {

    /**
     * Instance
     *
     * @var WritgoCMS_AIML_Admin_Settings
     */
    private static $instance = null;

    /**
     * Provider instance
     *
     * @var WritgoCMS_AIML_Provider
     */
    private $provider;

    /**
     * Get instance
     *
     * @return WritgoCMS_AIML_Admin_Settings
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
        $this->provider = WritgoCMS_AIML_Provider::get_instance();
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'AIML Settings', 'writgocms' ),
            __( 'WritgoCMS AIML', 'writgocms' ),
            'manage_options',
            'writgocms-aiml-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Text Generation Settings
        register_setting( 'writgocms_aiml_text', 'writgocms_text_provider', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_openai_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_openai_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_claude_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_claude_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_gemini_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_gemini_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_mistral_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_mistral_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_text_temperature', array( 'sanitize_callback' => 'floatval' ) );
        register_setting( 'writgocms_aiml_text', 'writgocms_text_max_tokens', array( 'sanitize_callback' => 'absint' ) );

        // Image Generation Settings
        register_setting( 'writgocms_aiml_image', 'writgocms_image_provider', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_dalle_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_stability_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_stability_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_leonardo_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_leonardo_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_replicate_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_replicate_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_image_size', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_image_quality', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_image_steps', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'writgocms_aiml_image', 'writgocms_image_cfg_scale', array( 'sanitize_callback' => 'floatval' ) );
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'settings_page_writgocms-aiml-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'writgocms-admin-aiml',
            WRITGOCMS_URI . '/assets/css/admin-aiml.css',
            array(),
            WRITGOCMS_VERSION
        );

        wp_enqueue_script(
            'writgocms-admin-aiml',
            WRITGOCMS_URI . '/assets/js/admin-aiml.js',
            array( 'jquery' ),
            WRITGOCMS_VERSION,
            true
        );

        wp_localize_script(
            'writgocms-admin-aiml',
            'writgocmsAiml',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'writgocms_aiml_nonce' ),
                'i18n'    => array(
                    'validating'    => __( 'Validating...', 'writgocms' ),
                    'valid'         => __( 'Valid!', 'writgocms' ),
                    'invalid'       => __( 'Invalid', 'writgocms' ),
                    'error'         => __( 'Error', 'writgocms' ),
                    'generating'    => __( 'Generating...', 'writgocms' ),
                    'success'       => __( 'Success!', 'writgocms' ),
                    'testPrompt'    => __( 'Write a short paragraph about artificial intelligence.', 'writgocms' ),
                    'imagePrompt'   => __( 'A beautiful sunset over mountains', 'writgocms' ),
                ),
            )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'text';
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">🤖</span>
                <?php esc_html_e( 'AIML Multi-Provider Settings', 'writgocms' ); ?>
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=writgocms-aiml-settings&tab=text" class="nav-tab <?php echo 'text' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    📝 <?php esc_html_e( 'Text Generation', 'writgocms' ); ?>
                </a>
                <a href="?page=writgocms-aiml-settings&tab=image" class="nav-tab <?php echo 'image' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    🖼️ <?php esc_html_e( 'Image Generation', 'writgocms' ); ?>
                </a>
                <a href="?page=writgocms-aiml-settings&tab=test" class="nav-tab <?php echo 'test' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    🧪 <?php esc_html_e( 'Test & Preview', 'writgocms' ); ?>
                </a>
                <a href="?page=writgocms-aiml-settings&tab=stats" class="nav-tab <?php echo 'stats' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    📊 <?php esc_html_e( 'Usage Statistics', 'writgocms' ); ?>
                </a>
            </nav>

            <div class="aiml-tab-content">
                <?php
                switch ( $active_tab ) {
                    case 'text':
                        $this->render_text_tab();
                        break;
                    case 'image':
                        $this->render_image_tab();
                        break;
                    case 'test':
                        $this->render_test_tab();
                        break;
                    case 'stats':
                        $this->render_stats_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render text generation tab
     */
    private function render_text_tab() {
        $text_providers   = $this->provider->get_text_providers();
        $current_provider = get_option( 'writgocms_text_provider', 'openai' );
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'writgocms_aiml_text' ); ?>

            <h2><?php esc_html_e( 'Select Text Generation Provider', 'writgocms' ); ?></h2>
            <div class="provider-cards">
                <?php foreach ( $text_providers as $key => $provider ) : ?>
                    <div class="provider-card <?php echo $key === $current_provider ? 'active' : ''; ?>" data-provider="<?php echo esc_attr( $key ); ?>">
                        <input type="radio" name="writgocms_text_provider" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_provider, $key ); ?> id="text_provider_<?php echo esc_attr( $key ); ?>">
                        <label for="text_provider_<?php echo esc_attr( $key ); ?>">
                            <span class="provider-icon">
                                <?php
                                $icons = array(
                                    'openai'  => '🟢',
                                    'claude'  => '🟣',
                                    'gemini'  => '🔵',
                                    'mistral' => '🟠',
                                );
                                echo esc_html( $icons[ $key ] ?? '⚪' );
                                ?>
                            </span>
                            <span class="provider-name"><?php echo esc_html( $provider['name'] ); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="provider-settings">
                <!-- OpenAI Settings -->
                <div class="provider-setting-group" data-provider="openai" style="<?php echo 'openai' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'OpenAI Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_openai_api_key" value="<?php echo esc_attr( get_option( 'writgocms_openai_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="openai"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_openai_model">
                                    <?php foreach ( $text_providers['openai']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_openai_model', 'gpt-3.5-turbo' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Claude Settings -->
                <div class="provider-setting-group" data-provider="claude" style="<?php echo 'claude' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'Claude Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_claude_api_key" value="<?php echo esc_attr( get_option( 'writgocms_claude_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="claude"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_claude_model">
                                    <?php foreach ( $text_providers['claude']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_claude_model', 'claude-3-sonnet-20240229' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Gemini Settings -->
                <div class="provider-setting-group" data-provider="gemini" style="<?php echo 'gemini' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'Google Gemini Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_gemini_api_key" value="<?php echo esc_attr( get_option( 'writgocms_gemini_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="gemini"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_gemini_model">
                                    <?php foreach ( $text_providers['gemini']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_gemini_model', 'gemini-pro' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Mistral Settings -->
                <div class="provider-setting-group" data-provider="mistral" style="<?php echo 'mistral' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'Mistral AI Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_mistral_api_key" value="<?php echo esc_attr( get_option( 'writgocms_mistral_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="mistral"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_mistral_model">
                                    <?php foreach ( $text_providers['mistral']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_mistral_model', 'mistral-small-latest' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <h3><?php esc_html_e( 'Advanced Settings', 'writgocms' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Temperature', 'writgocms' ); ?></th>
                    <td>
                        <input type="range" name="writgocms_text_temperature" min="0" max="2" step="0.1" value="<?php echo esc_attr( get_option( 'writgocms_text_temperature', '0.7' ) ); ?>" class="range-input">
                        <span class="range-value"><?php echo esc_html( get_option( 'writgocms_text_temperature', '0.7' ) ); ?></span>
                        <p class="description"><?php esc_html_e( 'Higher values make output more random, lower values more deterministic.', 'writgocms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Max Tokens', 'writgocms' ); ?></th>
                    <td>
                        <input type="number" name="writgocms_text_max_tokens" value="<?php echo esc_attr( get_option( 'writgocms_text_max_tokens', '1000' ) ); ?>" min="100" max="4000" class="small-text">
                        <p class="description"><?php esc_html_e( 'Maximum number of tokens to generate.', 'writgocms' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render image generation tab
     */
    private function render_image_tab() {
        $image_providers  = $this->provider->get_image_providers();
        $current_provider = get_option( 'writgocms_image_provider', 'dalle' );
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'writgocms_aiml_image' ); ?>

            <h2><?php esc_html_e( 'Select Image Generation Provider', 'writgocms' ); ?></h2>
            <div class="provider-cards">
                <?php foreach ( $image_providers as $key => $provider ) : ?>
                    <div class="provider-card <?php echo $key === $current_provider ? 'active' : ''; ?>" data-provider="<?php echo esc_attr( $key ); ?>">
                        <input type="radio" name="writgocms_image_provider" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_provider, $key ); ?> id="image_provider_<?php echo esc_attr( $key ); ?>">
                        <label for="image_provider_<?php echo esc_attr( $key ); ?>">
                            <span class="provider-icon">
                                <?php
                                $icons = array(
                                    'dalle'     => '🎨',
                                    'stability' => '🌟',
                                    'leonardo'  => '🦁',
                                    'replicate' => '🔄',
                                );
                                echo esc_html( $icons[ $key ] ?? '⚪' );
                                ?>
                            </span>
                            <span class="provider-name"><?php echo esc_html( $provider['name'] ); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="provider-settings">
                <!-- DALL-E Settings -->
                <div class="provider-setting-group" data-provider="dalle" style="<?php echo 'dalle' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'DALL-E Settings', 'writgocms' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'DALL-E uses the same API key as OpenAI text generation. Configure it in the Text Generation tab.', 'writgocms' ); ?></p>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_dalle_model">
                                    <?php foreach ( $image_providers['dalle']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_dalle_model', 'dall-e-3' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Stability AI Settings -->
                <div class="provider-setting-group" data-provider="stability" style="<?php echo 'stability' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'Stability AI Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_stability_api_key" value="<?php echo esc_attr( get_option( 'writgocms_stability_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="stability"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_stability_model">
                                    <?php foreach ( $image_providers['stability']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_stability_model', 'stable-diffusion-xl-1024-v1-0' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Leonardo.ai Settings -->
                <div class="provider-setting-group" data-provider="leonardo" style="<?php echo 'leonardo' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'Leonardo.ai Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_leonardo_api_key" value="<?php echo esc_attr( get_option( 'writgocms_leonardo_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="leonardo"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_leonardo_model">
                                    <?php foreach ( $image_providers['leonardo']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_leonardo_model', 'leonardo-diffusion-xl' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Replicate Settings -->
                <div class="provider-setting-group" data-provider="replicate" style="<?php echo 'replicate' !== $current_provider ? 'display:none;' : ''; ?>">
                    <h3><?php esc_html_e( 'Replicate Settings', 'writgocms' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writgocms' ); ?></th>
                            <td>
                                <div class="api-key-field">
                                    <input type="password" name="writgocms_replicate_api_key" value="<?php echo esc_attr( get_option( 'writgocms_replicate_api_key' ) ); ?>" class="regular-text">
                                    <button type="button" class="button toggle-password">👁️</button>
                                    <button type="button" class="button validate-api" data-provider="replicate"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                    <span class="validation-status"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                            <td>
                                <select name="writgocms_replicate_model">
                                    <?php foreach ( $image_providers['replicate']['models'] as $model_key => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_replicate_model', 'flux-schnell' ), $model_key ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <h3><?php esc_html_e( 'Advanced Settings', 'writgocms' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Image Size', 'writgocms' ); ?></th>
                    <td>
                        <select name="writgocms_image_size">
                            <option value="1024x1024" <?php selected( get_option( 'writgocms_image_size', '1024x1024' ), '1024x1024' ); ?>>1024x1024</option>
                            <option value="1792x1024" <?php selected( get_option( 'writgocms_image_size' ), '1792x1024' ); ?>>1792x1024</option>
                            <option value="1024x1792" <?php selected( get_option( 'writgocms_image_size' ), '1024x1792' ); ?>>1024x1792</option>
                            <option value="512x512" <?php selected( get_option( 'writgocms_image_size' ), '512x512' ); ?>>512x512</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Quality (DALL-E 3)', 'writgocms' ); ?></th>
                    <td>
                        <select name="writgocms_image_quality">
                            <option value="standard" <?php selected( get_option( 'writgocms_image_quality', 'standard' ), 'standard' ); ?>><?php esc_html_e( 'Standard', 'writgocms' ); ?></option>
                            <option value="hd" <?php selected( get_option( 'writgocms_image_quality' ), 'hd' ); ?>><?php esc_html_e( 'HD', 'writgocms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Steps (Stability/Leonardo)', 'writgocms' ); ?></th>
                    <td>
                        <input type="range" name="writgocms_image_steps" min="10" max="50" value="<?php echo esc_attr( get_option( 'writgocms_image_steps', '30' ) ); ?>" class="range-input">
                        <span class="range-value"><?php echo esc_html( get_option( 'writgocms_image_steps', '30' ) ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'CFG Scale (Stability)', 'writgocms' ); ?></th>
                    <td>
                        <input type="range" name="writgocms_image_cfg_scale" min="1" max="20" step="0.5" value="<?php echo esc_attr( get_option( 'writgocms_image_cfg_scale', '7' ) ); ?>" class="range-input">
                        <span class="range-value"><?php echo esc_html( get_option( 'writgocms_image_cfg_scale', '7' ) ); ?></span>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render test tab
     */
    private function render_test_tab() {
        ?>
        <div class="test-interface">
            <h2><?php esc_html_e( 'Test AI Generation', 'writgocms' ); ?></h2>

            <div class="test-type-toggle">
                <button type="button" class="button test-type-btn active" data-type="text">📝 <?php esc_html_e( 'Text Generation', 'writgocms' ); ?></button>
                <button type="button" class="button test-type-btn" data-type="image">🖼️ <?php esc_html_e( 'Image Generation', 'writgocms' ); ?></button>
            </div>

            <div class="test-form">
                <div class="test-input-group">
                    <label for="test-prompt"><?php esc_html_e( 'Prompt', 'writgocms' ); ?></label>
                    <textarea id="test-prompt" rows="3" placeholder="<?php esc_attr_e( 'Enter your prompt here...', 'writgocms' ); ?>"></textarea>
                </div>

                <div class="test-actions">
                    <button type="button" id="test-generate" class="button button-primary">
                        ✨ <?php esc_html_e( 'Generate', 'writgocms' ); ?>
                    </button>
                    <span class="test-status"></span>
                </div>
            </div>

            <div class="test-result" style="display: none;">
                <h3><?php esc_html_e( 'Result', 'writgocms' ); ?></h3>
                <div class="test-result-content"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render statistics tab
     */
    private function render_stats_tab() {
        $stats = get_option( 'writgocms_aiml_usage_stats', array() );
        $totals = array(
            'text'  => 0,
            'image' => 0,
        );

        foreach ( $stats as $date_stats ) {
            if ( isset( $date_stats['text'] ) ) {
                foreach ( $date_stats['text'] as $count ) {
                    $totals['text'] += $count;
                }
            }
            if ( isset( $date_stats['image'] ) ) {
                foreach ( $date_stats['image'] as $count ) {
                    $totals['image'] += $count;
                }
            }
        }
        ?>
        <div class="stats-dashboard">
            <h2><?php esc_html_e( 'Usage Statistics (Last 30 Days)', 'writgocms' ); ?></h2>

            <div class="stats-cards">
                <div class="stat-card">
                    <span class="stat-icon">📝</span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( $totals['text'] ); ?></span>
                        <span class="stat-label"><?php esc_html_e( 'Text Generations', 'writgocms' ); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🖼️</span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( $totals['image'] ); ?></span>
                        <span class="stat-label"><?php esc_html_e( 'Image Generations', 'writgocms' ); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📊</span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( $totals['text'] + $totals['image'] ); ?></span>
                        <span class="stat-label"><?php esc_html_e( 'Total Requests', 'writgocms' ); ?></span>
                    </div>
                </div>
            </div>

            <h3><?php esc_html_e( 'Recent Activity', 'writgocms' ); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'writgocms' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'writgocms' ); ?></th>
                        <th><?php esc_html_e( 'Provider', 'writgocms' ); ?></th>
                        <th><?php esc_html_e( 'Count', 'writgocms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows = array();
                    foreach ( $stats as $date => $date_stats ) {
                        foreach ( array( 'text', 'image' ) as $type ) {
                            if ( isset( $date_stats[ $type ] ) ) {
                                foreach ( $date_stats[ $type ] as $provider => $count ) {
                                    $rows[] = array(
                                        'date'     => $date,
                                        'type'     => $type,
                                        'provider' => $provider,
                                        'count'    => $count,
                                    );
                                }
                            }
                        }
                    }

                    usort(
                        $rows,
                        function( $a, $b ) {
                            return strcmp( $b['date'], $a['date'] );
                        }
                    );

                    if ( empty( $rows ) ) :
                        ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e( 'No usage data yet.', 'writgocms' ); ?></td>
                        </tr>
                        <?php
                    else :
                        foreach ( array_slice( $rows, 0, 20 ) as $row ) :
                            ?>
                        <tr>
                            <td><?php echo esc_html( $row['date'] ); ?></td>
                            <td><?php echo 'text' === $row['type'] ? '📝 Text' : '🖼️ Image'; ?></td>
                            <td><?php echo esc_html( ucfirst( $row['provider'] ) ); ?></td>
                            <td><?php echo esc_html( $row['count'] ); ?></td>
                        </tr>
                            <?php
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Initialize admin settings
WritgoCMS_AIML_Admin_Settings::get_instance();
