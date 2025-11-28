<?php
/**
 * AIML Provider Class
 *
 * Multi-provider support for text and image generation.
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
     * Text providers
     *
     * @var array
     */
    private $text_providers = array(
        'openai'  => array(
            'name'   => 'OpenAI',
            'models' => array(
                'gpt-4'              => 'GPT-4',
                'gpt-4-turbo'        => 'GPT-4 Turbo',
                'gpt-3.5-turbo'      => 'GPT-3.5 Turbo',
            ),
        ),
        'claude'  => array(
            'name'   => 'Anthropic Claude',
            'models' => array(
                'claude-3-opus-20240229'   => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307'  => 'Claude 3 Haiku',
            ),
        ),
        'gemini'  => array(
            'name'   => 'Google Gemini',
            'models' => array(
                'gemini-pro'   => 'Gemini Pro',
                'gemini-ultra' => 'Gemini Ultra',
            ),
        ),
        'mistral' => array(
            'name'   => 'Mistral AI',
            'models' => array(
                'mistral-large-latest'  => 'Mistral Large',
                'mistral-medium-latest' => 'Mistral Medium',
                'mistral-small-latest'  => 'Mistral Small',
            ),
        ),
    );

    /**
     * Image providers
     *
     * @var array
     */
    private $image_providers = array(
        'dalle'     => array(
            'name'   => 'DALL-E (OpenAI)',
            'models' => array(
                'dall-e-3' => 'DALL-E 3',
                'dall-e-2' => 'DALL-E 2',
            ),
        ),
        'stability' => array(
            'name'   => 'Stability AI',
            'models' => array(
                'stable-diffusion-xl-1024-v1-0' => 'Stable Diffusion XL',
                'stable-diffusion-v1-6'         => 'Stable Diffusion 1.6',
            ),
        ),
        'leonardo'  => array(
            'name'   => 'Leonardo.ai',
            'models' => array(
                'leonardo-diffusion-xl' => 'Leonardo Diffusion XL',
                'leonardo-vision-xl'    => 'Leonardo Vision XL',
            ),
        ),
        'replicate' => array(
            'name'   => 'Replicate',
            'models' => array(
                'flux-schnell' => 'Flux Schnell',
                'sdxl'         => 'SDXL',
            ),
        ),
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
     * Get text providers
     *
     * @return array
     */
    public function get_text_providers() {
        return $this->text_providers;
    }

    /**
     * Get image providers
     *
     * @return array
     */
    public function get_image_providers() {
        return $this->image_providers;
    }

    /**
     * Check rate limit
     *
     * @param string $provider Provider name.
     * @return bool
     */
    private function check_rate_limit( $provider ) {
        $limits = get_option( $this->rate_limit_option, array() );
        $now    = time();

        if ( ! isset( $limits[ $provider ] ) ) {
            return true;
        }

        $limit_data = $limits[ $provider ];
        $window     = 60; // 1 minute window
        $max_calls  = 10; // Max 10 calls per minute

        if ( $now - $limit_data['timestamp'] > $window ) {
            return true;
        }

        return $limit_data['count'] < $max_calls;
    }

    /**
     * Update rate limit
     *
     * @param string $provider Provider name.
     */
    private function update_rate_limit( $provider ) {
        $limits = get_option( $this->rate_limit_option, array() );
        $now    = time();
        $window = 60;

        if ( ! isset( $limits[ $provider ] ) || $now - $limits[ $provider ]['timestamp'] > $window ) {
            $limits[ $provider ] = array(
                'timestamp' => $now,
                'count'     => 1,
            );
        } else {
            $limits[ $provider ]['count']++;
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
     * Generate text with OpenAI
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_text_openai( $prompt, $model = 'gpt-3.5-turbo', $settings = array() ) {
        $api_key = get_option( 'writgocms_openai_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'OpenAI API key is not configured.', 'writgocms' ) );
        }

        $defaults = array(
            'temperature' => 0.7,
            'max_tokens'  => 1000,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
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

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }

        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            return array(
                'success' => true,
                'content' => $body['choices'][0]['message']['content'],
                'usage'   => isset( $body['usage'] ) ? $body['usage'] : array(),
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from OpenAI.', 'writgocms' ) );
    }

    /**
     * Generate text with Claude
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_text_claude( $prompt, $model = 'claude-3-sonnet-20240229', $settings = array() ) {
        $api_key = get_option( 'writgocms_claude_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Claude API key is not configured.', 'writgocms' ) );
        }

        $defaults = array(
            'temperature' => 0.7,
            'max_tokens'  => 1000,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => 60,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'      => $model,
                        'messages'   => array(
                            array(
                                'role'    => 'user',
                                'content' => $prompt,
                            ),
                        ),
                        'max_tokens' => (int) $settings['max_tokens'],
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }

        if ( isset( $body['content'][0]['text'] ) ) {
            return array(
                'success' => true,
                'content' => $body['content'][0]['text'],
                'usage'   => isset( $body['usage'] ) ? $body['usage'] : array(),
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from Claude.', 'writgocms' ) );
    }

    /**
     * Generate text with Gemini
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_text_gemini( $prompt, $model = 'gemini-pro', $settings = array() ) {
        $api_key = get_option( 'writgocms_gemini_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Gemini API key is not configured.', 'writgocms' ) );
        }

        $defaults = array(
            'temperature' => 0.7,
            'max_tokens'  => 1000,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key,
            array(
                'timeout' => 60,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'contents'         => array(
                            array(
                                'parts' => array(
                                    array(
                                        'text' => $prompt,
                                    ),
                                ),
                            ),
                        ),
                        'generationConfig' => array(
                            'temperature'     => (float) $settings['temperature'],
                            'maxOutputTokens' => (int) $settings['max_tokens'],
                        ),
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }

        if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return array(
                'success' => true,
                'content' => $body['candidates'][0]['content']['parts'][0]['text'],
                'usage'   => array(),
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from Gemini.', 'writgocms' ) );
    }

    /**
     * Generate text with Mistral
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_text_mistral( $prompt, $model = 'mistral-small-latest', $settings = array() ) {
        $api_key = get_option( 'writgocms_mistral_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Mistral API key is not configured.', 'writgocms' ) );
        }

        $defaults = array(
            'temperature' => 0.7,
            'max_tokens'  => 1000,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $response = wp_remote_post(
            'https://api.mistral.ai/v1/chat/completions',
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

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }

        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            return array(
                'success' => true,
                'content' => $body['choices'][0]['message']['content'],
                'usage'   => isset( $body['usage'] ) ? $body['usage'] : array(),
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from Mistral.', 'writgocms' ) );
    }

    /**
     * Generate image with DALL-E
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_image_dalle( $prompt, $model = 'dall-e-3', $settings = array() ) {
        $api_key = get_option( 'writgocms_openai_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'OpenAI API key is not configured.', 'writgocms' ) );
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
            'https://api.openai.com/v1/images/generations',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body_params ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }

        if ( isset( $body['data'][0]['url'] ) ) {
            $image_url = $body['data'][0]['url'];
            $saved     = $this->save_image_to_media_library( $image_url, $prompt );

            if ( is_wp_error( $saved ) ) {
                return array(
                    'success'    => true,
                    'image_url'  => $image_url,
                    'saved'      => false,
                    'save_error' => $saved->get_error_message(),
                );
            }

            return array(
                'success'       => true,
                'image_url'     => wp_get_attachment_url( $saved ),
                'attachment_id' => $saved,
                'saved'         => true,
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from DALL-E.', 'writgocms' ) );
    }

    /**
     * Generate image with Stability AI
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_image_stability( $prompt, $model = 'stable-diffusion-xl-1024-v1-0', $settings = array() ) {
        $api_key = get_option( 'writgocms_stability_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Stability AI API key is not configured.', 'writgocms' ) );
        }

        $defaults = array(
            'width'     => 1024,
            'height'    => 1024,
            'steps'     => 30,
            'cfg_scale' => 7,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $response = wp_remote_post(
            'https://api.stability.ai/v1/generation/' . $model . '/text-to-image',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'text_prompts' => array(
                            array(
                                'text'   => $prompt,
                                'weight' => 1,
                            ),
                        ),
                        'width'        => (int) $settings['width'],
                        'height'       => (int) $settings['height'],
                        'steps'        => (int) $settings['steps'],
                        'cfg_scale'    => (float) $settings['cfg_scale'],
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['message'] ) ) {
            return new WP_Error( 'api_error', $body['message'] );
        }

        if ( isset( $body['artifacts'][0]['base64'] ) ) {
            $image_data = base64_decode( $body['artifacts'][0]['base64'] );
            $saved      = $this->save_image_data_to_media_library( $image_data, $prompt );

            if ( is_wp_error( $saved ) ) {
                return $saved;
            }

            return array(
                'success'       => true,
                'image_url'     => wp_get_attachment_url( $saved ),
                'attachment_id' => $saved,
                'saved'         => true,
            );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from Stability AI.', 'writgocms' ) );
    }

    /**
     * Generate image with Leonardo.ai
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_image_leonardo( $prompt, $model = 'leonardo-diffusion-xl', $settings = array() ) {
        $api_key = get_option( 'writgocms_leonardo_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Leonardo.ai API key is not configured.', 'writgocms' ) );
        }

        $defaults = array(
            'width'  => 1024,
            'height' => 1024,
            'steps'  => 30,
        );
        $settings = wp_parse_args( $settings, $defaults );

        // Start generation
        $response = wp_remote_post(
            'https://cloud.leonardo.ai/api/rest/v1/generations',
            array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'prompt'      => $prompt,
                        'modelId'     => $model,
                        'width'       => (int) $settings['width'],
                        'height'      => (int) $settings['height'],
                        'num_images'  => 1,
                        'num_inference_steps' => (int) $settings['steps'],
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error'] );
        }

        if ( isset( $body['sdGenerationJob']['generationId'] ) ) {
            $generation_id = $body['sdGenerationJob']['generationId'];

            // Poll for results
            for ( $i = 0; $i < 30; $i++ ) {
                sleep( 2 );

                $status_response = wp_remote_get(
                    'https://cloud.leonardo.ai/api/rest/v1/generations/' . $generation_id,
                    array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $api_key,
                        ),
                    )
                );

                if ( is_wp_error( $status_response ) ) {
                    continue;
                }

                $status_body = json_decode( wp_remote_retrieve_body( $status_response ), true );
                if ( isset( $status_body['generations_by_pk']['generated_images'][0]['url'] ) ) {
                    $image_url = $status_body['generations_by_pk']['generated_images'][0]['url'];
                    $saved     = $this->save_image_to_media_library( $image_url, $prompt );

                    if ( is_wp_error( $saved ) ) {
                        return array(
                            'success'    => true,
                            'image_url'  => $image_url,
                            'saved'      => false,
                            'save_error' => $saved->get_error_message(),
                        );
                    }

                    return array(
                        'success'       => true,
                        'image_url'     => wp_get_attachment_url( $saved ),
                        'attachment_id' => $saved,
                        'saved'         => true,
                    );
                }
            }
        }

        return new WP_Error( 'timeout', __( 'Image generation timed out.', 'writgocms' ) );
    }

    /**
     * Generate image with Replicate
     *
     * @param string $prompt   The prompt.
     * @param string $model    The model to use.
     * @param array  $settings Additional settings.
     * @return array|WP_Error
     */
    public function generate_image_replicate( $prompt, $model = 'flux-schnell', $settings = array() ) {
        $api_key = get_option( 'writgocms_replicate_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Replicate API key is not configured.', 'writgocms' ) );
        }

        $model_versions = array(
            'flux-schnell' => 'black-forest-labs/flux-schnell',
            'sdxl'         => 'stability-ai/sdxl:39ed52f2a78e934b3ba6e2a89f5b1c712de7dfea535525255b1aa35c5565e08b',
        );

        $model_id = isset( $model_versions[ $model ] ) ? $model_versions[ $model ] : $model_versions['flux-schnell'];

        $defaults = array(
            'width'  => 1024,
            'height' => 1024,
        );
        $settings = wp_parse_args( $settings, $defaults );

        $input = array(
            'prompt' => $prompt,
        );

        if ( 'sdxl' === $model ) {
            $input['width']  = (int) $settings['width'];
            $input['height'] = (int) $settings['height'];
        }

        $response = wp_remote_post(
            'https://api.replicate.com/v1/predictions',
            array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Token ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'version' => $model_id,
                        'input'   => $input,
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error'] );
        }

        if ( isset( $body['urls']['get'] ) ) {
            $prediction_url = $body['urls']['get'];

            // Poll for results
            for ( $i = 0; $i < 60; $i++ ) {
                sleep( 2 );

                $status_response = wp_remote_get(
                    $prediction_url,
                    array(
                        'headers' => array(
                            'Authorization' => 'Token ' . $api_key,
                        ),
                    )
                );

                if ( is_wp_error( $status_response ) ) {
                    continue;
                }

                $status_body = json_decode( wp_remote_retrieve_body( $status_response ), true );
                if ( 'succeeded' === $status_body['status'] && ! empty( $status_body['output'] ) ) {
                    $image_url = is_array( $status_body['output'] ) ? $status_body['output'][0] : $status_body['output'];
                    $saved     = $this->save_image_to_media_library( $image_url, $prompt );

                    if ( is_wp_error( $saved ) ) {
                        return array(
                            'success'    => true,
                            'image_url'  => $image_url,
                            'saved'      => false,
                            'save_error' => $saved->get_error_message(),
                        );
                    }

                    return array(
                        'success'       => true,
                        'image_url'     => wp_get_attachment_url( $saved ),
                        'attachment_id' => $saved,
                        'saved'         => true,
                    );
                } elseif ( 'failed' === $status_body['status'] ) {
                    return new WP_Error( 'generation_failed', $status_body['error'] ?? __( 'Image generation failed.', 'writgocms' ) );
                }
            }
        }

        return new WP_Error( 'timeout', __( 'Image generation timed out.', 'writgocms' ) );
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
     * Save image data to media library
     *
     * @param string $image_data Base64 decoded image data.
     * @param string $prompt     Original prompt for title.
     * @return int|WP_Error Attachment ID or error.
     */
    private function save_image_data_to_media_library( $image_data, $prompt ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload_dir = wp_upload_dir();
        $filename   = 'ai-generated-' . wp_unique_id() . '.png';
        $file_path  = $upload_dir['path'] . '/' . $filename;

        // Save the image data to a file
        $saved = file_put_contents( $file_path, $image_data );

        if ( false === $saved ) {
            return new WP_Error( 'save_failed', __( 'Failed to save image file.', 'writgocms' ) );
        }

        $file_type = wp_check_filetype( $filename, null );

        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title'     => sanitize_text_field( substr( $prompt, 0, 100 ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $file_path, 0 );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        return $attachment_id;
    }

    /**
     * Generate text (main method)
     *
     * @param string $prompt   The prompt.
     * @param string $provider Provider name.
     * @param string $model    Model to use.
     * @param array  $settings Settings.
     * @return array|WP_Error
     */
    public function generate_text( $prompt, $provider = null, $model = null, $settings = array() ) {
        if ( null === $provider ) {
            $provider = get_option( 'writgocms_text_provider', 'openai' );
        }

        if ( ! $this->check_rate_limit( $provider ) ) {
            return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please try again later.', 'writgocms' ) );
        }

        // Check cache
        $cache_key = 'text_' . md5( $prompt . $provider . $model . serialize( $settings ) );
        $cached    = $this->get_cached( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        switch ( $provider ) {
            case 'openai':
                $result = $this->generate_text_openai( $prompt, $model ?? get_option( 'writgocms_openai_model', 'gpt-3.5-turbo' ), $settings );
                break;
            case 'claude':
                $result = $this->generate_text_claude( $prompt, $model ?? get_option( 'writgocms_claude_model', 'claude-3-sonnet-20240229' ), $settings );
                break;
            case 'gemini':
                $result = $this->generate_text_gemini( $prompt, $model ?? get_option( 'writgocms_gemini_model', 'gemini-pro' ), $settings );
                break;
            case 'mistral':
                $result = $this->generate_text_mistral( $prompt, $model ?? get_option( 'writgocms_mistral_model', 'mistral-small-latest' ), $settings );
                break;
            default:
                return new WP_Error( 'invalid_provider', __( 'Invalid text provider.', 'writgocms' ) );
        }

        $this->update_rate_limit( $provider );

        if ( ! is_wp_error( $result ) ) {
            $this->set_cached( $cache_key, $result );
            $this->track_usage( 'text', $provider );
        }

        return $result;
    }

    /**
     * Generate image (main method)
     *
     * @param string $prompt   The prompt.
     * @param string $provider Provider name.
     * @param string $model    Model to use.
     * @param array  $settings Settings.
     * @return array|WP_Error
     */
    public function generate_image( $prompt, $provider = null, $model = null, $settings = array() ) {
        if ( null === $provider ) {
            $provider = get_option( 'writgocms_image_provider', 'dalle' );
        }

        if ( ! $this->check_rate_limit( $provider ) ) {
            return new WP_Error( 'rate_limited', __( 'Rate limit exceeded. Please try again later.', 'writgocms' ) );
        }

        switch ( $provider ) {
            case 'dalle':
                $result = $this->generate_image_dalle( $prompt, $model ?? get_option( 'writgocms_dalle_model', 'dall-e-3' ), $settings );
                break;
            case 'stability':
                $result = $this->generate_image_stability( $prompt, $model ?? get_option( 'writgocms_stability_model', 'stable-diffusion-xl-1024-v1-0' ), $settings );
                break;
            case 'leonardo':
                $result = $this->generate_image_leonardo( $prompt, $model ?? get_option( 'writgocms_leonardo_model', 'leonardo-diffusion-xl' ), $settings );
                break;
            case 'replicate':
                $result = $this->generate_image_replicate( $prompt, $model ?? get_option( 'writgocms_replicate_model', 'flux-schnell' ), $settings );
                break;
            default:
                return new WP_Error( 'invalid_provider', __( 'Invalid image provider.', 'writgocms' ) );
        }

        $this->update_rate_limit( $provider );

        if ( ! is_wp_error( $result ) ) {
            $this->track_usage( 'image', $provider );
        }

        return $result;
    }

    /**
     * Track usage statistics
     *
     * @param string $type     Type (text/image).
     * @param string $provider Provider name.
     */
    private function track_usage( $type, $provider ) {
        $stats = get_option( 'writgocms_aiml_usage_stats', array() );
        $date  = gmdate( 'Y-m-d' );

        if ( ! isset( $stats[ $date ] ) ) {
            $stats[ $date ] = array();
        }

        if ( ! isset( $stats[ $date ][ $type ] ) ) {
            $stats[ $date ][ $type ] = array();
        }

        if ( ! isset( $stats[ $date ][ $type ][ $provider ] ) ) {
            $stats[ $date ][ $type ][ $provider ] = 0;
        }

        $stats[ $date ][ $type ][ $provider ]++;

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

        $prompt   = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : null;
        $model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgocms' ) ) );
        }

        $result = $this->generate_text( $prompt, $provider, $model );

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

        $prompt   = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : null;
        $model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgocms' ) ) );
        }

        $result = $this->generate_image( $prompt, $provider, $model );

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

        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
        $api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API key is required.', 'writgocms' ) ) );
        }

        $valid = $this->validate_api_key( $provider, $api_key );

        if ( is_wp_error( $valid ) ) {
            wp_send_json_error( array( 'message' => $valid->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'API key is valid!', 'writgocms' ) ) );
    }

    /**
     * Validate API key
     *
     * @param string $provider Provider name.
     * @param string $api_key  API key.
     * @return bool|WP_Error
     */
    private function validate_api_key( $provider, $api_key ) {
        switch ( $provider ) {
            case 'openai':
                $response = wp_remote_get(
                    'https://api.openai.com/v1/models',
                    array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $api_key,
                        ),
                    )
                );
                break;

            case 'claude':
                $response = wp_remote_post(
                    'https://api.anthropic.com/v1/messages',
                    array(
                        'headers' => array(
                            'x-api-key'         => $api_key,
                            'anthropic-version' => '2023-06-01',
                            'Content-Type'      => 'application/json',
                        ),
                        'body'    => wp_json_encode(
                            array(
                                'model'      => 'claude-3-haiku-20240307',
                                'max_tokens' => 10,
                                'messages'   => array(
                                    array(
                                        'role'    => 'user',
                                        'content' => 'Hi',
                                    ),
                                ),
                            )
                        ),
                    )
                );
                break;

            case 'gemini':
                $response = wp_remote_get(
                    'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key
                );
                break;

            case 'mistral':
                $response = wp_remote_get(
                    'https://api.mistral.ai/v1/models',
                    array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $api_key,
                        ),
                    )
                );
                break;

            case 'stability':
                $response = wp_remote_get(
                    'https://api.stability.ai/v1/user/account',
                    array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $api_key,
                        ),
                    )
                );
                break;

            case 'leonardo':
                $response = wp_remote_get(
                    'https://cloud.leonardo.ai/api/rest/v1/me',
                    array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $api_key,
                        ),
                    )
                );
                break;

            case 'replicate':
                $response = wp_remote_get(
                    'https://api.replicate.com/v1/account',
                    array(
                        'headers' => array(
                            'Authorization' => 'Token ' . $api_key,
                        ),
                    )
                );
                break;

            default:
                return new WP_Error( 'invalid_provider', __( 'Invalid provider.', 'writgocms' ) );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            return new WP_Error( 'invalid_key', __( 'Invalid API key.', 'writgocms' ) );
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

        $type     = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'text';
        $prompt   = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : null;

        if ( empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'writgocms' ) ) );
        }

        if ( 'text' === $type ) {
            $result = $this->generate_text( $prompt, $provider );
        } else {
            $result = $this->generate_image( $prompt, $provider );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }
}

// Initialize the provider
WritgoCMS_AIML_Provider::get_instance();
