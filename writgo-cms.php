<?php
/**
 * Plugin Name: WritgoAI
 * Plugin URI: https://github.com/Mikeyy1405/WritgoAI-plugin
 * Description: AI-Powered Multi-Provider Integration for WordPress. Features multiple AI text providers (OpenAI, Claude, Gemini, Mistral) and image providers (DALL-E, Stability AI, Flux, Midjourney). Includes Gutenberg block support and Classic Editor integration.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Author: Mikeyy1405
 * Author URI: https://github.com/Mikeyy1405
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: writgoai
 * Domain Path: /languages
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'WRITGOAI_VERSION' ) ) {
	define( 'WRITGOAI_VERSION', '1.0.0' );
}
if ( ! defined( 'WRITGOAI_FILE' ) ) {
	define( 'WRITGOAI_FILE', __FILE__ );
}
if ( ! defined( 'WRITGOAI_DIR' ) ) {
	define( 'WRITGOAI_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WRITGOAI_URL' ) ) {
	define( 'WRITGOAI_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WRITGOAI_URI' ) ) {
	define( 'WRITGOAI_URI', WRITGOAI_URL );
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function writgoai_init() {
	// Load text domain for translations.
	load_plugin_textdomain( 'writgoai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Load Database Schema (for migrations).
	require_once WRITGOAI_DIR . 'inc/database/class-writgo-db-schema.php';

	// Check and run database migrations if needed.
	$db_schema = WritgoAI_DB_Schema::get_instance();
	$db_schema->maybe_update();

	// Load Auth Manager first (new authentication system).
	require_once WRITGOAI_DIR . 'inc/class-auth-manager.php';

	// Load License Manager (legacy support - will use AuthManager).
	require_once WRITGOAI_DIR . 'inc/class-license-manager.php';
	require_once WRITGOAI_DIR . 'inc/admin-license-settings.php';

	// Auto-authenticate with API on admin load using WordPress user.
	add_action( 'admin_init', 'writgoai_auto_authenticate' );
	
	// Initialize plugin updater
	if ( is_admin() ) {
		require_once WRITGOAI_DIR . 'inc/class-plugin-updater.php';
		new WritgoAI_Plugin_Updater( WRITGOAI_FILE, WRITGOAI_VERSION );
	}

	// Load API Client (for credit endpoints integration).
	require_once WRITGOAI_DIR . 'inc/class-api-client.php';

	// Load Credit Manager (credit-based subscription system).
	require_once WRITGOAI_DIR . 'inc/class-credit-manager.php';
	require_once WRITGOAI_DIR . 'inc/admin-license-manager.php';
	require_once WRITGOAI_DIR . 'inc/admin/credit-history-page.php';

	// Load WooCommerce Integration (if WooCommerce is active).
	if ( class_exists( 'WooCommerce' ) || file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
		require_once WRITGOAI_DIR . 'licensing/woocommerce/class-woocommerce-integration.php';
	}

	// Load AI Proxy (secure server-side API proxy).
	require_once WRITGOAI_DIR . 'inc/api/class-writgo-ai-proxy.php';

	// Load AI Integration files.
	require_once WRITGOAI_DIR . 'inc/class-ai-provider.php';
	require_once WRITGOAI_DIR . 'inc/class-content-planner.php';
	require_once WRITGOAI_DIR . 'inc/class-post-updater.php';
	require_once WRITGOAI_DIR . 'inc/admin-ai-settings.php';
	require_once WRITGOAI_DIR . 'inc/gutenberg-ai-block.php';
	require_once WRITGOAI_DIR . 'inc/class-gutenberg-toolbar.php';
	require_once WRITGOAI_DIR . 'inc/classic-editor-button.php';

	// Load AI Toolbar for Gutenberg editor.
	require_once WRITGOAI_DIR . 'inc/class-ai-actions.php';
	require_once WRITGOAI_DIR . 'inc/class-gutenberg-ai.php';

	// Load Social Media Manager files.
	require_once WRITGOAI_DIR . 'inc/class-social-media-manager.php';
	require_once WRITGOAI_DIR . 'inc/admin-social-media-settings.php';

	// Load Google Search Console Integration files.
	require_once WRITGOAI_DIR . 'inc/class-gsc-provider.php';
	require_once WRITGOAI_DIR . 'inc/class-gsc-data-handler.php';
	require_once WRITGOAI_DIR . 'inc/class-ctr-optimizer.php';
	require_once WRITGOAI_DIR . 'inc/admin-gsc-settings.php';

	// Load Site Analyzer & Keyword Research files (PR #2).
	require_once WRITGOAI_DIR . 'inc/class-site-analyzer.php';
	require_once WRITGOAI_DIR . 'inc/class-dataforseo-api.php';
	require_once WRITGOAI_DIR . 'inc/class-keyword-research.php';
	require_once WRITGOAI_DIR . 'inc/class-cron-jobs.php';
	require_once WRITGOAI_DIR . 'inc/admin/dashboard.php';
	require_once WRITGOAI_DIR . 'inc/admin/keyword-research-page.php';
	require_once WRITGOAI_DIR . 'inc/admin/settings-dataforseo.php';
	require_once WRITGOAI_DIR . 'inc/admin/post-list-columns.php';

	// Load Admin Controller and Setup Wizard (Beginner-friendly interface).
	require_once WRITGOAI_DIR . 'inc/admin/class-admin-controller.php';
	require_once WRITGOAI_DIR . 'inc/admin/class-setup-wizard.php';
	WritgoAI_Admin_Controller::get_instance();
	WritgoAI_Setup_Wizard::get_instance();
}
add_action( 'plugins_loaded', 'writgoai_init' );

/**
 * Auto-authenticate with API using WordPress user.
 *
 * Called on admin_init to ensure user has a valid API session.
 *
 * @return void
 */
function writgoai_auto_authenticate() {
	// Only for users with manage_options capability.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$auth_manager = WritgoAI_Auth_Manager::get_instance();

	// Check if we have a valid API session.
	if ( ! $auth_manager->has_valid_session() ) {
		// Auto-authenticate with API using WordPress credentials.
		$auth_manager->authenticate_with_api();
	}
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function writgoai_activate() {
	// Create API usage tracking tables.
	require_once WRITGOAI_DIR . 'inc/database/class-writgo-db-schema.php';
	$db_schema = WritgoAI_DB_Schema::get_instance();
	$db_schema->create_tables();

	// Create GSC database tables.
	require_once WRITGOAI_DIR . 'inc/class-gsc-provider.php';
	require_once WRITGOAI_DIR . 'inc/class-gsc-data-handler.php';

	$data_handler = WritgoAI_GSC_Data_Handler::get_instance();
	$data_handler->create_tables();
	$data_handler->schedule_sync();

	// Create Social Media database tables.
	require_once WRITGOAI_DIR . 'inc/class-ai-provider.php';
	require_once WRITGOAI_DIR . 'inc/class-social-media-manager.php';

	$social_media_manager = WritgoAI_Social_Media_Manager::get_instance();
	$social_media_manager->create_tables();

	// Schedule daily license check.
	if ( ! class_exists( 'WritgoAI_License_Manager' ) ) {
		require_once WRITGOAI_DIR . 'inc/class-license-manager.php';
	}
	$license_manager = WritgoAI_License_Manager::get_instance();
	$license_manager->schedule_daily_check();

	// Schedule cron jobs for site analysis and sync (PR #2).
	require_once WRITGOAI_DIR . 'inc/class-cron-jobs.php';
	$cron_jobs = WritgoAI_Cron_Jobs::get_instance();
	$cron_jobs->maybe_schedule_events();

	// Set transient for wizard redirect on first activation.
	set_transient( 'writgoai_activation_redirect', true, 30 );
}
register_activation_hook( __FILE__, 'writgoai_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function writgoai_deactivate() {
	// Unschedule sync cron.
	require_once WRITGOAI_DIR . 'inc/class-gsc-provider.php';
	require_once WRITGOAI_DIR . 'inc/class-gsc-data-handler.php';

	$data_handler = WritgoAI_GSC_Data_Handler::get_instance();
	$data_handler->unschedule_sync();

	// Unschedule license check.
	if ( ! class_exists( 'WritgoAI_License_Manager' ) ) {
		require_once WRITGOAI_DIR . 'inc/class-license-manager.php';
	}
	$license_manager = WritgoAI_License_Manager::get_instance();
	$license_manager->unschedule_daily_check();

	// Unschedule cron jobs (PR #2).
	require_once WRITGOAI_DIR . 'inc/class-cron-jobs.php';
	$cron_jobs = WritgoAI_Cron_Jobs::get_instance();
	$cron_jobs->unschedule_events();
}
register_deactivation_hook( __FILE__, 'writgoai_deactivate' );
