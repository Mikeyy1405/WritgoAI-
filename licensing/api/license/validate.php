<?php
/**
 * License Validation API Endpoint
 *
 * Validates a license key and returns license status, credits, and site URL.
 *
 * Request (POST):
 * {
 *   "license_key": "XXXX-XXXX-XXXX-XXXX",
 *   "site_url": "https://example.com" (optional, for site verification)
 * }
 *
 * Response:
 * {
 *   "valid": true,
 *   "status": "active",
 *   "credits_remaining": 450,
 *   "credits_total": 500,
 *   "site_url": "https://example.com",
 *   "plan_name": "Professional",
 *   "expires_at": "2025-12-31 23:59:59"
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

if ( empty( $license_key ) ) {
    send_json_response( array( 'error' => 'License key is required' ), 400 );
}

// Sanitize license key (should be in format XXXX-XXXX-XXXX-XXXX).
if ( ! preg_match( '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $license_key ) ) {
    send_json_response( array( 
        'valid' => false,
        'error' => 'Invalid license key format' 
    ), 400 );
}

$license_key = strtoupper( $license_key );
$site_url = isset( $input['site_url'] ) ? filter_var( $input['site_url'], FILTER_SANITIZE_URL ) : null;

try {
    $pdo = get_pdo_connection();

    // Get license details.
    $stmt = $pdo->prepare(
        'SELECT 
            l.id,
            l.license_key,
            l.email,
            l.site_url,
            l.status,
            l.plan_name,
            l.expires_at,
            l.stripe_price_id
        FROM licenses l
        WHERE l.license_key = ?'
    );
    $stmt->execute( array( $license_key ) );
    $license = $stmt->fetch();

    if ( ! $license ) {
        send_json_response( array(
            'valid' => false,
            'error' => 'License not found',
        ), 404 );
    }

    // Check if license is valid.
    $valid_statuses = array( 'active', 'trial' );
    $is_valid = in_array( $license['status'], $valid_statuses, true );

    // Check expiration.
    if ( $is_valid && null !== $license['expires_at'] ) {
        $expires_at = strtotime( $license['expires_at'] );
        if ( $expires_at < time() ) {
            $is_valid = false;
            // Update status to expired.
            $update_stmt = $pdo->prepare( 'UPDATE licenses SET status = ? WHERE id = ?' );
            $update_stmt->execute( array( 'expired', $license['id'] ) );
            $license['status'] = 'expired';
        }
    }

    // Get current credits.
    $credits_stmt = $pdo->prepare(
        'SELECT credits_total, credits_used, credits_remaining, period_start, period_end
        FROM user_credits
        WHERE license_id = ? AND period_start <= CURDATE() AND period_end >= CURDATE()
        ORDER BY period_start DESC
        LIMIT 1'
    );
    $credits_stmt->execute( array( $license['id'] ) );
    $credits = $credits_stmt->fetch();

    $credits_remaining = $credits ? (int) $credits['credits_remaining'] : 0;
    $credits_total = $credits ? (int) $credits['credits_total'] : 0;

    // Update site URL if provided and different.
    if ( $site_url && $site_url !== $license['site_url'] ) {
        $update_site_stmt = $pdo->prepare( 'UPDATE licenses SET site_url = ? WHERE id = ?' );
        $update_site_stmt->execute( array( $site_url, $license['id'] ) );

        // Log activity.
        log_validation_activity( $pdo, $license['id'], $site_url );
    }

    // Prepare response.
    $response = array(
        'valid'             => $is_valid,
        'status'            => $license['status'],
        'credits_remaining' => $credits_remaining,
        'credits_total'     => $credits_total,
        'site_url'          => $license['site_url'] ?: $site_url,
        'plan_name'         => $license['plan_name'],
        'expires_at'        => $license['expires_at'],
    );

    send_json_response( $response );

} catch ( PDOException $e ) {
    error_log( 'License validation database error: ' . $e->getMessage() );
    send_json_response( array( 'error' => 'Database error' ), 500 );
} catch ( Exception $e ) {
    error_log( 'License validation error: ' . $e->getMessage() );
    send_json_response( array( 'error' => 'Server error' ), 500 );
}

/**
 * Log validation activity
 *
 * @param PDO    $pdo        Database connection.
 * @param int    $license_id License ID.
 * @param string $site_url   Site URL.
 * @return void
 */
function log_validation_activity( $pdo, $license_id, $site_url ) {
    $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;

    $stmt = $pdo->prepare(
        'INSERT INTO license_activity (license_id, activity_type, ip_address, user_agent, metadata)
        VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute( array(
        $license_id,
        'validation',
        $ip_address,
        $user_agent,
        json_encode( array( 'site_url' => $site_url ) ),
    ) );
}
