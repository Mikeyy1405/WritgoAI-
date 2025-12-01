<?php
/**
 * WooCommerce Integration for WritgoAI Subscriptions
 *
 * Handles WooCommerce subscription integration including:
 * - Auto license generation on purchase
 * - Subscription product setup
 * - Order processing hooks
 *
 * @package WritgoAI-Licensing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access denied.' );
}

/**
 * Class WritgoAI_WooCommerce_Integration
 */
class WritgoAI_WooCommerce_Integration {

    /**
     * Instance
     *
     * @var WritgoAI_WooCommerce_Integration
     */
    private static $instance = null;

    /**
     * Subscription plans configuration
     *
     * @var array
     */
    private $plans = array(
        'starter' => array(
            'name'        => 'Starter',
            'price'       => 29,
            'credits'     => 1000,
            'description' => '1,000 credits/month - Full access to all features',
        ),
        'pro' => array(
            'name'        => 'Pro',
            'price'       => 79,
            'credits'     => 3000,
            'description' => '3,000 credits/month - Full access to all features',
        ),
        'enterprise' => array(
            'name'        => 'Enterprise',
            'price'       => 199,
            'credits'     => 10000,
            'description' => '10,000 credits/month - Full access to all features',
        ),
    );

    /**
     * Features included in all plans
     *
     * @var array
     */
    private $features = array(
        'AI Rewrite',
        'AI Images',
        'SEO Tools',
        'Internal Links',
        'Gutenberg Toolbar',
        'Keyword Research',
        'Analytics',
        'All Future Features',
    );

    /**
     * Get instance
     *
     * @return WritgoAI_WooCommerce_Integration
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
        // WooCommerce hooks.
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_completed_order' ) );
        add_action( 'woocommerce_order_status_processing', array( $this, 'process_completed_order' ) );
        add_action( 'woocommerce_subscription_status_active', array( $this, 'process_subscription_activated' ) );
        add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'process_subscription_renewal' ) );
        add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'process_subscription_cancelled' ) );
        add_action( 'woocommerce_subscription_status_expired', array( $this, 'process_subscription_expired' ) );

        // Admin hooks.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'maybe_create_subscription_products' ) );
    }

    /**
     * Process completed order - generate license
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function process_completed_order( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // Check if license was already generated.
        $license_generated = $order->get_meta( '_writgoai_license_generated' );
        if ( 'yes' === $license_generated ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $plan_type = $product->get_meta( '_writgoai_plan_type' );
            if ( empty( $plan_type ) || ! isset( $this->plans[ $plan_type ] ) ) {
                continue;
            }

            // Generate license for this order.
            $license_data = $this->generate_license( $order, $plan_type );

            if ( $license_data ) {
                // Store license key in order meta.
                $order->update_meta_data( '_writgoai_license_key', $license_data['license_key'] );
                $order->update_meta_data( '_writgoai_license_generated', 'yes' );
                $order->save();

                // Send license email.
                $this->send_license_email( $order, $license_data );
            }
        }
    }

    /**
     * Process subscription activated
     *
     * @param WC_Subscription $subscription Subscription object.
     * @return void
     */
    public function process_subscription_activated( $subscription ) {
        $parent_order = $subscription->get_parent();
        if ( $parent_order ) {
            $this->process_completed_order( $parent_order->get_id() );
        }
    }

    /**
     * Process subscription renewal - refresh credits
     *
     * @param WC_Subscription $subscription Subscription object.
     * @return void
     */
    public function process_subscription_renewal( $subscription ) {
        $license_key = $subscription->get_meta( '_writgoai_license_key' );

        if ( empty( $license_key ) ) {
            // Try to get from parent order.
            $parent_order = $subscription->get_parent();
            if ( $parent_order ) {
                $license_key = $parent_order->get_meta( '_writgoai_license_key' );
            }
        }

        if ( empty( $license_key ) ) {
            return;
        }

        // Refresh credits for the new period.
        $this->refresh_license_credits( $license_key, $subscription );
    }

    /**
     * Process subscription cancelled
     *
     * @param WC_Subscription $subscription Subscription object.
     * @return void
     */
    public function process_subscription_cancelled( $subscription ) {
        $this->update_license_status( $subscription, 'cancelled' );
    }

    /**
     * Process subscription expired
     *
     * @param WC_Subscription $subscription Subscription object.
     * @return void
     */
    public function process_subscription_expired( $subscription ) {
        $this->update_license_status( $subscription, 'expired' );
    }

    /**
     * Generate a new license for an order
     *
     * @param WC_Order $order     Order object.
     * @param string   $plan_type Plan type (starter, pro, enterprise).
     * @return array|false License data or false on failure.
     */
    private function generate_license( $order, $plan_type ) {
        global $wpdb;

        // Generate unique license key.
        $license_key = $this->generate_license_key();

        $customer_email = $order->get_billing_email();
        $plan_config    = $this->plans[ $plan_type ];

        // Calculate period dates.
        $period_start = gmdate( 'Y-m-d' );
        $period_end   = gmdate( 'Y-m-d', strtotime( '+1 month' ) );
        $expires_at   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );

        // Try to insert license into the database.
        // Note: This requires the licensing database tables to exist.
        try {
            // Check if we have access to the licensing database.
            // For WordPress installations, we'll use WordPress options to store license data.
            $license_data = array(
                'license_key'   => $license_key,
                'email'         => $customer_email,
                'plan_type'     => $plan_type,
                'plan_name'     => $plan_config['name'],
                'credits_total' => $plan_config['credits'],
                'credits_used'  => 0,
                'status'        => 'active',
                'order_id'      => $order->get_id(),
                'activated_at'  => current_time( 'mysql' ),
                'expires_at'    => $expires_at,
                'period_start'  => $period_start,
                'period_end'    => $period_end,
                'features'      => $this->features,
            );

            // Store license in WordPress options for local management.
            $licenses = get_option( 'writgoai_licenses', array() );
            $licenses[ $license_key ] = $license_data;
            update_option( 'writgoai_licenses', $licenses );

            // Log the license creation.
            $this->log_license_activity( $license_key, 'created', array(
                'order_id'  => $order->get_id(),
                'plan_type' => $plan_type,
            ) );

            return $license_data;

        } catch ( Exception $e ) {
            error_log( 'WritgoAI: Failed to generate license - ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Generate a unique license key
     *
     * @return string License key in format XXXX-XXXX-XXXX-XXXX.
     */
    private function generate_license_key() {
        $segments = array();
        for ( $i = 0; $i < 4; $i++ ) {
            $segments[] = strtoupper( substr( bin2hex( random_bytes( 2 ) ), 0, 4 ) );
        }
        return implode( '-', $segments );
    }

    /**
     * Refresh license credits for renewal
     *
     * @param string          $license_key  License key.
     * @param WC_Subscription $subscription Subscription object.
     * @return bool
     */
    private function refresh_license_credits( $license_key, $subscription ) {
        $licenses = get_option( 'writgoai_licenses', array() );

        if ( ! isset( $licenses[ $license_key ] ) ) {
            return false;
        }

        $plan_type = $licenses[ $license_key ]['plan_type'];
        $plan_config = $this->plans[ $plan_type ];

        // Calculate new period dates.
        $period_start = gmdate( 'Y-m-d' );
        $period_end   = gmdate( 'Y-m-d', strtotime( '+1 month' ) );
        $expires_at   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );

        // Update license with new credits.
        $licenses[ $license_key ]['credits_total'] = $plan_config['credits'];
        $licenses[ $license_key ]['credits_used']  = 0;
        $licenses[ $license_key ]['period_start']  = $period_start;
        $licenses[ $license_key ]['period_end']    = $period_end;
        $licenses[ $license_key ]['expires_at']    = $expires_at;
        $licenses[ $license_key ]['status']        = 'active';

        update_option( 'writgoai_licenses', $licenses );

        // Log the renewal.
        $this->log_license_activity( $license_key, 'renewed', array(
            'credits_refreshed' => $plan_config['credits'],
        ) );

        return true;
    }

    /**
     * Update license status
     *
     * @param WC_Subscription $subscription Subscription object.
     * @param string          $status       New status.
     * @return bool
     */
    private function update_license_status( $subscription, $status ) {
        $license_key = $subscription->get_meta( '_writgoai_license_key' );

        if ( empty( $license_key ) ) {
            $parent_order = $subscription->get_parent();
            if ( $parent_order ) {
                $license_key = $parent_order->get_meta( '_writgoai_license_key' );
            }
        }

        if ( empty( $license_key ) ) {
            return false;
        }

        $licenses = get_option( 'writgoai_licenses', array() );

        if ( ! isset( $licenses[ $license_key ] ) ) {
            return false;
        }

        $licenses[ $license_key ]['status'] = $status;
        update_option( 'writgoai_licenses', $licenses );

        // Log the status change.
        $this->log_license_activity( $license_key, $status, array() );

        return true;
    }

    /**
     * Log license activity
     *
     * @param string $license_key   License key.
     * @param string $activity_type Activity type.
     * @param array  $metadata      Additional metadata.
     * @return void
     */
    private function log_license_activity( $license_key, $activity_type, $metadata = array() ) {
        $activity_log = get_option( 'writgoai_license_activity', array() );

        $activity_log[] = array(
            'license_key'   => $license_key,
            'activity_type' => $activity_type,
            'metadata'      => $metadata,
            'ip_address'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'created_at'    => current_time( 'mysql' ),
        );

        // Keep only last 1000 entries.
        if ( count( $activity_log ) > 1000 ) {
            $activity_log = array_slice( $activity_log, -1000 );
        }

        update_option( 'writgoai_license_activity', $activity_log );
    }

    /**
     * Send license delivery email
     *
     * @param WC_Order $order        Order object.
     * @param array    $license_data License data.
     * @return bool
     */
    private function send_license_email( $order, $license_data ) {
        $to = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name();

        $subject = sprintf(
            /* translators: %s: Plan name */
            __( 'Your WritgoAI %s License Key', 'writgocms' ),
            $license_data['plan_name']
        );

        $message = $this->get_license_email_template( $order, $license_data, $customer_name );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: WritgoAI <noreply@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
        );

        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Get license email template
     *
     * @param WC_Order $order         Order object.
     * @param array    $license_data  License data.
     * @param string   $customer_name Customer name.
     * @return string
     */
    private function get_license_email_template( $order, $license_data, $customer_name ) {
        $features_list = implode( '</li><li>', $this->features );

        $template = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your WritgoAI License</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .license-box { background: white; border: 2px dashed #667eea; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; }
        .license-key { font-size: 24px; font-weight: bold; color: #667eea; letter-spacing: 2px; font-family: monospace; }
        .plan-info { background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .features-list { list-style: none; padding: 0; }
        .features-list li { padding: 8px 0; padding-left: 25px; position: relative; }
        .features-list li:before { content: "✓"; position: absolute; left: 0; color: #28a745; font-weight: bold; }
        .cta-button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to WritgoAI!</h1>
            <p>Your ' . esc_html( $license_data['plan_name'] ) . ' subscription is now active</p>
        </div>
        <div class="content">
            <p>Hi ' . esc_html( $customer_name ) . ',</p>
            <p>Thank you for your purchase! Your WritgoAI license has been activated and is ready to use.</p>
            
            <div class="license-box">
                <p style="margin: 0; color: #666;">Your License Key:</p>
                <p class="license-key">' . esc_html( $license_data['license_key'] ) . '</p>
                <p style="margin: 0; font-size: 12px; color: #999;">Keep this key safe - you\'ll need it to activate the plugin.</p>
            </div>
            
            <div class="plan-info">
                <h3 style="margin-top: 0;">📦 ' . esc_html( $license_data['plan_name'] ) . ' Plan Details</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>' . number_format( $license_data['credits_total'] ) . ' credits</strong> per month</li>
                    <li>Credits reset on: <strong>' . esc_html( $license_data['period_end'] ) . '</strong></li>
                    <li>100% access to all features</li>
                </ul>
            </div>
            
            <h3>✨ Features Included:</h3>
            <ul class="features-list">
                <li>' . $features_list . '</li>
            </ul>
            
            <h3>🚀 Getting Started:</h3>
            <ol>
                <li>Install the WritgoAI plugin on your WordPress site</li>
                <li>Go to WritgoAI → License in your WordPress admin</li>
                <li>Enter your email and license key above</li>
                <li>Click "Activate License" and start creating!</li>
            </ol>
            
            <p style="text-align: center;">
                <a href="' . esc_url( admin_url( 'admin.php?page=writgocms-license' ) ) . '" class="cta-button">Activate Your License →</a>
            </p>
            
            <div class="footer">
                <p>Order #' . esc_html( $order->get_id() ) . ' | ' . esc_html( $order->get_date_created()->format( 'F j, Y' ) ) . '</p>
                <p>Questions? Contact us at support@writgoai.com</p>
                <p>© ' . gmdate( 'Y' ) . ' WritgoAI. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>';

        return $template;
    }

    /**
     * Add admin menu for subscription management
     *
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'writgocms-aiml',
            __( 'Subscription Products', 'writgocms' ),
            __( '📦 Products', 'writgocms' ),
            'manage_options',
            'writgocms-products',
            array( $this, 'render_products_page' )
        );
    }

    /**
     * Maybe create subscription products on admin init
     *
     * @return void
     */
    public function maybe_create_subscription_products() {
        // Only run once.
        if ( get_option( 'writgoai_products_created' ) ) {
            return;
        }

        // Check if WooCommerce is active.
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Sanitize and check if we're on the products page with create action.
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'writgocms-products' !== $page ) {
            return;
        }

        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        if ( 'create_products' !== $action ) {
            return;
        }

        // Verify nonce.
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'writgoai_create_products' ) ) {
            return;
        }

        $this->create_subscription_products();
        update_option( 'writgoai_products_created', true );

        // Redirect to remove query args.
        wp_safe_redirect( admin_url( 'admin.php?page=writgocms-products&created=1' ) );
        exit;
    }

    /**
     * Create WooCommerce subscription products
     *
     * @return void
     */
    private function create_subscription_products() {
        if ( ! class_exists( 'WC_Product_Subscription' ) && ! class_exists( 'WC_Product_Simple' ) ) {
            return;
        }

        foreach ( $this->plans as $plan_type => $plan_config ) {
            // Check if product already exists.
            $existing = get_posts( array(
                'post_type'  => 'product',
                'meta_key'   => '_writgoai_plan_type',
                'meta_value' => $plan_type,
                'numberposts' => 1,
            ) );

            if ( ! empty( $existing ) ) {
                continue;
            }

            // Create the product.
            $product_class = class_exists( 'WC_Product_Subscription' ) ? 'WC_Product_Subscription' : 'WC_Product_Simple';
            $product = new $product_class();

            $product->set_name( 'WritgoAI ' . $plan_config['name'] );
            $product->set_description( $this->get_product_description( $plan_config ) );
            $product->set_short_description( $plan_config['description'] );
            $product->set_regular_price( $plan_config['price'] );
            $product->set_status( 'publish' );
            $product->set_catalog_visibility( 'visible' );
            $product->set_sold_individually( true );
            $product->set_virtual( true );

            // Set subscription-specific data if WooCommerce Subscriptions is active.
            if ( class_exists( 'WC_Product_Subscription' ) && $product instanceof WC_Product_Subscription ) {
                $product->update_meta_data( '_subscription_price', $plan_config['price'] );
                $product->update_meta_data( '_subscription_period', 'month' );
                $product->update_meta_data( '_subscription_period_interval', '1' );
            }

            // Set WritgoAI-specific meta.
            $product->update_meta_data( '_writgoai_plan_type', $plan_type );
            $product->update_meta_data( '_writgoai_credits', $plan_config['credits'] );

            $product->save();
        }
    }

    /**
     * Get product description HTML
     *
     * @param array $plan_config Plan configuration.
     * @return string
     */
    private function get_product_description( $plan_config ) {
        $features_html = '';
        foreach ( $this->features as $feature ) {
            $features_html .= '<li>✅ ' . esc_html( $feature ) . '</li>';
        }

        return sprintf(
            '<h3>%s Plan</h3>
            <p><strong>%s credits per month</strong></p>
            <p>%s</p>
            <h4>All plans include 100%% access to:</h4>
            <ul>%s</ul>
            <p><em>Credits reset monthly. Unused credits do not roll over.</em></p>',
            esc_html( $plan_config['name'] ),
            number_format( $plan_config['credits'] ),
            esc_html( $plan_config['description'] ),
            $features_html
        );
    }

    /**
     * Render products admin page
     *
     * @return void
     */
    public function render_products_page() {
        $woocommerce_active = class_exists( 'WooCommerce' );
        $subscriptions_active = class_exists( 'WC_Subscriptions' );
        $products_created = get_option( 'writgoai_products_created', false );

        // Check for existing products.
        $existing_products = array();
        if ( $woocommerce_active ) {
            foreach ( $this->plans as $plan_type => $plan_config ) {
                $products = get_posts( array(
                    'post_type'  => 'product',
                    'meta_key'   => '_writgoai_plan_type',
                    'meta_value' => $plan_type,
                    'numberposts' => 1,
                ) );
                if ( ! empty( $products ) ) {
                    $existing_products[ $plan_type ] = $products[0];
                }
            }
        }

        ?>
        <div class="wrap writgocms-aiml-settings">
            <h1 class="aiml-header">
                <span class="aiml-logo">📦</span>
                <?php esc_html_e( 'WritgoAI Subscription Products', 'writgocms' ); ?>
            </h1>

            <?php
            $created_param = isset( $_GET['created'] ) ? sanitize_text_field( wp_unslash( $_GET['created'] ) ) : '';
            if ( '1' === $created_param ) :
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Subscription products have been created successfully!', 'writgocms' ); ?></p>
            </div>
            <?php endif; ?>

            <div class="aiml-tab-content">
                <!-- Status Cards -->
                <div class="planner-card">
                    <h2><?php esc_html_e( 'System Status', 'writgocms' ); ?></h2>
                    <table class="widefat" style="max-width: 500px;">
                        <tr>
                            <td><?php esc_html_e( 'WooCommerce', 'writgocms' ); ?></td>
                            <td>
                                <?php if ( $woocommerce_active ) : ?>
                                    <span style="color: green;">✅ <?php esc_html_e( 'Active', 'writgocms' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">❌ <?php esc_html_e( 'Not Installed', 'writgocms' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'WooCommerce Subscriptions', 'writgocms' ); ?></td>
                            <td>
                                <?php if ( $subscriptions_active ) : ?>
                                    <span style="color: green;">✅ <?php esc_html_e( 'Active', 'writgocms' ); ?></span>
                                <?php else : ?>
                                    <span style="color: orange;">⚠️ <?php esc_html_e( 'Not Installed (Simple products will be created)', 'writgocms' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Plans Overview -->
                <div class="planner-card">
                    <h2><?php esc_html_e( 'Subscription Plans', 'writgocms' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'All plans include 100% access to all features. The only difference is the monthly credit allowance.', 'writgocms' ); ?></p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                        <?php foreach ( $this->plans as $plan_type => $plan_config ) : ?>
                        <div style="background: <?php echo 'enterprise' === $plan_type ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#f8f9fa'; ?>; padding: 25px; border-radius: 10px; <?php echo 'enterprise' === $plan_type ? 'color: white;' : ''; ?>">
                            <h3 style="margin-top: 0; <?php echo 'enterprise' === $plan_type ? 'color: white;' : ''; ?>">
                                <?php echo esc_html( $plan_config['name'] ); ?>
                                <?php if ( 'pro' === $plan_type ) : ?>
                                    <span style="background: #ffc107; color: #333; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px;"><?php esc_html_e( 'Popular', 'writgocms' ); ?></span>
                                <?php endif; ?>
                            </h3>
                            <p style="font-size: 32px; margin: 10px 0; font-weight: bold;">
                                €<?php echo esc_html( $plan_config['price'] ); ?><span style="font-size: 14px; font-weight: normal;">/<?php esc_html_e( 'month', 'writgocms' ); ?></span>
                            </p>
                            <p style="font-size: 18px;">
                                <strong><?php echo number_format( $plan_config['credits'] ); ?></strong> <?php esc_html_e( 'credits/month', 'writgocms' ); ?>
                            </p>
                            <hr style="opacity: 0.3; margin: 15px 0;">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ( $this->features as $feature ) : ?>
                                <li style="padding: 5px 0;">✅ <?php echo esc_html( $feature ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ( isset( $existing_products[ $plan_type ] ) ) : ?>
                            <p style="margin-top: 15px;">
                                <a href="<?php echo esc_url( get_edit_post_link( $existing_products[ $plan_type ]->ID ) ); ?>" class="button">
                                    <?php esc_html_e( 'Edit Product', 'writgocms' ); ?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( $woocommerce_active && empty( $existing_products ) ) : ?>
                    <p style="margin-top: 20px;">
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=writgocms-products&action=create_products' ), 'writgoai_create_products' ) ); ?>" class="button button-primary button-hero">
                            <?php esc_html_e( 'Create Subscription Products', 'writgocms' ); ?>
                        </a>
                    </p>
                    <?php elseif ( ! $woocommerce_active ) : ?>
                    <div class="notice notice-warning" style="margin-top: 20px;">
                        <p><?php esc_html_e( 'WooCommerce is required to create subscription products. Please install and activate WooCommerce first.', 'writgocms' ); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Credit Costs -->
                <div class="planner-card">
                    <h2><?php esc_html_e( 'Credit Costs per Action', 'writgocms' ); ?></h2>
                    <table class="widefat striped" style="max-width: 500px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Action', 'writgocms' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Credits', 'writgocms' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><?php esc_html_e( 'AI Rewrite (small)', 'writgocms' ); ?></td><td style="text-align: right;">10</td></tr>
                            <tr><td><?php esc_html_e( 'AI Rewrite (paragraph)', 'writgocms' ); ?></td><td style="text-align: right;">25</td></tr>
                            <tr><td><?php esc_html_e( 'AI Rewrite (full)', 'writgocms' ); ?></td><td style="text-align: right;">50</td></tr>
                            <tr><td><?php esc_html_e( 'AI Image', 'writgocms' ); ?></td><td style="text-align: right;">100</td></tr>
                            <tr><td><?php esc_html_e( 'SEO Analysis', 'writgocms' ); ?></td><td style="text-align: right;">20</td></tr>
                            <tr><td><?php esc_html_e( 'Internal Links', 'writgocms' ); ?></td><td style="text-align: right;">5</td></tr>
                            <tr><td><?php esc_html_e( 'Keyword Research', 'writgocms' ); ?></td><td style="text-align: right;">15</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get all plans
     *
     * @return array
     */
    public function get_plans() {
        return $this->plans;
    }

    /**
     * Get all features
     *
     * @return array
     */
    public function get_features() {
        return $this->features;
    }
}

// Initialize if WooCommerce is available or if running as standalone.
add_action( 'plugins_loaded', function() {
    WritgoAI_WooCommerce_Integration::get_instance();
}, 20 );
