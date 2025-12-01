<?php
/**
 * Plugin Name: WritgoCMS AI
 * Plugin URI: https://github.com/Mikeyy1405/WritgoCMS
 * Description: AI-Powered Multi-Provider Integration for WordPress. Features 4 AI text providers (OpenAI, Claude, Gemini, Mistral) and 4 image providers (DALL-E, Stability AI, Leonardo, Replicate). Includes Gutenberg block support and Classic Editor integration.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Author: Mikeyy1405
 * Author URI: https://github.com/Mikeyy1405
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: writgocms
 * Domain Path: /languages
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'WRITGOCMS_VERSION' ) ) {
	define( 'WRITGOCMS_VERSION', '1.0.0' );
}
if ( ! defined( 'WRITGOCMS_DIR' ) ) {
	define( 'WRITGOCMS_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WRITGOCMS_URL' ) ) {
	define( 'WRITGOCMS_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WRITGOCMS_URI' ) ) {
	define( 'WRITGOCMS_URI', WRITGOCMS_URL );
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function writgocms_init() {
	// Load text domain for translations.
	load_plugin_textdomain( 'writgocms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Load Database Schema (for migrations).
	require_once WRITGOCMS_DIR . 'inc/database/class-writgo-db-schema.php';

	// Check and run database migrations if needed.
	$db_schema = WritgoCMS_DB_Schema::get_instance();
	$db_schema->maybe_update();

	// Load License Manager first (foundation for everything).
	require_once WRITGOCMS_DIR . 'inc/class-license-manager.php';
	require_once WRITGOCMS_DIR . 'inc/class-plugin-updater.php';
	require_once WRITGOCMS_DIR . 'inc/admin-license-settings.php';

	// Load API Client (for credit endpoints integration).
	require_once WRITGOCMS_DIR . 'inc/class-api-client.php';

	// Load Credit Manager (credit-based subscription system).
	require_once WRITGOCMS_DIR . 'inc/class-credit-manager.php';
	require_once WRITGOCMS_DIR . 'inc/admin-license-manager.php';
	require_once WRITGOCMS_DIR . 'inc/admin/credit-history-page.php';

	// Load WooCommerce Integration (if WooCommerce is active).
	if ( class_exists( 'WooCommerce' ) || file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
		require_once WRITGOCMS_DIR . 'licensing/woocommerce/class-woocommerce-integration.php';
	}

	// Load AIML Proxy (secure server-side API proxy).
	require_once WRITGOCMS_DIR . 'inc/api/class-writgo-aiml-proxy.php';

	// Load AIML Integration files.
	require_once WRITGOCMS_DIR . 'inc/class-aiml-provider.php';
	require_once WRITGOCMS_DIR . 'inc/class-content-planner.php';
	require_once WRITGOCMS_DIR . 'inc/class-post-updater.php';
	require_once WRITGOCMS_DIR . 'inc/admin-aiml-settings.php';
	require_once WRITGOCMS_DIR . 'inc/gutenberg-aiml-block.php';
	require_once WRITGOCMS_DIR . 'inc/class-gutenberg-toolbar.php';
	require_once WRITGOCMS_DIR . 'inc/classic-editor-button.php';

	// Load AI Toolbar for Gutenberg editor.
	require_once WRITGOCMS_DIR . 'inc/class-ai-actions.php';
	require_once WRITGOCMS_DIR . 'inc/class-gutenberg-ai.php';

	// Load Social Media Manager files.
	require_once WRITGOCMS_DIR . 'inc/class-social-media-manager.php';
	require_once WRITGOCMS_DIR . 'inc/admin-social-media-settings.php';

	// Load Google Search Console Integration files.
	require_once WRITGOCMS_DIR . 'inc/class-gsc-provider.php';
	require_once WRITGOCMS_DIR . 'inc/class-gsc-data-handler.php';
	require_once WRITGOCMS_DIR . 'inc/class-ctr-optimizer.php';
	require_once WRITGOCMS_DIR . 'inc/admin-gsc-settings.php';

	// Load Site Analyzer & Keyword Research files (PR #2).
	require_once WRITGOCMS_DIR . 'inc/class-site-analyzer.php';
	require_once WRITGOCMS_DIR . 'inc/class-dataforseo-api.php';
	require_once WRITGOCMS_DIR . 'inc/class-keyword-research.php';
	require_once WRITGOCMS_DIR . 'inc/class-cron-jobs.php';
	require_once WRITGOCMS_DIR . 'inc/admin/dashboard.php';
	require_once WRITGOCMS_DIR . 'inc/admin/keyword-research-page.php';
	require_once WRITGOCMS_DIR . 'inc/admin/settings-dataforseo.php';
	require_once WRITGOCMS_DIR . 'inc/admin/post-list-columns.php';

	// Load Admin Controller and Setup Wizard (Beginner-friendly interface).
	require_once WRITGOCMS_DIR . 'inc/admin/class-admin-controller.php';
	require_once WRITGOCMS_DIR . 'inc/admin/class-setup-wizard.php';
	WritgoCMS_Admin_Controller::get_instance();
	WritgoCMS_Setup_Wizard::get_instance();
}
add_action( 'plugins_loaded', 'writgocms_init' );

/**
 * Plugin activation hook.
 *
 * @return void
 */
function writgocms_activate() {
	// Create API usage tracking tables.
	require_once WRITGOCMS_DIR . 'inc/database/class-writgo-db-schema.php';
	$db_schema = WritgoCMS_DB_Schema::get_instance();
	$db_schema->create_tables();

	// Create GSC database tables.
	require_once WRITGOCMS_DIR . 'inc/class-gsc-provider.php';
	require_once WRITGOCMS_DIR . 'inc/class-gsc-data-handler.php';

	$data_handler = WritgoCMS_GSC_Data_Handler::get_instance();
	$data_handler->create_tables();
	$data_handler->schedule_sync();

	// Create Social Media database tables.
	require_once WRITGOCMS_DIR . 'inc/class-aiml-provider.php';
	require_once WRITGOCMS_DIR . 'inc/class-social-media-manager.php';

	$social_media_manager = WritgoCMS_Social_Media_Manager::get_instance();
	$social_media_manager->create_tables();

	// Schedule daily license check.
	if ( ! class_exists( 'WritgoCMS_License_Manager' ) ) {
		require_once WRITGOCMS_DIR . 'inc/class-license-manager.php';
	}
	$license_manager = WritgoCMS_License_Manager::get_instance();
	$license_manager->schedule_daily_check();

	// Schedule cron jobs for site analysis and sync (PR #2).
	require_once WRITGOCMS_DIR . 'inc/class-cron-jobs.php';
	$cron_jobs = WritgoCMS_Cron_Jobs::get_instance();
	$cron_jobs->maybe_schedule_events();

	// Set transient for wizard redirect on first activation.
	set_transient( 'writgocms_activation_redirect', true, 30 );
}
register_activation_hook( __FILE__, 'writgocms_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function writgocms_deactivate() {
	// Unschedule sync cron.
	require_once WRITGOCMS_DIR . 'inc/class-gsc-provider.php';
	require_once WRITGOCMS_DIR . 'inc/class-gsc-data-handler.php';

	$data_handler = WritgoCMS_GSC_Data_Handler::get_instance();
	$data_handler->unschedule_sync();

	// Unschedule license check.
	if ( ! class_exists( 'WritgoCMS_License_Manager' ) ) {
		require_once WRITGOCMS_DIR . 'inc/class-license-manager.php';
	}
	$license_manager = WritgoCMS_License_Manager::get_instance();
	$license_manager->unschedule_daily_check();

	// Unschedule cron jobs (PR #2).
	require_once WRITGOCMS_DIR . 'inc/class-cron-jobs.php';
	$cron_jobs = WritgoCMS_Cron_Jobs::get_instance();
	$cron_jobs->unschedule_events();
}
register_deactivation_hook( __FILE__, 'writgocms_deactivate' );
