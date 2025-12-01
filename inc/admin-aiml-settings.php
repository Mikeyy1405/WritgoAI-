<?php
/**
 * AIML Admin Settings Panel
 *
 * Admin interface for AIMLAPI configuration.
 * Nederlandse versie - Dutch interface for WritgoAI.
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
     * Add admin menu - Dutch menu structure
     */
    public function add_admin_menu() {
        // Add main menu item.
        add_menu_page(
            'WritgoAI Dashboard',
            'WritgoAI',
            'manage_options',
            'writgocms-aiml',
            array( $this, 'render_dashboard_page' ),
            'dashicons-welcome-widgets-menus',
            26 // Position after Pages.
        );

        // Add Dashboard submenu (Dutch: Dashboard).
        add_submenu_page(
            'writgocms-aiml',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'writgocms-aiml',
            array( $this, 'render_dashboard_page' )
        );

        // Add Website Analysis submenu (Dutch: Website Analyse).
        add_submenu_page(
            'writgocms-aiml',
            'Website Analyse',
            '🔍 Website Analyse',
            'manage_options',
            'writgocms-aiml-analyse',
            array( $this, 'render_analyse_page' )
        );

        // Add Content Plan submenu (Dutch: Contentplan).
        add_submenu_page(
            'writgocms-aiml',
            'Contentplan',
            '📋 Contentplan',
            'manage_options',
            'writgocms-aiml-contentplan',
            array( $this, 'render_contentplan_page' )
        );

        // Add Generated Content submenu (Dutch: Gegenereerde Content).
        add_submenu_page(
            'writgocms-aiml',
            'Gegenereerde Content',
            '✍️ Gegenereerde Content',
            'manage_options',
            'writgocms-aiml-generated',
            array( $this, 'render_generated_content_page' )
        );

        // Add Usage Statistics submenu (Dutch: Statistieken).
        add_submenu_page(
            'writgocms-aiml',
            'Statistieken',
            '📈 Statistieken',
            'manage_options',
            'writgocms-aiml-stats',
            array( $this, 'render_stats_page' )
        );

        // Add Settings submenu (Dutch: Instellingen).
        add_submenu_page(
            'writgocms-aiml',
            'Instellingen',
            '⚙️ Instellingen',
            'manage_options',
            'writgocms-aiml-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // AIMLAPI Settings - API key is now handled server-side.
        // Note: The 'writgocms_aimlapi_key' option is kept in the database for backward
        // compatibility with existing installations. It's used as a fallback by the
        // AIML provider if no server-side key is configured. The UI for entering the key
        // has been removed as we now prefer server-side configuration via WRITGO_AIML_API_KEY
        // constant or environment variable.
        
        // WritgoAI API Settings.
        register_setting( 'writgocms_aiml_settings', 'writgocms_license_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_api_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
        
        register_setting( 'writgocms_aiml_settings', 'writgocms_default_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_default_image_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_text_temperature', array( 'sanitize_callback' => 'floatval' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_text_max_tokens', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_image_size', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_image_quality', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        // New Dutch settings
        register_setting( 'writgocms_aiml_settings', 'writgocms_website_theme', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_target_audience', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_content_tone', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_content_categories', array( 'sanitize_callback' => array( $this, 'sanitize_categories' ) ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_items_per_analysis', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_weekly_updates', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_notifications', array( 'sanitize_callback' => 'absint' ) );

        // Gutenberg Toolbar Settings.
        register_setting( 'writgocms_aiml_settings', 'writgocms_toolbar_enabled', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_toolbar_buttons', array( 'sanitize_callback' => array( $this, 'sanitize_toolbar_buttons' ) ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_toolbar_rewrite_tone', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'writgocms_aiml_settings', 'writgocms_toolbar_links_limit', array( 'sanitize_callback' => 'absint' ) );
        
        // Add AJAX handlers for credit operations.
        add_action( 'wp_ajax_writgoai_get_credits', array( $this, 'ajax_get_credits' ) );
        add_action( 'wp_ajax_writgoai_refresh_credits', array( $this, 'ajax_refresh_credits' ) );
    }

    /**
     * Sanitize toolbar buttons settings
     *
     * @param array $input Toolbar buttons input.
     * @return array
     */
    public function sanitize_toolbar_buttons( $input ) {
        if ( ! is_array( $input ) ) {
            return array(
                'rewrite'     => true,
                'links'       => true,
                'image'       => true,
                'rewrite_all' => true,
            );
        }
        return array(
            'rewrite'     => isset( $input['rewrite'] ) ? (bool) $input['rewrite'] : false,
            'links'       => isset( $input['links'] ) ? (bool) $input['links'] : false,
            'image'       => isset( $input['image'] ) ? (bool) $input['image'] : false,
            'rewrite_all' => isset( $input['rewrite_all'] ) ? (bool) $input['rewrite_all'] : false,
        );
    }

    /**
     * Sanitize categories array
     *
     * @param array $input Categories input.
     * @return array
     */
    public function sanitize_categories( $input ) {
        if ( ! is_array( $input ) ) {
            return array( 'informatief', 'reviews', 'top_lijstjes', 'vergelijkingen' );
        }
        return array_map( 'sanitize_text_field', $input );
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
            'writgoai_page_writgocms-aiml-analyse',
            'writgoai_page_writgocms-aiml-contentplan',
            'writgoai_page_writgocms-aiml-generated',
            'writgoai_page_writgocms-aiml-stats',
            'writgoai_page_writgocms-aiml-settings',
        );

        // Also check for alternative hook formats
        if ( strpos( $hook, 'writgocms-aiml' ) === false ) {
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
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'restUrl'      => esc_url_raw( rest_url( 'writgo/v1/' ) ),
                'nonce'        => wp_create_nonce( 'writgocms_aiml_nonce' ),
                'restNonce'    => wp_create_nonce( 'wp_rest' ),
                'i18n'         => array(
                    // Dutch translations
                    'validating'              => 'Valideren...',
                    'valid'                   => 'Geldig!',
                    'invalid'                 => 'Ongeldig',
                    'error'                   => 'Fout',
                    'generating'              => 'Genereren...',
                    'success'                 => 'Gelukt!',
                    'testPrompt'              => 'Schrijf een korte paragraaf over kunstmatige intelligentie.',
                    'imagePrompt'             => 'Een mooie zonsondergang boven de bergen',
                    'generatingMap'           => 'Contentplan wordt gegenereerd...',
                    'generatingPlan'          => 'Gedetailleerd plan wordt gegenereerd...',
                    'savePlan'                => 'Plan Opslaan',
                    'planSaved'               => 'Contentplan succesvol opgeslagen!',
                    'planDeleted'             => 'Contentplan verwijderd.',
                    'confirmDelete'           => 'Weet je zeker dat je dit contentplan wilt verwijderen?',
                    'noNiche'                 => 'Voer een niche/onderwerp in.',
                    'noPlanName'              => 'Voer een plannaam in.',
                    'pillarContent'           => 'Hoofdonderwerpen',
                    'clusterArticles'         => 'Gerelateerde Artikelen',
                    'keywords'                => 'Zoekwoorden',
                    'priority'                => 'Prioriteit',
                    'contentGaps'             => 'Ontbrekende Content',
                    'recommendedOrder'        => 'Aanbevolen Publicatievolgorde',
                    'generateDetailedPlan'    => 'Genereer Gedetailleerd Plan',
                    'generateContent'         => 'Genereer Content',
                    'high'                    => 'Hoog',
                    'medium'                  => 'Gemiddeld',
                    'low'                     => 'Laag',
                    // Sitemap analysis translations
                    'analyzing'               => 'Analyseren...',
                    'analysisComplete'        => 'Analyse voltooid!',
                    'analysisError'           => 'Fout bij analyse',
                    'startAnalysis'           => 'Start Website Analyse',
                    'refreshAnalysis'         => 'Ververs Analyse',
                    'noSitemap'               => 'Geen sitemap gevonden. Zorg ervoor dat WordPress sitemaps zijn ingeschakeld.',
                    // Content categories
                    'informatief'             => 'Informatieve Content',
                    'reviews'                 => 'Reviews',
                    'topLijstjes'             => 'Top Lijstjes',
                    'vergelijkingen'          => 'Vergelijkingen',
                    // Actions
                    'publishDraft'            => 'Publiceer als concept',
                    'publishNow'              => 'Direct publiceren',
                    'preview'                 => 'Voorbeeld',
                    'edit'                    => 'Bewerken',
                    'delete'                  => 'Verwijderen',
                    'load'                    => 'Laden',
                    'export'                  => 'Exporteren',
                    'save'                    => 'Opslaan',
                    'cancel'                  => 'Annuleren',
                    // Rate limiting / usage
                    'rateLimitExceeded'       => 'Je hebt je dagelijkse limiet bereikt. Probeer het morgen opnieuw.',
                    'serviceActive'           => 'AI Service Actief',
                    'serviceInactive'         => 'AI Service Niet Geconfigureerd',
                ),
            )
        );
    }

    /**
     * Render dashboard page - Dutch interface with site analysis
     */
    public function render_dashboard_page() {
        // Use new beginner-friendly dashboard template.
        $dashboard_template = WRITGOCMS_DIR . 'inc/admin/views/dashboard.php';
        if ( file_exists( $dashboard_template ) ) {
            include $dashboard_template;
            return;
        }

        // Use new Dashboard class for rendering.
        if ( class_exists( 'WritgoCMS_Dashboard' ) ) {
            $dashboard = WritgoCMS_Dashboard::get_instance();
            $dashboard->render();
            return;
        }

        // Fallback to old dashboard if class not loaded.
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
        $service_active = $this->is_ai_service_active();
        $site_analysis = get_option( 'writgocms_site_analysis', array() );
        $has_analysis = ! empty( $site_analysis );
        $content_plan = get_option( 'writgocms_content_plan', array() );
        ?>
        <div class="wrap writgocms-aiml-settings writgocms-dashboard">
            <h1 class="aiml-header">
                <span class="aiml-logo">🤖</span>
                Welkom bij WritgoAI
            </h1>

            <div class="aiml-tab-content">
                <!-- AI Service Status -->
                <div class="dashboard-service-status">
                    <?php if ( $service_active ) : ?>
                    <span class="status-badge success">✓ AI Service Actief</span>
                    <?php else : ?>
                    <div class="notice notice-warning inline" style="margin-bottom: 20px;">
                        <p><strong>⚠️ AI Service Niet Geconfigureerd:</strong> Neem contact op met de beheerder om de AI service te configureren.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! $has_analysis ) : ?>
                <!-- Welcome Screen - First Time -->
                <div class="dashboard-welcome writgoai-welcome-box">
                    <h2>🤖 Welkom bij WritgoAI</h2>
                    <p>Jouw AI-gestuurde Content Marketing Assistent</p>
                    <p class="welcome-subtitle">Laat WritgoAI je website analyseren en een gepersonaliseerd contentplan genereren op basis van je bestaande content.</p>
                    <div class="welcome-action">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="button button-primary button-hero">
                            🔍 Start Website Analyse
                        </a>
                    </div>
                </div>
                <?php else : ?>
                <!-- Dashboard with Analysis Results -->
                <div class="dashboard-analysis-results">
                    <div class="analysis-header-card">
                        <h2>📊 Website Analyse Resultaat</h2>
                        <div class="analysis-summary">
                            <div class="summary-item">
                                <span class="summary-label">Website thema:</span>
                                <span class="summary-value"><?php echo esc_html( isset( $site_analysis['theme'] ) ? $site_analysis['theme'] : 'Onbekend' ); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Bestaande content:</span>
                                <span class="summary-value"><?php echo esc_html( isset( $site_analysis['total_posts'] ) ? $site_analysis['total_posts'] : 0 ); ?> artikelen</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Gevonden content gaps:</span>
                                <span class="summary-value"><?php echo esc_html( isset( $content_plan['total_items'] ) ? $content_plan['total_items'] : 0 ); ?> onderwerpen</span>
                            </div>
                        </div>
                        <div class="analysis-actions">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="button">🔄 Ververs Analyse</a>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-settings' ) ); ?>" class="button">⚙️ Instellingen</a>
                        </div>
                    </div>

                    <!-- Content Plan Categories -->
                    <?php if ( ! empty( $content_plan ) && isset( $content_plan['categories'] ) ) : ?>
                    <div class="content-plan-overview">
                        <h3>📋 Nieuw Contentplan (<?php echo esc_html( isset( $content_plan['total_items'] ) ? $content_plan['total_items'] : 0 ); ?> items)</h3>
                        
                        <div class="content-categories-grid">
                            <!-- Informatieve Content -->
                            <?php if ( ! empty( $content_plan['categories']['informatief'] ) ) : ?>
                            <div class="content-category category-informatief">
                                <div class="category-header">
                                    <span class="category-icon">📚</span>
                                    <h4>Informatieve Content</h4>
                                    <span class="category-count"><?php echo count( $content_plan['categories']['informatief'] ); ?> items</span>
                                </div>
                                <ul class="category-items">
                                    <?php foreach ( array_slice( $content_plan['categories']['informatief'], 0, 3 ) as $item ) : ?>
                                    <li>
                                        <span class="item-title"><?php echo esc_html( $item['title'] ); ?></span>
                                        <button type="button" class="button button-small generate-content-btn" data-item="<?php echo esc_attr( wp_json_encode( $item ) ); ?>">Genereer</button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ( count( $content_plan['categories']['informatief'] ) > 3 ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=informatief' ) ); ?>" class="see-more">Bekijk alle →</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Reviews -->
                            <?php if ( ! empty( $content_plan['categories']['reviews'] ) ) : ?>
                            <div class="content-category category-reviews">
                                <div class="category-header">
                                    <span class="category-icon">⭐</span>
                                    <h4>Reviews</h4>
                                    <span class="category-count"><?php echo count( $content_plan['categories']['reviews'] ); ?> items</span>
                                </div>
                                <ul class="category-items">
                                    <?php foreach ( array_slice( $content_plan['categories']['reviews'], 0, 3 ) as $item ) : ?>
                                    <li>
                                        <span class="item-title"><?php echo esc_html( $item['title'] ); ?></span>
                                        <button type="button" class="button button-small generate-content-btn" data-item="<?php echo esc_attr( wp_json_encode( $item ) ); ?>">Genereer</button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ( count( $content_plan['categories']['reviews'] ) > 3 ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=reviews' ) ); ?>" class="see-more">Bekijk alle →</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Top Lijstjes -->
                            <?php if ( ! empty( $content_plan['categories']['top_lijstjes'] ) ) : ?>
                            <div class="content-category category-top-lijstjes">
                                <div class="category-header">
                                    <span class="category-icon">🏆</span>
                                    <h4>Top Lijstjes</h4>
                                    <span class="category-count"><?php echo count( $content_plan['categories']['top_lijstjes'] ); ?> items</span>
                                </div>
                                <ul class="category-items">
                                    <?php foreach ( array_slice( $content_plan['categories']['top_lijstjes'], 0, 3 ) as $item ) : ?>
                                    <li>
                                        <span class="item-title"><?php echo esc_html( $item['title'] ); ?></span>
                                        <button type="button" class="button button-small generate-content-btn" data-item="<?php echo esc_attr( wp_json_encode( $item ) ); ?>">Genereer</button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ( count( $content_plan['categories']['top_lijstjes'] ) > 3 ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=top_lijstjes' ) ); ?>" class="see-more">Bekijk alle →</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Vergelijkingen -->
                            <?php if ( ! empty( $content_plan['categories']['vergelijkingen'] ) ) : ?>
                            <div class="content-category category-vergelijkingen">
                                <div class="category-header">
                                    <span class="category-icon">⚖️</span>
                                    <h4>Vergelijkingen</h4>
                                    <span class="category-count"><?php echo count( $content_plan['categories']['vergelijkingen'] ); ?> items</span>
                                </div>
                                <ul class="category-items">
                                    <?php foreach ( array_slice( $content_plan['categories']['vergelijkingen'], 0, 3 ) as $item ) : ?>
                                    <li>
                                        <span class="item-title"><?php echo esc_html( $item['title'] ); ?></span>
                                        <button type="button" class="button button-small generate-content-btn" data-item="<?php echo esc_attr( wp_json_encode( $item ) ); ?>">Genereer</button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ( count( $content_plan['categories']['vergelijkingen'] ) > 3 ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=vergelijkingen' ) ); ?>" class="see-more">Bekijk alle →</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <span class="stat-icon">📝</span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo esc_html( $totals['text'] ); ?></span>
                            <span class="stat-label">Tekst Generaties</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">🖼️</span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo esc_html( $totals['image'] ); ?></span>
                            <span class="stat-label">Afbeelding Generaties</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">📊</span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo esc_html( $totals['text'] + $totals['image'] ); ?></span>
                            <span class="stat-label">Totaal Verzoeken</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">📁</span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo esc_html( $plans_count ); ?></span>
                            <span class="stat-label">Opgeslagen Plannen</span>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Widgets Grid -->
                <div class="dashboard-widgets">
                    <!-- Settings Widget -->
                    <div class="dashboard-widget widget-primary">
                        <div class="widget-icon">⚙️</div>
                        <div class="widget-content">
                            <h3>Instellingen</h3>
                            <p>Configureer je AI model voorkeuren en bekijk je usage.</p>
                            <?php if ( $service_active ) : ?>
                                <span class="widget-badge success">Service Actief</span>
                            <?php else : ?>
                                <span class="widget-badge warning">Service Niet Geconfigureerd</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-settings' ) ); ?>" class="widget-button">
                            Open Instellingen
                        </a>
                    </div>

                    <!-- Website Analysis Widget -->
                    <div class="dashboard-widget widget-primary">
                        <div class="widget-icon">🔍</div>
                        <div class="widget-content">
                            <h3>Website Analyse</h3>
                            <p>Analyseer je website en ontdek content mogelijkheden.</p>
                            <?php if ( $has_analysis ) : ?>
                                <span class="widget-badge info">Laatste analyse: <?php echo esc_html( isset( $site_analysis['date'] ) ? $site_analysis['date'] : 'Onbekend' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="widget-button">
                            <?php echo $has_analysis ? 'Ververs Analyse' : 'Start Analyse'; ?>
                        </a>
                    </div>

                    <!-- Content Plan Widget -->
                    <div class="dashboard-widget widget-secondary">
                        <div class="widget-icon">📋</div>
                        <div class="widget-content">
                            <h3>Contentplan</h3>
                            <p>Bekijk en beheer je gegenereerde contentplan items.</p>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="widget-button">
                            Bekijk Contentplan
                        </a>
                    </div>

                    <!-- Statistics Widget -->
                    <div class="dashboard-widget widget-secondary">
                        <div class="widget-icon">📈</div>
                        <div class="widget-content">
                            <h3>Statistieken</h3>
                            <p>Bekijk gedetailleerde gebruiksstatistieken en activiteit.</p>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-stats' ) ); ?>" class="widget-button">
                            Bekijk Statistieken
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-section">
                    <h3>Recente Activiteit</h3>
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
                        <p class="no-activity">Nog geen activiteit. Begin met content genereren om je gebruiksgeschiedenis te zien.</p>
                        <?php
                    else :
                        ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Type</th>
                                    <th>Model</th>
                                    <th>Aantal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( array_slice( $rows, 0, 5 ) as $row ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $row['date'] ); ?></td>
                                    <td><?php echo 'text' === $row['type'] ? esc_html( '📝 Tekst' ) : esc_html( '🖼️ Afbeelding' ); ?></td>
                                    <td><?php echo esc_html( $row['model'] ); ?></td>
                                    <td><?php echo esc_html( $row['count'] ); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p style="margin-top: 15px;">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-stats' ) ); ?>">Bekijk alle activiteit →</a>
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
     * Render website analysis page (Dutch: Website Analyse)
     */
    public function render_analyse_page() {
        $site_analysis = get_option( 'writgocms_site_analysis', array() );
        $has_analysis = ! empty( $site_analysis );
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">🔍</span>
                Website Analyse
            </h1>

            <div class="aiml-tab-content">
                <div class="analysis-dashboard">
                    <!-- Analysis Input -->
                    <div class="analysis-input-section">
                        <div class="planner-card">
                            <h3>🔍 Website Analyse Starten</h3>
                            <p class="description">
                                WritgoAI analyseert automatisch je WordPress sitemap om bestaande content te identificeren en nieuwe content mogelijkheden te vinden.
                            </p>
                            
                            <div class="analysis-options">
                                <div class="planner-field">
                                    <label for="manual-theme">Website Thema (optioneel)</label>
                                    <input type="text" id="manual-theme" class="regular-text" placeholder="Bijv. Auto's, Gezondheid, Technologie..." value="<?php echo esc_attr( isset( $site_analysis['theme'] ) ? $site_analysis['theme'] : '' ); ?>">
                                    <p class="description">Laat leeg voor automatische detectie of voer handmatig je hoofdthema in.</p>
                                </div>
                            </div>

                            <div class="planner-actions">
                                <button type="button" id="start-site-analysis" class="button button-primary button-hero">
                                    🔍 <?php echo $has_analysis ? 'Ververs Analyse' : 'Start Website Analyse'; ?>
                                </button>
                                <span class="analysis-status"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Analysis Results -->
                    <?php if ( $has_analysis ) : ?>
                    <div class="analysis-results-section">
                        <div class="planner-card">
                            <h3>📊 Analyse Resultaten</h3>
                            
                            <div class="analysis-summary-grid">
                                <div class="summary-card">
                                    <span class="summary-icon">📄</span>
                                    <div class="summary-data">
                                        <span class="summary-number"><?php echo esc_html( isset( $site_analysis['total_posts'] ) ? $site_analysis['total_posts'] : 0 ); ?></span>
                                        <span class="summary-label">Totaal Artikelen</span>
                                    </div>
                                </div>
                                <div class="summary-card">
                                    <span class="summary-icon">📁</span>
                                    <div class="summary-data">
                                        <span class="summary-number"><?php echo esc_html( isset( $site_analysis['total_pages'] ) ? $site_analysis['total_pages'] : 0 ); ?></span>
                                        <span class="summary-label">Pagina's</span>
                                    </div>
                                </div>
                                <div class="summary-card">
                                    <span class="summary-icon">🏷️</span>
                                    <div class="summary-data">
                                        <span class="summary-number"><?php echo esc_html( isset( $site_analysis['categories_count'] ) ? $site_analysis['categories_count'] : 0 ); ?></span>
                                        <span class="summary-label">Categorieën</span>
                                    </div>
                                </div>
                                <div class="summary-card">
                                    <span class="summary-icon">🎯</span>
                                    <div class="summary-data">
                                        <span class="summary-value"><?php echo esc_html( isset( $site_analysis['theme'] ) ? $site_analysis['theme'] : 'Onbekend' ); ?></span>
                                        <span class="summary-label">Gedetecteerd Thema</span>
                                    </div>
                                </div>
                            </div>

                            <?php if ( isset( $site_analysis['top_categories'] ) && ! empty( $site_analysis['top_categories'] ) ) : ?>
                            <div class="top-categories">
                                <h4>Top Categorieën</h4>
                                <div class="categories-list">
                                    <?php foreach ( $site_analysis['top_categories'] as $cat ) : ?>
                                    <span class="category-tag"><?php echo esc_html( $cat['name'] ); ?> (<?php echo esc_html( $cat['count'] ); ?>)</span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="analysis-meta">
                                <p><strong>Laatste analyse:</strong> <?php echo esc_html( isset( $site_analysis['date'] ) ? $site_analysis['date'] : 'Onbekend' ); ?></p>
                            </div>
                        </div>

                        <!-- Generate Content Plan Button -->
                        <div class="planner-card">
                            <h3>📋 Contentplan Genereren</h3>
                            <p class="description">
                                Op basis van de analyse kan WritgoAI een contentplan genereren met nieuwe artikel ideeën, verdeeld over 4 categorieën.
                            </p>
                            
                            <div class="content-categories-preview">
                                <div class="category-preview">
                                    <span class="category-icon">📚</span>
                                    <span class="category-name">Informatief</span>
                                </div>
                                <div class="category-preview">
                                    <span class="category-icon">⭐</span>
                                    <span class="category-name">Reviews</span>
                                </div>
                                <div class="category-preview">
                                    <span class="category-icon">🏆</span>
                                    <span class="category-name">Top Lijstjes</span>
                                </div>
                                <div class="category-preview">
                                    <span class="category-icon">⚖️</span>
                                    <span class="category-name">Vergelijkingen</span>
                                </div>
                            </div>

                            <div class="planner-actions">
                                <button type="button" id="generate-content-plan" class="button button-primary button-hero">
                                    ✨ Genereer Contentplan
                                </button>
                                <span class="content-plan-status"></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render content plan page (Dutch: Contentplan)
     */
    public function render_contentplan_page() {
        $content_plan = get_option( 'writgocms_content_plan', array() );
        $filter_category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">📋</span>
                Contentplan
            </h1>

            <div class="aiml-tab-content">
                <?php if ( empty( $content_plan ) || ! isset( $content_plan['categories'] ) ) : ?>
                <div class="no-content-plan">
                    <div class="empty-state">
                        <span class="empty-icon">📋</span>
                        <h3>Nog geen contentplan</h3>
                        <p>Start een website analyse om je eerste contentplan te genereren.</p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-analyse' ) ); ?>" class="button button-primary button-hero">
                            🔍 Start Website Analyse
                        </a>
                    </div>
                </div>
                <?php else : ?>
                
                <!-- Category Filters -->
                <div class="content-plan-filters">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="filter-btn <?php echo empty( $filter_category ) ? 'active' : ''; ?>">
                        Alle (<?php echo esc_html( $content_plan['total_items'] ); ?>)
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=informatief' ) ); ?>" class="filter-btn filter-informatief <?php echo 'informatief' === $filter_category ? 'active' : ''; ?>">
                        📚 Informatief (<?php echo isset( $content_plan['categories']['informatief'] ) ? count( $content_plan['categories']['informatief'] ) : 0; ?>)
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=reviews' ) ); ?>" class="filter-btn filter-reviews <?php echo 'reviews' === $filter_category ? 'active' : ''; ?>">
                        ⭐ Reviews (<?php echo isset( $content_plan['categories']['reviews'] ) ? count( $content_plan['categories']['reviews'] ) : 0; ?>)
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=top_lijstjes' ) ); ?>" class="filter-btn filter-top-lijstjes <?php echo 'top_lijstjes' === $filter_category ? 'active' : ''; ?>">
                        🏆 Top Lijstjes (<?php echo isset( $content_plan['categories']['top_lijstjes'] ) ? count( $content_plan['categories']['top_lijstjes'] ) : 0; ?>)
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan&category=vergelijkingen' ) ); ?>" class="filter-btn filter-vergelijkingen <?php echo 'vergelijkingen' === $filter_category ? 'active' : ''; ?>">
                        ⚖️ Vergelijkingen (<?php echo isset( $content_plan['categories']['vergelijkingen'] ) ? count( $content_plan['categories']['vergelijkingen'] ) : 0; ?>)
                    </a>
                </div>

                <!-- Content Plan Items -->
                <div class="content-plan-items">
                    <?php
                    $categories_to_show = array( 'informatief', 'reviews', 'top_lijstjes', 'vergelijkingen' );
                    if ( ! empty( $filter_category ) && in_array( $filter_category, $categories_to_show, true ) ) {
                        $categories_to_show = array( $filter_category );
                    }

                    foreach ( $categories_to_show as $category ) :
                        if ( empty( $content_plan['categories'][ $category ] ) ) {
                            continue;
                        }
                        
                        $category_labels = array(
                            'informatief'    => array( 'icon' => '📚', 'label' => 'Informatieve Content', 'color' => 'blue' ),
                            'reviews'        => array( 'icon' => '⭐', 'label' => 'Reviews', 'color' => 'gold' ),
                            'top_lijstjes'   => array( 'icon' => '🏆', 'label' => 'Top Lijstjes', 'color' => 'green' ),
                            'vergelijkingen' => array( 'icon' => '⚖️', 'label' => 'Vergelijkingen', 'color' => 'purple' ),
                        );
                        $cat_info = $category_labels[ $category ];
                    ?>
                    <div class="content-category-section category-<?php echo esc_attr( $category ); ?>">
                        <div class="category-section-header">
                            <span class="category-icon"><?php echo esc_html( $cat_info['icon'] ); ?></span>
                            <h3><?php echo esc_html( $cat_info['label'] ); ?></h3>
                            <span class="category-count"><?php echo count( $content_plan['categories'][ $category ] ); ?> items</span>
                        </div>
                        
                        <div class="content-items-list">
                            <?php foreach ( $content_plan['categories'][ $category ] as $item ) : ?>
                            <div class="content-item" data-item="<?php echo esc_attr( wp_json_encode( $item ) ); ?>">
                                <div class="item-main">
                                    <h4 class="item-title"><?php echo esc_html( $item['title'] ); ?></h4>
                                    <?php if ( ! empty( $item['description'] ) ) : ?>
                                    <p class="item-description"><?php echo esc_html( $item['description'] ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $item['keywords'] ) ) : ?>
                                    <div class="item-keywords">
                                        <?php foreach ( $item['keywords'] as $keyword ) : ?>
                                        <span class="keyword-tag"><?php echo esc_html( $keyword ); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-actions">
                                    <button type="button" class="button button-primary generate-content-btn" data-item="<?php echo esc_attr( wp_json_encode( $item ) ); ?>">
                                        ✨ Genereer Content
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render generated content page (Dutch: Gegenereerde Content)
     */
    public function render_generated_content_page() {
        $generated_content = get_option( 'writgocms_generated_content', array() );
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">✍️</span>
                Gegenereerde Content
            </h1>

            <div class="aiml-tab-content">
                <?php if ( empty( $generated_content ) ) : ?>
                <div class="no-generated-content">
                    <div class="empty-state">
                        <span class="empty-icon">✍️</span>
                        <h3>Nog geen gegenereerde content</h3>
                        <p>Ga naar je contentplan en klik op "Genereer Content" bij een onderwerp.</p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-aiml-contentplan' ) ); ?>" class="button button-primary button-hero">
                            📋 Bekijk Contentplan
                        </a>
                    </div>
                </div>
                <?php else : ?>
                <div class="generated-content-list">
                    <?php foreach ( array_reverse( $generated_content ) as $index => $content ) : ?>
                    <div class="generated-content-item">
                        <div class="content-header">
                            <h3><?php echo esc_html( $content['title'] ); ?></h3>
                            <span class="content-date"><?php echo esc_html( $content['generated_date'] ); ?></span>
                        </div>
                        <div class="content-meta">
                            <span class="content-category"><?php echo esc_html( $content['category'] ); ?></span>
                            <?php if ( isset( $content['post_id'] ) && $content['post_id'] > 0 ) : ?>
                            <span class="content-status published">Gepubliceerd</span>
                            <?php else : ?>
                            <span class="content-status draft">Concept</span>
                            <?php endif; ?>
                        </div>
                        <div class="content-preview">
                            <?php echo wp_kses_post( wp_trim_words( $content['content'], 50, '...' ) ); ?>
                        </div>
                        <div class="content-actions">
                            <?php if ( isset( $content['post_id'] ) && $content['post_id'] > 0 ) : ?>
                            <a href="<?php echo esc_url( get_edit_post_link( $content['post_id'] ) ); ?>" class="button">Bewerken</a>
                            <a href="<?php echo esc_url( get_permalink( $content['post_id'] ) ); ?>" class="button" target="_blank">Bekijken</a>
                            <?php else : ?>
                            <button type="button" class="button button-primary publish-content-btn" data-index="<?php echo esc_attr( $index ); ?>">Publiceer als Concept</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render stats page (Dutch: Statistieken)
     */
    public function render_stats_page() {
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">📈</span>
                Statistieken
            </h1>

            <div class="aiml-tab-content">
                <?php $this->render_stats_tab(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page (Dutch: Instellingen)
     */
    public function render_settings_page() {
        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">⚙️</span>
                WritgoAI Instellingen
            </h1>

            <div class="aiml-tab-content">
                <?php $this->render_settings_tab(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings tab (Dutch translations)
     */
    private function render_settings_tab() {
        $text_models  = $this->provider->get_text_models();
        $image_models = $this->provider->get_image_models();
        $content_categories = get_option( 'writgocms_content_categories', array( 'informatief', 'reviews', 'top_lijstjes', 'vergelijkingen' ) );
        
        // Check if AI service is configured (API key available server-side).
        $service_active = $this->is_ai_service_active();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'writgocms_aiml_settings' ); ?>

            <!-- License & API Settings Section -->
            <div class="aiml-settings-section">
                <h2>🔑 Licentie & API Instellingen</h2>
                <p class="description">
                    Configureer je WritgoAI licentie sleutel voor toegang tot credit-based features.
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_license_key">Licentie Sleutel</label>
                        </th>
                        <td>
                            <input type="text" id="writgocms_license_key" name="writgocms_license_key" value="<?php echo esc_attr( get_option( 'writgocms_license_key', '' ) ); ?>" class="regular-text" placeholder="Voer je licentie sleutel in">
                            <p class="description">Je WritgoAI licentie sleutel voor credit management en API toegang.</p>
                            <?php
                            // Show license status if available.
                            if ( class_exists( 'WritgoCMS_API_Client' ) && ! empty( get_option( 'writgocms_license_key' ) ) ) {
                                $api_client = WritgoCMS_API_Client::get_instance();
                                $status = $api_client->get_subscription_status();
                                if ( ! is_wp_error( $status ) && isset( $status['status'] ) ) :
                                    $status_class = ( 'active' === $status['status'] ) ? 'success' : 'warning';
                                    ?>
                                    <p>
                                        <span class="status-badge <?php echo esc_attr( $status_class ); ?>">
                                            <?php
                                            if ( 'active' === $status['status'] ) {
                                                echo '✓ Licentie Actief';
                                            } else {
                                                echo '⚠️ Licentie ' . esc_html( ucfirst( $status['status'] ) );
                                            }
                                            ?>
                                        </span>
                                        <?php if ( ! empty( $status['plan_name'] ) ) : ?>
                                            - <?php echo esc_html( $status['plan_name'] ); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_api_url">API URL (Optioneel)</label>
                        </th>
                        <td>
                            <input type="url" id="writgocms_api_url" name="writgocms_api_url" value="<?php echo esc_attr( get_option( 'writgocms_api_url', 'https://api.writgoai.com' ) ); ?>" class="regular-text" placeholder="https://api.writgoai.com">
                            <p class="description">API endpoint URL. Laat standaard staan tenzij je een custom server gebruikt.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- AI Service Status Section -->
            <div class="aiml-settings-section aiml-service-status">
                <h2>🤖 AI Service Status</h2>
                
                <div class="service-status-card <?php echo $service_active ? 'status-active' : 'status-inactive'; ?>">
                    <div class="status-indicator">
                        <?php if ( $service_active ) : ?>
                            <span class="status-badge success">✓ AI Service Actief</span>
                            <p class="status-description">De AI service is correct geconfigureerd en klaar voor gebruik.</p>
                        <?php else : ?>
                            <span class="status-badge warning">⚠️ AI Service Niet Geconfigureerd</span>
                            <p class="status-description">De AI service moet door een beheerder worden geconfigureerd. Neem contact op met support als je deze melding blijft zien.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Usage Dashboard -->
                <div class="usage-dashboard" id="usage-dashboard">
                    <h3>📊 API Gebruik Vandaag</h3>
                    <div class="usage-stats-container">
                        <div class="usage-stat">
                            <span class="usage-label">Requests Gebruikt</span>
                            <span class="usage-value" id="requests-used">-</span>
                        </div>
                        <div class="usage-stat">
                            <span class="usage-label">Resterende Requests</span>
                            <span class="usage-value" id="requests-remaining">-</span>
                        </div>
                        <div class="usage-stat">
                            <span class="usage-label">Dagelijks Limiet</span>
                            <span class="usage-value" id="daily-limit">-</span>
                        </div>
                    </div>
                    <div class="usage-progress-container">
                        <div class="usage-progress-bar">
                            <div class="usage-progress-fill" id="usage-progress-fill" style="width: 0%;"></div>
                        </div>
                        <p class="usage-reset-info">Limiet reset om: <span id="reset-time">-</span></p>
                    </div>
                </div>
            </div>

            <!-- AI Model Settings Section -->
            <div class="aiml-settings-section">
                <h2>🧠 AI Model Instellingen</h2>
                <p class="description">
                    Selecteer je voorkeurs AI modellen voor tekst- en afbeeldingsgeneratie.
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_default_model">Tekst AI Model</label>
                        </th>
                        <td>
                            <select id="writgocms_default_model" name="writgocms_default_model">
                                <?php foreach ( $text_models as $model_key => $model_name ) : ?>
                                    <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_default_model', 'gpt-4o' ), $model_key ); ?>>
                                        <?php echo esc_html( $model_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Selecteer het standaard AI model voor tekstgeneratie.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Website Settings Section -->
            <div class="aiml-settings-section">
                <h2>🌐 Website Instellingen</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_website_theme">Hoofdthema</label>
                        </th>
                        <td>
                            <input type="text" id="writgocms_website_theme" name="writgocms_website_theme" value="<?php echo esc_attr( get_option( 'writgocms_website_theme', '' ) ); ?>" class="regular-text" placeholder="Automatisch detecteren of handmatig invoeren">
                            <p class="description">Laat leeg voor automatische detectie of voer je hoofdthema handmatig in.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_target_audience">Doelgroep</label>
                        </th>
                        <td>
                            <textarea id="writgocms_target_audience" name="writgocms_target_audience" rows="2" class="large-text" placeholder="Bijv. Ondernemers tussen 30-50 jaar die geïnteresseerd zijn in..."><?php echo esc_textarea( get_option( 'writgocms_target_audience', '' ) ); ?></textarea>
                            <p class="description">Beschrijf je doelgroep voor betere content suggesties.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_content_tone">Toon</label>
                        </th>
                        <td>
                            <select id="writgocms_content_tone" name="writgocms_content_tone">
                                <option value="professioneel" <?php selected( get_option( 'writgocms_content_tone', 'professioneel' ), 'professioneel' ); ?>>Professioneel</option>
                                <option value="informeel" <?php selected( get_option( 'writgocms_content_tone' ), 'informeel' ); ?>>Informeel</option>
                                <option value="vriendelijk" <?php selected( get_option( 'writgocms_content_tone' ), 'vriendelijk' ); ?>>Vriendelijk</option>
                                <option value="zakelijk" <?php selected( get_option( 'writgocms_content_tone' ), 'zakelijk' ); ?>>Zakelijk</option>
                                <option value="enthousiast" <?php selected( get_option( 'writgocms_content_tone' ), 'enthousiast' ); ?>>Enthousiast</option>
                            </select>
                            <p class="description">De schrijfstijl voor gegenereerde content.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Content Plan Preferences Section -->
            <div class="aiml-settings-section">
                <h2>📋 Contentplan Voorkeuren</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            Content Categorieën
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="writgocms_content_categories[]" value="informatief" <?php checked( in_array( 'informatief', $content_categories, true ) ); ?>>
                                    📚 Informatieve Content (How-to guides, uitleg artikelen, tutorials)
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_content_categories[]" value="reviews" <?php checked( in_array( 'reviews', $content_categories, true ) ); ?>>
                                    ⭐ Reviews (Product reviews, service reviews, voor- en nadelen)
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_content_categories[]" value="top_lijstjes" <?php checked( in_array( 'top_lijstjes', $content_categories, true ) ); ?>>
                                    🏆 Top Lijstjes (Beste X van 2025, Top 10, rankings)
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_content_categories[]" value="vergelijkingen" <?php checked( in_array( 'vergelijkingen', $content_categories, true ) ); ?>>
                                    ⚖️ Vergelijkingen (X vs Y, feature comparisons, alternatieven)
                                </label>
                            </fieldset>
                            <p class="description">Selecteer welke content categorieën je in je contentplan wilt opnemen.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_items_per_analysis">Items per Analyse</label>
                        </th>
                        <td>
                            <select id="writgocms_items_per_analysis" name="writgocms_items_per_analysis">
                                <option value="10" <?php selected( get_option( 'writgocms_items_per_analysis', 20 ), 10 ); ?>>10</option>
                                <option value="20" <?php selected( get_option( 'writgocms_items_per_analysis', 20 ), 20 ); ?>>20</option>
                                <option value="30" <?php selected( get_option( 'writgocms_items_per_analysis', 20 ), 30 ); ?>>30</option>
                                <option value="50" <?php selected( get_option( 'writgocms_items_per_analysis', 20 ), 50 ); ?>>50</option>
                            </select>
                            <p class="description">Aantal contentplan items per analyse.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Automatic Updates Section -->
            <div class="aiml-settings-section">
                <h2>🔄 Automatische Updates</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            Updates
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="writgocms_weekly_updates" value="1" <?php checked( get_option( 'writgocms_weekly_updates', 0 ), 1 ); ?>>
                                    Wekelijks nieuwe contentplan items genereren
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_notifications" value="1" <?php checked( get_option( 'writgocms_notifications', 0 ), 1 ); ?>>
                                    Notificaties bij nieuwe suggesties
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Gutenberg AI Toolbar Section -->
            <?php
            $toolbar_enabled = get_option( 'writgocms_toolbar_enabled', 1 );
            $toolbar_buttons = get_option( 'writgocms_toolbar_buttons', array(
                'rewrite'     => true,
                'links'       => true,
                'image'       => true,
                'rewrite_all' => true,
            ) );
            $default_rewrite_tone = get_option( 'writgocms_toolbar_rewrite_tone', 'professional' );
            $links_limit = get_option( 'writgocms_toolbar_links_limit', 5 );
            ?>
            <div class="aiml-settings-section">
                <h2>🛠️ Gutenberg AI Toolbar</h2>
                <p class="description">
                    Configureer de AI-gestuurde toolbar die in de Gutenberg editor verschijnt.
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            Toolbar Inschakelen
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="writgocms_toolbar_enabled" value="1" <?php checked( $toolbar_enabled, 1 ); ?>>
                                AI Toolbar weergeven in Gutenberg editor
                            </label>
                            <p class="description">Schakel de AI-toolbar in of uit in de Gutenberg block editor.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            Toolbar Knoppen
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="writgocms_toolbar_buttons[rewrite]" value="1" <?php checked( isset( $toolbar_buttons['rewrite'] ) ? $toolbar_buttons['rewrite'] : true ); ?>>
                                    🤖 AI Rewrite - Herschrijf geselecteerde tekst
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_toolbar_buttons[links]" value="1" <?php checked( isset( $toolbar_buttons['links'] ) ? $toolbar_buttons['links'] : true ); ?>>
                                    🔗 Add Links - Interne link suggesties
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_toolbar_buttons[image]" value="1" <?php checked( isset( $toolbar_buttons['image'] ) ? $toolbar_buttons['image'] : true ); ?>>
                                    🖼️ Generate Image - AI afbeelding genereren
                                </label><br>
                                <label>
                                    <input type="checkbox" name="writgocms_toolbar_buttons[rewrite_all]" value="1" <?php checked( isset( $toolbar_buttons['rewrite_all'] ) ? $toolbar_buttons['rewrite_all'] : true ); ?>>
                                    📝 Rewrite All - Hele blok herschrijven
                                </label>
                            </fieldset>
                            <p class="description">Selecteer welke knoppen in de AI toolbar worden weergegeven.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_toolbar_rewrite_tone">Standaard Herschrijf Toon</label>
                        </th>
                        <td>
                            <select id="writgocms_toolbar_rewrite_tone" name="writgocms_toolbar_rewrite_tone">
                                <option value="professional" <?php selected( $default_rewrite_tone, 'professional' ); ?>>Professioneel</option>
                                <option value="casual" <?php selected( $default_rewrite_tone, 'casual' ); ?>>Informeel</option>
                                <option value="friendly" <?php selected( $default_rewrite_tone, 'friendly' ); ?>>Vriendelijk</option>
                                <option value="formal" <?php selected( $default_rewrite_tone, 'formal' ); ?>>Formeel</option>
                                <option value="creative" <?php selected( $default_rewrite_tone, 'creative' ); ?>>Creatief</option>
                            </select>
                            <p class="description">De standaard schrijfstijl voor AI herschrijvingen.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_toolbar_links_limit">Interne Links Limiet</label>
                        </th>
                        <td>
                            <select id="writgocms_toolbar_links_limit" name="writgocms_toolbar_links_limit">
                                <option value="3" <?php selected( $links_limit, 3 ); ?>>3 links</option>
                                <option value="5" <?php selected( $links_limit, 5 ); ?>>5 links</option>
                                <option value="10" <?php selected( $links_limit, 10 ); ?>>10 links</option>
                                <option value="15" <?php selected( $links_limit, 15 ); ?>>15 links</option>
                            </select>
                            <p class="description">Maximum aantal interne link suggesties per selectie.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Text Generation Settings -->
            <div class="aiml-settings-section">
                <h2>📝 Tekst Generatie Instellingen</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_text_temperature">Temperatuur</label>
                        </th>
                        <td>
                            <input type="range" id="writgocms_text_temperature" name="writgocms_text_temperature" min="0" max="2" step="0.1" value="<?php echo esc_attr( get_option( 'writgocms_text_temperature', '0.7' ) ); ?>" class="range-input">
                            <span class="range-value"><?php echo esc_html( get_option( 'writgocms_text_temperature', '0.7' ) ); ?></span>
                            <p class="description">Hogere waarden maken output meer willekeurig, lagere waarden meer deterministisch.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_text_max_tokens">Maximale Tokens</label>
                        </th>
                        <td>
                            <input type="number" id="writgocms_text_max_tokens" name="writgocms_text_max_tokens" value="<?php echo esc_attr( get_option( 'writgocms_text_max_tokens', '1000' ) ); ?>" min="100" max="4000" class="small-text">
                            <p class="description">Maximum aantal tokens om te genereren.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Image Generation Settings -->
            <div class="aiml-settings-section">
                <h2>🖼️ Afbeelding Generatie Instellingen</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="writgocms_default_image_model">Standaard Afbeelding Model</label>
                        </th>
                        <td>
                            <select id="writgocms_default_image_model" name="writgocms_default_image_model">
                                <?php foreach ( $image_models as $model_key => $model_name ) : ?>
                                    <option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( get_option( 'writgocms_default_image_model', 'dall-e-3' ), $model_key ); ?>>
                                        <?php echo esc_html( $model_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Selecteer het standaard AI model voor afbeeldingsgeneratie.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="writgocms_image_size">Afbeelding Grootte</label>
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
                            <label for="writgocms_image_quality">Afbeelding Kwaliteit (DALL-E 3)</label>
                        </th>
                        <td>
                            <select id="writgocms_image_quality" name="writgocms_image_quality">
                                <option value="standard" <?php selected( get_option( 'writgocms_image_quality', 'standard' ), 'standard' ); ?>>Standaard</option>
                                <option value="hd" <?php selected( get_option( 'writgocms_image_quality', 'standard' ), 'hd' ); ?>>HD</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="💾 Opslaan">
            </p>
        </form>

        <!-- DataForSEO Settings Section (PR #2) -->
        <form method="post" action="options.php">
            <?php settings_fields( 'writgocms_dataforseo_settings' ); ?>
            <?php
            if ( class_exists( 'WritgoCMS_DataForSEO_Settings' ) ) {
                $dataforseo_settings = WritgoCMS_DataForSEO_Settings::get_instance();
                $dataforseo_settings->render_settings_section();
            }
            ?>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="💾 Opslaan">
            </p>
        </form>
        <?php
    }

    /**
     * Render test tab (kept for backward compatibility - redirects to content generation)
     */
    private function render_test_tab() {
        $text_models  = $this->provider->get_text_models();
        $image_models = $this->provider->get_image_models();
        ?>
        <div class="test-interface">
            <h2>Test AI Generatie</h2>

            <div class="test-type-toggle">
                <button type="button" class="button test-type-btn active" data-type="text">📝 Tekst Generatie</button>
                <button type="button" class="button test-type-btn" data-type="image">🖼️ Afbeelding Generatie</button>
            </div>

            <div class="test-form">
                <div class="test-input-group">
                    <label for="test-model">Model</label>
                    <select id="test-model" class="test-model-select">
                        <optgroup label="Tekst Modellen" class="text-models">
                            <?php foreach ( $text_models as $model_key => $model_name ) : ?>
                                <option value="<?php echo esc_attr( $model_key ); ?>"><?php echo esc_html( $model_name ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Afbeelding Modellen" class="image-models" style="display:none;">
                            <?php foreach ( $image_models as $model_key => $model_name ) : ?>
                                <option value="<?php echo esc_attr( $model_key ); ?>"><?php echo esc_html( $model_name ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="test-input-group">
                    <label for="test-prompt">Prompt</label>
                    <textarea id="test-prompt" rows="3" placeholder="Voer hier je prompt in..."></textarea>
                </div>

                <div class="test-actions">
                    <button type="button" id="test-generate" class="button button-primary">
                        ✨ Genereer
                    </button>
                    <span class="test-status"></span>
                </div>
            </div>

            <div class="test-result" style="display: none;">
                <h3>Resultaat</h3>
                <div class="test-result-content"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render statistics tab (Dutch: Statistieken)
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
            <h2>Gebruiksstatistieken (Laatste 30 Dagen)</h2>

            <div class="stats-cards">
                <div class="stat-card">
                    <span class="stat-icon">📝</span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( $totals['text'] ); ?></span>
                        <span class="stat-label">Tekst Generaties</span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🖼️</span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( $totals['image'] ); ?></span>
                        <span class="stat-label">Afbeelding Generaties</span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📊</span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( $totals['text'] + $totals['image'] ); ?></span>
                        <span class="stat-label">Totaal Verzoeken</span>
                    </div>
                </div>
            </div>

            <h3>Recente Activiteit</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Type</th>
                        <th>Model</th>
                        <th>Aantal</th>
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
                            <td colspan="4">Nog geen gebruiksdata.</td>
                        </tr>
                        <?php
                    else :
                        foreach ( array_slice( $rows, 0, 20 ) as $row ) :
                            ?>
                        <tr>
                            <td><?php echo esc_html( $row['date'] ); ?></td>
                            <td><?php echo 'text' === $row['type'] ? '📝 Tekst' : '🖼️ Afbeelding'; ?></td>
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
     * Render content planner tab (kept for backward compatibility)
     */
    private function render_content_planner_tab() {
        ?>
        <div class="content-planner-dashboard">
            <h2>Topical Authority Map Generator</h2>
            <p class="description">
                Genereer een AI-gestuurd contentplan voor je website. Voer je niche in en laat AI een uitgebreide topical authority map maken met hoofdonderwerpen en cluster artikelen.
            </p>

            <div class="content-planner-grid">
                <!-- Input Section -->
                <div class="content-planner-input">
                    <div class="planner-card">
                        <h3>🎯 Definieer je Niche</h3>
                        
                        <div class="planner-field">
                            <label for="planner-niche">Hoofd Niche/Onderwerp</label>
                            <input type="text" id="planner-niche" class="regular-text" placeholder="bijv. Digitale Marketing, Fitness, Duurzaam Leven">
                            <p class="description">Voer het hoofdonderwerp of niche in voor je contentstrategie.</p>
                        </div>

                        <div class="planner-field">
                            <label for="planner-website-type">Website Type</label>
                            <select id="planner-website-type">
                                <option value="blog">Blog / Content Website</option>
                                <option value="ecommerce">E-commerce / Webshop</option>
                                <option value="saas">SaaS / Software Bedrijf</option>
                                <option value="agency">Bureau / Dienstverlener</option>
                                <option value="portfolio">Portfolio / Persoonlijk Merk</option>
                                <option value="news">Nieuws / Media Website</option>
                            </select>
                        </div>

                        <div class="planner-field">
                            <label for="planner-audience">Doelgroep (Optioneel)</label>
                            <textarea id="planner-audience" rows="2" placeholder="bijv. Ondernemers tussen 30-50 jaar die hun online aanwezigheid willen vergroten"></textarea>
                        </div>

                        <div class="planner-actions">
                            <button type="button" id="generate-topical-map" class="button button-primary button-hero">
                                ✨ Genereer Topical Authority Map
                            </button>
                            <span class="planner-status"></span>
                        </div>
                    </div>

                    <!-- Saved Plans Section -->
                    <div class="planner-card">
                        <h3>📁 Opgeslagen Contentplannen</h3>
                        <div id="saved-plans-list">
                            <p class="no-plans">Nog geen opgeslagen contentplannen. Genereer een topical map om te beginnen!</p>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="content-planner-results" style="display: none;">
                    <div class="planner-card">
                        <div class="results-header">
                            <h3>🗺️ Jouw Topical Authority Map</h3>
                            <div class="results-actions">
                                <button type="button" id="save-content-plan" class="button button-secondary">
                                    💾 Plan Opslaan
                                </button>
                                <button type="button" id="export-content-plan" class="button button-secondary">
                                    📤 Exporteer JSON
                                </button>
                            </div>
                        </div>
                        
                        <div id="topical-map-content">
                            <!-- Dynamically populated -->
                        </div>
                    </div>

                    <!-- Content Detail Panel -->
                    <div class="planner-card" id="content-detail-panel" style="display: none;">
                        <h3>📝 Artikel Content Plan</h3>
                        <div id="content-detail-result">
                            <!-- Dynamically populated -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Plan Modal -->
            <div id="save-plan-modal" class="planner-modal" style="display: none;">
                <div class="planner-modal-content">
                    <h3>Contentplan Opslaan</h3>
                    <div class="planner-field">
                        <label for="plan-name">Plan Naam</label>
                        <input type="text" id="plan-name" class="regular-text" placeholder="bijv. Q1 2024 Contentstrategie">
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="confirm-save-plan" class="button button-primary">Opslaan</button>
                        <button type="button" id="cancel-save-plan" class="button">Annuleren</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Check if AI service is active (API key is configured server-side)
     *
     * @return bool
     */
    private function is_ai_service_active() {
        // Check wp-config.php constant.
        if ( defined( 'WRITGO_AIML_API_KEY' ) && WRITGO_AIML_API_KEY ) {
            return true;
        }

        // Check environment variable.
        $env_key = getenv( 'WRITGO_AIML_API_KEY' );
        if ( $env_key ) {
            return true;
        }

        // Check license manager for injected key.
        if ( class_exists( 'WritgoCMS_License_Manager' ) ) {
            $license_manager = WritgoCMS_License_Manager::get_instance();
            $injected_key    = $license_manager->get_injected_api_key();

            if ( ! is_wp_error( $injected_key ) && ! empty( $injected_key ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * AJAX handler: Get credits
     *
     * @return void
     */
    public function ajax_get_credits() {
        check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgocms' ) ) );
        }

        if ( ! class_exists( 'WritgoCMS_API_Client' ) ) {
            wp_send_json_error( array( 'message' => __( 'API Client niet beschikbaar.', 'writgocms' ) ) );
        }

        $api_client = WritgoCMS_API_Client::get_instance();
        $balance = $api_client->get_credit_balance();

        if ( is_wp_error( $balance ) ) {
            wp_send_json_error( array(
                'message' => $balance->get_error_message(),
                'code'    => $balance->get_error_code(),
            ) );
        }

        wp_send_json_success( $balance );
    }

    /**
     * AJAX handler: Refresh credits (force refresh cache)
     *
     * @return void
     */
    public function ajax_refresh_credits() {
        check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgocms' ) ) );
        }

        if ( ! class_exists( 'WritgoCMS_API_Client' ) ) {
            wp_send_json_error( array( 'message' => __( 'API Client niet beschikbaar.', 'writgocms' ) ) );
        }

        $api_client = WritgoCMS_API_Client::get_instance();
        
        // Force refresh by passing true.
        $balance = $api_client->get_credit_balance( true );

        if ( is_wp_error( $balance ) ) {
            wp_send_json_error( array(
                'message' => $balance->get_error_message(),
                'code'    => $balance->get_error_code(),
            ) );
        }

        wp_send_json_success( $balance );
    }
}

// Initialize admin settings
WritgoCMS_AIML_Admin_Settings::get_instance();
