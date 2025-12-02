<?php
/**
 * Content Planner Class
 *
 * AI-powered Topical Authority Map generator for content planning.
 * Includes sitemap analysis and content gap detection.
 * Nederlandse versie - Dutch interface for WritgoAI.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Content_Planner
 */
class WritgoAI_Content_Planner {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Content_Planner
	 */
	private static $instance = null;

	/**
	 * Provider instance
	 *
	 * @var WritgoAI_AI_Provider
	 */
	private $provider;

	/**
	 * Content categories with Dutch labels
	 *
	 * @var array
	 */
	private $content_categories = array(
		'informatief'    => array(
			'icon'        => '📚',
			'label'       => 'Informatieve Content',
			'description' => 'How-to guides, uitleg artikelen, tutorials, educatieve content, FAQ\'s',
			'color'       => '#3b82f6', // Blue
		),
		'reviews'        => array(
			'icon'        => '⭐',
			'label'       => 'Reviews',
			'description' => 'Product reviews, service reviews, voor- en nadelen analyse',
			'color'       => '#f59e0b', // Gold/Orange
		),
		'top_lijstjes'   => array(
			'icon'        => '🏆',
			'label'       => 'Top Lijstjes',
			'description' => 'Beste X van 2025, Top 10, rankings, vergelijkende lijsten',
			'color'       => '#10b981', // Green
		),
		'vergelijkingen' => array(
			'icon'        => '⚖️',
			'label'       => 'Vergelijkingen',
			'description' => 'X vs Y, feature comparisons, side-by-side vergelijkingen, alternatieven',
			'color'       => '#8b5cf6', // Purple
		),
	);

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Content_Planner
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
		$this->provider = WritgoAI_AI_Provider::get_instance();
		add_action( 'wp_ajax_writgoai_generate_topical_map', array( $this, 'ajax_generate_topical_map' ) );
		add_action( 'wp_ajax_writgoai_generate_content_plan', array( $this, 'ajax_generate_content_plan' ) );
		add_action( 'wp_ajax_writgoai_save_content_plan', array( $this, 'ajax_save_content_plan' ) );
		add_action( 'wp_ajax_writgoai_get_saved_plans', array( $this, 'ajax_get_saved_plans' ) );
		add_action( 'wp_ajax_writgoai_delete_content_plan', array( $this, 'ajax_delete_content_plan' ) );
		// New AJAX handlers for sitemap analysis
		add_action( 'wp_ajax_writgoai_analyze_sitemap', array( $this, 'ajax_analyze_sitemap' ) );
		add_action( 'wp_ajax_writgoai_generate_categorized_plan', array( $this, 'ajax_generate_categorized_plan' ) );
		add_action( 'wp_ajax_writgoai_generate_article_content', array( $this, 'ajax_generate_article_content' ) );
		add_action( 'wp_ajax_writgoai_publish_content', array( $this, 'ajax_publish_content' ) );
	}

	/**
	 * Analyze WordPress sitemap
	 *
	 * @param string $manual_theme Optional manual theme override.
	 * @return array Analysis results.
	 */
	public function analyze_sitemap( $manual_theme = '' ) {
		$analysis = array(
			'total_posts'      => 0,
			'total_pages'      => 0,
			'categories'       => array(),
			'categories_count' => 0,
			'tags'             => array(),
			'top_categories'   => array(),
			'existing_topics'  => array(),
			'theme'            => '',
			'date'             => current_time( 'Y-m-d H:i:s' ),
		);

		// Get all published posts
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$analysis['total_posts'] = count( $posts );

		// Get all published pages
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$analysis['total_pages'] = count( $pages );

		// Get categories with post counts
		$categories = get_categories(
			array(
				'hide_empty' => false,
			)
		);
		$analysis['categories_count'] = count( $categories );

		$cat_data = array();
		foreach ( $categories as $category ) {
			$cat_data[] = array(
				'name'  => $category->name,
				'slug'  => $category->slug,
				'count' => $category->count,
			);
		}
		usort(
			$cat_data,
			function( $a, $b ) {
				return $b['count'] - $a['count'];
			}
		);
		$analysis['categories']     = $cat_data;
		$analysis['top_categories'] = array_slice( $cat_data, 0, 5 );

		// Get tags
		$tags = get_tags(
			array(
				'hide_empty' => false,
			)
		);
		$tag_data = array();
		foreach ( $tags as $tag ) {
			$tag_data[] = array(
				'name'  => $tag->name,
				'slug'  => $tag->slug,
				'count' => $tag->count,
			);
		}
		$analysis['tags'] = $tag_data;

		// Extract existing topics from post titles
		$post_titles = array();
		foreach ( $posts as $post_id ) {
			$post_titles[] = get_the_title( $post_id );
		}
		$analysis['existing_topics'] = array_slice( $post_titles, 0, 20 );

		// Determine theme
		if ( ! empty( $manual_theme ) ) {
			$analysis['theme'] = sanitize_text_field( $manual_theme );
		} elseif ( ! empty( $analysis['top_categories'] ) ) {
			// Use top category as theme
			$analysis['theme'] = $analysis['top_categories'][0]['name'];
		} else {
			$analysis['theme'] = get_bloginfo( 'name' );
		}

		// Save analysis to options
		update_option( 'writgoai_site_analysis', $analysis );

		return $analysis;
	}

	/**
	 * Generate categorized content plan based on site analysis
	 *
	 * @param array $analysis Site analysis data.
	 * @return array|WP_Error Content plan or error.
	 */
	public function generate_categorized_content_plan( $analysis ) {
		$theme            = isset( $analysis['theme'] ) ? $analysis['theme'] : 'Algemeen';
		$existing_topics  = isset( $analysis['existing_topics'] ) ? $analysis['existing_topics'] : array();
		$top_categories   = isset( $analysis['top_categories'] ) ? $analysis['top_categories'] : array();
		$target_audience  = get_option( 'writgoai_target_audience', '' );
		$items_per_analysis = get_option( 'writgoai_items_per_analysis', 20 );

		// Escape existing topics to prevent prompt injection
		$escaped_topics = array_map( 'esc_html', array_slice( $existing_topics, 0, 10 ) );
		$existing_topics_text = ! empty( $escaped_topics ) 
			? 'Bestaande artikelen op deze website: ' . implode( ', ', $escaped_topics )
			: '';

		// Escape category names
		$escaped_categories = array_map( 'esc_html', array_column( $top_categories, 'name' ) );
		$categories_text = ! empty( $escaped_categories )
			? 'Huidige categorieën: ' . implode( ', ', $escaped_categories )
			: '';

		$prompt = sprintf(
			'Je bent een expert SEO content strateeg die in het Nederlands schrijft. Maak een uitgebreid contentplan voor een website over "%s".

%s
%s
%s

Genereer %d nieuwe artikel ideeën die nog NIET bestaan op deze website. Verdeel ze over deze 4 categorieën:

1. 📚 Informatieve Content: How-to guides, uitleg artikelen, tutorials, educatieve content
2. ⭐ Reviews: Product reviews, service reviews, voor- en nadelen analyse  
3. 🏆 Top Lijstjes: "Beste X van 2025", "Top 10", rankings
4. ⚖️ Vergelijkingen: "X vs Y", feature comparisons, alternatieven

Gebruik EXACT deze JSON structuur (in het Nederlands):

{
  "theme": "%s",
  "total_items": %d,
  "categories": {
    "informatief": [
      {
        "title": "Nederlandse artikel titel",
        "description": "Korte beschrijving van het artikel",
        "keywords": ["zoekwoord1", "zoekwoord2"],
        "priority": "high|medium|low"
      }
    ],
    "reviews": [...],
    "top_lijstjes": [...],
    "vergelijkingen": [...]
  }
}

Zorg dat alle titels en content in het Nederlands zijn. Verdeel de %d items evenredig over de 4 categorieën.
Geef alleen geldige JSON terug, geen extra tekst.',
			esc_html( $theme ),
			$existing_topics_text,
			$categories_text,
			! empty( $target_audience ) ? 'Doelgroep: ' . $target_audience : '',
			$items_per_analysis,
			esc_html( $theme ),
			$items_per_analysis,
			$items_per_analysis
		);

		$result = $this->provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.7,
				'max_tokens'  => 3000,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = $this->parse_topical_map_response( $result['content'] );

		if ( isset( $parsed['error'] ) && $parsed['error'] ) {
			return new WP_Error( 'parse_error', $parsed['message'] );
		}

		// Save content plan
		update_option( 'writgoai_content_plan', $parsed );

		return array(
			'success'      => true,
			'content_plan' => $parsed,
		);
	}

	/**
	 * Generate article content for a specific topic
	 *
	 * @param array $item Content plan item.
	 * @return array|WP_Error Generated content or error.
	 */
	public function generate_article_content( $item ) {
		$title       = isset( $item['title'] ) ? $item['title'] : '';
		$description = isset( $item['description'] ) ? $item['description'] : '';
		$keywords    = isset( $item['keywords'] ) ? $item['keywords'] : array();
		$category    = isset( $item['category'] ) ? $item['category'] : 'informatief';
		$tone        = get_option( 'writgoai_content_tone', 'professioneel' );

		if ( empty( $title ) ) {
			return new WP_Error( 'missing_title', 'Titel is verplicht.' );
		}

		$keywords_text = ! empty( $keywords ) ? 'Zoekwoorden om te gebruiken: ' . implode( ', ', $keywords ) : '';

		$prompt = sprintf(
			'Je bent een expert content schrijver die in het Nederlands schrijft. Schrijf een uitgebreid SEO-geoptimaliseerd artikel.

Titel: %s
Beschrijving: %s
%s
Categorie: %s
Schrijfstijl: %s

Schrijf een compleet artikel met:
1. Een pakkende introductie
2. Duidelijke H2 en H3 kopjes
3. Informatieve paragrafen
4. Praktische tips waar van toepassing
5. Een sterke conclusie

Het artikel moet minimaal 800 woorden zijn en volledig in het Nederlands geschreven zijn.
Gebruik HTML opmaak (h2, h3, p, ul, li, strong, em tags).

Begin direct met de content, geen titel of meta informatie.',
			esc_html( $title ),
			esc_html( $description ),
			$keywords_text,
			esc_html( $category ),
			esc_html( $tone )
		);

		$result = $this->provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.7,
				'max_tokens'  => 2500,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Generate meta description
		$meta_prompt = sprintf(
			'Schrijf een SEO meta description van maximaal 155 tekens voor dit artikel in het Nederlands:
Titel: %s
Onderwerp: %s',
			esc_html( $title ),
			esc_html( $description )
		);

		$meta_result = $this->provider->generate_text(
			$meta_prompt,
			null,
			array(
				'temperature' => 0.5,
				'max_tokens'  => 100,
			)
		);

		$meta_description = '';
		if ( ! is_wp_error( $meta_result ) && isset( $meta_result['content'] ) ) {
			$meta_description = sanitize_text_field( $meta_result['content'] );
		}

		$generated_content = array(
			'title'            => $title,
			'content'          => $result['content'],
			'meta_description' => $meta_description,
			'keywords'         => $keywords,
			'category'         => $category,
			'generated_date'   => current_time( 'Y-m-d H:i:s' ),
		);

		// Save to generated content list
		$all_generated = get_option( 'writgoai_generated_content', array() );
		$all_generated[] = $generated_content;
		update_option( 'writgoai_generated_content', $all_generated );

		return array(
			'success' => true,
			'content' => $generated_content,
		);
	}

	/**
	 * Publish generated content as WordPress post
	 *
	 * @param array  $content Generated content data.
	 * @param string $status Post status (draft or publish).
	 * @return array|WP_Error Post data or error.
	 */
	public function publish_content( $content, $status = 'draft' ) {
		if ( empty( $content['title'] ) || empty( $content['content'] ) ) {
			return new WP_Error( 'missing_data', 'Titel en content zijn verplicht.' );
		}

		$post_data = array(
			'post_title'   => sanitize_text_field( $content['title'] ),
			'post_content' => wp_kses_post( $content['content'] ),
			'post_status'  => in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft',
			'post_type'    => 'post',
			'post_author'  => get_current_user_id(),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Add meta description if available
		if ( ! empty( $content['meta_description'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $content['meta_description'] );
			update_post_meta( $post_id, 'writgoai_meta_description', $content['meta_description'] );
		}

		// Add keywords as tags
		if ( ! empty( $content['keywords'] ) && is_array( $content['keywords'] ) ) {
			wp_set_post_tags( $post_id, $content['keywords'], true );
		}

		return array(
			'success' => true,
			'post_id' => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			'view_url' => get_permalink( $post_id ),
		);
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
			'message'     => __( 'Failed to parse AI response. Please try again.', 'writgoai' ),
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
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
		}

		$niche           = isset( $_POST['niche'] ) ? sanitize_text_field( wp_unslash( $_POST['niche'] ) ) : '';
		$website_type    = isset( $_POST['website_type'] ) ? sanitize_text_field( wp_unslash( $_POST['website_type'] ) ) : 'blog';
		$target_audience = isset( $_POST['target_audience'] ) ? sanitize_textarea_field( wp_unslash( $_POST['target_audience'] ) ) : '';

		if ( empty( $niche ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a niche/topic.', 'writgoai' ) ) );
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
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
		}

		$topic        = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : '';
		$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : 'article';
		$keywords     = isset( $_POST['keywords'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['keywords'] ) ) : array();

		if ( empty( $topic ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a topic.', 'writgoai' ) ) );
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
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
		}

		$plan_name = isset( $_POST['plan_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_name'] ) ) : '';
		$plan_data = isset( $_POST['plan_data'] ) ? wp_unslash( $_POST['plan_data'] ) : '';

		if ( empty( $plan_name ) || empty( $plan_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Plan name and data are required.', 'writgoai' ) ) );
		}

		// Validate and decode JSON.
		$decoded = json_decode( $plan_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid plan data format.', 'writgoai' ) ) );
		}

		$saved_plans = get_option( 'writgoai_saved_content_plans', array() );

		$plan_id                  = wp_generate_uuid4();
		$saved_plans[ $plan_id ] = array(
			'name'       => $plan_name,
			'data'       => $decoded,
			'created_at' => current_time( 'mysql' ),
			'user_id'    => get_current_user_id(),
		);

		update_option( 'writgoai_saved_content_plans', $saved_plans );

		wp_send_json_success(
			array(
				'message' => __( 'Content plan saved successfully!', 'writgoai' ),
				'plan_id' => $plan_id,
			)
		);
	}

	/**
	 * AJAX handler for getting saved plans
	 */
	public function ajax_get_saved_plans() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
		}

		$saved_plans = get_option( 'writgoai_saved_content_plans', array() );

		wp_send_json_success( array( 'plans' => $saved_plans ) );
	}

	/**
	 * AJAX handler for deleting content plan
	 */
	public function ajax_delete_content_plan() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
		}

		$plan_id = isset( $_POST['plan_id'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_id'] ) ) : '';

		if ( empty( $plan_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Plan ID is required.', 'writgoai' ) ) );
		}

		$saved_plans = get_option( 'writgoai_saved_content_plans', array() );

		if ( ! isset( $saved_plans[ $plan_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Plan not found.', 'writgoai' ) ) );
		}

		unset( $saved_plans[ $plan_id ] );
		update_option( 'writgoai_saved_content_plans', $saved_plans );

		wp_send_json_success( array( 'message' => 'Contentplan succesvol verwijderd!' ) );
	}

	/**
	 * AJAX handler for sitemap analysis
	 */
	public function ajax_analyze_sitemap() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$manual_theme = isset( $_POST['manual_theme'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_theme'] ) ) : '';

		$analysis = $this->analyze_sitemap( $manual_theme );

		wp_send_json_success(
			array(
				'message'  => 'Website analyse voltooid!',
				'analysis' => $analysis,
			)
		);
	}

	/**
	 * AJAX handler for generating categorized content plan
	 */
	public function ajax_generate_categorized_plan() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$analysis = get_option( 'writgoai_site_analysis', array() );

		if ( empty( $analysis ) ) {
			wp_send_json_error( array( 'message' => 'Voer eerst een website analyse uit.' ) );
		}

		$result = $this->generate_categorized_content_plan( $analysis );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for generating article content
	 */
	public function ajax_generate_article_content() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$item_json = isset( $_POST['item'] ) ? wp_unslash( $_POST['item'] ) : '';

		if ( empty( $item_json ) ) {
			wp_send_json_error( array( 'message' => 'Geen artikel item opgegeven.' ) );
		}

		$item = json_decode( $item_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => 'Ongeldig artikel formaat.' ) );
		}

		$result = $this->generate_article_content( $item );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for publishing content
	 */
	public function ajax_publish_content() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$content_json = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'draft';

		if ( empty( $content_json ) ) {
			wp_send_json_error( array( 'message' => 'Geen content opgegeven.' ) );
		}

		$content = json_decode( $content_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => 'Ongeldig content formaat.' ) );
		}

		$result = $this->publish_content( $content, $status );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$status_text = 'draft' === $status ? 'als concept' : 'direct';
		wp_send_json_success(
			array(
				'message'  => 'Content succesvol gepubliceerd ' . $status_text . '!',
				'post_id'  => $result['post_id'],
				'edit_url' => $result['edit_url'],
				'view_url' => $result['view_url'],
			)
		);
	}
}

// Initialize.
WritgoAI_Content_Planner::get_instance();
