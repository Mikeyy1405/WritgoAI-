<?php
/**
 * Credit History Admin Page
 *
 * Displays credit transaction history and usage details.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Credit_History_Page
 */
class WritgoCMS_Credit_History_Page {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Credit_History_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Credit_History_Page
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin menu
	 *
	 * Note: Credit History menu item removed as it's not needed without license system.
	 * Credit information can be viewed in the main dashboard or settings if needed.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		// Menu item removed - not needed in simplified admin UI
		// Credit information available elsewhere in the interface
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'writgoai_page_writgocms-credit-history' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'writgocms-credit-history',
			WRITGOCMS_URL . 'assets/css/admin-aiml.css',
			array(),
			WRITGOCMS_VERSION
		);
	}

	/**
	 * Render page
	 *
	 * @return void
	 */
	public function render_page() {
		// Get API client.
		if ( ! class_exists( 'WritgoCMS_API_Client' ) ) {
			echo '<div class="wrap"><p>' . esc_html__( 'API Client niet beschikbaar.', 'writgocms' ) . '</p></div>';
			return;
		}

		$api_client = WritgoCMS_API_Client::get_instance();
		
		// Get pagination parameters (already sanitized by max and absint).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a GET parameter for pagination, nonce not required for read-only operations.
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page = 50;
		$offset = ( $paged - 1 ) * $per_page;

		// Get credit history.
		$history_data = $api_client->get_credit_history( $per_page, $offset );
		$balance_data = $api_client->get_credit_balance();

		?>
		<div class="wrap writgocms-aiml-settings">
			<h1 class="aiml-header">
				<span class="aiml-logo">💳</span>
				<?php esc_html_e( 'Credit Geschiedenis', 'writgocms' ); ?>
			</h1>

			<?php if ( is_wp_error( $balance_data ) ) : ?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'Fout bij ophalen van credit informatie:', 'writgocms' ); ?></strong><br>
						<?php echo esc_html( $balance_data->get_error_message() ); ?>
					</p>
				</div>
			<?php else : ?>
				<!-- Credit Balance Summary -->
				<div class="aiml-tab-content">
					<div class="credit-balance-summary">
						<div class="balance-card">
							<div class="balance-icon">💰</div>
							<div class="balance-content">
								<div class="balance-value"><?php echo number_format( $balance_data['credits_remaining'] ?? 0 ); ?></div>
								<div class="balance-label"><?php esc_html_e( 'Credits Beschikbaar', 'writgocms' ); ?></div>
							</div>
						</div>
						<div class="balance-card">
							<div class="balance-icon">📊</div>
							<div class="balance-content">
								<div class="balance-value"><?php echo number_format( $balance_data['credits_used'] ?? 0 ); ?></div>
								<div class="balance-label"><?php esc_html_e( 'Credits Gebruikt', 'writgocms' ); ?></div>
							</div>
						</div>
						<div class="balance-card">
							<div class="balance-icon">📦</div>
							<div class="balance-content">
								<div class="balance-value"><?php echo number_format( $balance_data['credits_total'] ?? 0 ); ?></div>
								<div class="balance-label"><?php esc_html_e( 'Totaal Credits', 'writgocms' ); ?></div>
							</div>
						</div>
						<?php if ( ! empty( $balance_data['period_end'] ) ) : ?>
						<div class="balance-card">
							<div class="balance-icon">🔄</div>
							<div class="balance-content">
								<div class="balance-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $balance_data['period_end'] ) ) ); ?></div>
								<div class="balance-label"><?php esc_html_e( 'Reset Datum', 'writgocms' ); ?></div>
							</div>
						</div>
						<?php endif; ?>
					</div>

					<!-- Credit History Table -->
					<div class="credit-history-section">
						<h2><?php esc_html_e( 'Transactie Geschiedenis', 'writgocms' ); ?></h2>

						<?php if ( is_wp_error( $history_data ) ) : ?>
							<div class="notice notice-error inline">
								<p><?php echo esc_html( $history_data->get_error_message() ); ?></p>
							</div>
						<?php elseif ( empty( $history_data['transactions'] ) ) : ?>
							<div class="no-history">
								<p><?php esc_html_e( 'Nog geen transacties gevonden.', 'writgocms' ); ?></p>
							</div>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped credit-history-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Datum', 'writgocms' ); ?></th>
										<th><?php esc_html_e( 'Tijd', 'writgocms' ); ?></th>
										<th><?php esc_html_e( 'Actie', 'writgocms' ); ?></th>
										<th><?php esc_html_e( 'Credits', 'writgocms' ); ?></th>
										<th><?php esc_html_e( 'Saldo', 'writgocms' ); ?></th>
										<th><?php esc_html_e( 'Details', 'writgocms' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $history_data['transactions'] as $transaction ) : ?>
									<tr>
										<td>
											<?php
											$date = isset( $transaction['created_at'] ) ? strtotime( $transaction['created_at'] ) : time();
											echo esc_html( date_i18n( get_option( 'date_format' ), $date ) );
											?>
										</td>
										<td>
											<?php echo esc_html( date_i18n( get_option( 'time_format' ), $date ) ); ?>
										</td>
										<td>
											<?php
											$action = isset( $transaction['action_type'] ) ? $transaction['action_type'] : __( 'Onbekend', 'writgocms' );
											echo '<span class="action-type">' . esc_html( $this->format_action_type( $action ) ) . '</span>';
											?>
										</td>
										<td>
											<?php
											$amount = isset( $transaction['amount'] ) ? (int) $transaction['amount'] : 0;
											$class = $amount < 0 ? 'credit-debit' : 'credit-credit';
											echo '<span class="' . esc_attr( $class ) . '">' . number_format( $amount ) . '</span>';
											?>
										</td>
										<td>
											<?php echo number_format( isset( $transaction['balance_after'] ) ? (int) $transaction['balance_after'] : 0 ); ?>
										</td>
										<td>
											<?php
											if ( ! empty( $transaction['metadata'] ) ) {
												$metadata = is_array( $transaction['metadata'] ) ? $transaction['metadata'] : json_decode( $transaction['metadata'], true );
												if ( is_array( $metadata ) ) {
													echo '<button type="button" class="button button-small view-details" data-details="' . esc_attr( wp_json_encode( $metadata ) ) . '">';
													echo esc_html__( 'Bekijk', 'writgocms' );
													echo '</button>';
												}
											}
											?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<!-- Pagination -->
							<?php
							$total_pages = isset( $history_data['total_pages'] ) ? (int) $history_data['total_pages'] : 1;
							if ( $total_pages > 1 ) :
								?>
								<div class="tablenav bottom">
									<div class="tablenav-pages">
										<?php
										echo paginate_links( array(
											'base'      => add_query_arg( 'paged', '%#%' ),
											'format'    => '',
											'prev_text' => '&laquo;',
											'next_text' => '&raquo;',
											'total'     => $total_pages,
											'current'   => $paged,
										) );
										?>
									</div>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Details Modal -->
		<div id="transaction-details-modal" class="writgoai-modal" style="display: none;">
			<div class="modal-content">
				<span class="close">&times;</span>
				<h2><?php esc_html_e( 'Transactie Details', 'writgocms' ); ?></h2>
				<div id="transaction-details-content"></div>
			</div>
		</div>

		<style>
			.credit-balance-summary {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 20px;
				margin-bottom: 30px;
			}
			.balance-card {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 8px;
				padding: 20px;
				display: flex;
				align-items: center;
				gap: 15px;
			}
			.balance-icon {
				font-size: 32px;
			}
			.balance-value {
				font-size: 24px;
				font-weight: bold;
				color: #1e1e1e;
			}
			.balance-label {
				font-size: 13px;
				color: #666;
			}
			.credit-history-table .action-type {
				display: inline-block;
				padding: 2px 8px;
				background: #e9ecef;
				border-radius: 4px;
				font-size: 12px;
			}
			.credit-debit {
				color: #dc3545;
				font-weight: bold;
			}
			.credit-credit {
				color: #28a745;
				font-weight: bold;
			}
			.writgoai-modal {
				display: none;
				position: fixed;
				z-index: 100000;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				background-color: rgba(0,0,0,0.5);
			}
			.writgoai-modal .modal-content {
				background-color: #fff;
				margin: 5% auto;
				padding: 20px;
				border-radius: 8px;
				width: 80%;
				max-width: 600px;
				box-shadow: 0 4px 6px rgba(0,0,0,0.1);
			}
			.writgoai-modal .close {
				color: #aaa;
				float: right;
				font-size: 28px;
				font-weight: bold;
				cursor: pointer;
			}
			.writgoai-modal .close:hover {
				color: #000;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// View details button
			$('.view-details').on('click', function() {
				var details = $(this).data('details');
				var html = '<pre>' + JSON.stringify(details, null, 2) + '</pre>';
				$('#transaction-details-content').html(html);
				$('#transaction-details-modal').fadeIn();
			});

			// Close modal
			$('.writgoai-modal .close').on('click', function() {
				$(this).closest('.writgoai-modal').fadeOut();
			});

			// Close on outside click
			$(window).on('click', function(event) {
				if ($(event.target).hasClass('writgoai-modal')) {
					$('.writgoai-modal').fadeOut();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Format action type for display
	 *
	 * @param string $action_type Action type.
	 * @return string
	 */
	private function format_action_type( $action_type ) {
		$labels = array(
			'text_generation'      => __( 'Tekst Generatie', 'writgocms' ),
			'image_generation'     => __( 'Afbeelding Generatie', 'writgocms' ),
			'ai_rewrite_small'     => __( 'Kleine Herschrijving', 'writgocms' ),
			'ai_rewrite_paragraph' => __( 'Paragraaf Herschrijving', 'writgocms' ),
			'ai_rewrite_full'      => __( 'Volledige Herschrijving', 'writgocms' ),
			'ai_image'             => __( 'AI Afbeelding', 'writgocms' ),
			'seo_analysis'         => __( 'SEO Analyse', 'writgocms' ),
			'internal_links'       => __( 'Interne Links', 'writgocms' ),
			'keyword_research'     => __( 'Zoekwoord Onderzoek', 'writgocms' ),
			'subscription_renewal' => __( 'Abonnement Verlenging', 'writgocms' ),
			'manual_adjustment'    => __( 'Handmatige Aanpassing', 'writgocms' ),
		);

		return isset( $labels[ $action_type ] ) ? $labels[ $action_type ] : $action_type;
	}
}

// Initialize.
WritgoCMS_Credit_History_Page::get_instance();
