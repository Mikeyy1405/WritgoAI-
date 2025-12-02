<?php
/**
 * Social Media Manager Class
 *
 * AI-powered Social Media multi-channel posting with automatic image generation.
 * Nederlandse versie - Dutch interface for WritgoAI.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoAI_Social_Media_Manager
 */
class WritgoAI_Social_Media_Manager {

	/**
	 * Instance
	 *
	 * @var WritgoAI_Social_Media_Manager
	 */
	private static $instance = null;

	/**
	 * Provider instance
	 *
	 * @var WritgoAI_AI_Provider
	 */
	private $provider;

	/**
	 * Supported platforms with Dutch labels
	 *
	 * @var array
	 */
	private $platforms = array(
		'facebook'  => array(
			'name'        => 'Facebook',
			'icon'        => '📘',
			'max_chars'   => 63206,
			'image_sizes' => array(
				'post'  => array( 1200, 630 ),
				'story' => array( 1080, 1920 ),
			),
		),
		'instagram' => array(
			'name'        => 'Instagram',
			'icon'        => '📷',
			'max_chars'   => 2200,
			'image_sizes' => array(
				'post'     => array( 1080, 1080 ),
				'story'    => array( 1080, 1920 ),
				'carousel' => array( 1080, 1080 ),
			),
		),
		'twitter'   => array(
			'name'        => 'Twitter/X',
			'icon'        => '🐦',
			'max_chars'   => 280,
			'image_sizes' => array(
				'post' => array( 1200, 675 ),
			),
		),
		'linkedin'  => array(
			'name'        => 'LinkedIn',
			'icon'        => '💼',
			'max_chars'   => 3000,
			'image_sizes' => array(
				'post' => array( 1200, 627 ),
			),
		),
		'pinterest' => array(
			'name'        => 'Pinterest',
			'icon'        => '📌',
			'max_chars'   => 500,
			'image_sizes' => array(
				'pin' => array( 1000, 1500 ),
			),
		),
		'tiktok'    => array(
			'name'        => 'TikTok',
			'icon'        => '🎵',
			'max_chars'   => 2200,
			'image_sizes' => array(
				'video' => array( 1080, 1920 ),
			),
		),
		'threads'   => array(
			'name'        => 'Threads',
			'icon'        => '🧵',
			'max_chars'   => 500,
			'image_sizes' => array(
				'post' => array( 1080, 1080 ),
			),
		),
	);

	/**
	 * Image template types with Dutch labels
	 *
	 * @var array
	 */
	private $template_types = array(
		'stats_card'       => array(
			'icon'        => '📊',
			'label'       => 'Stats Card',
			'description' => 'Cijfers en feiten prominent',
		),
		'quote_card'       => array(
			'icon'        => '💬',
			'label'       => 'Quote Card',
			'description' => 'Inspirerende quotes op mooie achtergrond',
		),
		'listicle'         => array(
			'icon'        => '📝',
			'label'       => 'Listicle',
			'description' => 'Genummerde lijst met icons',
		),
		'product_showcase' => array(
			'icon'        => '🎯',
			'label'       => 'Product Showcase',
			'description' => 'Product centraal met highlights',
		),
		'comparison'       => array(
			'icon'        => '⚖️',
			'label'       => 'Comparison',
			'description' => 'Side-by-side vergelijking',
		),
		'custom_ai'        => array(
			'icon'        => '🤖',
			'label'       => 'Custom AI',
			'description' => 'DALL-E 3 gegenereerde afbeelding',
		),
	);

	/**
	 * Content tones with Dutch labels
	 *
	 * @var array
	 */
	private $content_tones = array(
		'professioneel' => 'Professioneel',
		'informeel'     => 'Informeel',
		'vriendelijk'   => 'Vriendelijk',
		'enthousiast'   => 'Enthousiast',
		'zakelijk'      => 'Zakelijk',
		'inspirerend'   => 'Inspirerend',
	);

	/**
	 * Get instance
	 *
	 * @return WritgoAI_Social_Media_Manager
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

		// AJAX handlers.
		add_action( 'wp_ajax_writgoai_generate_social_posts', array( $this, 'ajax_generate_social_posts' ) );
		add_action( 'wp_ajax_writgoai_save_social_post', array( $this, 'ajax_save_social_post' ) );
		add_action( 'wp_ajax_writgoai_schedule_social_post', array( $this, 'ajax_schedule_social_post' ) );
		add_action( 'wp_ajax_writgoai_get_scheduled_posts', array( $this, 'ajax_get_scheduled_posts' ) );
		add_action( 'wp_ajax_writgoai_delete_scheduled_post', array( $this, 'ajax_delete_scheduled_post' ) );
		add_action( 'wp_ajax_writgoai_save_hashtag_set', array( $this, 'ajax_save_hashtag_set' ) );
		add_action( 'wp_ajax_writgoai_get_hashtag_sets', array( $this, 'ajax_get_hashtag_sets' ) );
		add_action( 'wp_ajax_writgoai_suggest_hashtags', array( $this, 'ajax_suggest_hashtags' ) );
		add_action( 'wp_ajax_writgoai_get_social_analytics', array( $this, 'ajax_get_social_analytics' ) );
		add_action( 'wp_ajax_writgoai_get_blog_posts', array( $this, 'ajax_get_blog_posts' ) );
	}

	/**
	 * Get supported platforms
	 *
	 * @return array
	 */
	public function get_platforms() {
		return $this->platforms;
	}

	/**
	 * Get template types
	 *
	 * @return array
	 */
	public function get_template_types() {
		return $this->template_types;
	}

	/**
	 * Get content tones
	 *
	 * @return array
	 */
	public function get_content_tones() {
		return $this->content_tones;
	}

	/**
	 * Create database tables
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Social accounts table.
		$table_accounts = $wpdb->prefix . 'writgoai_social_accounts';
		$sql_accounts   = "CREATE TABLE IF NOT EXISTS $table_accounts (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			platform varchar(50) NOT NULL,
			account_name varchar(255) NOT NULL,
			account_id varchar(255) DEFAULT '',
			access_token text,
			refresh_token text,
			expires_at datetime DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY platform (platform),
			KEY status (status)
		) $charset_collate;";

		// Social posts table.
		$table_posts = $wpdb->prefix . 'writgoai_social_posts';
		$sql_posts   = "CREATE TABLE IF NOT EXISTS $table_posts (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) DEFAULT NULL,
			platform varchar(50) NOT NULL,
			content text NOT NULL,
			media_urls longtext,
			hashtags varchar(500) DEFAULT '',
			scheduled_time datetime DEFAULT NULL,
			posted_time datetime DEFAULT NULL,
			status varchar(20) DEFAULT 'draft',
			analytics longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY platform (platform),
			KEY status (status),
			KEY scheduled_time (scheduled_time)
		) $charset_collate;";

		// Generated images table.
		$table_images = $wpdb->prefix . 'writgoai_generated_images';
		$sql_images   = "CREATE TABLE IF NOT EXISTS $table_images (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) DEFAULT NULL,
			platform varchar(50) NOT NULL,
			template_type varchar(100) NOT NULL,
			image_url varchar(500) NOT NULL,
			attachment_id bigint(20) DEFAULT NULL,
			width int(11) DEFAULT NULL,
			height int(11) DEFAULT NULL,
			generation_method varchar(50) DEFAULT 'template',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY platform (platform)
		) $charset_collate;";

		// Hashtag sets table.
		$table_hashtags = $wpdb->prefix . 'writgoai_hashtag_sets';
		$sql_hashtags   = "CREATE TABLE IF NOT EXISTS $table_hashtags (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			hashtags longtext NOT NULL,
			category varchar(100) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY category (category)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_accounts );
		dbDelta( $sql_posts );
		dbDelta( $sql_images );
		dbDelta( $sql_hashtags );
	}

	/**
	 * Generate platform-specific social posts from blog content
	 *
	 * @param string $content   Blog content or summary.
	 * @param string $title     Blog title.
	 * @param array  $platforms Platforms to generate for.
	 * @param array  $options   Generation options.
	 * @return array|WP_Error Generated posts or error.
	 */
	public function generate_social_posts( $content, $title, $platforms = array(), $options = array() ) {
		$defaults = array(
			'tone'          => 'professioneel',
			'use_hashtags'  => true,
			'use_emojis'    => true,
			'include_link'  => true,
			'link_url'      => '',
		);
		$options  = wp_parse_args( $options, $defaults );

		if ( empty( $platforms ) ) {
			$platforms = array( 'facebook', 'instagram', 'twitter', 'linkedin' );
		}

		$generated_posts = array();

		foreach ( $platforms as $platform ) {
			$result = $this->generate_platform_post( $content, $title, $platform, $options );
			if ( ! is_wp_error( $result ) ) {
				$generated_posts[ $platform ] = $result;
			}
		}

		return $generated_posts;
	}

	/**
	 * Generate post for a specific platform
	 *
	 * @param string $content  Blog content or summary.
	 * @param string $title    Blog title.
	 * @param string $platform Platform to generate for.
	 * @param array  $options  Generation options.
	 * @return array|WP_Error Generated post or error.
	 */
	public function generate_platform_post( $content, $title, $platform, $options = array() ) {
		if ( ! isset( $this->platforms[ $platform ] ) ) {
			return new WP_Error( 'invalid_platform', 'Ongeldig platform: ' . $platform );
		}

		$platform_info = $this->platforms[ $platform ];
		$max_chars     = $platform_info['max_chars'];
		$tone          = isset( $options['tone'] ) ? $options['tone'] : 'professioneel';
		$use_hashtags  = isset( $options['use_hashtags'] ) ? $options['use_hashtags'] : true;
		$use_emojis    = isset( $options['use_emojis'] ) ? $options['use_emojis'] : true;
		$include_link  = isset( $options['include_link'] ) ? $options['include_link'] : true;
		$link_url      = isset( $options['link_url'] ) ? $options['link_url'] : '[link]';

		$prompt = $this->build_platform_prompt( $platform, $content, $title, $tone, $max_chars, $use_hashtags, $use_emojis, $include_link, $link_url );

		$result = $this->provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.7,
				'max_tokens'  => 500,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$generated_content = trim( $result['content'] );

		// Extract hashtags if present.
		$hashtags = array();
		if ( preg_match_all( '/#(\w+)/u', $generated_content, $matches ) ) {
			$hashtags = $matches[1];
		}

		return array(
			'platform'  => $platform,
			'content'   => $generated_content,
			'hashtags'  => $hashtags,
			'char_count' => mb_strlen( $generated_content ),
			'max_chars' => $max_chars,
		);
	}

	/**
	 * Build prompt for platform-specific post generation
	 *
	 * @param string $platform     Platform name.
	 * @param string $content      Blog content.
	 * @param string $title        Blog title.
	 * @param string $tone         Content tone.
	 * @param int    $max_chars    Maximum characters.
	 * @param bool   $use_hashtags Whether to use hashtags.
	 * @param bool   $use_emojis   Whether to use emojis.
	 * @param bool   $include_link Whether to include link.
	 * @param string $link_url     Link URL.
	 * @return string Prompt.
	 */
	private function build_platform_prompt( $platform, $content, $title, $tone, $max_chars, $use_hashtags, $use_emojis, $include_link, $link_url ) {
		$platform_guidelines = array(
			'facebook'  => 'Schrijf een uitgebreide Facebook post met engagement-vraag aan het einde. Gebruik 2-3 paragrafen.',
			'instagram' => 'Schrijf een Instagram caption met visual focus. Gebruik korte zinnen en voeg veel relevante hashtags toe (10-15). Vermeld "Link in bio!" als er een link is.',
			'twitter'   => 'Schrijf een korte, krachtige tweet van maximaal 280 karakters. Wees beknopt maar impactvol. Gebruik 2-3 hashtags.',
			'linkedin'  => 'Schrijf een professionele LinkedIn post met data en inzichten. Gebruik bullet points (✅) voor key takeaways. Focus op zakelijke waarde.',
			'pinterest' => 'Schrijf een Pinterest pin beschrijving die zoekbaar is. Gebruik keywords en beschrijf de visuele content.',
			'tiktok'    => 'Schrijf een TikTok video beschrijving die trending is. Gebruik populaire hashtags en call-to-actions.',
			'threads'   => 'Schrijf een korte, conversational Threads post. Wees authentiek en uitnodigend voor discussie.',
		);

		$guideline = isset( $platform_guidelines[ $platform ] ) ? $platform_guidelines[ $platform ] : '';

		$emoji_instruction = $use_emojis ? 'Gebruik relevante emoji\'s om de post visueel aantrekkelijker te maken.' : 'Gebruik GEEN emoji\'s.';
		$hashtag_instruction = $use_hashtags ? 'Voeg relevante hashtags toe.' : 'Gebruik GEEN hashtags.';
		$link_instruction = $include_link ? 'Voeg de link toe: ' . esc_html( $link_url ) : 'Voeg GEEN link toe.';

		$prompt = sprintf(
			'Je bent een expert social media manager die in het Nederlands schrijft.

Maak een %s post voor het volgende blog artikel:

Titel: %s

Samenvatting:
%s

Richtlijnen:
- %s
- Schrijfstijl: %s
- Maximum karakters: %d
- %s
- %s
- %s

Geef alleen de post tekst terug, geen extra uitleg of instructies.',
			esc_html( $this->platforms[ $platform ]['name'] ),
			esc_html( $title ),
			esc_html( wp_trim_words( $content, 200, '...' ) ),
			$guideline,
			esc_html( $tone ),
			$max_chars,
			$emoji_instruction,
			$hashtag_instruction,
			$link_instruction
		);

		return $prompt;
	}

	/**
	 * Suggest hashtags for content
	 *
	 * @param string $content  Content to analyze.
	 * @param string $platform Platform for hashtags.
	 * @param int    $count    Number of hashtags to suggest.
	 * @return array|WP_Error Suggested hashtags or error.
	 */
	public function suggest_hashtags( $content, $platform = 'instagram', $count = 15 ) {
		$prompt = sprintf(
			'Je bent een expert in social media hashtags. Suggereer %d relevante hashtags voor de volgende content op %s.

Content:
%s

Geef de hashtags terug als een JSON array, bijvoorbeeld: ["hashtag1", "hashtag2", "hashtag3"]
Gebruik alleen kleine letters, geen # symbool.
Focus op een mix van populaire en niche hashtags.',
			$count,
			esc_html( $platform ),
			esc_html( wp_trim_words( $content, 100, '...' ) )
		);

		$result = $this->provider->generate_text(
			$prompt,
			null,
			array(
				'temperature' => 0.6,
				'max_tokens'  => 200,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$content = trim( $result['content'] );
		$content = preg_replace( '/^```(?:json)?\s*/i', '', $content );
		$content = preg_replace( '/\s*```$/', '', $content );

		$hashtags = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $hashtags ) ) {
			// Try to extract hashtags from plain text.
			preg_match_all( '/["\']?#?(\w+)["\']?/', $content, $matches );
			$hashtags = isset( $matches[1] ) ? $matches[1] : array();
		}

		return array_map( 'strtolower', array_slice( $hashtags, 0, $count ) );
	}

	/**
	 * Get best posting times per platform
	 *
	 * @return array Best posting times.
	 */
	public function get_best_posting_times() {
		return array(
			'facebook'  => array(
				'best_days'  => array( 'dinsdag', 'donderdag' ),
				'best_times' => array( '19:00' ),
				'description' => 'Di/Do 19:00',
			),
			'instagram' => array(
				'best_days'  => array( 'woensdag', 'vrijdag' ),
				'best_times' => array( '12:00', '19:00' ),
				'description' => 'Wo 12:00, Vr 19:00',
			),
			'twitter'   => array(
				'best_days'  => array( 'dagelijks' ),
				'best_times' => array( '12:00', '13:00' ),
				'description' => 'Dagelijks 12:00-13:00',
			),
			'linkedin'  => array(
				'best_days'  => array( 'dinsdag', 'woensdag' ),
				'best_times' => array( '08:00' ),
				'description' => 'Di/Wo 08:00',
			),
			'pinterest' => array(
				'best_days'  => array( 'zaterdag', 'zondag' ),
				'best_times' => array( '20:00', '21:00' ),
				'description' => 'Za/Zo 20:00-21:00',
			),
		);
	}

	/**
	 * Save social post to database
	 *
	 * @param array $post_data Post data.
	 * @return int|WP_Error Post ID or error.
	 */
	public function save_social_post( $post_data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'writgoai_social_posts';

		$data = array(
			'post_id'        => isset( $post_data['post_id'] ) ? absint( $post_data['post_id'] ) : null,
			'platform'       => sanitize_text_field( $post_data['platform'] ),
			'content'        => wp_kses_post( $post_data['content'] ),
			'media_urls'     => isset( $post_data['media_urls'] ) ? wp_json_encode( $post_data['media_urls'] ) : null,
			'hashtags'       => isset( $post_data['hashtags'] ) ? sanitize_text_field( implode( ',', (array) $post_data['hashtags'] ) ) : '',
			'scheduled_time' => isset( $post_data['scheduled_time'] ) ? sanitize_text_field( $post_data['scheduled_time'] ) : null,
			'status'         => isset( $post_data['status'] ) ? sanitize_text_field( $post_data['status'] ) : 'draft',
		);

		$formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table, $data, $formats );

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Kon social post niet opslaan.' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get scheduled posts
	 *
	 * @param array $args Query arguments.
	 * @return array Scheduled posts.
	 */
	public function get_scheduled_posts( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => 'scheduled',
			'platform' => '',
			'from'     => '',
			'to'       => '',
			'limit'    => 50,
		);
		$args = wp_parse_args( $args, $defaults );

		$table = $wpdb->prefix . 'writgoai_social_posts';

		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['platform'] ) ) {
			$where[] = 'platform = %s';
			$values[] = $args['platform'];
		}

		if ( ! empty( $args['from'] ) ) {
			$where[] = 'scheduled_time >= %s';
			$values[] = $args['from'];
		}

		if ( ! empty( $args['to'] ) ) {
			$where[] = 'scheduled_time <= %s';
			$values[] = $args['to'];
		}

		$where_clause = implode( ' AND ', $where );
		$limit = absint( $args['limit'] );

		// Build the query using the table name directly (prefix is WordPress-controlled).
		$table_name = $wpdb->prefix . 'writgoai_social_posts';
		$base_query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY scheduled_time ASC LIMIT %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is safely constructed with wpdb->prefix and prepared placeholders.
			$results = $wpdb->get_results(
				$wpdb->prepare( $base_query, array_merge( $values, array( $limit ) ) ),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is safely constructed with wpdb->prefix and prepared placeholders.
			$results = $wpdb->get_results(
				$wpdb->prepare( $base_query, $limit ),
				ARRAY_A
			);
		}

		return $results ? $results : array();
	}

	/**
	 * Delete scheduled post
	 *
	 * @param int $post_id Social post ID.
	 * @return bool Success.
	 */
	public function delete_scheduled_post( $post_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'writgoai_social_posts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $table, array( 'id' => $post_id ), array( '%d' ) );

		return false !== $result;
	}

	/**
	 * Save hashtag set
	 *
	 * @param string $name     Set name.
	 * @param array  $hashtags Hashtags.
	 * @param string $category Category.
	 * @return int|WP_Error Set ID or error.
	 */
	public function save_hashtag_set( $name, $hashtags, $category = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'writgoai_hashtag_sets';

		$data = array(
			'name'     => sanitize_text_field( $name ),
			'hashtags' => wp_json_encode( array_map( 'sanitize_text_field', (array) $hashtags ) ),
			'category' => sanitize_text_field( $category ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table, $data, array( '%s', '%s', '%s' ) );

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Kon hashtag set niet opslaan.' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get hashtag sets
	 *
	 * @param string $category Filter by category.
	 * @return array Hashtag sets.
	 */
	public function get_hashtag_sets( $category = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'writgoai_hashtag_sets';

		if ( ! empty( $category ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Table name uses wpdb->prefix.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} WHERE category = %s ORDER BY name ASC",
					$category
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Table name uses wpdb->prefix.
			$results = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY name ASC", ARRAY_A );
		}

		// Decode hashtags JSON.
		if ( $results ) {
			foreach ( $results as &$set ) {
				$set['hashtags'] = json_decode( $set['hashtags'], true );
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Get social analytics summary
	 *
	 * @param int $days Number of days to analyze.
	 * @return array Analytics summary.
	 */
	public function get_analytics_summary( $days = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'writgoai_social_posts';
		$from_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Get posts count by platform.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Table name uses wpdb->prefix.
		$platform_stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT platform, COUNT(*) as count, status FROM {$table_name} WHERE created_at >= %s GROUP BY platform, status",
				$from_date
			),
			ARRAY_A
		);

		$summary = array(
			'total_posts'      => 0,
			'published_posts'  => 0,
			'scheduled_posts'  => 0,
			'draft_posts'      => 0,
			'by_platform'      => array(),
			'period_days'      => $days,
		);

		foreach ( $this->platforms as $key => $platform ) {
			$summary['by_platform'][ $key ] = array(
				'name'      => $platform['name'],
				'icon'      => $platform['icon'],
				'published' => 0,
				'scheduled' => 0,
				'draft'     => 0,
			);
		}

		if ( $platform_stats ) {
			foreach ( $platform_stats as $stat ) {
				$platform = $stat['platform'];
				$status   = $stat['status'];
				$count    = (int) $stat['count'];

				$summary['total_posts'] += $count;

				if ( isset( $summary['by_platform'][ $platform ] ) ) {
					if ( 'published' === $status || 'posted' === $status ) {
						$summary['by_platform'][ $platform ]['published'] = $count;
						$summary['published_posts'] += $count;
					} elseif ( 'scheduled' === $status ) {
						$summary['by_platform'][ $platform ]['scheduled'] = $count;
						$summary['scheduled_posts'] += $count;
					} else {
						$summary['by_platform'][ $platform ]['draft'] = $count;
						$summary['draft_posts'] += $count;
					}
				}
			}
		}

		return $summary;
	}

	/**
	 * AJAX: Generate social posts
	 */
	public function ajax_generate_social_posts() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$content   = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
		$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$platforms = isset( $_POST['platforms'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['platforms'] ) ) : array();
		$tone      = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'professioneel';
		$use_hashtags = isset( $_POST['use_hashtags'] ) ? filter_var( wp_unslash( $_POST['use_hashtags'] ), FILTER_VALIDATE_BOOLEAN ) : true;
		$use_emojis   = isset( $_POST['use_emojis'] ) ? filter_var( wp_unslash( $_POST['use_emojis'] ), FILTER_VALIDATE_BOOLEAN ) : true;
		$link_url     = isset( $_POST['link_url'] ) ? esc_url_raw( wp_unslash( $_POST['link_url'] ) ) : '';

		if ( empty( $content ) || empty( $title ) ) {
			wp_send_json_error( array( 'message' => 'Titel en content zijn verplicht.' ) );
		}

		$options = array(
			'tone'         => $tone,
			'use_hashtags' => $use_hashtags,
			'use_emojis'   => $use_emojis,
			'include_link' => ! empty( $link_url ),
			'link_url'     => $link_url,
		);

		$result = $this->generate_social_posts( $content, $title, $platforms, $options );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => 'Social media posts succesvol gegenereerd!',
			'posts'   => $result,
		) );
	}

	/**
	 * AJAX: Save social post
	 */
	public function ajax_save_social_post() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$post_data = array(
			'post_id'  => isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0,
			'platform' => isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '',
			'content'  => isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '',
			'hashtags' => isset( $_POST['hashtags'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['hashtags'] ) ) : array(),
			'status'   => 'draft',
		);

		if ( empty( $post_data['platform'] ) || empty( $post_data['content'] ) ) {
			wp_send_json_error( array( 'message' => 'Platform en content zijn verplicht.' ) );
		}

		$result = $this->save_social_post( $post_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => 'Social post opgeslagen!',
			'post_id' => $result,
		) );
	}

	/**
	 * AJAX: Schedule social post
	 */
	public function ajax_schedule_social_post() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$post_data = array(
			'post_id'        => isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0,
			'platform'       => isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '',
			'content'        => isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '',
			'hashtags'       => isset( $_POST['hashtags'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['hashtags'] ) ) : array(),
			'scheduled_time' => isset( $_POST['scheduled_time'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_time'] ) ) : '',
			'status'         => 'scheduled',
		);

		if ( empty( $post_data['platform'] ) || empty( $post_data['content'] ) || empty( $post_data['scheduled_time'] ) ) {
			wp_send_json_error( array( 'message' => 'Platform, content en geplande tijd zijn verplicht.' ) );
		}

		$result = $this->save_social_post( $post_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => 'Social post ingepland!',
			'post_id' => $result,
		) );
	}

	/**
	 * AJAX: Get scheduled posts
	 */
	public function ajax_get_scheduled_posts() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';
		$from     = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
		$to       = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';

		$posts = $this->get_scheduled_posts( array(
			'status'   => 'scheduled',
			'platform' => $platform,
			'from'     => $from,
			'to'       => $to,
		) );

		wp_send_json_success( array( 'posts' => $posts ) );
	}

	/**
	 * AJAX: Delete scheduled post
	 */
	public function ajax_delete_scheduled_post() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$post_id = isset( $_POST['social_post_id'] ) ? absint( wp_unslash( $_POST['social_post_id'] ) ) : 0;

		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Post ID is verplicht.' ) );
		}

		$result = $this->delete_scheduled_post( $post_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => 'Kon post niet verwijderen.' ) );
		}

		wp_send_json_success( array( 'message' => 'Post verwijderd!' ) );
	}

	/**
	 * AJAX: Save hashtag set
	 */
	public function ajax_save_hashtag_set() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$hashtags = isset( $_POST['hashtags'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['hashtags'] ) ) : array();
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

		if ( empty( $name ) || empty( $hashtags ) ) {
			wp_send_json_error( array( 'message' => 'Naam en hashtags zijn verplicht.' ) );
		}

		$result = $this->save_hashtag_set( $name, $hashtags, $category );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => 'Hashtag set opgeslagen!',
			'set_id'  => $result,
		) );
	}

	/**
	 * AJAX: Get hashtag sets
	 */
	public function ajax_get_hashtag_sets() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

		$sets = $this->get_hashtag_sets( $category );

		wp_send_json_success( array( 'sets' => $sets ) );
	}

	/**
	 * AJAX: Suggest hashtags
	 */
	public function ajax_suggest_hashtags() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$content  = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
		$platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : 'instagram';
		$count    = isset( $_POST['count'] ) ? absint( wp_unslash( $_POST['count'] ) ) : 15;

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Content is verplicht.' ) );
		}

		$result = $this->suggest_hashtags( $content, $platform, $count );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'  => 'Hashtags gegenereerd!',
			'hashtags' => $result,
		) );
	}

	/**
	 * AJAX: Get social analytics
	 */
	public function ajax_get_social_analytics() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$days = isset( $_POST['days'] ) ? absint( wp_unslash( $_POST['days'] ) ) : 30;

		$analytics = $this->get_analytics_summary( $days );

		wp_send_json_success( array( 'analytics' => $analytics ) );
	}

	/**
	 * AJAX: Get blog posts for selection
	 */
	public function ajax_get_blog_posts() {
		check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$posts = get_posts( $args );

		$result = array();
		foreach ( $posts as $post ) {
			$result[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'date'    => get_the_date( 'd-m-Y', $post ),
				'excerpt' => wp_trim_words( $post->post_content, 30, '...' ),
				'url'     => get_permalink( $post->ID ),
			);
		}

		wp_send_json_success( array( 'posts' => $result ) );
	}
}

// Initialize.
WritgoAI_Social_Media_Manager::get_instance();
