<?php
/**
 * AI Actions Class
 *
 * Handles AI-powered actions for text manipulation, link suggestions, and content generation.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_AI_Actions
 */
class WritgoAI_AI_Actions {

	/**
	 * Instance
	 *
	 * @var WritgoAI_AI_Actions
	 */
	private static $instance = null;

	/**
	 * AIML Provider instance
	 *
	 * @var WritgoAI_AI_Provider
	 */
	private $aiml_provider;

	/**
	 * Get instance
	 *
	 * @return WritgoAI_AI_Actions
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
		// AJAX handlers for AI actions.
		add_action( 'wp_ajax_writgoai_ai_rewrite', array( $this, 'ajax_rewrite_text' ) );
		add_action( 'wp_ajax_writgoai_ai_improve', array( $this, 'ajax_improve_text' ) );
		add_action( 'wp_ajax_writgoai_ai_expand', array( $this, 'ajax_expand_text' ) );
		add_action( 'wp_ajax_writgoai_ai_shorten', array( $this, 'ajax_shorten_text' ) );
		add_action( 'wp_ajax_writgoai_ai_add_links', array( $this, 'ajax_add_links' ) );
		add_action( 'wp_ajax_writgoai_ai_rewrite_block', array( $this, 'ajax_rewrite_block' ) );
		add_action( 'wp_ajax_writgoai_ai_rewrite_article', array( $this, 'ajax_rewrite_article' ) );
		add_action( 'wp_ajax_writgoai_ai_seo_optimize', array( $this, 'ajax_seo_optimize' ) );
		add_action( 'wp_ajax_writgoai_ai_generate_meta', array( $this, 'ajax_generate_meta' ) );
		add_action( 'wp_ajax_writgoai_ai_get_usage', array( $this, 'ajax_get_usage' ) );
	}

	/**
	 * Get AIML Provider
	 *
	 * @return WritgoAI_AI_Provider
	 */
	private function get_provider() {
		if ( null === $this->aiml_provider ) {
			$this->aiml_provider = WritgoAI_AI_Provider::get_instance();
		}
		return $this->aiml_provider;
	}

	/**
	 * Check license and permissions
	 *
	 * @return bool|WP_Error
	 */
	private function check_permissions() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'permission_denied', __( 'Je hebt geen toestemming om deze actie uit te voeren.', 'writgoai' ) );
		}

		if ( class_exists( 'WritgoAI_License_Manager' ) ) {
			$license_manager = WritgoAI_License_Manager::get_instance();
			if ( ! $license_manager->is_license_valid() ) {
				return new WP_Error( 'license_invalid', __( 'Je licentie is niet actief. Activeer je licentie om WritgoAI te gebruiken.', 'writgoai' ) );
			}
		}

		return true;
	}

	/**
	 * AJAX handler for rewriting text
	 */
	public function ajax_rewrite_text() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$text = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen tekst geselecteerd.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Herschrijf de volgende tekst op een frisse, natuurlijke manier terwijl je de originele betekenis behoudt. Behoud dezelfde toon en stijl. Geef alleen de herschreven tekst terug, zonder extra uitleg. Tekst: %s',
			$text
		);

		$result = $this->get_provider()->generate_text( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $text,
		) );
	}

	/**
	 * AJAX handler for improving text
	 */
	public function ajax_improve_text() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$text = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen tekst geselecteerd.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Verbeter de volgende tekst qua grammatica, spelling, leesbaarheid en stijl. Behoud de originele betekenis maar maak de tekst professioneler en duidelijker. Geef alleen de verbeterde tekst terug, zonder extra uitleg. Tekst: %s',
			$text
		);

		$result = $this->get_provider()->generate_text( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $text,
		) );
	}

	/**
	 * AJAX handler for expanding text
	 */
	public function ajax_expand_text() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$text = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen tekst geselecteerd.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Breid de volgende tekst uit met meer details, voorbeelden en context. Maak de tekst ongeveer 2x zo lang terwijl je dezelfde toon en stijl behoudt. Geef alleen de uitgebreide tekst terug, zonder extra uitleg. Tekst: %s',
			$text
		);

		$result = $this->get_provider()->generate_text( $prompt, null, array( 'max_tokens' => 2000 ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $text,
		) );
	}

	/**
	 * AJAX handler for shortening text
	 */
	public function ajax_shorten_text() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$text = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen tekst geselecteerd.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Maak de volgende tekst korter en bondiger terwijl je de belangrijkste punten behoudt. Verwijder overbodige woorden en maak de tekst ongeveer 50 procent korter. Geef alleen de verkorte tekst terug, zonder extra uitleg. Tekst: %s',
			$text
		);

		$result = $this->get_provider()->generate_text( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $text,
		) );
	}

	/**
	 * AJAX handler for adding internal links
	 */
	public function ajax_add_links() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$text    = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen tekst geselecteerd.', 'writgoai' ) ) );
		}

		// Get existing posts to suggest links.
		$existing_posts = $this->get_linkable_posts( $post_id );

		if ( empty( $existing_posts ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen geschikte interne links gevonden.', 'writgoai' ) ) );
		}

		$posts_list = implode( "\n", array_map( function( $post ) {
			return sprintf( '- "%s" (URL: %s)', $post['title'], $post['url'] );
		}, $existing_posts ) );

		$prompt = sprintf(
			'Analyseer de volgende tekst en voeg relevante interne links toe waar gepast. Gebruik alleen de links uit de beschikbare lijst hieronder. Voeg links toe als HTML anchor tags (<a href="URL">tekst</a>). Behoud de originele tekst structuur.

Beschikbare interne links:
%s

Tekst om te analyseren:
%s

Geef de tekst terug met toegevoegde interne links. Als er geen relevante links zijn, geef dan de originele tekst terug.',
			$posts_list,
			$text
		);

		$result = $this->get_provider()->generate_text( $prompt, null, array( 'max_tokens' => 2000 ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $text,
		) );
	}

	/**
	 * Get linkable posts for internal linking
	 *
	 * @param int $exclude_post_id Post ID to exclude.
	 * @return array
	 */
	private function get_linkable_posts( $exclude_post_id = 0 ) {
		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'post__not_in'   => $exclude_post_id ? array( $exclude_post_id ) : array(),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$posts = get_posts( $args );
		$result = array();

		foreach ( $posts as $post ) {
			$result[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'url'   => get_permalink( $post->ID ),
			);
		}

		return $result;
	}

	/**
	 * AJAX handler for rewriting entire block
	 */
	public function ajax_rewrite_block() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen blok inhoud gevonden.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Herschrijf de volgende paragraaf volledig op een frisse, natuurlijke manier. Behoud de kernboodschap maar gebruik andere woorden en zinsstructuren. Verbeter waar mogelijk de leesbaarheid en flow. Geef alleen de herschreven tekst terug, zonder extra uitleg. Tekst: %s',
			wp_strip_all_tags( $content )
		);

		$result = $this->get_provider()->generate_text( $prompt, null, array( 'max_tokens' => 1500 ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $content,
		) );
	}

	/**
	 * AJAX handler for rewriting entire article
	 */
	public function ajax_rewrite_article() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen artikel inhoud gevonden.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Herschrijf het volgende artikel volledig. Behoud de structuur met paragrafen en koppen waar aanwezig, maar gebruik andere woorden en zinsstructuren. Verbeter de leesbaarheid en flow. Titel: %s

Inhoud:
%s

Geef het herschreven artikel terug met behoud van de originele structuur (paragrafen, koppen, etc.).',
			$title,
			wp_strip_all_tags( $content )
		);

		$result = $this->get_provider()->generate_text( $prompt, null, array( 'max_tokens' => 4000 ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'  => $result['content'],
			'original' => $content,
		) );
	}

	/**
	 * AJAX handler for SEO optimization
	 */
	public function ajax_seo_optimize() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen artikel inhoud gevonden.', 'writgoai' ) ) );
		}

		$keyword_instruction = ! empty( $keyword ) ? sprintf( 'Focus keyword: %s. ', $keyword ) : '';

		$prompt = sprintf(
			'Analyseer en optimaliseer het volgende artikel voor SEO. %sGeef concrete aanbevelingen voor:
1. Titel optimalisatie
2. Gebruik van koppen (H2, H3)
3. Keyword plaatsing
4. Meta description suggestie
5. Interne link mogelijkheden

Titel: %s
Inhoud: %s

Geef een JSON object terug met de volgende structuur:
{
  "optimized_title": "geoptimaliseerde titel",
  "suggested_headings": ["kop 1", "kop 2"],
  "meta_description": "meta beschrijving van max 160 karakters",
  "keyword_suggestions": ["keyword 1", "keyword 2"],
  "improvements": ["verbetering 1", "verbetering 2"],
  "seo_score": 75
}',
			$keyword_instruction,
			$title,
			wp_strip_all_tags( $content )
		);

		$result = $this->get_provider()->generate_text( $prompt, null, array( 'max_tokens' => 1500 ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Try to parse JSON from response.
		$seo_data = $this->parse_json_response( $result['content'] );

		wp_send_json_success( array(
			'seo_data' => $seo_data,
			'raw'      => $result['content'],
		) );
	}

	/**
	 * AJAX handler for generating meta description
	 */
	public function ajax_generate_meta() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			wp_send_json_error( array( 'message' => $permission_check->get_error_message() ) );
		}

		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		if ( empty( $content ) && empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen inhoud gevonden.', 'writgoai' ) ) );
		}

		$prompt = sprintf(
			'Genereer een SEO-geoptimaliseerde meta description voor het volgende artikel. De meta description moet:
- Maximaal 155-160 karakters zijn
- Aantrekkelijk en call-to-action bevatten
- Het belangrijkste keyword bevatten
- Gebruikers motiveren om te klikken

Titel: %s
Inhoud: %s

Geef alleen de meta description terug, zonder quotes of extra uitleg.',
			$title,
			wp_strip_all_tags( substr( $content, 0, 2000 ) )
		);

		$result = $this->get_provider()->generate_text( $prompt, null, array( 'max_tokens' => 200 ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'meta_description' => trim( $result['content'] ),
		) );
	}

	/**
	 * AJAX handler for getting usage stats
	 */
	public function ajax_get_usage() {
		check_ajax_referer( 'writgoai_ai_toolbar_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toestemming.', 'writgoai' ) ) );
		}

		$usage = array(
			'requests_used'      => 0,
			'requests_remaining' => 1000,
			'daily_limit'        => 1000,
		);

		// Try to get real usage from license manager.
		if ( class_exists( 'WritgoAI_License_Manager' ) ) {
			$license_manager = WritgoAI_License_Manager::get_instance();
			$status = $license_manager->get_license_status();

			if ( isset( $status['usage'] ) ) {
				$usage = wp_parse_args( $status['usage'], $usage );
			}

			if ( isset( $status['limits'] ) ) {
				$usage['daily_limit'] = isset( $status['limits']['daily'] ) ? $status['limits']['daily'] : 1000;
			}
		}

		wp_send_json_success( $usage );
	}

	/**
	 * Parse JSON response from AI
	 *
	 * @param string $response AI response.
	 * @return array
	 */
	private function parse_json_response( $response ) {
		// Try to extract JSON from response.
		$response = trim( $response );

		// Remove markdown code blocks if present.
		$response = preg_replace( '/^```json?\s*/', '', $response );
		$response = preg_replace( '/\s*```$/', '', $response );

		$decoded = json_decode( $response, true );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded;
		}

		// Try to find JSON object in response.
		if ( preg_match( '/\{[^{}]*\}/s', $response, $matches ) ) {
			$decoded = json_decode( $matches[0], true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $decoded;
			}
		}

		return array( 'raw' => $response );
	}
}

// Initialize.
WritgoAI_AI_Actions::get_instance();
