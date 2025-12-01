<?php
/**
 * Stripe Webhook Handler
 *
 * Handles incoming webhook events from Stripe for subscription management.
 * Validates webhook signature and processes subscription events.
 *
 * Supported events:
 * - customer.subscription.created
 * - customer.subscription.updated
 * - customer.subscription.deleted
 * - invoice.payment_succeeded
 * - invoice.payment_failed
 *
 * @package WritgoAI-Licensing
 */

// Define API constant for config.
define( 'LICENSING_API', true );

// Load configuration.
require_once __DIR__ . '/../config.php';

// Require HTTPS.
require_https();

// Only accept POST requests.
if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
    send_json_response( array( 'error' => 'Method not allowed' ), 405 );
}

// Get the webhook payload.
$payload = file_get_contents( 'php://input' );
$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';

if ( empty( $payload ) || empty( $sig_header ) ) {
    send_json_response( array( 'error' => 'Missing payload or signature' ), 400 );
}

/**
 * Verify Stripe webhook signature
 *
 * @param string $payload    Raw webhook payload.
 * @param string $sig_header Stripe-Signature header.
 * @param string $secret     Webhook secret.
 * @return bool True if valid, false otherwise.
 */
function verify_stripe_signature( $payload, $sig_header, $secret ) {
    // Parse the signature header.
    $elements = explode( ',', $sig_header );
    $timestamp = null;
    $signatures = array();

    foreach ( $elements as $element ) {
        $parts = explode( '=', $element, 2 );
        if ( 2 === count( $parts ) ) {
            if ( 't' === $parts[0] ) {
                $timestamp = $parts[1];
            } elseif ( 'v1' === $parts[0] ) {
                $signatures[] = $parts[1];
            }
        }
    }

    if ( null === $timestamp || empty( $signatures ) ) {
        return false;
    }

    // Check timestamp to prevent replay attacks (5 minute tolerance).
    if ( abs( time() - (int) $timestamp ) > 300 ) {
        return false;
    }

    // Compute expected signature.
    $signed_payload = $timestamp . '.' . $payload;
    $expected_signature = hash_hmac( 'sha256', $signed_payload, $secret );

    // Compare signatures.
    foreach ( $signatures as $signature ) {
        if ( hash_equals( $expected_signature, $signature ) ) {
            return true;
        }
    }

    return false;
}

// Verify the webhook signature.
if ( ! verify_stripe_signature( $payload, $sig_header, STRIPE_WEBHOOK_SECRET ) ) {
    send_json_response( array( 'error' => 'Invalid signature' ), 400 );
}

// Decode the event.
$event = json_decode( $payload, true );

if ( null === $event || ! isset( $event['type'] ) ) {
    send_json_response( array( 'error' => 'Invalid payload' ), 400 );
}

try {
    $pdo = get_pdo_connection();

    switch ( $event['type'] ) {
        case 'customer.subscription.created':
            handle_subscription_created( $pdo, $event['data']['object'] );
            break;

        case 'customer.subscription.updated':
            handle_subscription_updated( $pdo, $event['data']['object'] );
            break;

        case 'customer.subscription.deleted':
            handle_subscription_deleted( $pdo, $event['data']['object'] );
            break;

        case 'invoice.payment_succeeded':
            handle_invoice_payment_succeeded( $pdo, $event['data']['object'] );
            break;

        case 'invoice.payment_failed':
            handle_invoice_payment_failed( $pdo, $event['data']['object'] );
            break;

        default:
            // Acknowledge unhandled events.
            send_json_response( array( 'received' => true, 'handled' => false ) );
    }

    send_json_response( array( 'received' => true, 'handled' => true ) );

} catch ( PDOException $e ) {
    error_log( 'Stripe webhook database error: ' . $e->getMessage() );
    send_json_response( array( 'error' => 'Database error' ), 500 );
} catch ( Exception $e ) {
    error_log( 'Stripe webhook error: ' . $e->getMessage() );
    send_json_response( array( 'error' => 'Processing error' ), 500 );
}

/**
 * Handle subscription created event
 *
 * Uses INSERT ... ON DUPLICATE KEY UPDATE to prevent race conditions
 * when multiple webhooks arrive simultaneously.
 *
 * @param PDO   $pdo          Database connection.
 * @param array $subscription Subscription object from Stripe.
 * @return void
 */
function handle_subscription_created( $pdo, $subscription ) {
    $customer_id = $subscription['customer'];
    $subscription_id = $subscription['id'];
    $price_id = isset( $subscription['items']['data'][0]['price']['id'] ) 
        ? $subscription['items']['data'][0]['price']['id'] 
        : null;
    $status = 'active' === $subscription['status'] ? 'active' : 'trial';

    // Generate a license key for new records.
    $license_key = generate_license_key();

    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle race conditions.
    $stmt = $pdo->prepare(
        'INSERT INTO licenses 
            (license_key, email, stripe_customer_id, stripe_subscription_id, stripe_price_id, status, activated_at, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), FROM_UNIXTIME(?))
        ON DUPLICATE KEY UPDATE
            stripe_subscription_id = VALUES(stripe_subscription_id),
            stripe_price_id = VALUES(stripe_price_id),
            status = VALUES(status),
            activated_at = NOW(),
            expires_at = VALUES(expires_at)'
    );
    $stmt->execute( array(
        $license_key,
        '', // Email will be updated from customer object if available.
        $customer_id,
        $subscription_id,
        $price_id,
        $status,
        $subscription['current_period_end'],
    ) );

    // Get the license ID (either inserted or existing).
    $license_id = $pdo->lastInsertId();
    if ( ! $license_id ) {
        // Record existed, get its ID.
        $stmt = $pdo->prepare( 'SELECT id FROM licenses WHERE stripe_customer_id = ?' );
        $stmt->execute( array( $customer_id ) );
        $existing = $stmt->fetch();
        $license_id = $existing ? $existing['id'] : 0;
    }

    // Add initial credits.
    add_credits_for_period( $pdo, $license_id, $price_id, $subscription['current_period_start'], $subscription['current_period_end'] );

    // Log activity.
    log_activity( $pdo, $license_id, 'created', null, array( 'subscription_id' => $subscription_id ) );
}

/**
 * Handle subscription updated event
 *
 * @param PDO   $pdo          Database connection.
 * @param array $subscription Subscription object from Stripe.
 * @return void
 */
function handle_subscription_updated( $pdo, $subscription ) {
    $subscription_id = $subscription['id'];
    $price_id = isset( $subscription['items']['data'][0]['price']['id'] ) 
        ? $subscription['items']['data'][0]['price']['id'] 
        : null;

    // Map Stripe status to our status.
    $status_map = array(
        'active'   => 'active',
        'trialing' => 'trial',
        'past_due' => 'suspended',
        'canceled' => 'cancelled',
        'unpaid'   => 'suspended',
    );
    $status = isset( $status_map[ $subscription['status'] ] ) 
        ? $status_map[ $subscription['status'] ] 
        : 'suspended';

    $stmt = $pdo->prepare(
        'UPDATE licenses SET 
            stripe_price_id = ?,
            status = ?,
            expires_at = FROM_UNIXTIME(?)
        WHERE stripe_subscription_id = ?'
    );
    $stmt->execute( array(
        $price_id,
        $status,
        $subscription['current_period_end'],
        $subscription_id,
    ) );

    // Get license ID for activity log.
    $stmt = $pdo->prepare( 'SELECT id FROM licenses WHERE stripe_subscription_id = ?' );
    $stmt->execute( array( $subscription_id ) );
    $license = $stmt->fetch();

    if ( $license ) {
        log_activity( $pdo, $license['id'], 'renewed', null, array( 'status' => $status ) );
    }
}

/**
 * Handle subscription deleted event
 *
 * @param PDO   $pdo          Database connection.
 * @param array $subscription Subscription object from Stripe.
 * @return void
 */
function handle_subscription_deleted( $pdo, $subscription ) {
    $subscription_id = $subscription['id'];

    $stmt = $pdo->prepare(
        'UPDATE licenses SET status = ?, expires_at = NOW() WHERE stripe_subscription_id = ?'
    );
    $stmt->execute( array( 'cancelled', $subscription_id ) );

    // Get license ID for activity log.
    $stmt = $pdo->prepare( 'SELECT id FROM licenses WHERE stripe_subscription_id = ?' );
    $stmt->execute( array( $subscription_id ) );
    $license = $stmt->fetch();

    if ( $license ) {
        log_activity( $pdo, $license['id'], 'cancelled', null, null );
    }
}

/**
 * Handle invoice payment succeeded event
 *
 * @param PDO   $pdo     Database connection.
 * @param array $invoice Invoice object from Stripe.
 * @return void
 */
function handle_invoice_payment_succeeded( $pdo, $invoice ) {
    $subscription_id = isset( $invoice['subscription'] ) ? $invoice['subscription'] : null;

    if ( ! $subscription_id ) {
        return;
    }

    // Get the license.
    $stmt = $pdo->prepare( 'SELECT id, stripe_price_id FROM licenses WHERE stripe_subscription_id = ?' );
    $stmt->execute( array( $subscription_id ) );
    $license = $stmt->fetch();

    if ( ! $license ) {
        return;
    }

    // Update license status to active.
    $stmt = $pdo->prepare( 'UPDATE licenses SET status = ? WHERE id = ?' );
    $stmt->execute( array( 'active', $license['id'] ) );

    // Get billing period from invoice.
    $period_start = isset( $invoice['period_start'] ) ? $invoice['period_start'] : time();
    $period_end = isset( $invoice['period_end'] ) ? $invoice['period_end'] : strtotime( '+1 month' );

    // Refresh credits for the new period.
    add_credits_for_period( $pdo, $license['id'], $license['stripe_price_id'], $period_start, $period_end );

    log_activity( $pdo, $license['id'], 'credits_refreshed', get_plan_credits( $license['stripe_price_id'] ), null );
}

/**
 * Handle invoice payment failed event
 *
 * @param PDO   $pdo     Database connection.
 * @param array $invoice Invoice object from Stripe.
 * @return void
 */
function handle_invoice_payment_failed( $pdo, $invoice ) {
    $subscription_id = isset( $invoice['subscription'] ) ? $invoice['subscription'] : null;

    if ( ! $subscription_id ) {
        return;
    }

    // Suspend the license.
    $stmt = $pdo->prepare(
        'UPDATE licenses SET status = ? WHERE stripe_subscription_id = ?'
    );
    $stmt->execute( array( 'suspended', $subscription_id ) );

    // Get license ID for activity log.
    $stmt = $pdo->prepare( 'SELECT id FROM licenses WHERE stripe_subscription_id = ?' );
    $stmt->execute( array( $subscription_id ) );
    $license = $stmt->fetch();

    if ( $license ) {
        log_activity( $pdo, $license['id'], 'suspended', null, array( 'reason' => 'payment_failed' ) );
    }
}

/**
 * Add credits for a billing period
 *
 * Only creates new credit records for new periods. Does not reset existing
 * credits_used to preserve consumed credits during period renewals.
 *
 * @param PDO    $pdo          Database connection.
 * @param int    $license_id   License ID.
 * @param string $price_id     Stripe price ID.
 * @param int    $period_start Period start timestamp.
 * @param int    $period_end   Period end timestamp.
 * @return void
 */
function add_credits_for_period( $pdo, $license_id, $price_id, $period_start, $period_end ) {
    $credits = get_plan_credits( $price_id );

    if ( $credits <= 0 ) {
        return;
    }

    $start_date = gmdate( 'Y-m-d', $period_start );
    $end_date = gmdate( 'Y-m-d', $period_end );

    // Insert new credits record for this period, or update credits_total only if period already exists.
    // Do not reset credits_used to preserve consumed credits.
    $stmt = $pdo->prepare(
        'INSERT INTO user_credits (license_id, credits_total, credits_used, period_start, period_end) 
        VALUES (?, ?, 0, ?, ?)
        ON DUPLICATE KEY UPDATE credits_total = VALUES(credits_total)'
    );
    $stmt->execute( array( $license_id, $credits, $start_date, $end_date ) );
}

/**
 * Log license activity
 *
 * @param PDO         $pdo           Database connection.
 * @param int         $license_id    License ID.
 * @param string      $activity_type Activity type.
 * @param int|null    $credits       Credits amount if applicable.
 * @param array|null  $metadata      Additional metadata.
 * @return void
 */
function log_activity( $pdo, $license_id, $activity_type, $credits = null, $metadata = null ) {
    $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;

    $stmt = $pdo->prepare(
        'INSERT INTO license_activity (license_id, activity_type, credits_amount, ip_address, user_agent, metadata) 
        VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute( array(
        $license_id,
        $activity_type,
        $credits,
        $ip_address,
        $user_agent,
        null !== $metadata ? json_encode( $metadata ) : null,
    ) );
}
