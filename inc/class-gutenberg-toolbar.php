<?php
/**
 * Gutenberg AI Toolbar Class
 *
 * Adds an AI-powered toolbar to the Gutenberg editor for text rewriting,
 * internal link suggestions, and AI image generation.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Gutenberg_Toolbar
 */
class WritgoCMS_Gutenberg_Toolbar {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Gutenberg_Toolbar
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Gutenberg_Toolbar
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_toolbar_assets' ) );
		add_action( 'wp_ajax_writgocms_toolbar_rewrite', array( $this, 'ajax_rewrite_text' ) );
		add_action( 'wp_ajax_writgocms_toolbar_generate_image', array( $this, 'ajax_generate_image' ) );
		add_action( 'wp_ajax_writgocms_toolbar_get_internal_links', array( $this, 'ajax_get_internal_links' ) );
	}

	/**
	 * Enqueue toolbar assets for block editor
	 */
	public function enqueue_toolbar_assets() {
		// Check if toolbar is enabled in settings.
		$toolbar_enabled = get_option( 'writgocms_toolbar_enabled', true );
		if ( ! $toolbar_enabled ) {
			return;
		}

		// Get toolbar button settings.
		$toolbar_buttons = get_option( 'writgocms_toolbar_buttons', array(
			'rewrite'     => true,
			'links'       => true,
			'image'       => true,
			'rewrite_all' => true,
		) );

		// Get default rewrite tone.
		$default_tone = get_option( 'writgocms_toolbar_rewrite_tone', 'professional' );

		// Get internal links limit.
		$links_limit = get_option( 'writgocms_toolbar_links_limit', 5 );

		wp_enqueue_script(
			'writgocms-gutenberg-toolbar',
			WRITGOCMS_URL . 'assets/js/gutenberg-toolbar.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-compose', 'wp-data', 'wp-rich-text', 'wp-plugins', 'wp-edit-post' ),
			WRITGOCMS_VERSION,
			true
		);

		wp_enqueue_style(
			'writgocms-gutenberg-toolbar-style',
			WRITGOCMS_URL . 'assets/css/gutenberg-toolbar.css',
			array(),
			WRITGOCMS_VERSION
		);

		wp_localize_script(
			'writgocms-gutenberg-toolbar',
			'writgocmsToolbar',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'writgocms_toolbar_nonce' ),
				'buttons'      => $toolbar_buttons,
				'defaultTone'  => $default_tone,
				'linksLimit'   => (int) $links_limit,
				'i18n'         => array(
					// Toolbar buttons.
					'rewrite'          => __( 'AI Rewrite', 'writgocms' ),
					'addLinks'         => __( 'Add Links', 'writgocms' ),
					'generateImage'    => __( 'Generate Image', 'writgocms' ),
					'rewriteAll'       => __( 'Rewrite All', 'writgocms' ),
					// Modal titles.
					'rewriteTitle'     => __( 'AI Rewrite Text', 'writgocms' ),
					'imageTitle'       => __( 'Generate AI Image', 'writgocms' ),
					'linksTitle'       => __( 'Suggested Internal Links', 'writgocms' ),
					// Modal content.
					'loading'          => __( 'Generating...', 'writgocms' ),
					'preview'          => __( 'Preview', 'writgocms' ),
					'originalText'     => __( 'Original Text', 'writgocms' ),
					'rewrittenText'    => __( 'Rewritten Text', 'writgocms' ),
					'imagePrompt'      => __( 'Describe the image you want to generate...', 'writgocms' ),
					'useSelectedText'  => __( 'Or use selected text as prompt', 'writgocms' ),
					'noLinksFound'     => __( 'No relevant internal links found.', 'writgocms' ),
					// Buttons.
					'accept'           => __( 'Accept', 'writgocms' ),
					'cancel'           => __( 'Cancel', 'writgocms' ),
					'regenerate'       => __( 'Regenerate', 'writgocms' ),
					'generate'         => __( 'Generate', 'writgocms' ),
					'insertImage'      => __( 'Insert Image', 'writgocms' ),
					'insertLinks'      => __( 'Insert Selected', 'writgocms' ),
					// Tones.
					'toneLabel'        => __( 'Rewrite Tone', 'writgocms' ),
					'toneProfessional' => __( 'Professional', 'writgocms' ),
					'toneCasual'       => __( 'Casual', 'writgocms' ),
					'toneFriendly'     => __( 'Friendly', 'writgocms' ),
					'toneFormal'       => __( 'Formal', 'writgocms' ),
					'toneCreative'     => __( 'Creative', 'writgocms' ),
					// Success/Error messages.
					'successRewrite'   => __( 'Text rewritten successfully!', 'writgocms' ),
					'successImage'     => __( 'Image generated and inserted!', 'writgocms' ),
					'successLinks'     => __( 'Links inserted successfully!', 'writgocms' ),
					'errorGeneral'     => __( 'An error occurred. Please try again.', 'writgocms' ),
					'errorNoSelection' => __( 'Please select some text first.', 'writgocms' ),
					'errorNoLicense'   => __( 'Please activate your license to use this feature.', 'writgocms' ),
					'errorRateLimit'   => __( 'Rate limit exceeded. Please try again later.', 'writgocms' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for text rewriting
	 */
	public function ajax_rewrite_text() {
		check_ajax_referer( 'writgocms_toolbar_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$text = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';
		$tone = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'professional';

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'No text provided.', 'writgocms' ) ) );
		}

		// Build the prompt based on the selected tone.
		$tone_instructions = $this->get_tone_instructions( $tone );
		$prompt            = sprintf(
			'%s\n\nOriginal text:\n%s\n\nRewritten text:',
			$tone_instructions,
			$text
		);

		// Use the AIML provider for text generation.
		if ( ! class_exists( 'WritgoCMS_AIML_Provider' ) ) {
			wp_send_json_error( array( 'message' => __( 'AI provider not available.', 'writgocms' ) ) );
		}

		$provider = WritgoCMS_AIML_Provider::get_instance();
		$result   = $provider->generate_text( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'original'   => $text,
			'rewritten'  => isset( $result['content'] ) ? $result['content'] : '',
		) );
	}

	/**
	 * Get tone instructions for rewriting
	 *
	 * @param string $tone The tone to use.
	 * @return string The instruction prompt for the tone.
	 */
	private function get_tone_instructions( $tone ) {
		$instructions = array(
			'professional' => 'Rewrite the following text in a professional and polished manner. Maintain clarity and use formal business language.',
			'casual'       => 'Rewrite the following text in a casual and conversational tone. Keep it friendly and easy to read.',
			'friendly'     => 'Rewrite the following text in a warm and friendly tone. Make it approachable and personable.',
			'formal'       => 'Rewrite the following text in a formal and academic tone. Use precise language and maintain a serious tone.',
			'creative'     => 'Rewrite the following text in a creative and engaging way. Use vivid language and make it captivating.',
		);

		return isset( $instructions[ $tone ] ) ? $instructions[ $tone ] : $instructions['professional'];
	}

	/**
	 * AJAX handler for AI image generation
	 */
	public function ajax_generate_image() {
		check_ajax_referer( 'writgocms_toolbar_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';

		if ( empty( $prompt ) ) {
			wp_send_json_error( array( 'message' => __( 'No prompt provided.', 'writgocms' ) ) );
		}

		// Use the AIML provider for image generation.
		if ( ! class_exists( 'WritgoCMS_AIML_Provider' ) ) {
			wp_send_json_error( array( 'message' => __( 'AI provider not available.', 'writgocms' ) ) );
		}

		$provider = WritgoCMS_AIML_Provider::get_instance();
		$result   = $provider->generate_image( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'image_url'     => isset( $result['image_url'] ) ? $result['image_url'] : '',
			'attachment_id' => isset( $result['attachment_id'] ) ? $result['attachment_id'] : 0,
		) );
	}

	/**
	 * AJAX handler for getting internal link suggestions
	 */
	public function ajax_get_internal_links() {
		check_ajax_referer( 'writgocms_toolbar_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$text  = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';
		$limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 5;

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'No text provided.', 'writgocms' ) ) );
		}

		// Extract keywords from the text.
		$keywords = $this->extract_keywords( $text );

		// Search for relevant posts/pages.
		$suggestions = $this->find_related_content( $keywords, $limit );

		wp_send_json_success( array(
			'links' => $suggestions,
		) );
	}

	/**
	 * Extract keywords from text for internal linking
	 *
	 * @param string $text The text to extract keywords from.
	 * @return array Array of keywords.
	 */
	private function extract_keywords( $text ) {
		/**
		 * Filter stop words for keyword extraction.
		 *
		 * @param array $stop_words Default stop words for English and Dutch.
		 */
		$stop_words = apply_filters(
			'writgocms_toolbar_stop_words',
			array(
				// English stop words.
				'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
				'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been',
				'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
				'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need',
				'dare', 'ought', 'used', 'this', 'that', 'these', 'those', 'i', 'you',
				'he', 'she', 'it', 'we', 'they', 'what', 'which', 'who', 'whom',
				// Dutch stop words.
				'de', 'het', 'een', 'en', 'of', 'maar', 'in', 'op', 'aan', 'voor',
				'van', 'met', 'door', 'uit', 'als', 'is', 'was', 'zijn', 'waren',
				'dat', 'die', 'deze', 'dit', 'ik', 'je', 'jij', 'hij', 'zij', 'wij',
			)
		);

		// Convert to lowercase and remove punctuation.
		$text  = strtolower( $text );
		$text  = preg_replace( '/[^\p{L}\p{N}\s]/u', '', $text );
		$words = preg_split( '/\s+/', $text );

		/**
		 * Filter the minimum word length for keyword extraction.
		 *
		 * @param int $min_length Minimum word length (default: 4).
		 */
		$min_word_length = apply_filters( 'writgocms_toolbar_min_word_length', 4 );

		// Filter out stop words and short words.
		$keywords = array();
		foreach ( $words as $word ) {
			$word = trim( $word );
			if ( strlen( $word ) >= $min_word_length && ! in_array( $word, $stop_words, true ) ) {
				$keywords[] = $word;
			}
		}

		// Get unique keywords and limit to top 10.
		$keywords = array_unique( $keywords );
		return array_slice( $keywords, 0, 10 );
	}

	/**
	 * Find related content based on keywords
	 *
	 * @param array $keywords Keywords to search for.
	 * @param int   $limit    Maximum number of results.
	 * @return array Array of related posts/pages.
	 */
	private function find_related_content( $keywords, $limit = 5 ) {
		if ( empty( $keywords ) ) {
			return array();
		}

		$search_query = implode( ' ', $keywords );

		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			's'              => $search_query,
			'posts_per_page' => $limit,
			'orderby'        => 'relevance',
			'no_found_rows'  => true, // Optimization: skip counting total rows.
		);

		$query   = new WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$results[] = array(
					'id'      => $post_id,
					'title'   => get_the_title(),
					'url'     => get_permalink(),
					'excerpt' => wp_trim_words( get_the_excerpt(), 15, '...' ),
					'type'    => get_post_type(),
				);

				if ( count( $results ) >= $limit ) {
					break;
				}
			}
		}

		wp_reset_postdata();

		return $results;
	}
}

// Initialize the toolbar.
WritgoCMS_Gutenberg_Toolbar::get_instance();
