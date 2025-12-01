<?php
/**
 * Credit Manager Class
 *
 * Handles credit tracking, consumption, and balance management for WritgoAI.
 * Features:
 * - Credit balance tracking with monthly reset
 * - Credit consumption per action
 * - Credit checking before AI operations
 * - Dashboard widget integration
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoCMS_Credit_Manager
 */
class WritgoCMS_Credit_Manager {

    /**
     * Instance
     *
     * @var WritgoCMS_Credit_Manager
     */
    private static $instance = null;

    /**
     * Credit costs per action
     *
     * @var array
     */
    private $credit_costs = array(
        'ai_rewrite_small'     => 10,
        'ai_rewrite_paragraph' => 25,
        'ai_rewrite_full'      => 50,
        'ai_image'             => 100,
        'seo_analysis'         => 20,
        'internal_links'       => 5,
        'keyword_research'     => 15,
        'text_generation'      => 10,  // Legacy - general text generation.
        'image_generation'     => 100, // Legacy - general image generation.
    );

    /**
     * Grace period in days before marking expired licenses as inactive
     *
     * @var int
     */
    private $grace_period_days = 7;

    /**
     * License manager instance
     *
     * @var WritgoCMS_License_Manager
     */
    private $license_manager;

    /**
     * Get instance
     *
     * @return WritgoCMS_Credit_Manager
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
        // Get license manager instance.
        if ( class_exists( 'WritgoCMS_License_Manager' ) ) {
            $this->license_manager = WritgoCMS_License_Manager::get_instance();
        }

        // AJAX handlers for credit operations.
        add_action( 'wp_ajax_writgocms_get_credits', array( $this, 'ajax_get_credits' ) );
        add_action( 'wp_ajax_writgocms_check_credits', array( $this, 'ajax_check_credits' ) );
        add_action( 'wp_ajax_writgocms_consume_credits', array( $this, 'ajax_consume_credits' ) );

        // REST API endpoints.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Dashboard widget.
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

        // Admin bar credit display.
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_credits' ), 100 );

        // Enqueue admin scripts for credit operations.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Filter to check credits before AI operations.
        add_filter( 'writgocms_before_ai_operation', array( $this, 'check_credits_before_operation' ), 10, 2 );

        // Cron for monthly credit reset check.
        add_action( 'writgocms_check_credit_reset', array( $this, 'maybe_reset_credits' ) );

        // Schedule cron if not scheduled.
        if ( ! wp_next_scheduled( 'writgocms_check_credit_reset' ) ) {
            wp_schedule_event( time(), 'daily', 'writgocms_check_credit_reset' );
        }
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes() {
        register_rest_route( 'writgo/v1', '/credits', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_credits' ),
            'permission_callback' => array( $this, 'check_rest_permissions' ),
        ) );

        register_rest_route( 'writgo/v1', '/credits/check', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_check_credits' ),
            'permission_callback' => array( $this, 'check_rest_permissions' ),
            'args'                => array(
                'action' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'amount' => array(
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 0,
                ),
            ),
        ) );

        register_rest_route( 'writgo/v1', '/credits/consume', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_consume_credits' ),
            'permission_callback' => array( $this, 'check_rest_permissions' ),
            'args'                => array(
                'action' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'amount' => array(
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 0,
                ),
            ),
        ) );
    }

    /**
     * Enqueue admin scripts
     *
     * @return void
     */
    public function enqueue_admin_scripts() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Localize script for AJAX operations.
        wp_localize_script(
            'jquery',
            'writgocmsCredits',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'writgocms_credits_nonce' ),
            )
        );
    }

    /**
     * Check REST API permissions
     *
     * @return bool|WP_Error
     */
    public function check_rest_permissions() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'unauthorized', __( 'You must be logged in.', 'writgocms' ), array( 'status' => 401 ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error( 'forbidden', __( 'You do not have permission.', 'writgocms' ), array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Get credit information for current license
     *
     * @return array Credit information.
     */
    public function get_credit_info() {
        // First try to get from local storage (WooCommerce generated).
        $license_key = $this->get_license_key();
        if ( ! empty( $license_key ) ) {
            $local_license = $this->get_local_license( $license_key );
            if ( $local_license ) {
                // Check if period has reset.
                $this->maybe_reset_credits_for_license( $license_key, $local_license );
                $local_license = $this->get_local_license( $license_key );

                return array(
                    'credits_total'     => (int) $local_license['credits_total'],
                    'credits_used'      => (int) $local_license['credits_used'],
                    'credits_remaining' => (int) $local_license['credits_total'] - (int) $local_license['credits_used'],
                    'period_start'      => $local_license['period_start'],
                    'period_end'        => $local_license['period_end'],
                    'plan_name'         => $local_license['plan_name'],
                    'status'            => $local_license['status'],
                );
            }
        }

        // Fall back to license manager (remote API).
        if ( $this->license_manager ) {
            $status = $this->license_manager->get_license_status();

            if ( isset( $status['usage'] ) ) {
                $credits_total = isset( $status['limits']['credits'] ) ? (int) $status['limits']['credits'] : 0;
                $credits_used  = isset( $status['usage']['credits'] ) ? (int) $status['usage']['credits'] : 0;

                return array(
                    'credits_total'     => $credits_total,
                    'credits_used'      => $credits_used,
                    'credits_remaining' => $credits_total - $credits_used,
                    'period_start'      => isset( $status['period_start'] ) ? $status['period_start'] : '',
                    'period_end'        => isset( $status['period_end'] ) ? $status['period_end'] : '',
                    'plan_name'         => isset( $status['plan_name'] ) ? $status['plan_name'] : '',
                    'status'            => isset( $status['status'] ) ? $status['status'] : 'inactive',
                );
            }
        }

        // Return empty state.
        return array(
            'credits_total'     => 0,
            'credits_used'      => 0,
            'credits_remaining' => 0,
            'period_start'      => '',
            'period_end'        => '',
            'plan_name'         => '',
            'status'            => 'inactive',
        );
    }

    /**
     * Get license key
     *
     * @return string
     */
    private function get_license_key() {
        if ( $this->license_manager ) {
            return $this->license_manager->get_license_key();
        }
        return get_option( 'writgocms_license_key', '' );
    }

    /**
     * Get local license data
     *
     * @param string $license_key License key.
     * @return array|false License data or false if not found.
     */
    private function get_local_license( $license_key ) {
        $licenses = get_option( 'writgoai_licenses', array() );
        return isset( $licenses[ $license_key ] ) ? $licenses[ $license_key ] : false;
    }

    /**
     * Check if user has sufficient credits for an action
     *
     * @param string $action Action type.
     * @param int    $amount Optional custom amount (overrides action cost).
     * @return bool|WP_Error True if sufficient, WP_Error if not.
     */
    public function has_sufficient_credits( $action, $amount = 0 ) {
        $credit_info = $this->get_credit_info();

        if ( 'inactive' === $credit_info['status'] || 'expired' === $credit_info['status'] ) {
            return new WP_Error( 'license_inactive', __( 'Your license is not active.', 'writgocms' ) );
        }

        $required = $amount > 0 ? $amount : $this->get_credit_cost( $action );
        $remaining = $credit_info['credits_remaining'];

        if ( $remaining < $required ) {
            return new WP_Error(
                'insufficient_credits',
                sprintf(
                    /* translators: 1: required credits, 2: remaining credits */
                    __( 'Insufficient credits. Required: %1$d, Available: %2$d', 'writgocms' ),
                    $required,
                    $remaining
                ),
                array(
                    'required'  => $required,
                    'remaining' => $remaining,
                )
            );
        }

        return true;
    }

    /**
     * Get credit cost for an action
     *
     * @param string $action Action type.
     * @return int Credit cost.
     */
    public function get_credit_cost( $action ) {
        return isset( $this->credit_costs[ $action ] ) ? $this->credit_costs[ $action ] : 10;
    }

    /**
     * Get all credit costs
     *
     * @return array All credit costs.
     */
    public function get_all_credit_costs() {
        return $this->credit_costs;
    }

    /**
     * Consume credits for an action
     *
     * @param string $action Action type.
     * @param int    $amount Optional custom amount (overrides action cost).
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function consume_credits( $action, $amount = 0 ) {
        $credits_to_consume = $amount > 0 ? $amount : $this->get_credit_cost( $action );

        // Check if user has sufficient credits.
        $check = $this->has_sufficient_credits( $action, $credits_to_consume );
        if ( is_wp_error( $check ) ) {
            return $check;
        }

        // Try local consumption first.
        $license_key = $this->get_license_key();
        if ( ! empty( $license_key ) ) {
            $result = $this->consume_local_credits( $license_key, $credits_to_consume, $action );
            if ( $result ) {
                return true;
            }
        }

        // Fall back to remote API.
        if ( class_exists( 'WritgoCMS_License_Client' ) ) {
            $client = WritgoCMS_License_Client::get_instance();
            $result = $client->consume_credits( $credits_to_consume, $action );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            return true;
        }

        return new WP_Error( 'consume_failed', __( 'Failed to consume credits.', 'writgocms' ) );
    }

    /**
     * Consume credits from local storage
     *
     * @param string $license_key License key.
     * @param int    $amount      Amount to consume.
     * @param string $action      Action type.
     * @return bool
     */
    private function consume_local_credits( $license_key, $amount, $action ) {
        $licenses = get_option( 'writgoai_licenses', array() );

        if ( ! isset( $licenses[ $license_key ] ) ) {
            return false;
        }

        $license = $licenses[ $license_key ];

        // Check credits.
        $remaining = (int) $license['credits_total'] - (int) $license['credits_used'];
        if ( $remaining < $amount ) {
            return false;
        }

        // Consume credits.
        $licenses[ $license_key ]['credits_used'] = (int) $license['credits_used'] + $amount;
        update_option( 'writgoai_licenses', $licenses );

        // Log activity.
        $this->log_credit_consumption( $license_key, $amount, $action );

        return true;
    }

    /**
     * Log credit consumption
     *
     * @param string $license_key License key.
     * @param int    $amount      Amount consumed.
     * @param string $action      Action type.
     * @return void
     */
    private function log_credit_consumption( $license_key, $amount, $action ) {
        $activity_log = get_option( 'writgoai_credit_activity', array() );

        $activity_log[] = array(
            'license_key' => $license_key,
            'action'      => $action,
            'amount'      => $amount,
            'user_id'     => get_current_user_id(),
            'created_at'  => current_time( 'mysql' ),
        );

        // Keep only last 500 entries.
        if ( count( $activity_log ) > 500 ) {
            $activity_log = array_slice( $activity_log, -500 );
        }

        update_option( 'writgoai_credit_activity', $activity_log );
    }

    /**
     * Check if credits need to be reset for a license
     *
     * @param string $license_key License key.
     * @param array  $license     License data.
     * @return void
     */
    private function maybe_reset_credits_for_license( $license_key, $license ) {
        if ( empty( $license['period_end'] ) ) {
            return;
        }

        $period_end = strtotime( $license['period_end'] );
        $today      = strtotime( gmdate( 'Y-m-d' ) );

        // If period has ended, we should wait for subscription renewal.
        // The credits are reset when renewal webhook is received.
        // This function just checks - it doesn't auto-reset.
    }

    /**
     * Maybe reset credits (cron job)
     *
     * @return void
     */
    public function maybe_reset_credits() {
        // This is called daily to check for expired periods.
        // Credits are actually reset by the subscription renewal handler.
        $licenses = get_option( 'writgoai_licenses', array() );

        foreach ( $licenses as $key => $license ) {
            if ( ! isset( $license['period_end'] ) ) {
                continue;
            }

            $period_end = strtotime( $license['period_end'] );
            $today      = strtotime( gmdate( 'Y-m-d' ) );

            // If period ended more than grace_period_days ago and still active, mark as expired.
            if ( $today > $period_end + ( $this->grace_period_days * DAY_IN_SECONDS ) && 'active' === $license['status'] ) {
                $licenses[ $key ]['status'] = 'expired';
            }
        }

        update_option( 'writgoai_licenses', $licenses );
    }

    /**
     * Filter to check credits before AI operations
     *
     * @param bool   $can_proceed Whether the operation can proceed.
     * @param string $action      Action type.
     * @return bool|WP_Error
     */
    public function check_credits_before_operation( $can_proceed, $action ) {
        if ( ! $can_proceed ) {
            return $can_proceed;
        }

        return $this->has_sufficient_credits( $action );
    }

    /**
     * Add dashboard widget
     *
     * @return void
     */
    public function add_dashboard_widget() {
        // Only show if user can edit posts.
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Only show if license is active.
        $license_key = $this->get_license_key();
        if ( empty( $license_key ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'writgoai_credits_widget',
            '🤖 WritgoAI Credits',
            array( $this, 'render_dashboard_widget' )
        );
    }

    /**
     * Render dashboard widget
     *
     * @return void
     */
    public function render_dashboard_widget() {
        $credit_info = $this->get_credit_info();
        $percentage  = $credit_info['credits_total'] > 0
            ? ( $credit_info['credits_remaining'] / $credit_info['credits_total'] ) * 100
            : 0;

        $bar_color = $percentage > 50 ? '#28a745' : ( $percentage > 20 ? '#ffc107' : '#dc3545' );
        ?>
        <div class="writgoai-credits-widget">
            <style>
                .writgoai-credits-widget { padding: 10px 0; }
                .writgoai-credits-widget .credits-display {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                .writgoai-credits-widget .credits-number {
                    font-size: 32px;
                    font-weight: bold;
                    color: #1e1e1e;
                }
                .writgoai-credits-widget .credits-label {
                    color: #666;
                    font-size: 14px;
                }
                .writgoai-credits-widget .credits-bar {
                    height: 8px;
                    background: #e9ecef;
                    border-radius: 4px;
                    overflow: hidden;
                    margin-bottom: 15px;
                }
                .writgoai-credits-widget .credits-bar-fill {
                    height: 100%;
                    border-radius: 4px;
                    transition: width 0.3s ease;
                }
                .writgoai-credits-widget .credits-info {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                    font-size: 13px;
                    color: #666;
                }
                .writgoai-credits-widget .plan-badge {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 2px 10px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 500;
                }
            </style>

            <div class="credits-display">
                <div>
                    <div class="credits-number"><?php echo number_format( $credit_info['credits_remaining'] ); ?></div>
                    <div class="credits-label"><?php esc_html_e( 'credits remaining', 'writgocms' ); ?></div>
                </div>
                <div>
                    <?php if ( ! empty( $credit_info['plan_name'] ) ) : ?>
                    <span class="plan-badge"><?php echo esc_html( $credit_info['plan_name'] ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="credits-bar">
                <div class="credits-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%; background: <?php echo esc_attr( $bar_color ); ?>;"></div>
            </div>

            <div class="credits-info">
                <div>
                    <strong><?php echo number_format( $credit_info['credits_used'] ); ?></strong> <?php esc_html_e( 'used', 'writgocms' ); ?>
                </div>
                <div>
                    <strong><?php echo number_format( $credit_info['credits_total'] ); ?></strong> <?php esc_html_e( 'total', 'writgocms' ); ?>
                </div>
                <?php if ( ! empty( $credit_info['period_end'] ) ) : ?>
                <div style="grid-column: span 2;">
                    <?php
                    /* translators: %s: date */
                    echo esc_html( sprintf( __( 'Resets on: %s', 'writgocms' ), $credit_info['period_end'] ) );
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <p style="margin-top: 15px; margin-bottom: 0;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-license' ) ); ?>" class="button button-small">
                    <?php esc_html_e( 'View Details', 'writgocms' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Add credits display to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
     * @return void
     */
    public function add_admin_bar_credits( $wp_admin_bar ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $license_key = $this->get_license_key();
        if ( empty( $license_key ) ) {
            return;
        }

        $credit_info = $this->get_credit_info();

        $wp_admin_bar->add_node( array(
            'id'    => 'writgoai-credits',
            'title' => sprintf(
                '<span style="display: inline-flex; align-items: center; gap: 5px;">🤖 <strong>%s</strong> %s</span>',
                number_format( $credit_info['credits_remaining'] ),
                esc_html__( 'credits', 'writgocms' )
            ),
            'href'  => admin_url( 'admin.php?page=writgocms-license' ),
            'meta'  => array(
                'title' => sprintf(
                    /* translators: 1: remaining credits, 2: total credits */
                    __( '%1$s of %2$s credits remaining', 'writgocms' ),
                    number_format( $credit_info['credits_remaining'] ),
                    number_format( $credit_info['credits_total'] )
                ),
            ),
        ) );
    }

    /**
     * REST API: Get credits
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_credits( $request ) {
        $credit_info = $this->get_credit_info();
        $credit_info['credit_costs'] = $this->credit_costs;

        return new WP_REST_Response( $credit_info, 200 );
    }

    /**
     * REST API: Check credits for action
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_check_credits( $request ) {
        $action = $request->get_param( 'action' );
        $amount = $request->get_param( 'amount' );

        $result = $this->has_sufficient_credits( $action, $amount );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $credit_info = $this->get_credit_info();
        $required = $amount > 0 ? $amount : $this->get_credit_cost( $action );

        return new WP_REST_Response( array(
            'has_credits'       => true,
            'credits_required'  => $required,
            'credits_remaining' => $credit_info['credits_remaining'],
        ), 200 );
    }

    /**
     * REST API: Consume credits
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_consume_credits( $request ) {
        $action = $request->get_param( 'action' );
        $amount = $request->get_param( 'amount' );

        $result = $this->consume_credits( $action, $amount );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $credit_info = $this->get_credit_info();
        $consumed = $amount > 0 ? $amount : $this->get_credit_cost( $action );

        return new WP_REST_Response( array(
            'success'           => true,
            'credits_consumed'  => $consumed,
            'credits_remaining' => $credit_info['credits_remaining'],
        ), 200 );
    }

    /**
     * AJAX: Get credits
     *
     * @return void
     */
    public function ajax_get_credits() {
        check_ajax_referer( 'writgocms_credits_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $credit_info = $this->get_credit_info();
        $credit_info['credit_costs'] = $this->credit_costs;

        wp_send_json_success( $credit_info );
    }

    /**
     * AJAX: Check credits
     *
     * @return void
     */
    public function ajax_check_credits() {
        check_ajax_referer( 'writgocms_credits_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $action = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? absint( $_POST['amount'] ) : 0;

        $result = $this->has_sufficient_credits( $action, $amount );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
                'data'    => $result->get_error_data(),
            ) );
        }

        $credit_info = $this->get_credit_info();
        $required = $amount > 0 ? $amount : $this->get_credit_cost( $action );

        wp_send_json_success( array(
            'has_credits'       => true,
            'credits_required'  => $required,
            'credits_remaining' => $credit_info['credits_remaining'],
        ) );
    }

    /**
     * AJAX: Consume credits
     *
     * @return void
     */
    public function ajax_consume_credits() {
        check_ajax_referer( 'writgocms_credits_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $action = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? absint( $_POST['amount'] ) : 0;

        $result = $this->consume_credits( $action, $amount );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
        }

        $credit_info = $this->get_credit_info();
        $consumed = $amount > 0 ? $amount : $this->get_credit_cost( $action );

        wp_send_json_success( array(
            'success'           => true,
            'credits_consumed'  => $consumed,
            'credits_remaining' => $credit_info['credits_remaining'],
        ) );
    }
}

/**
 * Get credit manager instance
 *
 * @return WritgoCMS_Credit_Manager
 */
function writgocms_credits() {
    return WritgoCMS_Credit_Manager::get_instance();
}

/**
 * Check if user has sufficient credits
 *
 * @param string $action Action type.
 * @param int    $amount Optional custom amount.
 * @return bool|WP_Error
 */
function writgocms_has_credits( $action, $amount = 0 ) {
    return writgocms_credits()->has_sufficient_credits( $action, $amount );
}

/**
 * Consume credits for an action
 *
 * @param string $action Action type.
 * @param int    $amount Optional custom amount.
 * @return bool|WP_Error
 */
function writgocms_consume_credits( $action, $amount = 0 ) {
    return writgocms_credits()->consume_credits( $action, $amount );
}

/**
 * Get credit cost for an action
 *
 * @param string $action Action type.
 * @return int
 */
function writgocms_get_credit_cost( $action ) {
    return writgocms_credits()->get_credit_cost( $action );
}

// Initialize.
add_action( 'plugins_loaded', function() {
    WritgoCMS_Credit_Manager::get_instance();
}, 15 );
