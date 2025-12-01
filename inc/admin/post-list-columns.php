<?php
/**
 * Post List Columns
 *
 * Adds SEO score, ranking, and traffic columns to the WordPress posts list.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Post_List_Columns
 */
class WritgoCMS_Post_List_Columns {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Post_List_Columns
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Post_List_Columns
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
		add_filter( 'manage_post_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'make_sortable' ) );
		add_filter( 'bulk_actions-edit-post', array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-post', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_head', array( $this, 'add_column_styles' ) );
	}

	/**
	 * Add custom columns
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;

			// Add our columns after the title column.
			if ( 'title' === $key ) {
				$new_columns['writgocms_seo_score'] = __( 'SEO Score', 'writgocms' );
				$new_columns['writgocms_ranking']   = __( 'Ranking', 'writgocms' );
				$new_columns['writgocms_traffic']   = __( 'Traffic (30d)', 'writgocms' );
				$new_columns['writgocms_status']    = __( 'Status', 'writgocms' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'writgocms_seo_score':
				$this->render_seo_score( $post_id );
				break;
			case 'writgocms_ranking':
				$this->render_ranking( $post_id );
				break;
			case 'writgocms_traffic':
				$this->render_traffic( $post_id );
				break;
			case 'writgocms_status':
				$this->render_status( $post_id );
				break;
		}
	}

	/**
	 * Render SEO score column
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_seo_score( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_post_scores';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$score_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT seo_score FROM {$table_name} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		if ( $score_data ) {
			$score = $score_data['seo_score'];
			$class = $this->get_score_class( $score );
			echo '<span class="seo-score ' . esc_attr( $class ) . '">' . esc_html( $score ) . '</span>';
		} else {
			echo '<span class="seo-score not-analyzed">—</span>';
		}
	}

	/**
	 * Render ranking column
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_ranking( $post_id ) {
		$ranking = get_post_meta( $post_id, '_writgocms_avg_ranking', true );

		if ( $ranking ) {
			echo '<span class="ranking">' . esc_html( number_format( $ranking, 1 ) ) . '</span>';
		} else {
			echo '<span class="ranking not-ranked">—</span>';
		}
	}

	/**
	 * Render traffic column
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_traffic( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgoai_gsc_pages';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$traffic = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(clicks) FROM {$table_name} 
				WHERE post_id = %d 
				AND date > DATE_SUB(NOW(), INTERVAL 30 DAY)",
				$post_id
			)
		);

		if ( $traffic ) {
			echo '<span class="traffic">' . esc_html( number_format( $traffic ) ) . '</span>';
		} else {
			echo '<span class="traffic no-traffic">0</span>';
		}
	}

	/**
	 * Render status column
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_status( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'writgo_post_scores';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$score_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT seo_score FROM {$table_name} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		if ( ! $score_data ) {
			echo '<span class="status-badge status-not-analyzed">⚪ ' . esc_html__( 'Not Analyzed', 'writgocms' ) . '</span>';
			return;
		}

		$score = $score_data['seo_score'];

		if ( $score >= 70 ) {
			echo '<span class="status-badge status-good">✅ ' . esc_html__( 'Optimized', 'writgocms' ) . '</span>';
		} elseif ( $score >= 40 ) {
			echo '<span class="status-badge status-warning">⚠️ ' . esc_html__( 'Needs Work', 'writgocms' ) . '</span>';
		} else {
			echo '<span class="status-badge status-poor">❌ ' . esc_html__( 'Poor', 'writgocms' ) . '</span>';
		}
	}

	/**
	 * Get score CSS class
	 *
	 * @param int $score SEO score.
	 * @return string CSS class.
	 */
	private function get_score_class( $score ) {
		if ( $score >= 70 ) {
			return 'score-good';
		} elseif ( $score >= 40 ) {
			return 'score-warning';
		} else {
			return 'score-poor';
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function make_sortable( $columns ) {
		$columns['writgocms_seo_score'] = 'writgocms_seo_score';
		$columns['writgocms_ranking']   = 'writgocms_ranking';
		$columns['writgocms_traffic']   = 'writgocms_traffic';
		return $columns;
	}

	/**
	 * Add bulk actions
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_actions( $actions ) {
		$actions['writgocms_analyze'] = __( 'Analyze with WritgoAI', 'writgocms' );
		return $actions;
	}

	/**
	 * Handle bulk actions
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction Action name.
	 * @param array  $post_ids Post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		if ( 'writgocms_analyze' !== $doaction ) {
			return $redirect_to;
		}

		if ( ! class_exists( 'WritgoCMS_Site_Analyzer' ) ) {
			return $redirect_to;
		}

		$analyzer = WritgoCMS_Site_Analyzer::get_instance();
		$analyzed = 0;

		foreach ( $post_ids as $post_id ) {
			$analyzer->analyze_post( $post_id );
			++$analyzed;
		}

		$redirect_to = add_query_arg( 'writgocms_analyzed', $analyzed, $redirect_to );
		return $redirect_to;
	}

	/**
	 * Add column styles
	 */
	public function add_column_styles() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-post' !== $screen->id ) {
			return;
		}
		?>
		<style>
		.seo-score {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-weight: 600;
			font-size: 12px;
		}
		.seo-score.score-good {
			background: #d4edda;
			color: #155724;
		}
		.seo-score.score-warning {
			background: #fff3cd;
			color: #856404;
		}
		.seo-score.score-poor {
			background: #f8d7da;
			color: #721c24;
		}
		.seo-score.not-analyzed {
			background: #e2e3e5;
			color: #6c757d;
		}
		.status-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 500;
		}
		.status-badge.status-good {
			background: #d4edda;
			color: #155724;
		}
		.status-badge.status-warning {
			background: #fff3cd;
			color: #856404;
		}
		.status-badge.status-poor {
			background: #f8d7da;
			color: #721c24;
		}
		.status-badge.status-not-analyzed {
			background: #e2e3e5;
			color: #6c757d;
		}
		.ranking, .traffic {
			font-weight: 500;
		}
		.ranking.not-ranked, .traffic.no-traffic {
			color: #999;
		}
		</style>
		<?php
	}
}

// Initialize.
WritgoCMS_Post_List_Columns::get_instance();
