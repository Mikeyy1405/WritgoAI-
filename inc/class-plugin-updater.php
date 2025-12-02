<?php
/**
 * Plugin Updater
 * 
 * Handles automatic plugin updates from GitHub releases.
 *
 * @package WritgoAI
 */

if (!defined('ABSPATH')) {
    exit;
}

class WritgoAI_Plugin_Updater {
    
    /**
     * GitHub repository
     */
    private $github_repo = 'Mikeyy1405/WritgoAI-plugin';
    
    /**
     * Plugin slug
     */
    private $plugin_slug;
    
    /**
     * Plugin file path
     */
    private $plugin_file;
    
    /**
     * Current plugin version
     */
    private $current_version;
    
    /**
     * GitHub API URL
     */
    private $api_url;
    
    /**
     * Cache key for update check
     */
    private $cache_key = 'writgoai_update_check';
    
    /**
     * Cache expiration (12 hours)
     */
    private $cache_expiration = 43200;
    
    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file path.
     * @param string $current_version Current plugin version.
     */
    public function __construct($plugin_file, $current_version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->current_version = $current_version;
        $this->api_url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        
        // Add action link for manual update check
        add_filter('plugin_action_links_' . $this->plugin_slug, [$this, 'add_action_links']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX handler for manual update check
        add_action('wp_ajax_writgoai_check_updates', [$this, 'ajax_check_updates']);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugins page
        if ($hook !== 'plugins.php') {
            return;
        }
        
        wp_enqueue_script(
            'writgocms-plugin-updater',
            plugin_dir_url($this->plugin_file) . 'assets/js/plugin-updater.js',
            ['jquery'],
            $this->current_version,
            true
        );
        
        wp_localize_script(
            'writgocms-plugin-updater',
            'writgocmsUpdater',
            [
                'nonce' => wp_create_nonce('writgoai_update_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
            ]
        );
    }

    /**
     * Check GitHub for updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $remote_data = $this->get_remote_data();
        
        if ($remote_data && isset($remote_data['version'])) {
            if (version_compare($this->current_version, $remote_data['version'], '<')) {
                $transient->response[$this->plugin_slug] = (object) [
                    'slug'        => dirname($this->plugin_slug),
                    'plugin'      => $this->plugin_slug,
                    'new_version' => $remote_data['version'],
                    'url'         => $remote_data['url'],
                    'package'     => $remote_data['download_url'],
                    'icons'       => [],
                    'banners'     => [],
                    'tested'      => '6.4',
                    'requires'    => '5.8',
                    'requires_php' => '7.4',
                ];
            }
        }
        
        return $transient;
    }

    /**
     * Get plugin info for the WordPress plugin details popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $remote_data = $this->get_remote_data();
        
        if (!$remote_data) {
            return $result;
        }
        
        return (object) [
            'name'              => 'WritgoAI AI',
            'slug'              => dirname($this->plugin_slug),
            'version'           => $remote_data['version'],
            'author'            => '<a href="https://writgo.nl">Writgo</a>',
            'author_profile'    => 'https://writgo.nl',
            'homepage'          => "https://github.com/{$this->github_repo}",
            'requires'          => '5.8',
            'tested'            => '6.4',
            'requires_php'      => '7.4',
            'downloaded'        => 0,
            'last_updated'      => $remote_data['published_at'] ?? '',
            'sections'          => [
                'description'   => 'AI-powered content management for WordPress.',
                'changelog'     => $remote_data['changelog'] ?? 'See GitHub releases for full changelog.',
            ],
            'download_link'     => $remote_data['download_url'],
        ];
    }

    /**
     * Handle post-install (rename folder if needed)
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        // Only process our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $result;
        }
        
        // GitHub downloads have the format: RepoName-version
        // We need to rename to the correct plugin folder name
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->plugin_slug);
        
        // Move the plugin to the correct location
        if ($result['destination'] !== $plugin_folder) {
            // If target already exists, remove it first
            if ($wp_filesystem && $wp_filesystem->exists($plugin_folder)) {
                $wp_filesystem->delete($plugin_folder, true);
            }
            
            if ($wp_filesystem && $wp_filesystem->move($result['destination'], $plugin_folder)) {
                $result['destination'] = $plugin_folder;
            } else {
                return new WP_Error('move_failed', 'Failed to move plugin to correct directory.');
            }
        }
        
        // Reactivate plugin (avoid network activation in multisite)
        $activated = activate_plugin($this->plugin_slug, '', false);
        if (is_wp_error($activated)) {
            return new WP_Error('activation_failed', 'Plugin updated but reactivation failed: ' . $activated->get_error_message());
        }
        
        return $result;
    }
    
    /**
     * Get release data from GitHub API
     */
    private function get_remote_data() {
        // Check cache first
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($this->api_url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WritgoAI-Plugin-Updater',
            ],
        ]);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $release = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($release['tag_name'])) {
            return false;
        }
        
        // Get the zip download URL
        $download_url = '';
        if (!empty($release['assets'])) {
            // Prefer uploaded asset (zip file)
            foreach ($release['assets'] as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        // Fallback to source code zip
        if (empty($download_url)) {
            $download_url = $release['zipball_url'];
        }
        
        $data = [
            'version'      => ltrim($release['tag_name'], 'v'),
            'url'          => $release['html_url'],
            'download_url' => $download_url,
            'changelog'    => $release['body'] ?? '',
            'published_at' => $release['published_at'] ?? '',
        ];
        
        // Cache for 12 hours
        set_transient($this->cache_key, $data, $this->cache_expiration);
        
        return $data;
    }

    /**
     * Add action links to plugin row
     */
    public function add_action_links($links) {
        $check_link = '<a href="#" class="writgoai-check-update">' . __('Check for Updates', 'writgoai') . '</a>';
        array_unshift($links, $check_link);
        return $links;
    }

    /**
     * AJAX handler for manual update check
     */
    public function ajax_check_updates() {
        check_ajax_referer('writgoai_update_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_send_json_error(['message' => 'Geen toestemming.']);
        }
        
        // Clear cache to force fresh check
        delete_transient($this->cache_key);
        
        $remote_data = $this->get_remote_data();
        
        if (!$remote_data) {
            wp_send_json_error(['message' => 'Kon geen update informatie ophalen.']);
        }
        
        $has_update = version_compare($this->current_version, $remote_data['version'], '<');
        
        wp_send_json_success([
            'current_version' => $this->current_version,
            'latest_version'  => $remote_data['version'],
            'has_update'      => $has_update,
            'download_url'    => $remote_data['download_url'],
            'message'         => $has_update 
                ? sprintf('Nieuwe versie %s beschikbaar!', $remote_data['version'])
                : 'Je hebt de nieuwste versie.',
        ]);
    }
    
    /**
     * Force update check (clear cache)
     */
    public function force_check() {
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
}
