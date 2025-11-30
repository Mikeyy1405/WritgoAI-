<?php
/**
 * CTR Optimization Tool
 *
 * Analyzes meta content and suggests improvements using AI.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_CTR_Optimizer
 */
class WritgoCMS_CTR_Optimizer {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_CTR_Optimizer
	 */
	private static $instance = null;

	/**
	 * AIML Provider instance
	 *
	 * @var WritgoCMS_AIML_Provider
	 */
	private $ai_provider;

	/**
	 * GSC Data Handler instance
	 *
	 * @var WritgoCMS_GSC_Data_Handler
	 */
	private $data_handler;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_CTR_Optimizer
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
		$this->ai_provider  = WritgoCMS_AIML_Provider::get_instance();
		$this->data_handler = WritgoCMS_GSC_Data_Handler::get_instance();

		add_action( 'wp_ajax_writgocms_ctr_analyze', array( $this, 'ajax_analyze' ) );
		add_action( 'wp_ajax_writgocms_ctr_generate_suggestions', array( $this, 'ajax_generate_suggestions' ) );
	}

	/**
	 * Analyze post CTR
	 *
	 * @param int $post_id Post ID.
	 * @return array|WP_Error
	 */
	public function analyze_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Post niet gevonden.', 'writgocms' ) );
		}

		// Get current meta data.
		$current_title       = $this->get_meta_title( $post_id );
		$current_description = $this->get_meta_description( $post_id );

		// Get GSC data.
		$gsc_data = $this->data_handler->get_post_data( $post_id );

		$analysis = array(
			'post_id'             => $post_id,
			'post_title'          => $post->post_title,
			'current_title'       => $current_title,
			'current_description' => $current_description,
			'title_length'        => mb_strlen( $current_title ),
			'description_length'  => mb_strlen( $current_description ),
			'title_issues'        => $this->check_title_issues( $current_title ),
			'description_issues'  => $this->check_description_issues( $current_description ),
			'gsc_data'            => $gsc_data,
		);

		if ( $gsc_data && isset( $gsc_data['page_data'] ) ) {
			$position      = $gsc_data['page_data']->avg_position;
			$current_ctr   = $gsc_data['page_data']->avg_ctr;
			$benchmark_ctr = $this->data_handler->get_ctr_benchmark( $position );

			$analysis['current_ctr']     = $current_ctr;
			$analysis['benchmark_ctr']   = $benchmark_ctr;
			$analysis['ctr_performance'] = $this->get_ctr_performance( $current_ctr, $benchmark_ctr );
			$analysis['potential_clicks'] = $this->calculate_potential_clicks(
				$gsc_data['page_data']->total_impressions,
				$current_ctr,
				$benchmark_ctr
			);
		}

		return $analysis;
	}

	/**
	 * Get meta title
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_meta_title( $post_id ) {
		// Check popular SEO plugins.
		$title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
		if ( ! empty( $title ) ) {
			return $title;
		}

		$title = get_post_meta( $post_id, 'rank_math_title', true );
		if ( ! empty( $title ) ) {
			return $title;
		}

		$title = get_post_meta( $post_id, '_aioseo_title', true );
		if ( ! empty( $title ) ) {
			return $title;
		}

		// Fallback to post title.
		return get_the_title( $post_id );
	}

	/**
	 * Get meta description
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_meta_description( $post_id ) {
		// Check popular SEO plugins.
		$description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( ! empty( $description ) ) {
			return $description;
		}

		$description = get_post_meta( $post_id, 'rank_math_description', true );
		if ( ! empty( $description ) ) {
			return $description;
		}

		$description = get_post_meta( $post_id, '_aioseo_description', true );
		if ( ! empty( $description ) ) {
			return $description;
		}

		// Fallback to excerpt.
		$post = get_post( $post_id );
		if ( ! empty( $post->post_excerpt ) ) {
			return $post->post_excerpt;
		}

		return '';
	}

	/**
	 * Check title issues
	 *
	 * @param string $title Meta title.
	 * @return array
	 */
	private function check_title_issues( $title ) {
		$issues = array();
		$length = mb_strlen( $title );

		if ( $length < 30 ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => __( 'Title is te kort. Optimale lengte is 50-60 karakters.', 'writgocms' ),
			);
		} elseif ( $length > 60 ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => __( 'Title is te lang en wordt mogelijk afgekort in zoekresultaten.', 'writgocms' ),
			);
		}

		if ( empty( $title ) ) {
			$issues[] = array(
				'type'    => 'error',
				'message' => __( 'Geen meta title ingesteld.', 'writgocms' ),
			);
		}

		// Check for power words.
		$power_words = array( 'gratis', 'beste', 'nieuw', 'exclusief', 'geheim', 'ultiem', 'snel', 'eenvoudig', 'bewezen', 'gids', 'tips' );
		$has_power_word = false;
		foreach ( $power_words as $word ) {
			if ( stripos( $title, $word ) !== false ) {
				$has_power_word = true;
				break;
			}
		}

		if ( ! $has_power_word ) {
			$issues[] = array(
				'type'    => 'suggestion',
				'message' => __( 'Overweeg power words toe te voegen voor betere CTR.', 'writgocms' ),
			);
		}

		// Check for numbers.
		if ( ! preg_match( '/\d/', $title ) ) {
			$issues[] = array(
				'type'    => 'suggestion',
				'message' => __( 'Getallen in titles verhogen vaak de CTR.', 'writgocms' ),
			);
		}

		return $issues;
	}

	/**
	 * Check description issues
	 *
	 * @param string $description Meta description.
	 * @return array
	 */
	private function check_description_issues( $description ) {
		$issues = array();
		$length = mb_strlen( $description );

		if ( empty( $description ) ) {
			$issues[] = array(
				'type'    => 'error',
				'message' => __( 'Geen meta description ingesteld. Google zal zelf tekst kiezen.', 'writgocms' ),
			);
		} elseif ( $length < 120 ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => __( 'Description is te kort. Optimale lengte is 150-160 karakters.', 'writgocms' ),
			);
		} elseif ( $length > 160 ) {
			$issues[] = array(
				'type'    => 'warning',
				'message' => __( 'Description is te lang en wordt mogelijk afgekort.', 'writgocms' ),
			);
		}

		// Check for call to action.
		$cta_words = array( 'ontdek', 'leer', 'bekijk', 'download', 'probeer', 'start', 'lees', 'krijg', 'vind' );
		$has_cta = false;
		foreach ( $cta_words as $word ) {
			if ( stripos( $description, $word ) !== false ) {
				$has_cta = true;
				break;
			}
		}

		if ( ! $has_cta && ! empty( $description ) ) {
			$issues[] = array(
				'type'    => 'suggestion',
				'message' => __( 'Voeg een call-to-action toe om de CTR te verhogen.', 'writgocms' ),
			);
		}

		return $issues;
	}

	/**
	 * Get CTR performance label
	 *
	 * @param float $current_ctr   Current CTR.
	 * @param float $benchmark_ctr Benchmark CTR.
	 * @return array
	 */
	private function get_ctr_performance( $current_ctr, $benchmark_ctr ) {
		if ( $current_ctr >= $benchmark_ctr ) {
			$percentage = round( ( $current_ctr / $benchmark_ctr ) * 100 );
			return array(
				'status'  => 'good',
				'label'   => __( 'Uitstekend', 'writgocms' ),
				'message' => sprintf(
					/* translators: %d: percentage above benchmark */
					__( 'CTR is %d%% van de benchmark.', 'writgocms' ),
					$percentage
				),
			);
		} elseif ( $current_ctr >= $benchmark_ctr * 0.7 ) {
			return array(
				'status'  => 'average',
				'label'   => __( 'Gemiddeld', 'writgocms' ),
				'message' => __( 'CTR is enigszins onder de benchmark.', 'writgocms' ),
			);
		} else {
			return array(
				'status'  => 'poor',
				'label'   => __( 'Verbetering nodig', 'writgocms' ),
				'message' => __( 'CTR is significant onder de benchmark.', 'writgocms' ),
			);
		}
	}

	/**
	 * Calculate potential extra clicks
	 *
	 * @param int   $impressions  Total impressions.
	 * @param float $current_ctr  Current CTR.
	 * @param float $benchmark_ctr Benchmark CTR.
	 * @return int
	 */
	private function calculate_potential_clicks( $impressions, $current_ctr, $benchmark_ctr ) {
		if ( $current_ctr >= $benchmark_ctr ) {
			return 0;
		}

		$current_clicks   = $impressions * $current_ctr;
		$potential_clicks = $impressions * $benchmark_ctr;

		return max( 0, round( $potential_clicks - $current_clicks ) );
	}

	/**
	 * Generate AI suggestions for meta content
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $keyword     Target keyword.
	 * @param array  $analysis    Analysis data.
	 * @return array|WP_Error
	 */
	public function generate_suggestions( $post_id, $keyword = '', $analysis = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Post niet gevonden.', 'writgocms' ) );
		}

		$current_title       = isset( $analysis['current_title'] ) ? $analysis['current_title'] : $this->get_meta_title( $post_id );
		$current_description = isset( $analysis['current_description'] ) ? $analysis['current_description'] : $this->get_meta_description( $post_id );

		$prompt = $this->build_suggestion_prompt( $post, $current_title, $current_description, $keyword );

		$result = $this->ai_provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.7,
				'max_tokens'  => 1000,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$suggestions = $this->parse_suggestions( $result['content'] );

		return $suggestions;
	}

	/**
	 * Build suggestion prompt
	 *
	 * @param WP_Post $post                Post object.
	 * @param string  $current_title       Current title.
	 * @param string  $current_description Current description.
	 * @param string  $keyword             Target keyword.
	 * @return string
	 */
	private function build_suggestion_prompt( $post, $current_title, $current_description, $keyword = '' ) {
		$keyword_text = ! empty( $keyword ) ? sprintf( 'Het target keyword is: %s.', $keyword ) : '';

		return sprintf(
			'Je bent een SEO expert gespecialiseerd in het optimaliseren van CTR (Click-Through Rate) voor zoekresultaten. 

Ik heb een blog post met de volgende gegevens:
- Post titel: %s
- Huidige meta title: %s
- Huidige meta description: %s
%s

Genereer 3 verbeterde versies van de meta title en meta description die:
1. Optimaal zijn voor CTR (lokken klikken uit)
2. De juiste lengte hebben (title: 50-60 karakters, description: 150-160 karakters)
3. Power words bevatten die aandacht trekken
4. Een duidelijke call-to-action hebben
5. Het keyword op een natuurlijke manier bevatten

Geef je antwoord in het volgende JSON formaat:
{
  "suggestions": [
    {
      "title": "Voorgestelde meta title",
      "title_length": 55,
      "description": "Voorgestelde meta description",
      "description_length": 155,
      "expected_ctr_improvement": "10-20%%",
      "reasoning": "Korte uitleg waarom dit beter werkt"
    }
  ],
  "tips": ["Algemene tip 1", "Algemene tip 2"]
}

Antwoord alleen met valid JSON, geen andere tekst. Alles in het Nederlands.',
			esc_html( $post->post_title ),
			esc_html( $current_title ),
			esc_html( $current_description ),
			$keyword_text
		);
	}

	/**
	 * Parse suggestions from AI response
	 *
	 * @param string $content AI response content.
	 * @return array
	 */
	private function parse_suggestions( $content ) {
		$content = trim( $content );

		// Remove markdown code blocks if present.
		$content = preg_replace( '/^```(?:json)?\s*/i', '', $content );
		$content = preg_replace( '/\s*```$/', '', $content );

		$decoded = json_decode( $content, true );

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			return $decoded;
		}

		return array(
			'error'       => true,
			'message'     => __( 'Kon AI antwoord niet verwerken. Probeer opnieuw.', 'writgocms' ),
			'raw_content' => $content,
		);
	}

	/**
	 * AJAX handler for analyze
	 */
	public function ajax_analyze() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Post ID is vereist.', 'writgocms' ) ) );
		}

		$analysis = $this->analyze_post( $post_id );

		if ( is_wp_error( $analysis ) ) {
			wp_send_json_error( array( 'message' => $analysis->get_error_message() ) );
		}

		wp_send_json_success( $analysis );
	}

	/**
	 * AJAX handler for generate suggestions
	 */
	public function ajax_generate_suggestions() {
		check_ajax_referer( 'writgocms_gsc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgocms' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Post ID is vereist.', 'writgocms' ) ) );
		}

		$analysis    = $this->analyze_post( $post_id );
		$suggestions = $this->generate_suggestions( $post_id, $keyword, $analysis );

		if ( is_wp_error( $suggestions ) ) {
			wp_send_json_error( array( 'message' => $suggestions->get_error_message() ) );
		}

		wp_send_json_success( array(
			'analysis'    => $analysis,
			'suggestions' => $suggestions,
		) );
	}
}

// Initialize.
WritgoCMS_CTR_Optimizer::get_instance();
