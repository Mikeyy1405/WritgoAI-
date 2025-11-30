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
        // Add main menu item.
        add_menu_page(
            __( 'WritgoAI Dashboard', 'writgocms' ),
            __( 'WritgoAI', 'writgocms' ),
            'manage_options',
            'writgocms-aiml',
            array( $this, 'render_dashboard_page' ),
            'dashicons-welcome-widgets-menus',
            26 // Position after Pages.
        );

        // Add Dashboard submenu.
        add_submenu_page(
            'writgocms-aiml',
            __( 'Dashboard', 'writgocms' ),
            __( 'Dashboard', 'writgocms' ),
            'manage_options',
            'writgocms-aiml',
            array( $this, 'render_dashboard_page' )
        );

        // Add Content Planner submenu.
        add_submenu_page(
            'writgocms-aiml',
            __( 'Content Planner', 'writgocms' ),
            __( 'Content Planner', 'writgocms' ),
            'manage_options',
            'writgocms-aiml-content-planner',
            array( $this, 'render_content_planner_page' )
        );

        // Add Post Updater submenu.
        add_submenu_page(
            'writgocms-aiml',
            __( 'Post Updater', 'writgocms' ),
            '📝 ' . __( 'Post Updater', 'writgocms' ),
            'manage_options',
            'writgocms-aiml-post-updater',
            array( $this, 'render_post_updater_page' )
        );

        // Add Test & Preview submenu.
        add_submenu_page(
            'writgocms-aiml',
            __( 'Test & Preview', 'writgocms' ),
            __( 'Test & Preview', 'writgocms' ),
            'manage_options',
            'writgocms-aiml-test',
            array( $this, 'render_test_page' )
        );

        // Add Usage Statistics submenu.
        add_submenu_page(
            'writgocms-aiml',
            __( 'Usage Statistics', 'writgocms' ),
            __( 'Usage Statistics', 'writgocms' ),
            'manage_options',
            'writgocms-aiml-stats',
            array( $this, 'render_stats_page' )
        );

        // Add Settings submenu.
        add_submenu_page(
            'writgocms-aiml',
            __( 'Settings', 'writgocms' ),
            __( 'Settings', 'writgocms' ),
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
        // Check if we're on any WritgoAI admin page.
        $allowed_hooks = array(
            'toplevel_page_writgocms-aiml',
            'writgocms-aiml_page_writgocms-aiml-content-planner',
            'writgocms-aiml_page_writgocms-aiml-post-updater',
            'writgocms-aiml_page_writgocms-aiml-test',
            'writgocms-aiml_page_writgocms-aiml-stats',
            'writgocms-aiml_page_writgocms-aiml-settings',
        );

        if ( ! in_array( $hook, $allowed_hooks, true ) ) {
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
                    'validating'              => __( 'Validating...', 'writgocms' ),
                    'valid'                   => __( 'Valid!', 'writgocms' ),
                    'invalid'                 => __( 'Invalid', 'writgocms' ),
                    'error'                   => __( 'Error', 'writgocms' ),
                    'generating'              => __( 'Generating...', 'writgocms' ),
                    'success'                 => __( 'Success!', 'writgocms' ),
                    'testPrompt'              => __( 'Write a short paragraph about artificial intelligence.', 'writgocms' ),
                    'imagePrompt'             => __( 'A beautiful sunset over mountains', 'writgocms' ),
                    'generatingMap'           => __( 'Generating topical authority map...', 'writgocms' ),
                    'generatingPlan'          => __( 'Generating content plan...', 'writgocms' ),
                    'savePlan'                => __( 'Save Plan', 'writgocms' ),
                    'planSaved'               => __( 'Content plan saved successfully!', 'writgocms' ),
                    'planDeleted'             => __( 'Content plan deleted.', 'writgocms' ),
                    'confirmDelete'           => __( 'Are you sure you want to delete this content plan?', 'writgocms' ),
                    'noNiche'                 => __( 'Please enter a niche/topic.', 'writgocms' ),
                    'noPlanName'              => __( 'Please enter a plan name.', 'writgocms' ),
                    'pillarContent'           => __( 'Pillar Content', 'writgocms' ),
                    'clusterArticles'         => __( 'Cluster Articles', 'writgocms' ),
                    'keywords'                => __( 'Keywords', 'writgocms' ),
                    'priority'                => __( 'Priority', 'writgocms' ),
                    'contentGaps'             => __( 'Content Gaps to Address', 'writgocms' ),
                    'recommendedOrder'        => __( 'Recommended Publishing Order', 'writgocms' ),
                    'generateDetailedPlan'    => __( 'Generate Detailed Plan', 'writgocms' ),
                    'high'                    => __( 'High', 'writgocms' ),
                    'medium'                  => __( 'Medium', 'writgocms' ),
                    'low'                     => __( 'Low', 'writgocms' ),
                    // Post Updater translations (Dutch).
                    'postUpdater' => array(
                        'loading'              => __( 'Laden...', 'writgocms' ),
                        'analyzing'            => __( 'Analyseren...', 'writgocms' ),
                        'improving'            => __( 'Verbeteren...', 'writgocms' ),
                        'saving'               => __( 'Opslaan...', 'writgocms' ),
                        'noPostsFound'         => __( 'Geen posts gevonden die verbetering nodig hebben.', 'writgocms' ),
                        'selectPost'           => __( 'Selecteer een post om te verbeteren', 'writgocms' ),
                        'confirmBulk'          => __( 'Weet je zeker dat je deze posts wilt verbeteren?', 'writgocms' ),
                        'bulkComplete'         => __( 'Bulk verbetering voltooid!', 'writgocms' ),
                        'improvementComplete'  => __( 'Verbetering voltooid!', 'writgocms' ),
                        'savedAsDraft'         => __( 'Opgeslagen als concept', 'writgocms' ),
                        'published'            => __( 'Gepubliceerd', 'writgocms' ),
                        'errorOccurred'        => __( 'Er is een fout opgetreden', 'writgocms' ),
                        'monthsOld'            => __( 'maanden oud', 'writgocms' ),
                        'words'                => __( 'woorden', 'writgocms' ),
                        'seoScore'             => __( 'SEO Score', 'writgocms' ),
                        'viewPost'             => __( 'Bekijk Post', 'writgocms' ),
                        'improvePost'          => __( 'Verbeter & Herschrijf', 'writgocms' ),
                        'startImprovement'     => __( 'Start Verbetering', 'writgocms' ),
                        'cancel'               => __( 'Annuleer', 'writgocms' ),
                        'saveAsDraft'          => __( 'Opslaan als Concept', 'writgocms' ),
                        'publishNow'           => __( 'Direct Publiceren', 'writgocms' ),
                        'preview'              => __( 'Preview', 'writgocms' ),
                        'before'               => __( 'VOOR', 'writgocms' ),
                        'after'                => __( 'NA', 'writgocms' ),
                        'differences'          => __( 'VERSCHILLEN', 'writgocms' ),
                    ),
                ),
            )
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
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

        $saved_plans = get_option( 'writgocms_saved_content_plans', array() );
        $plans_count = count( $saved_plans );
        $has_api_key = ! empty( get_option( 'writgocms_aimlapi_key' ) );
        ?>
        <div class="wrap writgocms-aiml-settings writgocms-dashboard">
            <h1 class="aiml-header">
                <span class="aiml-logo">🤖</span>
                <?php esc_html_e( 'WritgoAI Dashboard', 'writgocms' ); ?>
            </h1>

            <div class="aiml-tab-content">
                <!-- Welcome Section -->
                <div class="dashboard-welcome">
                    <h2><?php esc_html_e( 'Welcome to WritgoAI', 'writgocms' ); ?></h2>
                    <p><?php esc_html_e( 'Your AI-powered content creation assistant. Generate text, images, and plan your content strategy with ease.', 'writgocms' ); ?></p>
                </div>

                <!-- Quick Stats -->
                <div class="dashboard-stats">
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
                    <div class="stat-card">
                        <span class="stat-icon">📁</span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo esc_html( $plans_count ); ?></span>
                            <span class="stat-label"><?php esc_html_e( 'Saved Plans', 'writgocms' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Widgets Grid -->
                <div class="dashboard-widgets">
                    <!-- Settings Widget -->
                    <div class="dashboard-widget widget-primary">
                        <div class="widget-icon">⚙️</div>
                        <div class="widget-content">
                            <h3><?php esc_html_e( 'Settings', 'writgocms' ); ?></h3>
                            <p><?php esc_html_e( 'Configure your API key and AI model preferences.', 'writgocms' ); ?></p>
                            <?php if ( ! $has_api_key ) : ?>
                                <span class="widget-badge warning"><?php esc_html_e( 'API Key Required', 'writgocms' ); ?></span>
                            <?php else : ?>
                                <span class="widget-badge success"><?php esc_html_e( 'Configured', 'writgocms' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-settings' ) ); ?>" class="widget-button">
                            <?php esc_html_e( 'Open Settings', 'writgocms' ); ?>
                        </a>
                    </div>

                    <!-- Content Planner Widget -->
                    <div class="dashboard-widget widget-primary">
                        <div class="widget-icon">🗺️</div>
                        <div class="widget-content">
                            <h3><?php esc_html_e( 'Content Planner', 'writgocms' ); ?></h3>
                            <p><?php esc_html_e( 'Generate AI-powered topical authority maps for your content strategy.', 'writgocms' ); ?></p>
                            <?php if ( $plans_count > 0 ) : ?>
                                <span class="widget-badge info"><?php echo esc_html( sprintf( __( '%d Saved Plans', 'writgocms' ), $plans_count ) ); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-content-planner' ) ); ?>" class="widget-button">
                            <?php esc_html_e( 'Plan Content', 'writgocms' ); ?>
                        </a>
                    </div>

                    <!-- Test & Preview Widget -->
                    <div class="dashboard-widget widget-secondary">
                        <div class="widget-icon">🧪</div>
                        <div class="widget-content">
                            <h3><?php esc_html_e( 'Test & Preview', 'writgocms' ); ?></h3>
                            <p><?php esc_html_e( 'Test AI text and image generation with different models.', 'writgocms' ); ?></p>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-test' ) ); ?>" class="widget-button">
                            <?php esc_html_e( 'Test Now', 'writgocms' ); ?>
                        </a>
                    </div>

                    <!-- Usage Statistics Widget -->
                    <div class="dashboard-widget widget-secondary">
                        <div class="widget-icon">📊</div>
                        <div class="widget-content">
                            <h3><?php esc_html_e( 'Usage Statistics', 'writgocms' ); ?></h3>
                            <p><?php esc_html_e( 'View detailed usage statistics and activity history.', 'writgocms' ); ?></p>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-stats' ) ); ?>" class="widget-button">
                            <?php esc_html_e( 'View Stats', 'writgocms' ); ?>
                        </a>
                    </div>
                </div>

                <!-- Topical Authority Map Generator Quick Access -->
                <div class="dashboard-feature-card">
                    <div class="feature-header">
                        <span class="feature-icon">🎯</span>
                        <h3><?php esc_html_e( 'Topical Authority Map Generator', 'writgocms' ); ?></h3>
                    </div>
                    <p><?php esc_html_e( 'Build comprehensive content strategies with AI-generated topical authority maps. Define your niche, target audience, and let AI create a structured content plan with pillar articles and cluster content.', 'writgocms' ); ?></p>
                    <div class="feature-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-content-planner' ) ); ?>" class="button button-primary button-hero">
                            ✨ <?php esc_html_e( 'Generate Content Map', 'writgocms' ); ?>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-section">
                    <h3><?php esc_html_e( 'Recent Activity', 'writgocms' ); ?></h3>
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
                        <p class="no-activity"><?php esc_html_e( 'No activity yet. Start generating content to see your usage history.', 'writgocms' ); ?></p>
                        <?php
                    else :
                        ?>
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
                                <?php foreach ( array_slice( $rows, 0, 5 ) as $row ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $row['date'] ); ?></td>
                                    <td><?php echo 'text' === $row['type'] ? esc_html( '📝 Text' ) : esc_html( '🖼️ Image' ); ?></td>
                                    <td><?php echo esc_html( $row['model'] ); ?></td>
                                    <td><?php echo esc_html( $row['count'] ); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p style="margin-top: 15px;">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-stats' ) ); ?>"><?php esc_html_e( 'View all activity →', 'writgocms' ); ?></a>
                        </p>
                        <?php
                    endif;
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render content planner page
     */
    public function render_content_planner_page() {
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">🗺️</span>
                <?php esc_html_e( 'Content Planner', 'writgocms' ); ?>
            </h1>

            <div class="aiml-tab-content">
                <?php $this->render_content_planner_tab(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render test page
     */
    public function render_test_page() {
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">🧪</span>
                <?php esc_html_e( 'Test & Preview', 'writgocms' ); ?>
            </h1>

            <div class="aiml-tab-content">
                <?php $this->render_test_tab(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render Post Updater page
     */
    public function render_post_updater_page() {
        $post_updater = WritgoCMS_Post_Updater::get_instance();
        $seo_plugin   = $post_updater->get_active_seo_plugin();
        $categories   = get_categories( array( 'hide_empty' => false ) );
        ?>
        <div class="wrap writgocms-aiml-settings writgocms-post-updater">
            <h1 class="aiml-header">
                <span class="aiml-logo">📝</span>
                <?php esc_html_e( 'Post Updater', 'writgocms' ); ?>
            </h1>
            <p class="header-subtitle"><?php esc_html_e( 'Verbeter en optimaliseer je bestaande content', 'writgocms' ); ?></p>

            <div class="aiml-tab-content">
                <!-- Filter Section -->
                <div class="post-updater-filters">
                    <h3>🔍 <?php esc_html_e( 'Filter Opties', 'writgocms' ); ?></h3>
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="filter-months"><?php esc_html_e( 'Datum:', 'writgocms' ); ?></label>
                            <select id="filter-months">
                                <option value="3"><?php esc_html_e( 'Ouder dan 3 maanden', 'writgocms' ); ?></option>
                                <option value="6" selected><?php esc_html_e( 'Ouder dan 6 maanden', 'writgocms' ); ?></option>
                                <option value="12"><?php esc_html_e( 'Ouder dan 12 maanden', 'writgocms' ); ?></option>
                                <option value="24"><?php esc_html_e( 'Ouder dan 24 maanden', 'writgocms' ); ?></option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label for="filter-seo"><?php esc_html_e( 'SEO Score:', 'writgocms' ); ?></label>
                            <select id="filter-seo">
                                <option value="0-100"><?php esc_html_e( 'Alle scores', 'writgocms' ); ?></option>
                                <option value="0-40"><?php esc_html_e( 'Rood (0-40)', 'writgocms' ); ?></option>
                                <option value="41-70"><?php esc_html_e( 'Oranje (41-70)', 'writgocms' ); ?></option>
                                <option value="71-100"><?php esc_html_e( 'Groen (71-100)', 'writgocms' ); ?></option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label for="filter-category"><?php esc_html_e( 'Categorie:', 'writgocms' ); ?></label>
                            <select id="filter-category">
                                <option value=""><?php esc_html_e( 'Alle categorieën', 'writgocms' ); ?></option>
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-item filter-search">
                            <label for="filter-search"><?php esc_html_e( 'Zoeken:', 'writgocms' ); ?></label>
                            <input type="text" id="filter-search" placeholder="<?php esc_attr_e( 'Zoek op titel...', 'writgocms' ); ?>">
                            <button type="button" id="btn-search" class="button">🔍</button>
                        </div>
                    </div>
                    <?php if ( $seo_plugin ) : ?>
                    <p class="seo-plugin-notice">
                        <?php
                        printf(
                            /* translators: %s: SEO plugin name */
                            esc_html__( '✅ Gedetecteerde SEO plugin: %s', 'writgocms' ),
                            '<strong>' . esc_html( 'yoast' === $seo_plugin ? 'Yoast SEO' : 'Rank Math' ) . '</strong>'
                        );
                        ?>
                    </p>
                    <?php else : ?>
                    <p class="seo-plugin-notice warning">
                        <?php esc_html_e( '⚠️ Geen SEO plugin gedetecteerd. Installeer Yoast SEO of Rank Math voor volledige functionaliteit.', 'writgocms' ); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Posts List Section -->
                <div class="post-updater-list-section">
                    <div class="section-header">
                        <h3>📊 <?php esc_html_e( 'Posts die Verbetering Nodig Hebben', 'writgocms' ); ?> <span id="posts-count">(0)</span></h3>
                        <div class="bulk-actions">
                            <button type="button" id="btn-select-all" class="button"><?php esc_html_e( 'Selecteer Alles', 'writgocms' ); ?></button>
                            <button type="button" id="btn-bulk-improve" class="button button-primary" disabled>🔄 <?php esc_html_e( 'Bulk Verbeteren', 'writgocms' ); ?> (<span id="selected-count">0</span>)</button>
                        </div>
                    </div>
                    <div id="posts-list" class="posts-list">
                        <div class="loading-state">
                            <span class="spinner is-active"></span>
                            <p><?php esc_html_e( 'Posts laden...', 'writgocms' ); ?></p>
                        </div>
                    </div>
                    <div id="posts-pagination" class="posts-pagination"></div>
                </div>

                <!-- Improvement Modal -->
                <div id="improvement-modal" class="post-updater-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🔄 <?php esc_html_e( 'Post Verbeteren', 'writgocms' ); ?></h3>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <h4 id="modal-post-title"></h4>

                            <div class="improvement-options">
                                <h5>📋 <?php esc_html_e( 'Selecteer Verbeteringen:', 'writgocms' ); ?></h5>
                                <div class="options-grid">
                                    <label class="option-item">
                                        <input type="checkbox" name="update_dates" checked>
                                        <span>🔄 <?php printf( esc_html__( 'Update verouderde informatie (jaartallen → %s)', 'writgocms' ), esc_html( gmdate( 'Y' ) ) ); ?></span>
                                    </label>
                                    <label class="option-item">
                                        <input type="checkbox" name="extend_content" checked>
                                        <span>📝 <?php esc_html_e( 'Verleng artikel naar 1500+ woorden', 'writgocms' ); ?></span>
                                    </label>
                                    <label class="option-item">
                                        <input type="checkbox" name="optimize_seo" checked>
                                        <span>🎯 <?php esc_html_e( 'Optimaliseer SEO score', 'writgocms' ); ?></span>
                                    </label>
                                    <label class="option-item">
                                        <input type="checkbox" name="rewrite_intro" checked>
                                        <span>✍️ <?php esc_html_e( 'Herschrijf introductie', 'writgocms' ); ?></span>
                                    </label>
                                    <label class="option-item">
                                        <input type="checkbox" name="improve_readability" checked>
                                        <span>📱 <?php esc_html_e( 'Verbeter leesbaarheid', 'writgocms' ); ?></span>
                                    </label>
                                    <label class="option-item">
                                        <input type="checkbox" name="add_links" checked>
                                        <span>🔗 <?php esc_html_e( 'Voeg relevante links toe', 'writgocms' ); ?></span>
                                    </label>
                                    <label class="option-item">
                                        <input type="checkbox" name="add_faq">
                                        <span>❓ <?php esc_html_e( 'Voeg FAQ sectie toe', 'writgocms' ); ?></span>
                                    </label>
                                </div>

                                <div class="focus-keyword-field">
                                    <label for="focus-keyword">🎯 <?php esc_html_e( 'Focus keyword:', 'writgocms' ); ?></label>
                                    <input type="text" id="focus-keyword" placeholder="<?php esc_attr_e( 'Laat leeg om bestaande te behouden', 'writgocms' ); ?>">
                                </div>

                                <div class="style-options">
                                    <div class="style-option">
                                        <label for="writing-tone"><?php esc_html_e( 'Schrijfstijl:', 'writgocms' ); ?></label>
                                        <select id="writing-tone">
                                            <option value="professional"><?php esc_html_e( 'Professioneel', 'writgocms' ); ?></option>
                                            <option value="casual"><?php esc_html_e( 'Informeel', 'writgocms' ); ?></option>
                                            <option value="friendly"><?php esc_html_e( 'Vriendelijk', 'writgocms' ); ?></option>
                                            <option value="expert"><?php esc_html_e( 'Expert', 'writgocms' ); ?></option>
                                        </select>
                                    </div>
                                    <div class="style-option">
                                        <label for="target-audience"><?php esc_html_e( 'Doelgroep:', 'writgocms' ); ?></label>
                                        <select id="target-audience">
                                            <option value="broad"><?php esc_html_e( 'Breed publiek', 'writgocms' ); ?></option>
                                            <option value="beginners"><?php esc_html_e( 'Beginners', 'writgocms' ); ?></option>
                                            <option value="professionals"><?php esc_html_e( 'Professionals', 'writgocms' ); ?></option>
                                            <option value="experts"><?php esc_html_e( 'Experts', 'writgocms' ); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="improvement-level">
                                    <h5>🤖 <?php esc_html_e( 'AI Modus:', 'writgocms' ); ?></h5>
                                    <div class="level-options">
                                        <label class="level-option">
                                            <input type="radio" name="improvement_level" value="light">
                                            <span class="level-indicator light"></span>
                                            <span class="level-text"><?php esc_html_e( 'Lichte aanpassingen (behoud 80% origineel)', 'writgocms' ); ?></span>
                                        </label>
                                        <label class="level-option">
                                            <input type="radio" name="improvement_level" value="medium" checked>
                                            <span class="level-indicator medium"></span>
                                            <span class="level-text"><?php esc_html_e( 'Gemiddelde verbetering (behoud 50% origineel)', 'writgocms' ); ?></span>
                                        </label>
                                        <label class="level-option">
                                            <input type="radio" name="improvement_level" value="heavy">
                                            <span class="level-indicator heavy"></span>
                                            <span class="level-text"><?php esc_html_e( 'Complete herschrijving (behoud structuur)', 'writgocms' ); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button modal-cancel"><?php esc_html_e( 'Annuleer', 'writgocms' ); ?></button>
                            <button type="button" id="btn-start-improvement" class="button button-primary">🚀 <?php esc_html_e( 'Start Verbetering', 'writgocms' ); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Preview Modal -->
                <div id="preview-modal" class="post-updater-modal preview-modal" style="display: none;">
                    <div class="modal-content large">
                        <div class="modal-header">
                            <h3>✅ <?php esc_html_e( 'Verbetering Voltooid!', 'writgocms' ); ?></h3>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <!-- Stats Overview -->
                            <div id="improvement-stats" class="improvement-stats"></div>

                            <!-- Comparison Tabs -->
                            <div class="comparison-tabs">
                                <button type="button" class="tab-btn active" data-tab="before"><?php esc_html_e( 'VOOR', 'writgocms' ); ?></button>
                                <button type="button" class="tab-btn" data-tab="after"><?php esc_html_e( 'NA', 'writgocms' ); ?></button>
                                <button type="button" class="tab-btn" data-tab="changes"><?php esc_html_e( 'WIJZIGINGEN', 'writgocms' ); ?></button>
                            </div>

                            <!-- Tab Content -->
                            <div id="comparison-content" class="comparison-content">
                                <div class="tab-panel active" data-panel="before"></div>
                                <div class="tab-panel" data-panel="after"></div>
                                <div class="tab-panel" data-panel="changes"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button modal-back">↩️ <?php esc_html_e( 'Terug naar Lijst', 'writgocms' ); ?></button>
                            <button type="button" id="btn-save-draft" class="button">💾 <?php esc_html_e( 'Opslaan als Concept', 'writgocms' ); ?></button>
                            <button type="button" id="btn-publish" class="button button-primary">🚀 <?php esc_html_e( 'Direct Publiceren', 'writgocms' ); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Improvement Modal -->
                <div id="bulk-modal" class="post-updater-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🔄 <?php esc_html_e( 'Bulk Verbeteren', 'writgocms' ); ?></h3>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p id="bulk-selected-info"></p>
                            <div class="bulk-options">
                                <label class="option-item">
                                    <input type="checkbox" name="bulk_update_dates" checked>
                                    <span>🔄 <?php printf( esc_html__( 'Update jaartallen naar %s', 'writgocms' ), esc_html( gmdate( 'Y' ) ) ); ?></span>
                                </label>
                                <label class="option-item">
                                    <input type="checkbox" name="bulk_optimize_seo" checked>
                                    <span>📊 <?php esc_html_e( 'Verbeter alle SEO scores', 'writgocms' ); ?></span>
                                </label>
                                <label class="option-item">
                                    <input type="checkbox" name="bulk_extend_content" checked>
                                    <span>📝 <?php esc_html_e( 'Verleng artikelen naar 1500+ woorden', 'writgocms' ); ?></span>
                                </label>
                                <label class="option-item">
                                    <input type="checkbox" name="bulk_add_faq">
                                    <span>❓ <?php esc_html_e( 'Voeg FAQ secties toe', 'writgocms' ); ?></span>
                                </label>
                            </div>
                            <div id="bulk-progress" class="bulk-progress" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <p class="progress-text"></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button modal-cancel"><?php esc_html_e( 'Annuleer', 'writgocms' ); ?></button>
                            <button type="button" id="btn-start-bulk" class="button button-primary">▶️ <?php esc_html_e( 'Start Bulk Actie', 'writgocms' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render stats page
     */
    public function render_stats_page() {
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">📊</span>
                <?php esc_html_e( 'Usage Statistics', 'writgocms' ); ?>
            </h1>

            <div class="aiml-tab-content">
                <?php $this->render_stats_tab(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">⚙️</span>
                <?php esc_html_e( 'WritgoAI Settings', 'writgocms' ); ?>
            </h1>

            <div class="aiml-tab-content">
                <?php $this->render_settings_tab(); ?>
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
                                <option value="1792x1024" <?php selected( get_option( 'writgocms_image_size', '1024x1024' ), '1792x1024' ); ?>>1792x1024</option>
                                <option value="1024x1792" <?php selected( get_option( 'writgocms_image_size', '1024x1024' ), '1024x1792' ); ?>>1024x1792</option>
                                <option value="512x512" <?php selected( get_option( 'writgocms_image_size', '1024x1024' ), '512x512' ); ?>>512x512</option>
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
                                <option value="hd" <?php selected( get_option( 'writgocms_image_quality', 'standard' ), 'hd' ); ?>><?php esc_html_e( 'HD', 'writgocms' ); ?></option>
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

    /**
     * Render content planner tab
     */
    private function render_content_planner_tab() {
        ?>
        <div class="content-planner-dashboard">
            <h2><?php esc_html_e( 'Topical Authority Map Generator', 'writgocms' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Generate an AI-powered content plan for your website. Enter your niche and let AI create a comprehensive topical authority map with pillar content and cluster articles.', 'writgocms' ); ?>
            </p>

            <div class="content-planner-grid">
                <!-- Input Section -->
                <div class="content-planner-input">
                    <div class="planner-card">
                        <h3>🎯 <?php esc_html_e( 'Define Your Niche', 'writgocms' ); ?></h3>
                        
                        <div class="planner-field">
                            <label for="planner-niche"><?php esc_html_e( 'Main Niche/Topic', 'writgocms' ); ?></label>
                            <input type="text" id="planner-niche" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Digital Marketing, Home Fitness, Sustainable Living', 'writgocms' ); ?>">
                            <p class="description"><?php esc_html_e( 'Enter the main topic or niche for your content strategy.', 'writgocms' ); ?></p>
                        </div>

                        <div class="planner-field">
                            <label for="planner-website-type"><?php esc_html_e( 'Website Type', 'writgocms' ); ?></label>
                            <select id="planner-website-type">
                                <option value="blog"><?php esc_html_e( 'Blog / Content Site', 'writgocms' ); ?></option>
                                <option value="ecommerce"><?php esc_html_e( 'E-commerce / Online Store', 'writgocms' ); ?></option>
                                <option value="saas"><?php esc_html_e( 'SaaS / Software Company', 'writgocms' ); ?></option>
                                <option value="agency"><?php esc_html_e( 'Agency / Service Provider', 'writgocms' ); ?></option>
                                <option value="portfolio"><?php esc_html_e( 'Portfolio / Personal Brand', 'writgocms' ); ?></option>
                                <option value="news"><?php esc_html_e( 'News / Media Site', 'writgocms' ); ?></option>
                            </select>
                        </div>

                        <div class="planner-field">
                            <label for="planner-audience"><?php esc_html_e( 'Target Audience (Optional)', 'writgocms' ); ?></label>
                            <textarea id="planner-audience" rows="2" placeholder="<?php esc_attr_e( 'e.g., Small business owners aged 30-50 looking to grow their online presence', 'writgocms' ); ?>"></textarea>
                        </div>

                        <div class="planner-actions">
                            <button type="button" id="generate-topical-map" class="button button-primary button-hero">
                                ✨ <?php esc_html_e( 'Generate Topical Authority Map', 'writgocms' ); ?>
                            </button>
                            <span class="planner-status"></span>
                        </div>
                    </div>

                    <!-- Saved Plans Section -->
                    <div class="planner-card">
                        <h3>📁 <?php esc_html_e( 'Saved Content Plans', 'writgocms' ); ?></h3>
                        <div id="saved-plans-list">
                            <p class="no-plans"><?php esc_html_e( 'No saved content plans yet. Generate a topical map to get started!', 'writgocms' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="content-planner-results" style="display: none;">
                    <div class="planner-card">
                        <div class="results-header">
                            <h3>🗺️ <?php esc_html_e( 'Your Topical Authority Map', 'writgocms' ); ?></h3>
                            <div class="results-actions">
                                <button type="button" id="save-content-plan" class="button button-secondary">
                                    💾 <?php esc_html_e( 'Save Plan', 'writgocms' ); ?>
                                </button>
                                <button type="button" id="export-content-plan" class="button button-secondary">
                                    📤 <?php esc_html_e( 'Export JSON', 'writgocms' ); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="topical-map-content">
                            <!-- Dynamically populated -->
                        </div>
                    </div>

                    <!-- Content Detail Panel -->
                    <div class="planner-card" id="content-detail-panel" style="display: none;">
                        <h3>📝 <?php esc_html_e( 'Article Content Plan', 'writgocms' ); ?></h3>
                        <div id="content-detail-result">
                            <!-- Dynamically populated -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Plan Modal -->
            <div id="save-plan-modal" class="planner-modal" style="display: none;">
                <div class="planner-modal-content">
                    <h3><?php esc_html_e( 'Save Content Plan', 'writgocms' ); ?></h3>
                    <div class="planner-field">
                        <label for="plan-name"><?php esc_html_e( 'Plan Name', 'writgocms' ); ?></label>
                        <input type="text" id="plan-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Q1 2024 Content Strategy', 'writgocms' ); ?>">
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="confirm-save-plan" class="button button-primary"><?php esc_html_e( 'Save', 'writgocms' ); ?></button>
                        <button type="button" id="cancel-save-plan" class="button"><?php esc_html_e( 'Cancel', 'writgocms' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize admin settings
WritgoCMS_AIML_Admin_Settings::get_instance();
