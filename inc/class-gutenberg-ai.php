<?php
/**
 * Gutenberg AI Toolbar Integration
 *
 * Adds AI-powered toolbar to the Gutenberg editor with text selection,
 * block toolbar, and sidebar panel functionality.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Gutenberg_AI
 */
class WritgoAI_Gutenberg_AI {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Gutenberg_AI
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Gutenberg_AI
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
			'writgoai-api-client',
			WRITGOAI_URL . 'assets/js/ai-api-client.js',
			array( 'jquery' ),
			WRITGOAI_VERSION,
			true
		);

		wp_enqueue_script(
			'writgoai-text-selection',
			WRITGOAI_URL . 'assets/js/text-selection.js',
			array( 'jquery', 'writgoai-api-client' ),
			WRITGOAI_VERSION,
			true
		);

		wp_enqueue_script(
			'writgoai-gutenberg-toolbar',
			WRITGOAI_URL . 'assets/js/gutenberg-toolbar.js',
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
				'writgoai-api-client',
				'writgoai-text-selection',
			),
			WRITGOAI_VERSION,
			true
		);

		// Enqueue AI toolbar CSS.
		wp_enqueue_style(
			'writgoai-toolbar',
			WRITGOAI_URL . 'assets/css/ai-toolbar.css',
			array(),
			WRITGOAI_VERSION
		);

		// Get license info for usage display.
		$license_info = $this->get_license_info();

		// Localize script with necessary data.
		wp_localize_script(
			'writgoai-api-client',
			'writgoaiAiToolbar',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'restUrl'    => rest_url( 'writgo/v1/' ),
				'nonce'      => wp_create_nonce( 'writgoai_ai_toolbar_nonce' ),
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

		if ( class_exists( 'WritgoAI_License_Manager' ) ) {
			$license_manager = WritgoAI_License_Manager::get_instance();
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
			'rewrite'              => __( 'Herschrijven', 'writgoai' ),
			'improve'              => __( 'Verbeteren', 'writgoai' ),
			'expand'               => __( 'Uitbreiden', 'writgoai' ),
			'shorten'              => __( 'Inkorten', 'writgoai' ),
			'addLinks'             => __( 'Links toevoegen', 'writgoai' ),

			// Block toolbar.
			'aiActions'            => __( 'WritgoAI', 'writgoai' ),
			'rewriteBlock'         => __( 'Blok herschrijven', 'writgoai' ),
			'generateImage'        => __( 'Afbeelding genereren', 'writgoai' ),
			'autoLink'             => __( 'Auto-link', 'writgoai' ),

			// Sidebar.
			'sidebarTitle'         => __( 'WritgoAI', 'writgoai' ),
			'rewriteArticle'       => __( 'Artikel herschrijven', 'writgoai' ),
			'seoOptimize'          => __( 'SEO optimaliseren', 'writgoai' ),
			'generateMeta'         => __( 'Meta description genereren', 'writgoai' ),
			'generateFeatured'     => __( 'Featured image genereren', 'writgoai' ),
			'autoLinkContent'      => __( 'Content auto-linken', 'writgoai' ),

			// Status messages.
			'processing'           => __( 'Bezig...', 'writgoai' ),
			'success'              => __( 'Klaar!', 'writgoai' ),
			'error'                => __( 'Er is een fout opgetreden', 'writgoai' ),
			'noTextSelected'       => __( 'Selecteer eerst tekst', 'writgoai' ),
			'licenseInvalid'       => __( 'Licentie niet actief', 'writgoai' ),
			'limitReached'         => __( 'Dagelijkse limiet bereikt', 'writgoai' ),

			// Usage.
			'usageLabel'           => __( 'Gebruik', 'writgoai' ),
			'requestsRemaining'    => __( 'verzoeken over', 'writgoai' ),

			// Actions.
			'apply'                => __( 'Toepassen', 'writgoai' ),
			'cancel'               => __( 'Annuleren', 'writgoai' ),
			'undo'                 => __( 'Ongedaan maken', 'writgoai' ),
			'preview'              => __( 'Voorbeeld', 'writgoai' ),

			// Image generation.
			'imagePrompt'          => __( 'Beschrijf de afbeelding die je wilt genereren', 'writgoai' ),
			'generateBtn'          => __( 'Genereren', 'writgoai' ),
			'insertImage'          => __( 'Afbeelding invoegen', 'writgoai' ),
		);
	}
}

// Initialize.
WritgoAI_Gutenberg_AI::get_instance();
