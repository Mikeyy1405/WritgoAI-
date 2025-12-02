<?php
/**
 * AI Provider Class
 *
 * AI integration for text and image generation via WritgoAI API server.
 * The WritgoAI API server handles communication with various AI providers.
 *
 * @package WritgoAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoAI_AI_Provider
 */
class WritgoAI_AI_Provider {

    /**
     * Instance
     *
     * @var WritgoAI_AI_Provider
     */
    private static $instance = null;

    /**
     * AI API Base URL
     *
     * @var string
     */
    private $api_base_url = 'https://api.aimlapi.com/v1';

    /**
     * Available text models via AI API
     *
     * @var array
     */
    private $text_models = array(
        // OpenAI GPT Models
        'gpt-5.1'                      => 'GPT-5.1 (Latest)',
        'gpt-4.5-turbo'                => 'GPT-4.5 Turbo',
        'gpt-4o'                       => 'GPT-4o',
        'gpt-4o-mini'                  => 'GPT-4o Mini',
        'gpt-4-turbo'                  => 'GPT-4 Turbo',
        'gpt-4'                        => 'GPT-4',
        'gpt-3.5-turbo'                => 'GPT-3.5 Turbo',
        // Anthropic Claude Models
        'claude-4.5-sonnet'            => 'Claude 4.5 Sonnet (Latest)',
        'claude-4-opus'                => 'Claude 4 Opus',
        'claude-4-sonnet'              => 'Claude 4 Sonnet',
        'claude-3.5-sonnet'            => 'Claude 3.5 Sonnet',
        'claude-3-opus-20240229'       => 'Claude 3 Opus',
        'claude-3-sonnet-20240229'     => 'Claude 3 Sonnet',
        'claude-3-haiku-20240307'      => 'Claude 3 Haiku',
        // Google Gemini Models
        'gemini-3-pro'                 => 'Gemini 3 Pro (Latest)',
        'gemini-2.5-pro'               => 'Gemini 2.5 Pro',
        'gemini-2.0-flash'             => 'Gemini 2.0 Flash',
        'gemini-1.5-pro'               => 'Gemini 1.5 Pro',
        'gemini-1.5-flash'             => 'Gemini 1.5 Flash',
        // Mistral Models
        'mistral-large-latest'         => 'Mistral Large',
        'mistral-medium-latest'        => 'Mistral Medium',
        'mistral-small-latest'         => 'Mistral Small',
        // Meta Llama Models
        'llama-3.2-90b'                => 'Llama 3.2 90B',
        'llama-3.1-405b'               => 'Llama 3.1 405B',
        'llama-3.1-70b'                => 'Llama 3.1 70B',
    );

    /**
     * Available image models via AI API
     *
     * @var array
     */
    private $image_models = array(
        // OpenAI DALL-E Models
        'dall-e-3'                          => 'DALL-E 3 (Latest)',
        'dall-e-2'                          => 'DALL-E 2',
        // Stability AI Models
        'stable-diffusion-3'                => 'Stable Diffusion 3',
        'stable-diffusion-xl-1024-v1-0'     => 'Stable Diffusion XL',
        'stable-diffusion-xl-turbo'         => 'Stable Diffusion XL Turbo',
        // Flux Models
        'flux-1.1-pro'                      => 'Flux 1.1 Pro (Latest)',
        'flux-pro'                          => 'Flux Pro',
        'flux-schnell'                      => 'Flux Schnell',
        'flux-dev'                          => 'Flux Dev',
        // Midjourney
        'midjourney-v6'                     => 'Midjourney v6',
        // Ideogram
        'ideogram-v2'                       => 'Ideogram v2',
    );

    /**
     * Cache group
     *
     * @var string
     */
    private $cache_group = 'writgoai_ai';

    /**
     * Rate limit option name
     *
     * @var string
     */
    private $rate_limit_option = 'writgoai_ai_rate_limits';

    /**
     * Get instance
     *
     * @return WritgoAI_AI_Provider
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
        add_action( 'wp_ajax_writgoai_generate_text', array( $this, 'ajax_generate_text' ) );
        add_action( 'wp_ajax_writgoai_generate_image', array( $this, 'ajax_generate_image' ) );
        add_action( 'wp_ajax_writgoai_validate_api_key', array( $this, 'ajax_validate_api_key' ) );
        add_action( 'wp_ajax_writgoai_test_generation', array( $this, 'ajax_test_generation' ) );
    }

    /**
     * Get available text models
     *
     * @return array
     */
    public function get_text_models() {
        return $this->text_models;
    }

    /**
     * Get available image models
     *
     * @return array
     */
    public function get_image_models() {
        return $this->image_models;
    }

    /**
     * Get AI API key
     *
     * This method gets the API key from the license manager which is injected
     * from the WritgoAI API server based on the user's license.
     *
     * @return string
     */
    private function get_api_key() {
        // First, try to get API key from license manager.
        if ( class_exists( 'WritgoAI_License_Manager' ) ) {
            $license_manager = WritgoAI_License_Manager::get_instance();
            $injected_key = $license_manager->get_injected_api_key();

            if ( ! is_wp_error( $injected_key ) && ! empty( $injected_key ) ) {
                return $injected_key;
            }
        }

        // Fall back to stored API key.
        return get_option( 'writgoai_aiapi_key', '' );
    }

    /**
     * Check if license is valid before allowing AI operations
     *
     * @return bool|WP_Error True if valid, WP_Error if not.
     */
    private function check_license_valid() {
        if ( ! class_exists( 'WritgoAI_License_Manager' ) ) {
            return true; // License manager not loaded, allow operation.
        }

        $license_manager = WritgoAI_License_Manager::get_instance();

        if ( ! $license_manager->is_license_valid() ) {
            return new WP_Error(
                'license_invalid',
                __( 'Je licentie is niet actief. Activeer je licentie om WritgoAI te gebruiken.', 'writgoai' )
            );
        }

        return true;
    }

    /**
     * Get default text model
     *
     * @return string
     */
    public function get_default_text_model() {
        return get_option( 'writgoai_default_model', 'gpt-4o' );
    }

    /**
     * Get default image model
     *
     * @return string
     */
    public function get_default_image_model() {
        return get_option( 'writgoai_default_image_model', 'dall-e-3' );
    }

    /**
     * Check rate limit
     *
     * @return bool
     */
    private function check_rate_limit() {
        $limits = get_option( $this->rate_limit_option, array() );
        $now    = time();

        if ( ! isset( $limits['aimlapi'] ) ) {
            return true;
        }

        $limit_data = $limits['aimlapi'];
        $window     = 60; // 1 minute window
        $max_calls  = 10; // Max 10 calls per minute

        if ( $now - $limit_data['timestamp'] > $window ) {
            return true;
        }

        return $limit_data['count'] < $max_calls;
    }

    /**
     * Update rate limit
     */
    private function update_rate_limit() {
        $limits = get_option( $this->rate_limit_option, array() );
        $now    = time();
        $window = 60;

        if ( ! isset( $limits['aimlapi'] ) || $now - $limits['aimlapi']['timestamp'] > $window ) {
            $limits['aimlapi'] = array(
                'timestamp' => $now,
                'count'     => 1,
            );
        } else {
            $limits['aimlapi']['count']++;
        }

        update_option( $this->rate_limit_option, $limits );
    }

    /**
     * Get cached response
     *
     * @param string $cache_key Cache key.
     * @return mixed|false
     */
    private function get_cached( $cache_key ) {
        return wp_cache_get( $cache_key, $this->cache_group );
    }

    /**
     * Set cached response
     *
     * @param string $cache_key Cache key.
     * @param mixed  $data      Data to cache.
     * @param int    $expire    Expiration time in seconds.
     */
    private function set_cached( $cache_key, $data, $expire = 3600 ) {
        wp_cache_set( $cache_key, $data, $this->cache_group, $expire );
    }

    /**
     * Generate text using AI API chat/completions endpoint
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_text( $prompt, $model = null, $settings = array() ) {
        // Check license validity first.
        $license_check = $this->check_license_valid();
        if ( is_wp_error( $license_check ) ) {
            return $license_check;
        }

        // Check credits before generation.
        $credit_check = $this->check_credits_for_action( 'text_generation' );
        if ( is_wp_error( $credit_check ) ) {
            return $credit_check;
        }

        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'AI service is niet beschikbaar. Neem contact op met de beheerder.', 'writgoai' ) );
        }

        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please try again later.', 'writgoai' ) );
        }

        if ( null === $model ) {
            $model = $this->get_default_text_model();
        }

        // Check cache
        $cache_key = 'text_' . md5( $prompt . $model . serialize( $settings ) );
        $cached    = $this->get_cached( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        $defaults = array(
            'temperature' => 0.7,
            'max_tokens'  => 1000,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $response = wp_remote_post(
            $this->api_base_url . '/chat/completions',
            array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'       => $model,
                        'messages'    => array(
                            array(
                                'role'    => 'user',
                                'content' => $prompt,
                            ),
                        ),
                        'temperature' => (float) $settings['temperature'],
                        'max_tokens'  => (int) $settings['max_tokens'],
                    )
                ),
            )
        );

        $this->update_rate_limit();

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown API error.', 'writgoai' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            $result = array(
                'success' => true,
                'content' => $body['choices'][0]['message']['content'],
                'model'   => $model,
                'usage'   => isset( $body['usage'] ) ? $body['usage'] : array(),
            );

            // Deduct credits after successful generation.
            $this->deduct_credits_for_action( 'text_generation' );

            $this->set_cached( $cache_key, $result );
            $this->track_usage( 'text', $model );

            return $result;
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from AI API.', 'writgoai' ) );
    }

    /**
     * Generate image using AI API images/generations endpoint
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_image( $prompt, $model = null, $settings = array() ) {
        // Check license validity first.
        $license_check = $this->check_license_valid();
        if ( is_wp_error( $license_check ) ) {
            return $license_check;
        }

        // Check credits before generation.
        $credit_check = $this->check_credits_for_action( 'image_generation' );
        if ( is_wp_error( $credit_check ) ) {
            return $credit_check;
        }

        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'AI service is niet beschikbaar. Neem contact op met de beheerder.', 'writgoai' ) );
        }

        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please try again later.', 'writgoai' ) );
        }

        if ( null === $model ) {
            $model = $this->get_default_image_model();
        }

        $defaults = array(
            'size'    => '1024x1024',
            'quality' => 'standard',
            'n'       => 1,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $body_params = array(
            'model'  => $model,
            'prompt' => $prompt,
            'n'      => (int) $settings['n'],
            'size'   => $settings['size'],
        );

        if ( 'dall-e-3' === $model ) {
            $body_params['quality'] = $settings['quality'];
        }

        $response = wp_remote_post(
            $this->api_base_url . '/images/generations',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body_params ),
            )
        );

        $this->update_rate_limit();

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown API error.', 'writgoai' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['data'][0]['url'] ) ) {
            $image_url = $body['data'][0]['url'];
            $saved     = $this->save_image_to_media_library( $image_url, $prompt );

            // Deduct credits after successful generation.
            $this->deduct_credits_for_action( 'image_generation' );

            $this->track_usage( 'image', $model );

            if ( is_wp_error( $saved ) ) {
                return array(
                    'success'    => true,
                    'image_url'  => $image_url,
                    'model'      => $model,
                    'saved'      => false,
                    'save_error' => $saved->get_error_message(),
                );
            }

            return array(
                'success'       => true,
                'image_url'     => wp_get_attachment_url( $saved ),
                'attachment_id' => $saved,
                'model'         => $model,
                'saved'         => true,
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from AI API.', 'writgoai' ) );
    }

    /**
     * Save image to media library from URL
     *
     * @param string $image_url Image URL.
     * @param string $prompt    Original prompt for title.
     * @return int|WP_Error Attachment ID or error.
     */
    private function save_image_to_media_library( $image_url, $prompt ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $image_url );

        if ( is_wp_error( $tmp ) ) {
            return $tmp;
        }

        $file_array = array(
            'name'     => 'ai-generated-' . wp_unique_id() . '.png',
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload( $file_array, 0, sanitize_text_field( substr( $prompt, 0, 100 ) ) );

        if ( is_wp_error( $attachment_id ) ) {
            if ( file_exists( $tmp ) ) {
                unlink( $tmp );
            }
            return $attachment_id;
        }

        return $attachment_id;
    }

    /**
     * Track usage statistics
     *
     * @param string $type  Type (text/image).
     * @param string $model Model used.
     */
    private function track_usage( $type, $model ) {
        $stats = get_option( 'writgoai_ai_usage_stats', array() );
        $date  = gmdate( 'Y-m-d' );

        if ( ! isset( $stats[ $date ] ) ) {
            $stats[ $date ] = array();
        }

        if ( ! isset( $stats[ $date ][ $type ] ) ) {
            $stats[ $date ][ $type ] = array();
        }

        if ( ! isset( $stats[ $date ][ $type ][ $model ] ) ) {
            $stats[ $date ][ $type ][ $model ] = 0;
        }

        $stats[ $date ][ $type ][ $model ]++;

        // Keep only last 30 days
        $cutoff = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
        foreach ( array_keys( $stats ) as $stat_date ) {
            if ( $stat_date < $cutoff ) {
                unset( $stats[ $stat_date ] );
            }
        }

        update_option( 'writgoai_ai_usage_stats', $stats );
    }

    /**
     * AJAX handler for text generation
     */
    public function ajax_generate_text() {
        check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
        }

        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgoai' ) ) );
        }

        $result = $this->generate_text( $prompt, $model );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for image generation
     */
    public function ajax_generate_image() {
        check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
        }

        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgoai' ) ) );
        }

        $result = $this->generate_image( $prompt, $model );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for API key validation
     */
    public function ajax_validate_api_key() {
        check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API key is required.', 'writgoai' ) ) );
        }

        $valid = $this->validate_api_key( $api_key );

        if ( is_wp_error( $valid ) ) {
            wp_send_json_error( array( 'message' => $valid->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'AI API key is valid!', 'writgoai' ) ) );
    }

    /**
     * Validate AI API key
     *
     * @param string $api_key API key.
     * @return bool|WP_Error
     */
    private function validate_api_key( $api_key ) {
        $response = wp_remote_get(
            $this->api_base_url . '/models',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            return new WP_Error( 'invalid_key', __( 'Invalid AI API key.', 'writgoai' ) );
        }

        return true;
    }

    /**
     * AJAX handler for test generation
     */
    public function ajax_test_generation() {
        check_ajax_referer( 'writgoai_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgoai' ) ) );
        }

        $type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'text';
        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgoai' ) ) );
        }

        if ( 'text' === $type ) {
            $result = $this->generate_text( $prompt, $model );
        } else {
            $result = $this->generate_image( $prompt, $model );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * Check credits for an action
     *
     * @param string $action_type Action type.
     * @return bool|WP_Error True if sufficient, WP_Error if not.
     */
    private function check_credits_for_action( $action_type ) {
        // Skip if API client is not available.
        if ( ! class_exists( 'WritgoAI_API_Client' ) ) {
            return true;
        }

        $api_client = WritgoAI_API_Client::get_instance();
        $balance = $api_client->get_credit_balance();

        // If API is unavailable, allow operation (fallback).
        if ( is_wp_error( $balance ) ) {
            // Log the error but don't block the operation.
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'WritgoAI Credit Check Failed: ' . $balance->get_error_message() );
            }
            return true;
        }

        // Check if user has sufficient credits.
        $credits_remaining = isset( $balance['credits_remaining'] ) ? (int) $balance['credits_remaining'] : 0;
        
        // Get credit cost from credit manager if available.
        $credit_cost = 10; // Default cost.
        if ( class_exists( 'WritgoAI_Credit_Manager' ) ) {
            $credit_manager = WritgoAI_Credit_Manager::get_instance();
            $credit_cost = $credit_manager->get_credit_cost( $action_type );
        }

        if ( $credits_remaining < $credit_cost ) {
            return new WP_Error(
                'INSUFFICIENT_CREDITS',
                sprintf(
                    /* translators: 1: required credits, 2: remaining credits */
                    __( 'Onvoldoende credits. Nodig: %1$d, Beschikbaar: %2$d. Upgrade je abonnement om door te gaan.', 'writgoai' ),
                    $credit_cost,
                    $credits_remaining
                )
            );
        }

        return true;
    }

    /**
     * Deduct credits for an action
     *
     * @param string $action_type Action type.
     * @return void
     */
    private function deduct_credits_for_action( $action_type ) {
        // Skip if API client is not available.
        if ( ! class_exists( 'WritgoAI_API_Client' ) ) {
            return;
        }

        $api_client = WritgoAI_API_Client::get_instance();
        $result = $api_client->deduct_credits( $action_type );

        // Log any errors but don't block the operation.
        if ( is_wp_error( $result ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WritgoAI Credit Deduction Failed: ' . $result->get_error_message() );
        }
    }
}

// Initialize the provider
WritgoAI_AI_Provider::get_instance();
