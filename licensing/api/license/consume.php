<?php
/**
 * Credit Consumption API Endpoint
 *
 * Atomically consumes credits for a license and logs the activity.
 * Uses SELECT ... FOR UPDATE to prevent race conditions.
 *
 * Request (POST):
 * {
 *   "license_key": "XXXX-XXXX-XXXX-XXXX",
 *   "amount": 1,
 *   "action": "text_generation" (optional, for logging)
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "credits_remaining": 449,
 *   "credits_consumed": 1
 * }
 *
 * Error Response:
 * {
 *   "success": false,
 *   "error": "Insufficient credits",
 *   "credits_remaining": 0
 * }
 *
 * @package WritgoAI-Licensing
 */

// Define API constant for config.
define( 'LICENSING_API', true );

// Load configuration.
require_once __DIR__ . '/../../config.php';

// Require HTTPS.
require_https();

// Only accept POST requests.
if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
    send_json_response( array( 'error' => 'Method not allowed' ), 405 );
}

// Get request body.
$input = json_decode( file_get_contents( 'php://input' ), true );

if ( null === $input ) {
    send_json_response( array( 'error' => 'Invalid JSON payload' ), 400 );
}

// Validate required fields.
$license_key = isset( $input['license_key'] ) ? trim( $input['license_key'] ) : '';
$amount = isset( $input['amount'] ) ? (int) $input['amount'] : 1;
$action = isset( $input['action'] ) ? trim( $input['action'] ) : 'unknown';

if ( empty( $license_key ) ) {
    send_json_response( array( 
        'success' => false,
        'error'   => 'License key is required' 
    ), 400 );
}

// Sanitize license key.
if ( ! preg_match( '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $license_key ) ) {
    send_json_response( array( 
        'success' => false,
        'error'   => 'Invalid license key format' 
    ), 400 );
}

$license_key = strtoupper( $license_key );

// Validate amount.
if ( $amount < 1 || $amount > 1000 ) {
    send_json_response( array( 
        'success' => false,
        'error'   => 'Invalid amount. Must be between 1 and 1000.' 
    ), 400 );
}

// Sanitize action.
$action = preg_replace( '/[^a-zA-Z0-9_-]/', '', $action );
$action = substr( $action, 0, 64 );

try {
    $pdo = get_pdo_connection();

    // Start transaction.
    $pdo->beginTransaction();

    // Get license with row lock.
    $license_stmt = $pdo->prepare(
        'SELECT id, status, expires_at FROM licenses WHERE license_key = ? FOR UPDATE'
    );
    $license_stmt->execute( array( $license_key ) );
    $license = $license_stmt->fetch();

    if ( ! $license ) {
        $pdo->rollBack();
        send_json_response( array(
            'success' => false,
            'error'   => 'License not found',
        ), 404 );
    }

    // Check if license is valid.
    $valid_statuses = array( 'active', 'trial' );
    if ( ! in_array( $license['status'], $valid_statuses, true ) ) {
        $pdo->rollBack();
        send_json_response( array(
            'success' => false,
            'error'   => 'License is not active',
            'status'  => $license['status'],
        ), 403 );
    }

    // Check expiration.
    if ( null !== $license['expires_at'] && strtotime( $license['expires_at'] ) < time() ) {
        $pdo->rollBack();
        send_json_response( array(
            'success' => false,
            'error'   => 'License has expired',
        ), 403 );
    }

    // Get current credits with row lock.
    $credits_stmt = $pdo->prepare(
        'SELECT id, credits_total, credits_used, credits_remaining, period_start, period_end
        FROM user_credits
        WHERE license_id = ? AND period_start <= CURDATE() AND period_end >= CURDATE()
        ORDER BY period_start DESC
        LIMIT 1
        FOR UPDATE'
    );
    $credits_stmt->execute( array( $license['id'] ) );
    $credits = $credits_stmt->fetch();

    if ( ! $credits ) {
        $pdo->rollBack();
        send_json_response( array(
            'success'           => false,
            'error'             => 'No credits available for current period',
            'credits_remaining' => 0,
        ), 403 );
    }

    $credits_remaining = (int) $credits['credits_remaining'];

    // Check if enough credits are available.
    if ( $credits_remaining < $amount ) {
        $pdo->rollBack();
        send_json_response( array(
            'success'           => false,
            'error'             => 'Insufficient credits',
            'credits_remaining' => $credits_remaining,
            'credits_requested' => $amount,
        ), 403 );
    }

    // Consume credits atomically.
    $new_used = (int) $credits['credits_used'] + $amount;
    $update_stmt = $pdo->prepare(
        'UPDATE user_credits SET credits_used = ? WHERE id = ?'
    );
    $update_stmt->execute( array( $new_used, $credits['id'] ) );

    // Log the activity.
    $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;

    $activity_stmt = $pdo->prepare(
        'INSERT INTO license_activity (license_id, activity_type, credits_amount, ip_address, user_agent, metadata)
        VALUES (?, ?, ?, ?, ?, ?)'
    );
    $activity_stmt->execute( array(
        $license['id'],
        'credits_consumed',
        $amount,
        $ip_address,
        $user_agent,
        json_encode( array( 'action' => $action ) ),
    ) );

    // Commit transaction.
    $pdo->commit();

    // Calculate new remaining credits.
    $new_remaining = $credits_remaining - $amount;

    send_json_response( array(
        'success'           => true,
        'credits_remaining' => $new_remaining,
        'credits_consumed'  => $amount,
    ) );

} catch ( PDOException $e ) {
    if ( $pdo->inTransaction() ) {
        $pdo->rollBack();
    }
    error_log( 'Credit consumption database error: ' . $e->getMessage() );
    send_json_response( array( 
        'success' => false,
        'error'   => 'Database error' 
    ), 500 );
} catch ( Exception $e ) {
    if ( isset( $pdo ) && $pdo->inTransaction() ) {
        $pdo->rollBack();
    }
    error_log( 'Credit consumption error: ' . $e->getMessage() );
    send_json_response( array( 
        'success' => false,
        'error'   => 'Server error' 
    ), 500 );
}
