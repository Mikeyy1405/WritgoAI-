<?php
/**
 * Content Planner Class
 *
 * AI-powered Topical Authority Map generator for content planning.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Content_Planner
 */
class WritgoCMS_Content_Planner {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Content_Planner
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
	 * @return WritgoCMS_Content_Planner
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
		add_action( 'wp_ajax_writgocms_generate_topical_map', array( $this, 'ajax_generate_topical_map' ) );
		add_action( 'wp_ajax_writgocms_generate_content_plan', array( $this, 'ajax_generate_content_plan' ) );
		add_action( 'wp_ajax_writgocms_save_content_plan', array( $this, 'ajax_save_content_plan' ) );
		add_action( 'wp_ajax_writgocms_get_saved_plans', array( $this, 'ajax_get_saved_plans' ) );
		add_action( 'wp_ajax_writgocms_delete_content_plan', array( $this, 'ajax_delete_content_plan' ) );
	}

	/**
	 * Generate topical authority map
	 *
	 * @param string $niche        The main niche/topic.
	 * @param string $website_type Type of website (blog, ecommerce, etc.).
	 * @param string $target_audience Target audience description.
	 * @return array|WP_Error
	 */
	public function generate_topical_map( $niche, $website_type = 'blog', $target_audience = '' ) {
		$prompt = $this->build_topical_map_prompt( $niche, $website_type, $target_audience );

		$result = $this->provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.7,
				'max_tokens'  => 2000,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = $this->parse_topical_map_response( $result['content'] );

		return array(
			'success'     => true,
			'topical_map' => $parsed,
			'raw_content' => $result['content'],
		);
	}

	/**
	 * Build prompt for topical authority map
	 *
	 * @param string $niche           The main niche/topic.
	 * @param string $website_type    Type of website.
	 * @param string $target_audience Target audience description.
	 * @return string
	 */
	private function build_topical_map_prompt( $niche, $website_type, $target_audience ) {
		$audience_text = ! empty( $target_audience )
			? sprintf( 'The target audience is: %s.', $target_audience )
			: '';

		return sprintf(
			'You are an expert SEO content strategist. Create a comprehensive Topical Authority Map for a %s website about "%s". %s

Generate a structured content plan with the following format. Use EXACTLY this JSON structure:

{
  "main_topic": "%s",
  "pillar_content": [
    {
      "title": "Pillar Article Title",
      "description": "Brief description of what this pillar covers",
      "keywords": ["keyword1", "keyword2", "keyword3"],
      "cluster_articles": [
        {
          "title": "Cluster Article Title",
          "description": "Brief description",
          "keywords": ["keyword1", "keyword2"],
          "priority": "high|medium|low"
        }
      ]
    }
  ],
  "content_gaps": ["Gap 1", "Gap 2"],
  "recommended_order": ["Article Title 1", "Article Title 2"]
}

Create 3-5 pillar topics with 3-5 cluster articles each. Focus on building topical authority and covering all aspects of the niche comprehensively.

Return ONLY valid JSON, no additional text or explanation.',
			esc_html( $website_type ),
			esc_html( $niche ),
			$audience_text,
			esc_html( $niche )
		);
	}

	/**
	 * Parse topical map response from AI
	 *
	 * @param string $content AI response content.
	 * @return array
	 */
	private function parse_topical_map_response( $content ) {
		// Try to extract JSON from the response.
		$content = trim( $content );

		// Remove markdown code blocks if present.
		$content = preg_replace( '/^```(?:json)?\s*/i', '', $content );
		$content = preg_replace( '/\s*```$/', '', $content );

		$decoded = json_decode( $content, true );

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			return $decoded;
		}

		// If JSON parsing fails, return structured error.
		return array(
			'error'       => true,
			'message'     => __( 'Failed to parse AI response. Please try again.', 'writgocms' ),
			'raw_content' => $content,
		);
	}

	/**
	 * Generate detailed content plan for a specific topic
	 *
	 * @param string $topic       The topic to generate content plan for.
	 * @param string $content_type Type of content (article, guide, etc.).
	 * @param array  $keywords    Keywords to target.
	 * @return array|WP_Error
	 */
	public function generate_content_plan( $topic, $content_type = 'article', $keywords = array() ) {
		$keywords_text = ! empty( $keywords )
			? sprintf( 'Target keywords: %s.', implode( ', ', $keywords ) )
			: '';

		$prompt = sprintf(
			'You are an expert content strategist. Create a detailed content outline for a %s about "%s". %s

Generate a comprehensive outline with the following JSON structure:

{
  "title": "SEO-optimized title",
  "meta_description": "160 character meta description",
  "target_keywords": ["primary keyword", "secondary keywords"],
  "estimated_word_count": 1500,
  "content_structure": {
    "introduction": "Brief description of intro approach",
    "sections": [
      {
        "heading": "H2 Section Heading",
        "key_points": ["Point 1", "Point 2", "Point 3"],
        "subsections": [
          {
            "heading": "H3 Subsection Heading",
            "key_points": ["Point 1", "Point 2"]
          }
        ]
      }
    ],
    "conclusion": "Brief description of conclusion approach"
  },
  "internal_links": ["Related topic 1", "Related topic 2"],
  "cta_suggestions": ["CTA suggestion 1", "CTA suggestion 2"]
}

Return ONLY valid JSON, no additional text.',
			esc_html( $content_type ),
			esc_html( $topic ),
			$keywords_text
		);

		$result = $this->provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.6,
				'max_tokens'  => 1500,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = $this->parse_topical_map_response( $result['content'] );

		return array(
			'success'      => true,
			'content_plan' => $parsed,
			'raw_content'  => $result['content'],
		);
	}

	/**
	 * AJAX handler for generating topical map
	 */
	public function ajax_generate_topical_map() {
		check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$niche           = isset( $_POST['niche'] ) ? sanitize_text_field( wp_unslash( $_POST['niche'] ) ) : '';
		$website_type    = isset( $_POST['website_type'] ) ? sanitize_text_field( wp_unslash( $_POST['website_type'] ) ) : 'blog';
		$target_audience = isset( $_POST['target_audience'] ) ? sanitize_textarea_field( wp_unslash( $_POST['target_audience'] ) ) : '';

		if ( empty( $niche ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a niche/topic.', 'writgocms' ) ) );
		}

		$result = $this->generate_topical_map( $niche, $website_type, $target_audience );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for generating content plan
	 */
	public function ajax_generate_content_plan() {
		check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$topic        = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : '';
		$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : 'article';
		$keywords     = isset( $_POST['keywords'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['keywords'] ) ) : array();

		if ( empty( $topic ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a topic.', 'writgocms' ) ) );
		}

		$result = $this->generate_content_plan( $topic, $content_type, $keywords );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for saving content plan
	 */
	public function ajax_save_content_plan() {
		check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$plan_name = isset( $_POST['plan_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_name'] ) ) : '';
		$plan_data = isset( $_POST['plan_data'] ) ? wp_unslash( $_POST['plan_data'] ) : '';

		if ( empty( $plan_name ) || empty( $plan_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Plan name and data are required.', 'writgocms' ) ) );
		}

		// Validate and decode JSON.
		$decoded = json_decode( $plan_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid plan data format.', 'writgocms' ) ) );
		}

		$saved_plans = get_option( 'writgocms_saved_content_plans', array() );

		$plan_id                  = wp_generate_uuid4();
		$saved_plans[ $plan_id ] = array(
			'name'       => $plan_name,
			'data'       => $decoded,
			'created_at' => current_time( 'mysql' ),
			'user_id'    => get_current_user_id(),
		);

		update_option( 'writgocms_saved_content_plans', $saved_plans );

		wp_send_json_success(
			array(
				'message' => __( 'Content plan saved successfully!', 'writgocms' ),
				'plan_id' => $plan_id,
			)
		);
	}

	/**
	 * AJAX handler for getting saved plans
	 */
	public function ajax_get_saved_plans() {
		check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$saved_plans = get_option( 'writgocms_saved_content_plans', array() );

		wp_send_json_success( array( 'plans' => $saved_plans ) );
	}

	/**
	 * AJAX handler for deleting content plan
	 */
	public function ajax_delete_content_plan() {
		check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
		}

		$plan_id = isset( $_POST['plan_id'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_id'] ) ) : '';

		if ( empty( $plan_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Plan ID is required.', 'writgocms' ) ) );
		}

		$saved_plans = get_option( 'writgocms_saved_content_plans', array() );

		if ( ! isset( $saved_plans[ $plan_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Plan not found.', 'writgocms' ) ) );
		}

		unset( $saved_plans[ $plan_id ] );
		update_option( 'writgocms_saved_content_plans', $saved_plans );

		wp_send_json_success( array( 'message' => __( 'Content plan deleted successfully!', 'writgocms' ) ) );
	}
}

// Initialize.
WritgoCMS_Content_Planner::get_instance();
