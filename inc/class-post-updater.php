<?php
/**
 * Post Updater Class
 *
 * AI-powered post improvement and SEO optimization with Yoast SEO and Rank Math integration.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Class WritgoAI_Post_Updater
 */
class WritgoAI_Post_Updater {

/**
 * Instance
 *
 * @var WritgoAI_Post_Updater
 */
private static $instance = null;

/**
 * Provider instance
 *
 * @var WritgoAI_AI_Provider
 */
private $provider;

/**
 * Active SEO plugin
 *
 * @var string
 */
private $active_seo_plugin = '';

/**
 * Get instance
 *
 * @return WritgoAI_Post_Updater
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
$this->detect_seo_plugin();

// AJAX handlers.
add_action( 'wp_ajax_writgoai_get_posts_for_update', array( $this, 'ajax_get_posts_for_update' ) );
add_action( 'wp_ajax_writgoai_analyze_post', array( $this, 'ajax_analyze_post' ) );
add_action( 'wp_ajax_writgoai_improve_post', array( $this, 'ajax_improve_post' ) );
add_action( 'wp_ajax_writgoai_save_improved_post', array( $this, 'ajax_save_improved_post' ) );
add_action( 'wp_ajax_writgoai_bulk_improve_posts', array( $this, 'ajax_bulk_improve_posts' ) );
add_action( 'wp_ajax_writgoai_get_post_updater_stats', array( $this, 'ajax_get_stats' ) );

// Register activation hook for database tables.
register_activation_hook( WRITGOAI_DIR . 'writgo-cms.php', array( $this, 'create_tables' ) );
}

/**
 * Detect active SEO plugin
 */
private function detect_seo_plugin() {
if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Meta' ) ) {
$this->active_seo_plugin = 'yoast';
} elseif ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
$this->active_seo_plugin = 'rankmath';
}
}

/**
 * Get active SEO plugin name
 *
 * @return string
 */
public function get_active_seo_plugin() {
return $this->active_seo_plugin;
}

/**
 * Create database tables
 */
public function create_tables() {
global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}writgoai_post_updates (
id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
post_id BIGINT(20) UNSIGNED NOT NULL,
original_content LONGTEXT,
improved_content LONGTEXT,
original_seo_score INT(3),
improved_seo_score INT(3),
improvements_made LONGTEXT,
updated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
status VARCHAR(20) DEFAULT 'pending',
PRIMARY KEY (id),
KEY post_id (post_id),
KEY status (status)
) $charset_collate;

CREATE TABLE IF NOT EXISTS {$wpdb->prefix}writgoai_seo_history (
id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
post_id BIGINT(20) UNSIGNED NOT NULL,
seo_plugin VARCHAR(50),
score INT(3),
issues LONGTEXT,
scan_date DATETIME DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
KEY post_id (post_id),
KEY scan_date (scan_date)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );
}

/**
 * Get posts that need improvement
 *
 * @param array $args Filter arguments.
 * @return array
 */
public function get_posts_for_update( $args = array() ) {
$defaults = array(
'posts_per_page' => 20,
'paged'          => 1,
'months_old'     => 6,
'min_seo_score'  => 0,
'max_seo_score'  => 100,
'category'       => '',
'search'         => '',
'orderby'        => 'date',
'order'          => 'ASC',
);
$args     = wp_parse_args( $args, $defaults );

// Calculate date threshold.
$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$args['months_old']} months" ) );

$query_args = array(
'post_type'      => 'post',
'post_status'    => 'publish',
'posts_per_page' => intval( $args['posts_per_page'] ),
'paged'          => intval( $args['paged'] ),
'date_query'     => array(
array(
'before' => $date_threshold,
),
),
'orderby'        => sanitize_key( $args['orderby'] ),
'order'          => strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC',
);

if ( ! empty( $args['category'] ) ) {
$query_args['cat'] = intval( $args['category'] );
}

if ( ! empty( $args['search'] ) ) {
$query_args['s'] = sanitize_text_field( $args['search'] );
}

$query = new WP_Query( $query_args );
$posts = array();

foreach ( $query->posts as $post ) {
$seo_data   = $this->get_seo_data( $post->ID );
$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );

// Apply SEO score filter.
$seo_score = isset( $seo_data['score'] ) ? intval( $seo_data['score'] ) : 0;
if ( $seo_score < intval( $args['min_seo_score'] ) || $seo_score > intval( $args['max_seo_score'] ) ) {
continue;
}

$posts[] = array(
'id'           => $post->ID,
'title'        => $post->post_title,
'date'         => $post->post_date,
'date_display' => date_i18n( 'd-m-Y', strtotime( $post->post_date ) ),
'age_months'   => $this->get_age_in_months( $post->post_date ),
'word_count'   => $word_count,
'edit_link'    => get_edit_post_link( $post->ID ),
'view_link'    => get_permalink( $post->ID ),
'seo_data'     => $seo_data,
);
}

return array(
'posts'       => $posts,
'total'       => $query->found_posts,
'total_pages' => $query->max_num_pages,
'current'     => intval( $args['paged'] ),
);
}

/**
 * Get age of post in months
 *
 * @param string $post_date Post date.
 * @return int
 */
private function get_age_in_months( $post_date ) {
$post_time    = strtotime( $post_date );
$current_time = time();
$diff         = $current_time - $post_time;
return floor( $diff / ( 30 * 24 * 60 * 60 ) );
}

/**
 * Get SEO data for a post
 *
 * @param int $post_id Post ID.
 * @return array
 */
public function get_seo_data( $post_id ) {
$seo_data = array(
'plugin'           => $this->active_seo_plugin,
'score'            => 0,
'readability'      => 0,
'focus_keyword'    => '',
'meta_title'       => '',
'meta_description' => '',
'issues'           => array(),
);

if ( 'yoast' === $this->active_seo_plugin ) {
$seo_data = $this->get_yoast_data( $post_id, $seo_data );
} elseif ( 'rankmath' === $this->active_seo_plugin ) {
$seo_data = $this->get_rankmath_data( $post_id, $seo_data );
}

return $seo_data;
}

/**
 * Get Yoast SEO data
 *
 * @param int   $post_id  Post ID.
 * @param array $seo_data Default SEO data.
 * @return array
 */
private function get_yoast_data( $post_id, $seo_data ) {
// Get focus keyword.
$focus_keyword = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
if ( $focus_keyword ) {
$seo_data['focus_keyword'] = $focus_keyword;
}

// Get meta title.
$meta_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
if ( $meta_title ) {
$seo_data['meta_title'] = $meta_title;
}

// Get meta description.
$meta_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
if ( $meta_desc ) {
$seo_data['meta_description'] = $meta_desc;
}

// Get SEO score (linkdex).
$linkdex = get_post_meta( $post_id, '_yoast_wpseo_linkdex', true );
if ( $linkdex ) {
$seo_data['score'] = intval( $linkdex );
}

// Get readability score.
$content_score = get_post_meta( $post_id, '_yoast_wpseo_content_score', true );
if ( $content_score ) {
$seo_data['readability'] = intval( $content_score );
}

// Analyze issues.
$seo_data['issues'] = $this->analyze_yoast_issues( $post_id, $seo_data );

return $seo_data;
}

/**
 * Analyze Yoast SEO issues
 *
 * @param int   $post_id  Post ID.
 * @param array $seo_data SEO data.
 * @return array
 */
private function analyze_yoast_issues( $post_id, $seo_data ) {
$issues = array();
$post   = get_post( $post_id );

// Check focus keyword.
if ( empty( $seo_data['focus_keyword'] ) ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Focus keyword ontbreekt', 'writgoai' ),
);
}

// Check meta description.
if ( empty( $seo_data['meta_description'] ) ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Meta beschrijving ontbreekt', 'writgoai' ),
);
} elseif ( strlen( $seo_data['meta_description'] ) < 120 ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Meta beschrijving te kort', 'writgoai' ),
);
}

// Check content length.
$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
if ( $word_count < 800 ) {
$issues[] = array(
'type'    => 'warning',
'message' => sprintf( __( 'Artikel te kort (%d woorden, aanbevolen: 1500+)', 'writgoai' ), $word_count ),
);
}

// Check outbound links.
$has_external_links = preg_match( '/<a[^>]+href=["\']https?:\/\/(?!(' . preg_quote( home_url(), '/' ) . '))[^"\']+["\'][^>]*>/i', $post->post_content );
if ( ! $has_external_links ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Geen outbound links', 'writgoai' ),
);
}

// Check readability.
if ( $seo_data['readability'] < 50 ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Leesbaarheid: moeilijk', 'writgoai' ),
);
}

return $issues;
}

/**
 * Get Rank Math data
 *
 * @param int   $post_id  Post ID.
 * @param array $seo_data Default SEO data.
 * @return array
 */
private function get_rankmath_data( $post_id, $seo_data ) {
// Get focus keyword.
$focus_keyword = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
if ( $focus_keyword ) {
$seo_data['focus_keyword'] = $focus_keyword;
}

// Get meta title.
$meta_title = get_post_meta( $post_id, 'rank_math_title', true );
if ( $meta_title ) {
$seo_data['meta_title'] = $meta_title;
}

// Get meta description.
$meta_desc = get_post_meta( $post_id, 'rank_math_description', true );
if ( $meta_desc ) {
$seo_data['meta_description'] = $meta_desc;
}

// Get SEO score.
$seo_score = get_post_meta( $post_id, 'rank_math_seo_score', true );
if ( $seo_score ) {
$seo_data['score'] = intval( $seo_score );
}

// Get secondary keywords.
$secondary_keywords = get_post_meta( $post_id, 'rank_math_secondary_focus_keyword', true );
if ( $secondary_keywords ) {
$seo_data['secondary_keywords'] = $secondary_keywords;
}

// Analyze issues.
$seo_data['issues'] = $this->analyze_rankmath_issues( $post_id, $seo_data );

return $seo_data;
}

/**
 * Analyze Rank Math issues
 *
 * @param int   $post_id  Post ID.
 * @param array $seo_data SEO data.
 * @return array
 */
private function analyze_rankmath_issues( $post_id, $seo_data ) {
$issues = array();
$post   = get_post( $post_id );

// Similar checks as Yoast.
if ( empty( $seo_data['focus_keyword'] ) ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Focus keyword ontbreekt', 'writgoai' ),
);
}

if ( empty( $seo_data['meta_description'] ) ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Meta beschrijving ontbreekt', 'writgoai' ),
);
} elseif ( strlen( $seo_data['meta_description'] ) < 120 ) {
$issues[] = array(
'type'    => 'warning',
'message' => __( 'Meta beschrijving te kort', 'writgoai' ),
);
}

$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
if ( $word_count < 800 ) {
$issues[] = array(
'type'    => 'warning',
'message' => sprintf( __( 'Artikel te kort (%d woorden, aanbevolen: 1500+)', 'writgoai' ), $word_count ),
);
}

return $issues;
}

/**
 * Analyze a post for improvements
 *
 * @param int $post_id Post ID.
 * @return array
 */
public function analyze_post( $post_id ) {
$post = get_post( $post_id );
if ( ! $post ) {
return new WP_Error( 'invalid_post', __( 'Ongeldige post.', 'writgoai' ) );
}

$seo_data   = $this->get_seo_data( $post_id );
$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );

// Build AI analysis prompt.
$prompt = sprintf(
'Je bent een SEO expert. Analyseer de volgende blog post en geef een JSON response met verbeteringssuggesties.

Titel: %s
Content (eerste 2000 tekens): %s
Huidige woorden: %d
Focus keyword: %s

Geef een JSON response met precies deze structuur:
{
  "outdated_info": ["lijst van verouderde informatie die geüpdatet moet worden"],
  "missing_sections": ["lijst van ontbrekende secties"],
  "seo_improvements": ["lijst van SEO verbeteringen"],
  "readability_issues": ["lijst van leesbaarheid problemen"],
  "content_gaps": ["lijst van content gaps"],
  "recommended_word_count": 1500,
  "overall_score": 50
}

Retourneer ALLEEN valid JSON, geen extra tekst.',
$post->post_title,
substr( wp_strip_all_tags( $post->post_content ), 0, 2000 ),
$word_count,
$seo_data['focus_keyword'] ? $seo_data['focus_keyword'] : 'geen'
);

$result = $this->provider->generate_text( $prompt, null, array( 'max_tokens' => 1500 ) );

if ( is_wp_error( $result ) ) {
return $result;
}

$content = isset( $result['content'] ) ? $result['content'] : '';
// Remove markdown code blocks.
$content = preg_replace( '/^```(?:json)?\s*/i', '', trim( $content ) );
$content = preg_replace( '/\s*```$/', '', $content );

$analysis = json_decode( $content, true );
if ( json_last_error() !== JSON_ERROR_NONE ) {
$analysis = array(
'error'       => true,
'raw_content' => $content,
);
}

return array(
'post_id'    => $post_id,
'seo_data'   => $seo_data,
'word_count' => $word_count,
'analysis'   => $analysis,
);
}

/**
 * Improve a post
 *
 * @param int   $post_id Post ID.
 * @param array $options Improvement options.
 * @return array|WP_Error
 */
public function improve_post( $post_id, $options = array() ) {
$post = get_post( $post_id );
if ( ! $post ) {
return new WP_Error( 'invalid_post', __( 'Ongeldige post.', 'writgoai' ) );
}

$defaults = array(
'update_dates'        => true,
'extend_content'      => true,
'optimize_seo'        => true,
'improve_readability' => true,
'add_faq'             => false,
'rewrite_intro'       => true,
'add_links'           => true,
'focus_keyword'       => '',
'tone'                => 'professional',
'target_audience'     => 'broad',
'improvement_level'   => 'medium',
);
$options  = wp_parse_args( $options, $defaults );

$seo_data   = $this->get_seo_data( $post_id );
$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );

// Build improvement prompt.
$instructions = array();

if ( $options['update_dates'] ) {
$current_year   = gmdate( 'Y' );
$instructions[] = sprintf( 'Update alle verouderde datums, jaren en statistieken naar %s', $current_year );
}
if ( $options['extend_content'] ) {
$instructions[] = 'Verleng het artikel naar minimaal 1500 woorden met relevante, waardevolle content';
}
if ( $options['optimize_seo'] ) {
$focus_kw       = $options['focus_keyword'] ? $options['focus_keyword'] : $seo_data['focus_keyword'];
$instructions[] = sprintf( 'Optimaliseer voor het focus keyword: "%s"', $focus_kw );
}
if ( $options['improve_readability'] ) {
$instructions[] = 'Verbeter de leesbaarheid met kortere zinnen, bullet points en duidelijke paragrafen';
}
if ( $options['add_faq'] ) {
$instructions[] = 'Voeg een FAQ sectie toe met 3-5 relevante vragen en antwoorden';
}
if ( $options['rewrite_intro'] ) {
$instructions[] = 'Herschrijf de introductie om direct de aandacht te trekken';
}
if ( $options['add_links'] ) {
$instructions[] = 'Suggereer plaatsen voor interne en externe links (markeer met [INTERNAL LINK: topic] of [EXTERNAL LINK: topic])';
}

$level_instruction = '';
switch ( $options['improvement_level'] ) {
case 'light':
$level_instruction = 'Behoud 80% van de originele content, maak alleen lichte aanpassingen.';
break;
case 'medium':
$level_instruction = 'Behoud 50% van de originele content, verbeter de rest significant.';
break;
case 'heavy':
$level_instruction = 'Volledige herschrijving, behoud alleen de structuur en hoofdpunten.';
break;
}

$prompt = sprintf(
'Je bent een Nederlandse content specialist en SEO expert. Verbeter de volgende blog post.

ORIGINELE TITEL: %s

ORIGINELE CONTENT:
%s

INSTRUCTIES:
%s

%s

TOON: %s
DOELGROEP: %s

GEEF EEN JSON RESPONSE MET:
{
  "new_title": "De verbeterde titel",
  "new_meta_description": "Meta beschrijving van 150-160 karakters",
  "new_content": "De volledige verbeterde content in HTML",
  "changes_summary": ["lijst van gemaakte wijzigingen"],
  "estimated_seo_score": 85
}

Retourneer ALLEEN valid JSON, geen extra tekst.',
$post->post_title,
$post->post_content,
implode( "\n", array_map( function( $i ) { return '- ' . $i; }, $instructions ) ),
$level_instruction,
$options['tone'],
$options['target_audience']
);

$result = $this->provider->generate_text(
$prompt,
null,
array(
'max_tokens'  => 4000,
'temperature' => 0.7,
)
);

if ( is_wp_error( $result ) ) {
return $result;
}

$content = isset( $result['content'] ) ? $result['content'] : '';
// Remove markdown code blocks.
$content = preg_replace( '/^```(?:json)?\s*/i', '', trim( $content ) );
$content = preg_replace( '/\s*```$/', '', $content );

$improved = json_decode( $content, true );
if ( json_last_error() !== JSON_ERROR_NONE ) {
return new WP_Error( 'parse_error', __( 'Kon AI response niet verwerken. Probeer het opnieuw.', 'writgoai' ) );
}

// Calculate word counts.
$original_words = $word_count;
$new_words      = str_word_count( wp_strip_all_tags( isset( $improved['new_content'] ) ? $improved['new_content'] : '' ) );

return array(
'post_id'        => $post_id,
'original'       => array(
'title'            => $post->post_title,
'content'          => $post->post_content,
'meta_description' => $seo_data['meta_description'],
'word_count'       => $original_words,
'seo_score'        => $seo_data['score'],
),
'improved'       => array(
'title'            => isset( $improved['new_title'] ) ? $improved['new_title'] : $post->post_title,
'content'          => isset( $improved['new_content'] ) ? $improved['new_content'] : '',
'meta_description' => isset( $improved['new_meta_description'] ) ? $improved['new_meta_description'] : '',
'word_count'       => $new_words,
'seo_score'        => isset( $improved['estimated_seo_score'] ) ? $improved['estimated_seo_score'] : 0,
'changes_summary'  => isset( $improved['changes_summary'] ) ? $improved['changes_summary'] : array(),
),
'focus_keyword'  => $options['focus_keyword'] ? $options['focus_keyword'] : $seo_data['focus_keyword'],
'options'        => $options,
);
}

/**
 * Save improved post
 *
 * @param int    $post_id Post ID.
 * @param array  $improved Improved data.
 * @param string $status Post status (draft or publish).
 * @return bool|WP_Error
 */
public function save_improved_post( $post_id, $improved, $status = 'draft' ) {
$post = get_post( $post_id );
if ( ! $post ) {
return new WP_Error( 'invalid_post', __( 'Ongeldige post.', 'writgoai' ) );
}

global $wpdb;

// Store original content in history.
$original_seo = $this->get_seo_data( $post_id );
$original     = array(
'title'            => $post->post_title,
'content'          => $post->post_content,
'meta_description' => $original_seo['meta_description'],
'seo_score'        => $original_seo['score'],
);

// Update post.
$update_args = array(
'ID'           => $post_id,
'post_title'   => isset( $improved['title'] ) ? sanitize_text_field( $improved['title'] ) : $post->post_title,
'post_content' => isset( $improved['content'] ) ? wp_kses_post( $improved['content'] ) : $post->post_content,
'post_status'  => $status === 'publish' ? 'publish' : 'draft',
);

$result = wp_update_post( $update_args, true );
if ( is_wp_error( $result ) ) {
return $result;
}

// Update SEO meta data.
if ( isset( $improved['meta_description'] ) && ! empty( $improved['meta_description'] ) ) {
$this->update_seo_meta( $post_id, $improved );
}

// Record update in database.
$wpdb->insert(
$wpdb->prefix . 'writgoai_post_updates',
array(
'post_id'            => $post_id,
'original_content'   => wp_json_encode( $original ),
'improved_content'   => wp_json_encode( $improved ),
'original_seo_score' => $original_seo['score'],
'improved_seo_score' => isset( $improved['seo_score'] ) ? intval( $improved['seo_score'] ) : 0,
'improvements_made'  => wp_json_encode( isset( $improved['changes_summary'] ) ? $improved['changes_summary'] : array() ),
'status'             => $status,
),
array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
);

return true;
}

/**
 * Update SEO meta data
 *
 * @param int   $post_id  Post ID.
 * @param array $improved Improved data.
 */
private function update_seo_meta( $post_id, $improved ) {
if ( 'yoast' === $this->active_seo_plugin ) {
if ( isset( $improved['meta_description'] ) ) {
update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $improved['meta_description'] ) );
}
if ( isset( $improved['focus_keyword'] ) && ! empty( $improved['focus_keyword'] ) ) {
update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $improved['focus_keyword'] ) );
}
} elseif ( 'rankmath' === $this->active_seo_plugin ) {
if ( isset( $improved['meta_description'] ) ) {
update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $improved['meta_description'] ) );
}
if ( isset( $improved['focus_keyword'] ) && ! empty( $improved['focus_keyword'] ) ) {
update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $improved['focus_keyword'] ) );
}
}
}

/**
 * Get Post Updater statistics
 *
 * @return array
 */
public function get_stats() {
global $wpdb;

// Count posts updated this month.
$month_start = gmdate( 'Y-m-01 00:00:00' );
$updates_this_month = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*) FROM {$wpdb->prefix}writgoai_post_updates WHERE updated_date >= %s",
$month_start
)
);

// Average SEO improvement.
$avg_improvement = $wpdb->get_var(
$wpdb->prepare(
"SELECT AVG(improved_seo_score - original_seo_score) FROM {$wpdb->prefix}writgoai_post_updates WHERE updated_date >= %s AND improved_seo_score > 0",
$month_start
)
);

// Posts older than 6 months.
$old_posts_count = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date < %s",
gmdate( 'Y-m-d H:i:s', strtotime( '-6 months' ) )
)
);

// Posts with low SEO (from our history).
$low_seo_count = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}writgoai_seo_history WHERE score < %d",
60
)
);

return array(
'posts_updated_this_month' => intval( $updates_this_month ),
'avg_seo_improvement'      => round( floatval( $avg_improvement ), 1 ),
'old_posts_count'          => intval( $old_posts_count ),
'low_seo_posts_count'      => intval( $low_seo_count ),
);
}

/**
 * AJAX: Get posts for update
 */
public function ajax_get_posts_for_update() {
check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

if ( ! current_user_can( 'edit_posts' ) ) {
wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
}

$args = array(
'posts_per_page' => isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20,
'paged'          => isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1,
'months_old'     => isset( $_POST['months_old'] ) ? intval( $_POST['months_old'] ) : 6,
'min_seo_score'  => isset( $_POST['min_seo_score'] ) ? intval( $_POST['min_seo_score'] ) : 0,
'max_seo_score'  => isset( $_POST['max_seo_score'] ) ? intval( $_POST['max_seo_score'] ) : 100,
'category'       => isset( $_POST['category'] ) ? intval( $_POST['category'] ) : '',
'search'         => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
);

$result = $this->get_posts_for_update( $args );
wp_send_json_success( $result );
}

/**
 * AJAX: Analyze post
 */
public function ajax_analyze_post() {
check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

if ( ! current_user_can( 'edit_posts' ) ) {
wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
}

$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
if ( ! $post_id ) {
wp_send_json_error( array( 'message' => __( 'Post ID is vereist.', 'writgoai' ) ) );
}

$result = $this->analyze_post( $post_id );

if ( is_wp_error( $result ) ) {
wp_send_json_error( array( 'message' => $result->get_error_message() ) );
}

wp_send_json_success( $result );
}

/**
 * AJAX: Improve post
 */
public function ajax_improve_post() {
check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

if ( ! current_user_can( 'edit_posts' ) ) {
wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
}

$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
if ( ! $post_id ) {
wp_send_json_error( array( 'message' => __( 'Post ID is vereist.', 'writgoai' ) ) );
}

$options = array(
'update_dates'        => isset( $_POST['update_dates'] ) && $_POST['update_dates'] === 'true',
'extend_content'      => isset( $_POST['extend_content'] ) && $_POST['extend_content'] === 'true',
'optimize_seo'        => isset( $_POST['optimize_seo'] ) && $_POST['optimize_seo'] === 'true',
'improve_readability' => isset( $_POST['improve_readability'] ) && $_POST['improve_readability'] === 'true',
'add_faq'             => isset( $_POST['add_faq'] ) && $_POST['add_faq'] === 'true',
'rewrite_intro'       => isset( $_POST['rewrite_intro'] ) && $_POST['rewrite_intro'] === 'true',
'add_links'           => isset( $_POST['add_links'] ) && $_POST['add_links'] === 'true',
'focus_keyword'       => isset( $_POST['focus_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ) ) : '',
'tone'                => isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'professional',
'target_audience'     => isset( $_POST['target_audience'] ) ? sanitize_text_field( wp_unslash( $_POST['target_audience'] ) ) : 'broad',
'improvement_level'   => isset( $_POST['improvement_level'] ) ? sanitize_text_field( wp_unslash( $_POST['improvement_level'] ) ) : 'medium',
);

$result = $this->improve_post( $post_id, $options );

if ( is_wp_error( $result ) ) {
wp_send_json_error( array( 'message' => $result->get_error_message() ) );
}

wp_send_json_success( $result );
}

/**
 * AJAX: Save improved post
 */
public function ajax_save_improved_post() {
check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

if ( ! current_user_can( 'edit_posts' ) ) {
wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
}

$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
if ( ! $post_id ) {
wp_send_json_error( array( 'message' => __( 'Post ID is vereist.', 'writgoai' ) ) );
}

$improved_data = isset( $_POST['improved_data'] ) ? wp_unslash( $_POST['improved_data'] ) : '';
if ( empty( $improved_data ) ) {
wp_send_json_error( array( 'message' => __( 'Geen verbeterde data ontvangen.', 'writgoai' ) ) );
}

$improved = json_decode( $improved_data, true );
if ( json_last_error() !== JSON_ERROR_NONE ) {
wp_send_json_error( array( 'message' => __( 'Ongeldige data format.', 'writgoai' ) ) );
}

$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'draft';

$result = $this->save_improved_post( $post_id, $improved, $status );

if ( is_wp_error( $result ) ) {
wp_send_json_error( array( 'message' => $result->get_error_message() ) );
}

wp_send_json_success( array( 'message' => __( 'Post succesvol opgeslagen!', 'writgoai' ) ) );
}

/**
 * AJAX: Bulk improve posts
 */
public function ajax_bulk_improve_posts() {
check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

if ( ! current_user_can( 'edit_posts' ) ) {
wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
}

$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', (array) $_POST['post_ids'] ) : array();
if ( empty( $post_ids ) ) {
wp_send_json_error( array( 'message' => __( 'Geen posts geselecteerd.', 'writgoai' ) ) );
}

$options = array(
'update_dates'        => isset( $_POST['update_dates'] ) && $_POST['update_dates'] === 'true',
'optimize_seo'        => isset( $_POST['optimize_seo'] ) && $_POST['optimize_seo'] === 'true',
'extend_content'      => isset( $_POST['extend_content'] ) && $_POST['extend_content'] === 'true',
'add_faq'             => isset( $_POST['add_faq'] ) && $_POST['add_faq'] === 'true',
'improvement_level'   => 'medium',
);

$results = array(
'success' => 0,
'failed'  => 0,
'details' => array(),
);

foreach ( $post_ids as $post_id ) {
$result = $this->improve_post( $post_id, $options );

if ( is_wp_error( $result ) ) {
$results['failed']++;
$results['details'][] = array(
'post_id' => $post_id,
'status'  => 'error',
'message' => $result->get_error_message(),
);
} else {
$save_result = $this->save_improved_post( $post_id, $result['improved'], 'draft' );

if ( is_wp_error( $save_result ) ) {
$results['failed']++;
$results['details'][] = array(
'post_id' => $post_id,
'status'  => 'error',
'message' => $save_result->get_error_message(),
);
} else {
$results['success']++;
$results['details'][] = array(
'post_id' => $post_id,
'status'  => 'success',
'title'   => $result['improved']['title'],
);
}
}
}

wp_send_json_success( $results );
}

/**
 * AJAX: Get statistics
 */
public function ajax_get_stats() {
check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

if ( ! current_user_can( 'edit_posts' ) ) {
wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'writgoai' ) ) );
}

$stats = $this->get_stats();
wp_send_json_success( $stats );
}
}

// Initialize.
WritgoAI_Post_Updater::get_instance();
