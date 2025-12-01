<?php
/**
 * AIML Provider Class
 *
 * AIMLAPI integration for text and image generation.
 * Uses https://api.aimlapi.com/v1 as the unified API endpoint.
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WritgoCMS_AIML_Provider
 */
class WritgoCMS_AIML_Provider {

    /**
     * Instance
     *
     * @var WritgoCMS_AIML_Provider
     */
    private static $instance = null;

    /**
     * AIMLAPI Base URL
     *
     * @var string
     */
    private $api_base_url = 'https://api.aimlapi.com/v1';

    /**
     * Available text models via AIMLAPI
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
     * Available image models via AIMLAPI
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
    private $cache_group = 'writgocms_aiml';

    /**
     * Rate limit option name
     *
     * @var string
     */
    private $rate_limit_option = 'writgocms_aiml_rate_limits';

    /**
     * Get instance
     *
     * @return WritgoCMS_AIML_Provider
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
        add_action( 'wp_ajax_writgocms_generate_text', array( $this, 'ajax_generate_text' ) );
        add_action( 'wp_ajax_writgocms_generate_image', array( $this, 'ajax_generate_image' ) );
        add_action( 'wp_ajax_writgocms_validate_api_key', array( $this, 'ajax_validate_api_key' ) );
        add_action( 'wp_ajax_writgocms_test_generation', array( $this, 'ajax_test_generation' ) );
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
     * Get AIMLAPI key
     *
     * This method first tries to get the API key from the license manager
     * (injected from the licensing server), then falls back to the stored option.
     *
     * @return string
     */
    private function get_api_key() {
        // First, try to get API key from license manager.
        if ( class_exists( 'WritgoCMS_License_Manager' ) ) {
            $license_manager = WritgoCMS_License_Manager::get_instance();
            $injected_key = $license_manager->get_injected_api_key();

            if ( ! is_wp_error( $injected_key ) && ! empty( $injected_key ) ) {
                return $injected_key;
            }
        }

        // Fall back to stored API key.
        return get_option( 'writgocms_aimlapi_key', '' );
    }

    /**
     * Check if license is valid before allowing AI operations
     *
     * @return bool|WP_Error True if valid, WP_Error if not.
     */
    private function check_license_valid() {
        if ( ! class_exists( 'WritgoCMS_License_Manager' ) ) {
            return true; // License manager not loaded, allow operation.
        }

        $license_manager = WritgoCMS_License_Manager::get_instance();

        if ( ! $license_manager->is_license_valid() ) {
            return new WP_Error(
                'license_invalid',
                __( 'Je licentie is niet actief. Activeer je licentie om WritgoAI te gebruiken.', 'writgocms' )
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
        return get_option( 'writgocms_default_model', 'gpt-4o' );
    }

    /**
     * Get default image model
     *
     * @return string
     */
    public function get_default_image_model() {
        return get_option( 'writgocms_default_image_model', 'dall-e-3' );
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
     * Generate text using AIMLAPI chat/completions endpoint
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

        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'AIMLAPI key is not configured. Please go to Settings > WritgoCMS AIML to configure your API key.', 'writgocms' ) );
        }

        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please try again later.', 'writgocms' ) );
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
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown API error.', 'writgocms' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            $result = array(
                'success' => true,
                'content' => $body['choices'][0]['message']['content'],
                'model'   => $model,
                'usage'   => isset( $body['usage'] ) ? $body['usage'] : array(),
            );

            $this->set_cached( $cache_key, $result );
            $this->track_usage( 'text', $model );

            return $result;
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from AIMLAPI.', 'writgocms' ) );
    }

    /**
     * Generate image using AIMLAPI images/generations endpoint
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

        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'AIMLAPI key is not configured. Please go to Settings > WritgoCMS AIML to configure your API key.', 'writgocms' ) );
        }

        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please try again later.', 'writgocms' ) );
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
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown API error.', 'writgocms' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['data'][0]['url'] ) ) {
            $image_url = $body['data'][0]['url'];
            $saved     = $this->save_image_to_media_library( $image_url, $prompt );

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

        return new WP_Error( 'invalid_response', __( 'Invalid response from AIMLAPI.', 'writgocms' ) );
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
        $stats = get_option( 'writgocms_aiml_usage_stats', array() );
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

        update_option( 'writgocms_aiml_usage_stats', $stats );
    }

    /**
     * AJAX handler for text generation
     */
    public function ajax_generate_text() {
        check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgocms' ) ) );
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
        check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgocms' ) ) );
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
        check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API key is required.', 'writgocms' ) ) );
        }

        $valid = $this->validate_api_key( $api_key );

        if ( is_wp_error( $valid ) ) {
            wp_send_json_error( array( 'message' => $valid->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'AIMLAPI key is valid!', 'writgocms' ) ) );
    }

    /**
     * Validate AIMLAPI key
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
            return new WP_Error( 'invalid_key', __( 'Invalid AIMLAPI key.', 'writgocms' ) );
        }

        return true;
    }

    /**
     * AJAX handler for test generation
     */
    public function ajax_test_generation() {
        check_ajax_referer( 'writgocms_aiml_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'writgocms' ) ) );
        }

        $type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'text';
        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgocms' ) ) );
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
}

// Initialize the provider
WritgoCMS_AIML_Provider::get_instance();
