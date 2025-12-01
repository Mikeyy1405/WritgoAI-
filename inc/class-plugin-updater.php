<?php
/**
 * Plugin Updater Class
 *
 * Handles automatic plugin updates from custom update server.
 * Features:
 * - Custom update server integration
 * - Version checking
 * - Plugin download with API key injection
 * - License verification before updates
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WritgoCMS_Plugin_Updater
 */
class WritgoCMS_Plugin_Updater {

	/**
	 * Instance
	 *
	 * @var WritgoCMS_Plugin_Updater
	 */
	private static $instance = null;

	/**
	 * Update server URL
	 *
	 * @var string
	 */
	private $update_server_url = 'https://api.writgoai.com/v1';

	/**
	 * Plugin file
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin basename
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Current plugin version
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Cache key for update info
	 *
	 * @var string
	 */
	private $cache_key = 'writgocms_update_info';

	/**
	 * Cache expiration in seconds (12 hours)
	 *
	 * @var int
	 */
	private $cache_expiration = 43200;

	/**
	 * License manager instance
	 *
	 * @var WritgoCMS_License_Manager
	 */
	private $license_manager;

	/**
	 * Get instance
	 *
	 * @return WritgoCMS_Plugin_Updater
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
		$this->plugin_file     = WRITGOCMS_DIR . 'writgo-cms.php';
		$this->plugin_slug     = 'writgo-cms';
		// Use plugin_basename() to get the correct path dynamically.
		$this->plugin_basename = plugin_basename( $this->plugin_file );
		$this->current_version = WRITGOCMS_VERSION;

		// Initialize license manager.
		$this->license_manager = WritgoCMS_License_Manager::get_instance();

		// Hook into WordPress update system.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_directory' ), 10, 4 );
		add_action( 'in_plugin_update_message-' . $this->plugin_basename, array( $this, 'update_message' ), 10, 2 );

		// Add custom AJAX for manual update check.
		add_action( 'wp_ajax_writgocms_check_updates', array( $this, 'ajax_check_updates' ) );
	}

	/**
	 * Check for plugin updates
	 *
	 * @param object $transient Update transient.
	 * @return object Modified transient.
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$update_info = $this->get_update_info();

		if ( ! $update_info || ! isset( $update_info['version'] ) ) {
			return $transient;
		}

		// Compare versions.
		if ( version_compare( $update_info['version'], $this->current_version, '>' ) ) {
			$plugin_data = array(
				'id'            => $this->plugin_basename,
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $update_info['version'],
				'url'           => isset( $update_info['url'] ) ? $update_info['url'] : '',
				'package'       => $this->get_download_url( $update_info['version'] ),
				'icons'         => isset( $update_info['icons'] ) ? $update_info['icons'] : array(),
				'banners'       => isset( $update_info['banners'] ) ? $update_info['banners'] : array(),
				'banners_rtl'   => array(),
				'tested'        => isset( $update_info['tested'] ) ? $update_info['tested'] : '',
				'requires_php'  => isset( $update_info['requires_php'] ) ? $update_info['requires_php'] : '7.4',
				'compatibility' => new stdClass(),
			);

			$transient->response[ $this->plugin_basename ] = (object) $plugin_data;
		} else {
			// No update available.
			$transient->no_update[ $this->plugin_basename ] = (object) array(
				'id'            => $this->plugin_basename,
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $this->current_version,
				'url'           => isset( $update_info['url'] ) ? $update_info['url'] : '',
				'package'       => '',
			);
		}

		return $transient;
	}

	/**
	 * Get plugin info for the plugins API
	 *
	 * @param false|object|array $result The result.
	 * @param string             $action The type of information being requested.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Plugin info or false.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( $this->plugin_slug !== $args->slug ) {
			return $result;
		}

		$update_info = $this->get_update_info( true );

		if ( ! $update_info ) {
			return $result;
		}

		$plugin_info = new stdClass();

		$plugin_info->name           = isset( $update_info['name'] ) ? $update_info['name'] : 'WritgoAI';
		$plugin_info->slug           = $this->plugin_slug;
		$plugin_info->version        = isset( $update_info['version'] ) ? $update_info['version'] : $this->current_version;
		$plugin_info->author         = isset( $update_info['author'] ) ? $update_info['author'] : '<a href="https://writgoai.com">WritgoAI</a>';
		$plugin_info->author_profile = isset( $update_info['author_profile'] ) ? $update_info['author_profile'] : 'https://writgoai.com';
		$plugin_info->requires       = isset( $update_info['requires'] ) ? $update_info['requires'] : '5.9';
		$plugin_info->tested         = isset( $update_info['tested'] ) ? $update_info['tested'] : '6.5';
		$plugin_info->requires_php   = isset( $update_info['requires_php'] ) ? $update_info['requires_php'] : '7.4';
		$plugin_info->last_updated   = isset( $update_info['last_updated'] ) ? $update_info['last_updated'] : '';
		$plugin_info->homepage       = isset( $update_info['homepage'] ) ? $update_info['homepage'] : 'https://writgoai.com';
		$plugin_info->download_link  = $this->get_download_url( $plugin_info->version );

		// Sections.
		$plugin_info->sections = array(
			'description'  => isset( $update_info['description'] ) ? $update_info['description'] : '',
			'installation' => isset( $update_info['installation'] ) ? $update_info['installation'] : '',
			'changelog'    => isset( $update_info['changelog'] ) ? $update_info['changelog'] : '',
		);

		// Icons and banners.
		if ( isset( $update_info['icons'] ) ) {
			$plugin_info->icons = $update_info['icons'];
		}

		if ( isset( $update_info['banners'] ) ) {
			$plugin_info->banners = $update_info['banners'];
		}

		return $plugin_info;
	}

	/**
	 * Get update info from server
	 *
	 * @param bool $force_refresh Force refresh from server.
	 * @return array|false Update info or false on error.
	 */
	public function get_update_info( $force_refresh = false ) {
		$cached = get_transient( $this->cache_key );

		if ( ! $force_refresh && false !== $cached ) {
			return $cached;
		}

		$license_key = $this->license_manager->get_license_key();

		// Build URL with query parameters for GET request.
		$request_url = add_query_arg(
			array(
				'license_key'     => $license_key,
				'site_url'        => home_url(),
				'product'         => 'writgoai',
				'current_version' => $this->current_version,
				'wp_version'      => get_bloginfo( 'version' ),
				'php_version'     => PHP_VERSION,
			),
			$this->update_server_url . '/plugin/info'
		);

		$response = wp_remote_get(
			$request_url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['version'] ) ) {
			return false;
		}

		// Sanitize the update info.
		$update_info = array(
			'name'           => isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : 'WritgoAI',
			'version'        => isset( $body['version'] ) ? sanitize_text_field( $body['version'] ) : '',
			'url'            => isset( $body['url'] ) ? esc_url_raw( $body['url'] ) : '',
			'author'         => isset( $body['author'] ) ? wp_kses_post( $body['author'] ) : '',
			'author_profile' => isset( $body['author_profile'] ) ? esc_url_raw( $body['author_profile'] ) : '',
			'requires'       => isset( $body['requires'] ) ? sanitize_text_field( $body['requires'] ) : '5.9',
			'tested'         => isset( $body['tested'] ) ? sanitize_text_field( $body['tested'] ) : '',
			'requires_php'   => isset( $body['requires_php'] ) ? sanitize_text_field( $body['requires_php'] ) : '7.4',
			'last_updated'   => isset( $body['last_updated'] ) ? sanitize_text_field( $body['last_updated'] ) : '',
			'homepage'       => isset( $body['homepage'] ) ? esc_url_raw( $body['homepage'] ) : '',
			'description'    => isset( $body['description'] ) ? wp_kses_post( $body['description'] ) : '',
			'installation'   => isset( $body['installation'] ) ? wp_kses_post( $body['installation'] ) : '',
			'changelog'      => isset( $body['changelog'] ) ? wp_kses_post( $body['changelog'] ) : '',
			'icons'          => isset( $body['icons'] ) ? array_map( 'esc_url_raw', (array) $body['icons'] ) : array(),
			'banners'        => isset( $body['banners'] ) ? array_map( 'esc_url_raw', (array) $body['banners'] ) : array(),
		);

		set_transient( $this->cache_key, $update_info, $this->cache_expiration );

		return $update_info;
	}

	/**
	 * Get download URL with license verification
	 *
	 * @param string $version Version to download.
	 * @return string Download URL.
	 */
	public function get_download_url( $version ) {
		$license_key = $this->license_manager->get_license_key();

		if ( empty( $license_key ) ) {
			return '';
		}

		$params = array(
			'license_key' => $license_key,
			'site_url'    => home_url(),
			'version'     => $version,
			'product'     => 'writgoai',
		);

		return add_query_arg( $params, $this->update_server_url . '/plugin/download' );
	}

	/**
	 * Fix source directory name after extraction
	 *
	 * @param string      $source        File source location.
	 * @param string      $remote_source Remote file source location.
	 * @param WP_Upgrader $upgrader      WP_Upgrader instance.
	 * @param array       $args          Extra arguments.
	 * @return string|WP_Error Modified source or error.
	 */
	public function fix_source_directory( $source, $remote_source, $upgrader, $args ) {
		global $wp_filesystem;

		if ( ! isset( $args['plugin'] ) || $args['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		// Get the expected directory name from the current plugin basename.
		$current_directory = dirname( $this->plugin_basename );
		$expected_directory = trailingslashit( $remote_source ) . $current_directory;

		if ( $source !== $expected_directory && $wp_filesystem ) {
			if ( $wp_filesystem->move( $source, $expected_directory ) ) {
				return $expected_directory;
			}
		}

		return $source;
	}

	/**
	 * Display update message in plugins list
	 *
	 * @param array  $plugin_data Plugin data.
	 * @param object $response    Response from update check.
	 */
	public function update_message( $plugin_data, $response ) {
		if ( ! $this->license_manager->is_license_valid() ) {
			echo '<br><span style="color: #dc3232;">';
			echo esc_html__( 'Je hebt een geldige licentie nodig om updates te ontvangen.', 'writgocms' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=writgocms-license' ) ) . '">';
			echo esc_html__( 'Licentie activeren', 'writgocms' );
			echo '</a></span>';
		}
	}

	/**
	 * AJAX handler for manual update check
	 */
	public function ajax_check_updates() {
		check_ajax_referer( 'writgocms_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Geen toestemming.' ) );
		}

		// Clear cache and check for updates.
		delete_transient( $this->cache_key );
		delete_site_transient( 'update_plugins' );

		$update_info = $this->get_update_info( true );

		if ( ! $update_info ) {
			wp_send_json_error( array( 'message' => 'Kon geen update informatie ophalen.' ) );
		}

		$has_update = version_compare( $update_info['version'], $this->current_version, '>' );

		wp_send_json_success( array(
			'message'         => $has_update ? 'Nieuwe versie beschikbaar!' : 'Je hebt de nieuwste versie.',
			'current_version' => $this->current_version,
			'latest_version'  => $update_info['version'],
			'has_update'      => $has_update,
			'changelog'       => isset( $update_info['changelog'] ) ? $update_info['changelog'] : '',
		) );
	}

	/**
	 * Get current plugin version
	 *
	 * @return string
	 */
	public function get_current_version() {
		return $this->current_version;
	}

	/**
	 * Set custom update server URL
	 *
	 * @param string $url Update server URL.
	 */
	public function set_update_server_url( $url ) {
		$this->update_server_url = esc_url_raw( $url );
	}

	/**
	 * Get update server URL
	 *
	 * @return string
	 */
	public function get_update_server_url() {
		return apply_filters( 'writgocms_update_server_url', $this->update_server_url );
	}

	/**
	 * Clear update cache
	 */
	public function clear_cache() {
		delete_transient( $this->cache_key );
		delete_site_transient( 'update_plugins' );
	}
}

// Initialize after license manager.
add_action( 'plugins_loaded', function() {
	WritgoCMS_Plugin_Updater::get_instance();
}, 20 );
