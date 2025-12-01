<?php
/**
 * Gutenberg AI Toolbar Integration
 *
 * Adds AI-powered toolbar to the Gutenberg editor with text selection,
 * block toolbar, and sidebar panel functionality.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Gutenberg_AI
 */
class WritgoCMS_Gutenberg_AI {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Gutenberg_AI
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Gutenberg_AI
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Enqueue editor assets
	 */
	public function enqueue_editor_assets() {
		// Check if user has permission.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Enqueue AI toolbar JavaScript.
		wp_enqueue_script(
			'writgocms-ai-api-client',
			WRITGOCMS_URL . 'assets/js/ai-api-client.js',
			array( 'jquery' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_enqueue_script(
			'writgocms-text-selection',
			WRITGOCMS_URL . 'assets/js/text-selection.js',
			array( 'jquery', 'writgocms-ai-api-client' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_enqueue_script(
			'writgocms-gutenberg-toolbar',
			WRITGOCMS_URL . 'assets/js/gutenberg-toolbar.js',
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-i18n',
				'wp-rich-text',
				'wp-block-editor',
				'wp-compose',
				'writgocms-ai-api-client',
				'writgocms-text-selection',
			),
			WRITGOCMS_VERSION,
			true
		);

		// Enqueue AI toolbar CSS.
		wp_enqueue_style(
			'writgocms-ai-toolbar',
			WRITGOCMS_URL . 'assets/css/ai-toolbar.css',
			array(),
			WRITGOCMS_VERSION
		);

		// Get license info for usage display.
		$license_info = $this->get_license_info();

		// Localize script with necessary data.
		wp_localize_script(
			'writgocms-ai-api-client',
			'writgocmsAiToolbar',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'restUrl'    => rest_url( 'writgo/v1/' ),
				'nonce'      => wp_create_nonce( 'writgocms_ai_toolbar_nonce' ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
				'postId'     => get_the_ID(),
				'isLicensed' => $license_info['is_valid'],
				'isAdmin'    => current_user_can( 'manage_options' ),
				'usage'      => $license_info['usage'],
				'i18n'       => $this->get_translations(),
			)
		);
	}

	/**
	 * Get license info
	 *
	 * @return array
	 */
	private function get_license_info() {
		$info = array(
			'is_valid' => true, // Default to true for admins.
			'usage'    => array(
				'requests_used'      => 0,
				'requests_remaining' => 1000,
				'daily_limit'        => 1000,
			),
		);

		// WordPress admin users always have access
		if ( current_user_can( 'manage_options' ) ) {
			$info['is_valid'] = true;
		}

		if ( class_exists( 'WritgoCMS_License_Manager' ) ) {
			$license_manager = WritgoCMS_License_Manager::get_instance();
			$status = $license_manager->get_license_status();

			// Use the license manager's validation which already checks admin status
			$info['is_valid'] = $license_manager->is_license_valid();

			if ( isset( $status['usage'] ) ) {
				$info['usage'] = wp_parse_args( $status['usage'], $info['usage'] );
			}

			if ( isset( $status['limits'] ) && isset( $status['limits']['daily'] ) ) {
				$info['usage']['daily_limit'] = $status['limits']['daily'];
			}
		}

		return $info;
	}

	/**
	 * Get translations for JavaScript
	 *
	 * @return array
	 */
	private function get_translations() {
		return array(
			// Selection toolbar.
			'rewrite'              => __( 'Herschrijven', 'writgocms' ),
			'improve'              => __( 'Verbeteren', 'writgocms' ),
			'expand'               => __( 'Uitbreiden', 'writgocms' ),
			'shorten'              => __( 'Inkorten', 'writgocms' ),
			'addLinks'             => __( 'Links toevoegen', 'writgocms' ),

			// Block toolbar.
			'aiActions'            => __( 'WritgoAI', 'writgocms' ),
			'rewriteBlock'         => __( 'Blok herschrijven', 'writgocms' ),
			'generateImage'        => __( 'Afbeelding genereren', 'writgocms' ),
			'autoLink'             => __( 'Auto-link', 'writgocms' ),

			// Sidebar.
			'sidebarTitle'         => __( 'WritgoAI', 'writgocms' ),
			'rewriteArticle'       => __( 'Artikel herschrijven', 'writgocms' ),
			'seoOptimize'          => __( 'SEO optimaliseren', 'writgocms' ),
			'generateMeta'         => __( 'Meta description genereren', 'writgocms' ),
			'generateFeatured'     => __( 'Featured image genereren', 'writgocms' ),
			'autoLinkContent'      => __( 'Content auto-linken', 'writgocms' ),

			// Status messages.
			'processing'           => __( 'Bezig...', 'writgocms' ),
			'success'              => __( 'Klaar!', 'writgocms' ),
			'error'                => __( 'Er is een fout opgetreden', 'writgocms' ),
			'noTextSelected'       => __( 'Selecteer eerst tekst', 'writgocms' ),
			'licenseInvalid'       => __( 'Licentie niet actief', 'writgocms' ),
			'limitReached'         => __( 'Dagelijkse limiet bereikt', 'writgocms' ),

			// Usage.
			'usageLabel'           => __( 'Gebruik', 'writgocms' ),
			'requestsRemaining'    => __( 'verzoeken over', 'writgocms' ),

			// Actions.
			'apply'                => __( 'Toepassen', 'writgocms' ),
			'cancel'               => __( 'Annuleren', 'writgocms' ),
			'undo'                 => __( 'Ongedaan maken', 'writgocms' ),
			'preview'              => __( 'Voorbeeld', 'writgocms' ),

			// Image generation.
			'imagePrompt'          => __( 'Beschrijf de afbeelding die je wilt genereren', 'writgocms' ),
			'generateBtn'          => __( 'Genereren', 'writgocms' ),
			'insertImage'          => __( 'Afbeelding invoegen', 'writgocms' ),
		);
	}
}

// Initialize.
WritgoCMS_Gutenberg_AI::get_instance();
