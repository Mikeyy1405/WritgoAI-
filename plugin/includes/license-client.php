<?php
/**
 * License Client for WordPress Plugin
 *
 * Provides functions to communicate with the WritgoAI Licensing API.
 * Uses WordPress HTTP API for making requests.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoAI_License_Client
 *
 * Client for interacting with the licensing API endpoints.
 */
class WritgoAI_License_Client {

    /**
     * Instance
     *
     * @var WritgoAI_License_Client
     */
    private static $instance = null;

    /**
     * API Base URL
     *
     * @var string
     */
    private $api_base_url;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * Get instance
     *
     * @return WritgoAI_License_Client
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
        // Allow filtering the API base URL.
        $this->api_base_url = apply_filters( 
            'writgoai_license_api_url', 
            'https://api.writgoai.com/v1' 
        );
    }

    /**
     * Set API base URL
     *
     * @param string $url API base URL.
     * @return void
     */
    public function set_api_base_url( $url ) {
        $this->api_base_url = esc_url_raw( $url );
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    public function get_api_base_url() {
        return $this->api_base_url;
    }

    /**
     * Get the stored license key
     *
     * @return string License key or empty string.
     */
    public function get_license_key() {
        return get_option( 'writgoai_license_key', '' );
    }

    /**
     * Validate a license key
     *
     * Calls the licensing API to validate a license key and retrieve
     * the license status, credits, and other information.
     *
     * @param string|null $license_key Optional license key. Uses stored key if not provided.
     * @return array|WP_Error Decoded JSON response or WP_Error on failure.
     */
    public function validate_license( $license_key = null ) {
        if ( null === $license_key ) {
            $license_key = $this->get_license_key();
        }

        if ( empty( $license_key ) ) {
            return new WP_Error( 
                'no_license_key', 
                __( 'No license key provided.', 'writgoai' ) 
            );
        }

        $response = $this->make_request( 
            '/license/validate', 
            array(
                'license_key' => sanitize_text_field( $license_key ),
                'site_url'    => home_url(),
            )
        );

        return $response;
    }

    /**
     * Consume credits for a license
     *
     * Atomically consumes credits for the specified action and logs the activity.
     *
     * @param int         $amount      Number of credits to consume. Default 1.
     * @param string      $action      Action type for logging (e.g., 'text_generation', 'image_generation').
     * @param string|null $license_key Optional license key. Uses stored key if not provided.
     * @return array|WP_Error Decoded JSON response or WP_Error on failure.
     */
    public function consume_credits( $amount = 1, $action = 'ai_generation', $license_key = null ) {
        if ( null === $license_key ) {
            $license_key = $this->get_license_key();
        }

        if ( empty( $license_key ) ) {
            return new WP_Error( 
                'no_license_key', 
                __( 'No license key provided.', 'writgoai' ) 
            );
        }

        // Validate amount.
        $amount = absint( $amount );
        if ( $amount < 1 ) {
            $amount = 1;
        }

        $response = $this->make_request( 
            '/license/consume', 
            array(
                'license_key' => sanitize_text_field( $license_key ),
                'amount'      => $amount,
                'action'      => sanitize_key( $action ),
            )
        );

        return $response;
    }

    /**
     * Check if license has sufficient credits
     *
     * @param int         $required_credits Number of credits required.
     * @param string|null $license_key      Optional license key.
     * @return bool|WP_Error True if sufficient credits, false otherwise, WP_Error on failure.
     */
    public function has_sufficient_credits( $required_credits = 1, $license_key = null ) {
        $validation = $this->validate_license( $license_key );

        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        if ( ! isset( $validation['valid'] ) || ! $validation['valid'] ) {
            return false;
        }

        $credits_remaining = isset( $validation['credits_remaining'] ) 
            ? (int) $validation['credits_remaining'] 
            : 0;

        return $credits_remaining >= $required_credits;
    }

    /**
     * Get remaining credits
     *
     * @param string|null $license_key Optional license key.
     * @return int|WP_Error Number of remaining credits or WP_Error on failure.
     */
    public function get_remaining_credits( $license_key = null ) {
        $validation = $this->validate_license( $license_key );

        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        return isset( $validation['credits_remaining'] ) 
            ? (int) $validation['credits_remaining'] 
            : 0;
    }

    /**
     * Get license status
     *
     * @param string|null $license_key Optional license key.
     * @return string|WP_Error License status or WP_Error on failure.
     */
    public function get_license_status( $license_key = null ) {
        $validation = $this->validate_license( $license_key );

        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        return isset( $validation['status'] ) 
            ? sanitize_text_field( $validation['status'] ) 
            : 'unknown';
    }

    /**
     * Make an API request
     *
     * @param string $endpoint API endpoint (relative to base URL).
     * @param array  $body     Request body.
     * @return array|WP_Error Decoded JSON response or WP_Error on failure.
     */
    private function make_request( $endpoint, $body = array() ) {
        $url = trailingslashit( $this->api_base_url ) . ltrim( $endpoint, '/' );

        $args = array(
            'method'      => 'POST',
            'timeout'     => $this->timeout,
            'httpversion' => '1.1',
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'WritgoAI/' . ( defined( 'WRITGOAI_VERSION' ) ? WRITGOAI_VERSION : '1.0.0' ),
            ),
            'body'        => wp_json_encode( $body ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // Decode JSON response.
        $decoded = json_decode( $response_body, true );

        if ( null === $decoded ) {
            return new WP_Error( 
                'json_decode_error', 
                __( 'Failed to parse API response.', 'writgoai' ),
                array(
                    'response_code' => $response_code,
                    'response_body' => $response_body,
                )
            );
        }

        // Check for API errors.
        if ( $response_code >= 400 ) {
            $error_message = isset( $decoded['error'] ) 
                ? $decoded['error'] 
                : __( 'API request failed.', 'writgoai' );

            return new WP_Error( 
                'api_error', 
                $error_message,
                array(
                    'response_code' => $response_code,
                    'response'      => $decoded,
                )
            );
        }

        return $decoded;
    }

    /**
     * Set request timeout
     *
     * @param int $timeout Timeout in seconds. Minimum 5 seconds.
     * @return void
     */
    public function set_timeout( $timeout ) {
        $timeout = absint( $timeout );
        // Ensure minimum timeout of 5 seconds.
        $this->timeout = max( 5, $timeout );
    }
}

/**
 * Get the license client instance
 *
 * Helper function to get the license client singleton.
 *
 * @return WritgoAI_License_Client
 */
function writgoai_license_client() {
    return WritgoAI_License_Client::get_instance();
}

/**
 * Validate license helper function
 *
 * @param string|null $license_key Optional license key.
 * @return array|WP_Error
 */
function writgoai_validate_license( $license_key = null ) {
    return writgoai_license_client()->validate_license( $license_key );
}

/**
 * Consume credits helper function
 *
 * @param int    $amount Number of credits to consume.
 * @param string $action Action type for logging.
 * @return array|WP_Error
 */
function writgoai_consume_credits( $amount = 1, $action = 'ai_generation' ) {
    return writgoai_license_client()->consume_credits( $amount, $action );
}

/**
 * Check if license has sufficient credits helper function
 *
 * @param int $required_credits Number of credits required.
 * @return bool|WP_Error
 */
function writgoai_has_credits( $required_credits = 1 ) {
    return writgoai_license_client()->has_sufficient_credits( $required_credits );
}
