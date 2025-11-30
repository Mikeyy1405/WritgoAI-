<?php
/**
 * AIML Admin Settings Panel
 *
 * Admin interface for AIMLAPI configuration.
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
        // AIMLAPI Settings
        register_setting( 'writgocms_aiml_settings', 'writgocms_aimlapi_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_default_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_default_image_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_text_temperature', array( 'sanitize_callback' => 'floatval' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_text_max_tokens', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_image_size', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_image_quality', array( 'sanitize_callback' => 'sanitize_text_field' ) );
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
            WRITGOCMS_URL . 'assets/css/admin-aiml.css',
            array(),
            WRITGOCMS_VERSION
        );

        wp_enqueue_script(
            'writgocms-admin-aiml',
            WRITGOCMS_URL . 'assets/js/admin-aiml.js',
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
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">🤖</span>
                <?php esc_html_e( 'WritgoCMS AI - AIMLAPI Settings', 'writgocms' ); ?>
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=writgocms-aiml-settings&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    ⚙️ <?php esc_html_e( 'Settings', 'writgocms' ); ?>
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
                    case 'settings':
                        $this->render_settings_tab();
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
     * Render settings tab
     */
    private function render_settings_tab() {
        $text_models  = $this->provider->get_text_models();
        $image_models = $this->provider->get_image_models();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'writgocms_aiml_settings' ); ?>

            <div class="aiml-settings-section">
                <h2><?php esc_html_e( 'AIMLAPI Configuration', 'writgocms' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Configure your AIMLAPI key to access AI models. Get your API key from', 'writgocms' ); ?>
                    <a href="https://aimlapi.com" target="_blank" rel="noopener noreferrer">aimlapi.com</a>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_aimlapi_key"><?php esc_html_e( 'AIMLAPI Key', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <div class="api-key-field">
                                <input type="password" id="writgocms_aimlapi_key" name="writgocms_aimlapi_key" value="<?php echo esc_attr( get_option( 'writgocms_aimlapi_key' ) ); ?>" class="regular-text">
                                <button type="button" class="button toggle-password">👁️</button>
                                <button type="button" class="button validate-api" id="validate-aimlapi-key"><?php esc_html_e( 'Validate', 'writgocms' ); ?></button>
                                <span class="validation-status"></span>
                            </div>
                            <p class="description"><?php esc_html_e( 'Your AIMLAPI key for accessing all AI models.', 'writgocms' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="aiml-settings-section">
                <h2><?php esc_html_e( 'Text Generation Settings', 'writgocms' ); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_default_model"><?php esc_html_e( 'Default Text Model', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <select id="writgocms_default_model" name="writgocms_default_model">
                                <?php foreach ( $text_models as $model_key => $model_name ) : ?>
                                    <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_default_model', 'gpt-4o' ), $model_key ); ?>>
                                        <?php echo esc_html( $model_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Select the default AI model for text generation.', 'writgocms' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_text_temperature"><?php esc_html_e( 'Temperature', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <input type="range" id="writgocms_text_temperature" name="writgocms_text_temperature" min="0" max="2" step="0.1" value="<?php echo esc_attr( get_option( 'writgocms_text_temperature', '0.7' ) ); ?>" class="range-input">
                            <span class="range-value"><?php echo esc_html( get_option( 'writgocms_text_temperature', '0.7' ) ); ?></span>
                            <p class="description"><?php esc_html_e( 'Higher values make output more random, lower values more deterministic.', 'writgocms' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_text_max_tokens"><?php esc_html_e( 'Max Tokens', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="writgocms_text_max_tokens" name="writgocms_text_max_tokens" value="<?php echo esc_attr( get_option( 'writgocms_text_max_tokens', '1000' ) ); ?>" min="100" max="4000" class="small-text">
                            <p class="description"><?php esc_html_e( 'Maximum number of tokens to generate.', 'writgocms' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="aiml-settings-section">
                <h2><?php esc_html_e( 'Image Generation Settings', 'writgocms' ); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_default_image_model"><?php esc_html_e( 'Default Image Model', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <select id="writgocms_default_image_model" name="writgocms_default_image_model">
                                <?php foreach ( $image_models as $model_key => $model_name ) : ?>
                                    <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_default_image_model', 'dall-e-3' ), $model_key ); ?>>
                                        <?php echo esc_html( $model_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Select the default AI model for image generation.', 'writgocms' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_image_size"><?php esc_html_e( 'Image Size', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <select id="writgocms_image_size" name="writgocms_image_size">
                                <option value="1024x1024" <?php selected( get_option( 'writgocms_image_size', '1024x1024' ), '1024x1024' ); ?>>1024x1024</option>
                                <option value="1792x1024" <?php selected( get_option( 'writgocms_image_size' ), '1792x1024' ); ?>>1792x1024</option>
                                <option value="1024x1792" <?php selected( get_option( 'writgocms_image_size' ), '1024x1792' ); ?>>1024x1792</option>
                                <option value="512x512" <?php selected( get_option( 'writgocms_image_size' ), '512x512' ); ?>>512x512</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_image_quality"><?php esc_html_e( 'Image Quality (DALL-E 3)', 'writgocms' ); ?></label>
                        </th>
                        <td>
                            <select id="writgocms_image_quality" name="writgocms_image_quality">
                                <option value="standard" <?php selected( get_option( 'writgocms_image_quality', 'standard' ), 'standard' ); ?>><?php esc_html_e( 'Standard', 'writgocms' ); ?></option>
                                <option value="hd" <?php selected( get_option( 'writgocms_image_quality' ), 'hd' ); ?>><?php esc_html_e( 'HD', 'writgocms' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render test tab
     */
    private function render_test_tab() {
        $text_models  = $this->provider->get_text_models();
        $image_models = $this->provider->get_image_models();
        ?>
        <div class="test-interface">
            <h2><?php esc_html_e( 'Test AI Generation', 'writgocms' ); ?></h2>

            <div class="test-type-toggle">
                <button type="button" class="button test-type-btn active" data-type="text">📝 <?php esc_html_e( 'Text Generation', 'writgocms' ); ?></button>
                <button type="button" class="button test-type-btn" data-type="image">🖼️ <?php esc_html_e( 'Image Generation', 'writgocms' ); ?></button>
            </div>

            <div class="test-form">
                <div class="test-input-group">
                    <label for="test-model"><?php esc_html_e( 'Model', 'writgocms' ); ?></label>
                    <select id="test-model" class="test-model-select">
                        <optgroup label="<?php esc_attr_e( 'Text Models', 'writgocms' ); ?>" class="text-models">
                            <?php foreach ( $text_models as $model_key => $model_name ) : ?>
                                <option value="<?php echo esc_attr( $model_key ); ?>"><?php echo esc_html( $model_name ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e( 'Image Models', 'writgocms' ); ?>" class="image-models" style="display:none;">
                            <?php foreach ( $image_models as $model_key => $model_name ) : ?>
                                <option value="<?php echo esc_attr( $model_key ); ?>"><?php echo esc_html( $model_name ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

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
                        <th><?php esc_html_e( 'Model', 'writgocms' ); ?></th>
                        <th><?php esc_html_e( 'Count', 'writgocms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows = array();
                    foreach ( $stats as $date => $date_stats ) {
                        foreach ( array( 'text', 'image' ) as $type ) {
                            if ( isset( $date_stats[ $type ] ) ) {
                                foreach ( $date_stats[ $type ] as $model => $count ) {
                                    $rows[] = array(
                                        'date'  => $date,
                                        'type'  => $type,
                                        'model' => $model,
                                        'count' => $count,
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
                            <td><?php echo esc_html( $row['model'] ); ?></td>
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
