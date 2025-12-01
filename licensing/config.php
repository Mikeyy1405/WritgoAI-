<?php
/**
 * Licensing API Configuration
 *
 * This file contains configuration settings for the licensing API.
 * IMPORTANT: Replace placeholder values with actual production values.
 *
 * @package WritgoAI-Licensing
 */

// Prevent direct access.
if ( ! defined( 'LICENSING_API' ) ) {
    exit( 'Direct access denied.' );
}

/**
 * Database Configuration
 * Replace with your actual database credentials.
 */
define( 'DB_HOST', 'localhost' );
define( 'DB_NAME', 'your_database_name' );
define( 'DB_USER', 'your_database_user' );
define( 'DB_PASS', 'your_database_password' );
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Stripe Configuration
 * Get these from your Stripe Dashboard: https://dashboard.stripe.com/apikeys
 */
define( 'STRIPE_SECRET_KEY', 'sk_live_YOUR_STRIPE_SECRET_KEY' );
define( 'STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_STRIPE_WEBHOOK_SECRET' );

/**
 * Plan to Credits Mapping
 * Maps Stripe price IDs to monthly credit allocations.
 * Add your Stripe price IDs here.
 *
 * New Credit-Based System (all plans have full feature access):
 * - Starter: €29/month - 1,000 credits
 * - Pro: €79/month - 3,000 credits
 * - Enterprise: €199/month - 10,000 credits
 */
$plan_credits_map = array(
    // Starter Plan - €29/month - 1,000 credits
    'price_STARTER_PLAN_ID'     => 1000,
    // Pro Plan - €79/month - 3,000 credits
    'price_PRO_PLAN_ID'         => 3000,
    // Enterprise Plan - €199/month - 10,000 credits
    'price_ENTERPRISE_PLAN_ID'  => 10000,
);

/**
 * Plan Names for Display
 */
$plan_names = array(
    'price_STARTER_PLAN_ID'    => 'Starter',
    'price_PRO_PLAN_ID'        => 'Pro',
    'price_ENTERPRISE_PLAN_ID' => 'Enterprise',
);

/**
 * Plan Prices (in EUR cents for Stripe)
 */
$plan_prices = array(
    'price_STARTER_PLAN_ID'    => 2900,  // €29
    'price_PRO_PLAN_ID'        => 7900,  // €79
    'price_ENTERPRISE_PLAN_ID' => 19900, // €199
);

/**
 * Credit Costs per Action
 * All plans have 100% access to all features - only credit costs apply.
 */
$credit_costs = array(
    'ai_rewrite_small'     => 10,   // AI Rewrite (small)
    'ai_rewrite_paragraph' => 25,   // AI Rewrite (paragraph)
    'ai_rewrite_full'      => 50,   // AI Rewrite (full)
    'ai_image'             => 100,  // AI Image generation
    'seo_analysis'         => 20,   // SEO analysis
    'internal_links'       => 5,    // Internal links suggestion
    'keyword_research'     => 15,   // Keyword research
    'text_generation'      => 10,   // General text generation (legacy)
    'image_generation'     => 100,  // Image generation (legacy)
);

/**
 * Get credit cost for an action
 *
 * @param string $action Action type.
 * @return int Credit cost for the action.
 */
function get_credit_cost( $action ) {
    global $credit_costs;
    return isset( $credit_costs[ $action ] ) ? $credit_costs[ $action ] : 10;
}

/**
 * Get plan name from price ID
 *
 * @param string $price_id Stripe price ID.
 * @return string Plan name.
 */
function get_plan_name( $price_id ) {
    global $plan_names;
    return isset( $plan_names[ $price_id ] ) ? $plan_names[ $price_id ] : 'Unknown';
}

/**
 * Valid license statuses that allow usage
 */
define( 'VALID_LICENSE_STATUSES', array( 'active', 'trial' ) );

/**
 * Maximum credits that can be consumed in a single request
 */
define( 'MAX_CREDIT_CONSUMPTION', 1000 );

/**
 * Get PDO Database Connection
 *
 * @return PDO Database connection.
 * @throws PDOException If connection fails.
 */
function get_pdo_connection() {
    static $pdo = null;

    if ( null === $pdo ) {
        $dsn     = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        );
        $pdo = new PDO( $dsn, DB_USER, DB_PASS, $options );
    }

    return $pdo;
}

/**
 * Get credits for a plan
 *
 * @param string $price_id Stripe price ID.
 * @return int Credits for the plan, or 0 if not found.
 */
function get_plan_credits( $price_id ) {
    global $plan_credits_map;
    return isset( $plan_credits_map[ $price_id ] ) ? $plan_credits_map[ $price_id ] : 0;
}

/**
 * Require HTTPS for all API endpoints
 *
 * HTTPS is required by default unless APP_ENV is explicitly set to 'development'.
 *
 * @return void
 * @throws Exception If not using HTTPS in production.
 */
function require_https() {
    $is_https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] )
                || ( ! empty( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] )
                || ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] );

    // Require HTTPS unless explicitly in development mode.
    $is_development = 'development' === getenv( 'APP_ENV' );

    if ( ! $is_https && ! $is_development ) {
        http_response_code( 403 );
        header( 'Content-Type: application/json' );
        echo json_encode( array( 'error' => 'HTTPS required' ) );
        exit;
    }
}

/**
 * Send JSON response
 *
 * @param array $data    Response data.
 * @param int   $code    HTTP status code.
 * @return void
 */
function send_json_response( $data, $code = 200 ) {
    http_response_code( $code );
    header( 'Content-Type: application/json' );
    echo json_encode( $data );
    exit;
}

/**
 * Generate a unique license key
 *
 * @return string License key in format XXXX-XXXX-XXXX-XXXX.
 */
function generate_license_key() {
    $segments = array();
    for ( $i = 0; $i < 4; $i++ ) {
        $segments[] = strtoupper( substr( bin2hex( random_bytes( 2 ) ), 0, 4 ) );
    }
    return implode( '-', $segments );
}
