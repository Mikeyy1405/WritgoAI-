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

	// Load AIML Integration files.
	require_once WRITGOCMS_DIR . 'inc/class-aiml-provider.php';
	require_once WRITGOCMS_DIR . 'inc/admin-aiml-settings.php';
	require_once WRITGOCMS_DIR . 'inc/gutenberg-aiml-block.php';
	require_once WRITGOCMS_DIR . 'inc/classic-editor-button.php';
}
add_action( 'plugins_loaded', 'writgocms_init' );
