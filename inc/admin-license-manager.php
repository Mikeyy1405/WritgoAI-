<?php
/**
 * Admin License Manager
 *
 * Admin page for viewing and managing all licenses.
 * Features:
 * - View all licenses and their status
 * - View license details
 * - Manage credits
 * - View activity logs
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoCMS_Admin_License_Manager
 */
class WritgoCMS_Admin_License_Manager {

    /**
     * Instance
     *
     * @var WritgoCMS_Admin_License_Manager
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WritgoCMS_Admin_License_Manager
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
        add_action( 'wp_ajax_writgocms_admin_update_license', array( $this, 'ajax_update_license' ) );
        add_action( 'wp_ajax_writgocms_admin_delete_license', array( $this, 'ajax_delete_license' ) );
        add_action( 'wp_ajax_writgocms_admin_add_credits', array( $this, 'ajax_add_credits' ) );
        add_action( 'wp_ajax_writgocms_admin_generate_test_license', array( $this, 'ajax_generate_test_license' ) );
    }

    /**
     * Add admin menu
     *
     * Note: License Manager menu item removed as part of UI simplification.
     * License management functionality still available via class methods for API/backend use.
     *
     * @return void
     */
    public function add_admin_menu() {
        // Menu item removed - not needed in simplified admin UI
        // Functionality still accessible programmatically if needed
    }

    /**
     * Enqueue scripts
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'writgocms-license-manager' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'writgocms-license-manager',
            WRITGOCMS_URL . 'assets/css/admin-aiml.css',
            array(),
            WRITGOCMS_VERSION
        );

        wp_enqueue_script(
            'writgocms-license-manager',
            WRITGOCMS_URL . 'assets/js/admin-aiml.js',
            array( 'jquery' ),
            WRITGOCMS_VERSION,
            true
        );

        wp_localize_script(
            'writgocms-license-manager',
            'writgocmsLicenseManager',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'writgocms_license_manager_nonce' ),
                'i18n'    => array(
                    'confirmDelete' => __( 'Are you sure you want to delete this license?', 'writgocms' ),
                    'saving'        => __( 'Saving...', 'writgocms' ),
                    'saved'         => __( 'Saved!', 'writgocms' ),
                    'error'         => __( 'Error', 'writgocms' ),
                ),
            )
        );
    }

    /**
     * Render license manager page
     *
     * @return void
     */
    public function render_license_manager_page() {
        $licenses      = get_option( 'writgoai_licenses', array() );
        $activity_log  = get_option( 'writgoai_license_activity', array() );
        $credit_log    = get_option( 'writgoai_credit_activity', array() );

        // Sort licenses by created date (newest first).
        uasort( $licenses, function( $a, $b ) {
            $a_time = isset( $a['activated_at'] ) ? strtotime( $a['activated_at'] ) : 0;
            $b_time = isset( $b['activated_at'] ) ? strtotime( $b['activated_at'] ) : 0;
            return $b_time - $a_time;
        } );

        // Get selected tab.
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'licenses';

        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">📋</span>
                <?php esc_html_e( 'License Manager', 'writgocms' ); ?>
            </h1>

            <!-- Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-license-manager&tab=licenses' ) ); ?>" 
                   class="nav-tab <?php echo 'licenses' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'All Licenses', 'writgocms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-license-manager&tab=activity' ) ); ?>" 
                   class="nav-tab <?php echo 'activity' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Activity Log', 'writgocms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-license-manager&tab=credits' ) ); ?>" 
                   class="nav-tab <?php echo 'credits' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Credit Usage', 'writgocms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=writgocms-license-manager&tab=stats' ) ); ?>" 
                   class="nav-tab <?php echo 'stats' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Statistics', 'writgocms' ); ?>
                </a>
            </nav>

            <div class="aiml-tab-content">
                <?php
                switch ( $tab ) {
                    case 'activity':
                        $this->render_activity_tab( $activity_log );
                        break;
                    case 'credits':
                        $this->render_credits_tab( $credit_log );
                        break;
                    case 'stats':
                        $this->render_stats_tab( $licenses, $credit_log );
                        break;
                    default:
                        $this->render_licenses_tab( $licenses );
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render licenses tab
     *
     * @param array $licenses All licenses.
     * @return void
     */
    private function render_licenses_tab( $licenses ) {
        ?>
        <div class="planner-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;"><?php esc_html_e( 'All Licenses', 'writgocms' ); ?> <span class="count">(<?php echo count( $licenses ); ?>)</span></h2>
                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                    <button type="button" class="button button-primary" id="generate-test-license-btn">
                        <span class="dashicons dashicons-admin-network" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Generate Test License', 'writgocms' ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ( empty( $licenses ) ) : ?>
                <p class="description"><?php esc_html_e( 'No licenses found. Licenses are created automatically when customers purchase a subscription.', 'writgocms' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'License Key', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Plan', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Credits', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Expires', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'writgocms' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $licenses as $key => $license ) : ?>
                            <?php
                            $remaining = (int) $license['credits_total'] - (int) $license['credits_used'];
                            $percentage = $license['credits_total'] > 0 ? ( $remaining / $license['credits_total'] ) * 100 : 0;
                            $status_class = $this->get_status_class( $license['status'] );
                            ?>
                            <tr data-license-key="<?php echo esc_attr( $key ); ?>">
                                <td>
                                    <code style="font-size: 12px;"><?php echo esc_html( $key ); ?></code>
                                </td>
                                <td><?php echo esc_html( $license['email'] ); ?></td>
                                <td>
                                    <span class="plan-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                        <?php echo esc_html( $license['plan_name'] ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 60px; height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;">
                                            <div style="width: <?php echo esc_attr( $percentage ); ?>%; height: 100%; background: <?php echo $percentage > 20 ? '#28a745' : '#dc3545'; ?>;"></div>
                                        </div>
                                        <span style="font-size: 12px;"><?php echo number_format( $remaining ); ?>/<?php echo number_format( $license['credits_total'] ); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( ucfirst( $license['status'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html( isset( $license['period_end'] ) ? $license['period_end'] : '-' ); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small view-license" data-license-key="<?php echo esc_attr( $key ); ?>">
                                        <?php esc_html_e( 'View', 'writgocms' ); ?>
                                    </button>
                                    <button type="button" class="button button-small add-credits" data-license-key="<?php echo esc_attr( $key ); ?>">
                                        <?php esc_html_e( '+ Credits', 'writgocms' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- License Detail Modal -->
        <div id="license-detail-modal" class="writgoai-modal" style="display: none;">
            <div class="writgoai-modal-content">
                <span class="writgoai-modal-close">&times;</span>
                <h2><?php esc_html_e( 'License Details', 'writgocms' ); ?></h2>
                <div id="license-detail-content"></div>
            </div>
        </div>

        <!-- Add Credits Modal -->
        <div id="add-credits-modal" class="writgoai-modal" style="display: none;">
            <div class="writgoai-modal-content">
                <span class="writgoai-modal-close">&times;</span>
                <h2><?php esc_html_e( 'Add Credits', 'writgocms' ); ?></h2>
                <form id="add-credits-form">
                    <input type="hidden" id="add-credits-license-key" name="license_key" value="">
                    <p>
                        <label for="add-credits-amount"><?php esc_html_e( 'Credits to Add:', 'writgocms' ); ?></label>
                        <input type="number" id="add-credits-amount" name="amount" min="1" max="10000" value="100" class="regular-text">
                    </p>
                    <p>
                        <label for="add-credits-reason"><?php esc_html_e( 'Reason:', 'writgocms' ); ?></label>
                        <input type="text" id="add-credits-reason" name="reason" class="regular-text" placeholder="<?php esc_attr_e( 'Optional reason for adding credits', 'writgocms' ); ?>">
                    </p>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Credits', 'writgocms' ); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Generate Test License Modal -->
        <div id="generate-test-license-modal" class="writgoai-modal" style="display: none;">
            <div class="writgoai-modal-content">
                <span class="writgoai-modal-close">&times;</span>
                <h2><?php esc_html_e( 'Generate Test License', 'writgocms' ); ?></h2>
                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                    <p class="description"><?php esc_html_e( 'Generate a test license for development and testing. This feature is only available when WP_DEBUG is enabled.', 'writgocms' ); ?></p>
                    <form id="generate-test-license-form">
                        <p>
                            <label>
                                <input type="checkbox" id="test-license-use-demo" name="use_demo" value="1">
                                <?php esc_html_e( 'Use fixed demo license (TEST-DEMO-1234-5678)', 'writgocms' ); ?>
                            </label>
                        </p>
                        <div id="test-license-custom-fields">
                            <p>
                                <label for="test-license-email"><?php esc_html_e( 'Email:', 'writgocms' ); ?></label>
                                <input type="email" id="test-license-email" name="email" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required>
                            </p>
                            <p>
                                <label for="test-license-plan"><?php esc_html_e( 'Plan:', 'writgocms' ); ?></label>
                                <select id="test-license-plan" name="plan" class="regular-text">
                                    <option value="starter"><?php esc_html_e( 'Starter (1,000 credits)', 'writgocms' ); ?></option>
                                    <option value="pro" selected><?php esc_html_e( 'Pro (3,000 credits)', 'writgocms' ); ?></option>
                                    <option value="enterprise"><?php esc_html_e( 'Enterprise (10,000 credits)', 'writgocms' ); ?></option>
                                </select>
                            </p>
                        </div>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Generate License', 'writgocms' ); ?></button>
                        </p>
                    </form>
                <?php else : ?>
                    <p style="color: #dc3545;"><?php esc_html_e( 'Test license generation is only available when WP_DEBUG is enabled in wp-config.php', 'writgocms' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .status-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 500;
            }
            .status-active { background: #d4edda; color: #155724; }
            .status-expired { background: #f8d7da; color: #721c24; }
            .status-suspended { background: #fff3cd; color: #856404; }
            .status-cancelled { background: #d6d8db; color: #1b1e21; }
            .status-trial { background: #d1ecf1; color: #0c5460; }

            .writgoai-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .writgoai-modal-content {
                background: white;
                padding: 30px;
                border-radius: 10px;
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                position: relative;
            }
            .writgoai-modal-close {
                position: absolute;
                top: 15px;
                right: 20px;
                font-size: 28px;
                cursor: pointer;
                color: #666;
            }
            .writgoai-modal-close:hover {
                color: #000;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var licenses = <?php echo wp_json_encode( $licenses ); ?>;

            // View license details
            $('.view-license').on('click', function() {
                var key = $(this).data('license-key');
                var license = licenses[key];
                
                if (!license) return;
                
                var remaining = parseInt(license.credits_total) - parseInt(license.credits_used);
                var percentage = license.credits_total > 0 ? (remaining / license.credits_total) * 100 : 0;
                
                var html = '<table class="widefat">';
                html += '<tr><th><?php esc_html_e( 'License Key', 'writgocms' ); ?></th><td><code>' + key + '</code></td></tr>';
                html += '<tr><th><?php esc_html_e( 'Email', 'writgocms' ); ?></th><td>' + license.email + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Plan', 'writgocms' ); ?></th><td>' + license.plan_name + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Status', 'writgocms' ); ?></th><td>' + license.status + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Credits Used', 'writgocms' ); ?></th><td>' + license.credits_used + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Credits Total', 'writgocms' ); ?></th><td>' + license.credits_total + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Credits Remaining', 'writgocms' ); ?></th><td>' + remaining + ' (' + percentage.toFixed(1) + '%)</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Period Start', 'writgocms' ); ?></th><td>' + (license.period_start || '-') + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Period End', 'writgocms' ); ?></th><td>' + (license.period_end || '-') + '</td></tr>';
                html += '<tr><th><?php esc_html_e( 'Activated At', 'writgocms' ); ?></th><td>' + (license.activated_at || '-') + '</td></tr>';
                if (license.order_id) {
                    html += '<tr><th><?php esc_html_e( 'Order ID', 'writgocms' ); ?></th><td><a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ); ?>' + license.order_id + '">#' + license.order_id + '</a></td></tr>';
                }
                html += '</table>';
                
                if (license.features && license.features.length) {
                    html += '<h3><?php esc_html_e( 'Features', 'writgocms' ); ?></h3><ul>';
                    for (var i = 0; i < license.features.length; i++) {
                        html += '<li>✅ ' + license.features[i] + '</li>';
                    }
                    html += '</ul>';
                }
                
                $('#license-detail-content').html(html);
                $('#license-detail-modal').show();
            });

            // Add credits
            $('.add-credits').on('click', function() {
                var key = $(this).data('license-key');
                $('#add-credits-license-key').val(key);
                $('#add-credits-modal').show();
            });

            // Close modals
            $('.writgoai-modal-close').on('click', function() {
                $(this).closest('.writgoai-modal').hide();
            });

            $('.writgoai-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Add credits form
            $('#add-credits-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var key = $('#add-credits-license-key').val();
                var amount = $('#add-credits-amount').val();
                var reason = $('#add-credits-reason').val();
                
                $btn.prop('disabled', true).text('<?php esc_html_e( 'Adding...', 'writgocms' ); ?>');
                
                $.ajax({
                    url: writgocmsLicenseManager.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_admin_add_credits',
                        nonce: writgocmsLicenseManager.nonce,
                        license_key: key,
                        amount: amount,
                        reason: reason
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e( 'An error occurred.', 'writgocms' ); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php esc_html_e( 'Add Credits', 'writgocms' ); ?>');
                    }
                });
            });

            // Generate test license button
            $('#generate-test-license-btn').on('click', function() {
                $('#generate-test-license-modal').show();
            });

            // Toggle demo license checkbox
            $('#test-license-use-demo').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#test-license-custom-fields').hide();
                    $('#test-license-email').prop('required', false);
                } else {
                    $('#test-license-custom-fields').show();
                    $('#test-license-email').prop('required', true);
                }
            });

            // Generate test license form
            $('#generate-test-license-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var useDemo = $('#test-license-use-demo').is(':checked');
                var email = $('#test-license-email').val();
                var plan = $('#test-license-plan').val();
                
                $btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'writgocms' ); ?>');
                
                $.ajax({
                    url: writgocmsLicenseManager.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'writgocms_admin_generate_test_license',
                        nonce: writgocmsLicenseManager.nonce,
                        use_demo: useDemo ? '1' : '0',
                        email: email,
                        plan: plan
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message + '\n\nLicense Key: ' + response.data.license_key);
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e( 'An error occurred.', 'writgocms' ); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php esc_html_e( 'Generate License', 'writgocms' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render activity tab
     *
     * @param array $activity_log Activity log.
     * @return void
     */
    private function render_activity_tab( $activity_log ) {
        // Reverse to show newest first.
        $activity_log = array_reverse( $activity_log );
        // Limit to 100 entries for display.
        $activity_log = array_slice( $activity_log, 0, 100 );

        ?>
        <div class="planner-card">
            <h2><?php esc_html_e( 'License Activity Log', 'writgocms' ); ?></h2>

            <?php if ( empty( $activity_log ) ) : ?>
                <p class="description"><?php esc_html_e( 'No activity recorded yet.', 'writgocms' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'License Key', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Activity', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Details', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'IP Address', 'writgocms' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $activity_log as $entry ) : ?>
                            <tr>
                                <td><?php echo esc_html( $entry['created_at'] ); ?></td>
                                <td><code style="font-size: 11px;"><?php echo esc_html( substr( $entry['license_key'], 0, 9 ) . '...' ); ?></code></td>
                                <td>
                                    <span class="activity-type activity-<?php echo esc_attr( $entry['activity_type'] ); ?>">
                                        <?php echo esc_html( $this->get_activity_label( $entry['activity_type'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    if ( ! empty( $entry['metadata'] ) ) {
                                        echo '<small>' . esc_html( wp_json_encode( $entry['metadata'] ) ) . '</small>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html( $entry['ip_address'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <style>
            .activity-type {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11px;
            }
            .activity-created { background: #d4edda; color: #155724; }
            .activity-renewed { background: #cce5ff; color: #004085; }
            .activity-cancelled { background: #f8d7da; color: #721c24; }
            .activity-expired { background: #fff3cd; color: #856404; }
        </style>
        <?php
    }

    /**
     * Render credits tab
     *
     * @param array $credit_log Credit activity log.
     * @return void
     */
    private function render_credits_tab( $credit_log ) {
        // Reverse to show newest first.
        $credit_log = array_reverse( $credit_log );
        // Limit to 100 entries for display.
        $credit_log = array_slice( $credit_log, 0, 100 );

        ?>
        <div class="planner-card">
            <h2><?php esc_html_e( 'Credit Usage Log', 'writgocms' ); ?></h2>

            <?php if ( empty( $credit_log ) ) : ?>
                <p class="description"><?php esc_html_e( 'No credit usage recorded yet.', 'writgocms' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'License Key', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'Credits', 'writgocms' ); ?></th>
                            <th><?php esc_html_e( 'User', 'writgocms' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $credit_log as $entry ) : ?>
                            <?php
                            $user = isset( $entry['user_id'] ) ? get_userdata( $entry['user_id'] ) : null;
                            ?>
                            <tr>
                                <td><?php echo esc_html( $entry['created_at'] ); ?></td>
                                <td><code style="font-size: 11px;"><?php echo esc_html( substr( $entry['license_key'], 0, 9 ) . '...' ); ?></code></td>
                                <td><?php echo esc_html( $this->get_action_label( $entry['action'] ) ); ?></td>
                                <td><strong>-<?php echo esc_html( $entry['amount'] ); ?></strong></td>
                                <td>
                                    <?php if ( $user ) : ?>
                                        <?php echo esc_html( $user->display_name ); ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render stats tab
     *
     * @param array $licenses   All licenses.
     * @param array $credit_log Credit activity log.
     * @return void
     */
    private function render_stats_tab( $licenses, $credit_log ) {
        // Calculate statistics.
        $total_licenses = count( $licenses );
        $active_licenses = 0;
        $total_credits_used = 0;
        $total_credits_available = 0;

        foreach ( $licenses as $license ) {
            if ( 'active' === $license['status'] ) {
                $active_licenses++;
            }
            $total_credits_used += (int) $license['credits_used'];
            $total_credits_available += (int) $license['credits_total'];
        }

        // Credits by action.
        $credits_by_action = array();
        foreach ( $credit_log as $entry ) {
            $action = $entry['action'];
            if ( ! isset( $credits_by_action[ $action ] ) ) {
                $credits_by_action[ $action ] = 0;
            }
            $credits_by_action[ $action ] += (int) $entry['amount'];
        }
        arsort( $credits_by_action );

        // Recent activity (last 7 days).
        $seven_days_ago = strtotime( '-7 days' );
        $recent_credits = 0;
        foreach ( $credit_log as $entry ) {
            if ( strtotime( $entry['created_at'] ) > $seven_days_ago ) {
                $recent_credits += (int) $entry['amount'];
            }
        }

        ?>
        <div class="planner-card">
            <h2><?php esc_html_e( 'License Statistics', 'writgocms' ); ?></h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #667eea;"><?php echo number_format( $total_licenses ); ?></div>
                    <div style="color: #666;"><?php esc_html_e( 'Total Licenses', 'writgocms' ); ?></div>
                </div>
                <div style="background: #d4edda; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #155724;"><?php echo number_format( $active_licenses ); ?></div>
                    <div style="color: #666;"><?php esc_html_e( 'Active Licenses', 'writgocms' ); ?></div>
                </div>
                <div style="background: #fff3cd; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #856404;"><?php echo number_format( $total_credits_used ); ?></div>
                    <div style="color: #666;"><?php esc_html_e( 'Total Credits Used', 'writgocms' ); ?></div>
                </div>
                <div style="background: #cce5ff; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #004085;"><?php echo number_format( $recent_credits ); ?></div>
                    <div style="color: #666;"><?php esc_html_e( 'Credits Used (7 days)', 'writgocms' ); ?></div>
                </div>
            </div>
        </div>

        <?php if ( ! empty( $credits_by_action ) ) : ?>
        <div class="planner-card">
            <h2><?php esc_html_e( 'Credits by Action Type', 'writgocms' ); ?></h2>
            <table class="widefat striped" style="max-width: 500px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Action', 'writgocms' ); ?></th>
                        <th style="text-align: right;"><?php esc_html_e( 'Credits Used', 'writgocms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $credits_by_action as $action => $credits ) : ?>
                        <tr>
                            <td><?php echo esc_html( $this->get_action_label( $action ) ); ?></td>
                            <td style="text-align: right;"><strong><?php echo number_format( $credits ); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Get status CSS class
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_class( $status ) {
        $classes = array(
            'active'    => 'active',
            'expired'   => 'expired',
            'suspended' => 'suspended',
            'cancelled' => 'cancelled',
            'trial'     => 'trial',
        );
        return isset( $classes[ $status ] ) ? $classes[ $status ] : 'default';
    }

    /**
     * Get activity label
     *
     * @param string $type Activity type.
     * @return string
     */
    private function get_activity_label( $type ) {
        $labels = array(
            'created'          => __( 'License Created', 'writgocms' ),
            'renewed'          => __( 'Subscription Renewed', 'writgocms' ),
            'cancelled'        => __( 'Subscription Cancelled', 'writgocms' ),
            'expired'          => __( 'License Expired', 'writgocms' ),
            'suspended'        => __( 'License Suspended', 'writgocms' ),
            'credits_added'    => __( 'Credits Added', 'writgocms' ),
            'credits_consumed' => __( 'Credits Consumed', 'writgocms' ),
        );
        return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( str_replace( '_', ' ', $type ) );
    }

    /**
     * Get action label
     *
     * @param string $action Action type.
     * @return string
     */
    private function get_action_label( $action ) {
        $labels = array(
            'ai_rewrite_small'     => __( 'AI Rewrite (Small)', 'writgocms' ),
            'ai_rewrite_paragraph' => __( 'AI Rewrite (Paragraph)', 'writgocms' ),
            'ai_rewrite_full'      => __( 'AI Rewrite (Full)', 'writgocms' ),
            'ai_image'             => __( 'AI Image', 'writgocms' ),
            'seo_analysis'         => __( 'SEO Analysis', 'writgocms' ),
            'internal_links'       => __( 'Internal Links', 'writgocms' ),
            'keyword_research'     => __( 'Keyword Research', 'writgocms' ),
            'text_generation'      => __( 'Text Generation', 'writgocms' ),
            'image_generation'     => __( 'Image Generation', 'writgocms' ),
        );
        return isset( $labels[ $action ] ) ? $labels[ $action ] : ucfirst( str_replace( '_', ' ', $action ) );
    }

    /**
     * AJAX: Update license
     *
     * @return void
     */
    public function ajax_update_license() {
        check_ajax_referer( 'writgocms_license_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
        $status      = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        if ( empty( $license_key ) || empty( $status ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields.', 'writgocms' ) ) );
        }

        $licenses = get_option( 'writgoai_licenses', array() );

        if ( ! isset( $licenses[ $license_key ] ) ) {
            wp_send_json_error( array( 'message' => __( 'License not found.', 'writgocms' ) ) );
        }

        $licenses[ $license_key ]['status'] = $status;
        update_option( 'writgoai_licenses', $licenses );

        wp_send_json_success( array( 'message' => __( 'License updated successfully.', 'writgocms' ) ) );
    }

    /**
     * AJAX: Delete license
     *
     * @return void
     */
    public function ajax_delete_license() {
        check_ajax_referer( 'writgocms_license_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

        if ( empty( $license_key ) ) {
            wp_send_json_error( array( 'message' => __( 'License key is required.', 'writgocms' ) ) );
        }

        $licenses = get_option( 'writgoai_licenses', array() );

        if ( ! isset( $licenses[ $license_key ] ) ) {
            wp_send_json_error( array( 'message' => __( 'License not found.', 'writgocms' ) ) );
        }

        unset( $licenses[ $license_key ] );
        update_option( 'writgoai_licenses', $licenses );

        wp_send_json_success( array( 'message' => __( 'License deleted successfully.', 'writgocms' ) ) );
    }

    /**
     * AJAX: Add credits
     *
     * @return void
     */
    public function ajax_add_credits() {
        check_ajax_referer( 'writgocms_license_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
        $amount      = isset( $_POST['amount'] ) ? absint( $_POST['amount'] ) : 0;
        $reason      = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

        if ( empty( $license_key ) || $amount < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid license key or amount.', 'writgocms' ) ) );
        }

        $licenses = get_option( 'writgoai_licenses', array() );

        if ( ! isset( $licenses[ $license_key ] ) ) {
            wp_send_json_error( array( 'message' => __( 'License not found.', 'writgocms' ) ) );
        }

        // Add credits by increasing total.
        $licenses[ $license_key ]['credits_total'] = (int) $licenses[ $license_key ]['credits_total'] + $amount;
        update_option( 'writgoai_licenses', $licenses );

        // Log activity.
        $activity_log = get_option( 'writgoai_license_activity', array() );
        $activity_log[] = array(
            'license_key'   => $license_key,
            'activity_type' => 'credits_added',
            'metadata'      => array(
                'amount' => $amount,
                'reason' => $reason,
                'admin'  => get_current_user_id(),
            ),
            'ip_address'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'created_at'    => current_time( 'mysql' ),
        );
        update_option( 'writgoai_license_activity', $activity_log );

        wp_send_json_success( array(
            /* translators: %d: number of credits added */
            'message' => sprintf( __( '%d credits added successfully.', 'writgocms' ), $amount ),
        ) );
    }

    /**
     * Generate a test license for development
     * Only available when WP_DEBUG is true
     *
     * @param string $email Email address.
     * @param string $plan  Plan type (starter, pro, enterprise).
     * @param bool   $use_demo Whether to use the fixed demo license.
     * @return array|WP_Error License data or error.
     */
    public function generate_test_license( $email = '', $plan = 'pro', $use_demo = false ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return new WP_Error( 'not_debug', __( 'Test licenses can only be generated in debug mode.', 'writgocms' ) );
        }

        // Plan credits mapping
        $plan_credits = array(
            'starter'    => 1000,
            'pro'        => 3000,
            'enterprise' => 10000,
        );

        // Check if demo license
        if ( $use_demo ) {
            $license_key = 'TEST-DEMO-1234-5678';
            $email       = 'demo@writgoai.com';
            $plan        = 'pro';
            $credits     = 3000;
            $is_demo     = true;
        } else {
            // Validate plan
            if ( ! isset( $plan_credits[ $plan ] ) ) {
                return new WP_Error( 'invalid_plan', __( 'Invalid plan type.', 'writgocms' ) );
            }

            // Validate email
            if ( empty( $email ) || ! is_email( $email ) ) {
                return new WP_Error( 'invalid_email', __( 'Valid email address is required.', 'writgocms' ) );
            }

            // Genereer license key
            $segments = array();
            for ( $i = 0; $i < 4; $i++ ) {
                $segments[] = strtoupper( substr( bin2hex( random_bytes( 2 ) ), 0, 4 ) );
            }
            $license_key = 'TEST-' . implode( '-', array_slice( $segments, 1 ) );
            $credits     = $plan_credits[ $plan ];
            $is_demo     = false;
        }

        // Check if license already exists
        $licenses = get_option( 'writgoai_licenses', array() );
        if ( isset( $licenses[ $license_key ] ) ) {
            return new WP_Error( 'license_exists', __( 'This license key already exists.', 'writgocms' ) );
        }

        $license_data = array(
            'license_key'   => $license_key,
            'email'         => $email,
            'plan_type'     => $plan,
            'plan_name'     => ucfirst( $plan ) . ( $is_demo ? ' (Demo)' : ' (Test)' ),
            'credits_total' => $credits,
            'credits_used'  => 0,
            'status'        => 'active',
            'order_id'      => 0,
            'activated_at'  => current_time( 'mysql' ),
            'expires_at'    => date( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
            'period_start'  => date( 'Y-m-d' ),
            'period_end'    => date( 'Y-m-d', strtotime( '+1 month' ) ),
            'features'      => array( 'ai_rewrite', 'ai_image', 'seo_analysis', 'keyword_research', 'content_planner' ),
            'is_test'       => true,
            'is_demo'       => $is_demo,
        );

        // Save to WordPress options
        $licenses[ $license_key ] = $license_data;
        update_option( 'writgoai_licenses', $licenses );

        // Activate directly
        update_option( 'writgoai_license_key', $license_key );
        update_option( 'writgoai_license_status', 'active' );
        update_option( 'writgoai_license_data', $license_data );

        // Log activity
        $activity_log   = get_option( 'writgoai_license_activity', array() );
        $activity_log[] = array(
            'license_key'   => $license_key,
            'activity_type' => 'created',
            'metadata'      => array(
                'type'    => 'test_license',
                'plan'    => $plan,
                'credits' => $credits,
                'is_demo' => $is_demo,
            ),
            'ip_address'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'created_at'    => current_time( 'mysql' ),
        );
        update_option( 'writgoai_license_activity', $activity_log );

        return $license_data;
    }

    /**
     * AJAX: Generate test license
     *
     * @return void
     */
    public function ajax_generate_test_license() {
        check_ajax_referer( 'writgocms_license_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $use_demo = isset( $_POST['use_demo'] ) && '1' === $_POST['use_demo'];
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $plan     = isset( $_POST['plan'] ) ? sanitize_text_field( wp_unslash( $_POST['plan'] ) ) : 'pro';

        $result = $this->generate_test_license( $email, $plan, $use_demo );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message'     => $use_demo
                ? __( 'Demo license generated and activated successfully!', 'writgocms' )
                : __( 'Test license generated and activated successfully!', 'writgocms' ),
            'license_key' => $result['license_key'],
            'license'     => $result,
        ) );
    }
}

// Initialize.
add_action( 'plugins_loaded', function() {
    WritgoCMS_Admin_License_Manager::get_instance();
}, 20 );
