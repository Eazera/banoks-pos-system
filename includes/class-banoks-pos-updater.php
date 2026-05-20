<?php
/**
 * GitHub release updater for Banoks POS System.
 *
 * @package Banoks_POS
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds WordPress plugin update support from GitHub Releases.
 */
class Banoks_POS_Updater {

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin basename used by WordPress.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * GitHub repository in owner/repo format.
	 *
	 * @var string
	 */
	private $github_repo;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Main plugin file path.
	 * @param string $github_repo GitHub repository in owner/repo format.
	 */
	public function __construct( $plugin_file, $github_repo ) {
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->github_repo     = trim( $github_repo );

		if ( empty( $this->github_repo ) || false === strpos( $this->github_repo, '/' ) ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'rename_github_release_folder' ), 10, 4 );
	}

	/**
	 * Checks GitHub for a newer release.
	 *
	 * @param object $transient WordPress update transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) || empty( $transient->checked[ $this->plugin_basename ] ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( empty( $release['version'] ) || empty( $release['zipball_url'] ) ) {
			return $transient;
		}

		$current_version = $transient->checked[ $this->plugin_basename ];

		if ( version_compare( $release['version'], $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'id'          => $this->plugin_basename,
				'slug'        => dirname( $this->plugin_basename ),
				'plugin'      => $this->plugin_basename,
				'new_version' => $release['version'],
				'url'         => $release['html_url'],
				'package'     => $release['zipball_url'],
				'tested'      => get_bloginfo( 'version' ),
			);
		}

		return $transient;
	}

	/**
	 * Shows release details in the WordPress update modal.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args Plugin API arguments.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( $this->plugin_basename ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( empty( $release['version'] ) ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Banoks POS System',
			'slug'          => dirname( $this->plugin_basename ),
			'version'       => $release['version'],
			'author'        => '<a href="https://Eazera.ph">Eazera</a>',
			'homepage'      => $release['html_url'],
			'download_link' => $release['zipball_url'],
			'sections'      => array(
				'description' => 'Customized plugin POS system developed by Eazera Team.',
				'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( wpautop( $release['body'] ) ) : 'See the GitHub release for details.',
			),
		);
	}

	/**
	 * Renames GitHub's extracted folder to the actual plugin folder name.
	 *
	 * @param string|WP_Error $source        Source path.
	 * @param string          $remote_source Remote source path.
	 * @param WP_Upgrader     $upgrader      Upgrader instance.
	 * @param array           $hook_extra    Extra hook data.
	 * @return string|WP_Error
	 */
	public function rename_github_release_folder( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || $this->plugin_basename !== $hook_extra['plugin'] || is_wp_error( $source ) ) {
			return $source;
		}

		global $wp_filesystem;

		$plugin_folder = dirname( $this->plugin_basename );
		$new_source    = trailingslashit( $remote_source ) . $plugin_folder;

		if ( trailingslashit( $source ) === trailingslashit( $new_source ) ) {
			return $source;
		}

		if ( $wp_filesystem->exists( $new_source ) ) {
			$wp_filesystem->delete( $new_source, true );
		}

		if ( $wp_filesystem->move( $source, $new_source ) ) {
			return $new_source;
		}

		return $source;
	}

	/**
	 * Gets and caches the latest GitHub release.
	 *
	 * @return array
	 */
	private function get_latest_release() {
		$cache_key = 'banoks_pos_github_release_' . md5( $this->github_repo );
		$release   = get_site_transient( $cache_key );

		if ( false !== $release ) {
			return $release;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . rawurlencode( $this->get_repo_owner() ) . '/' . rawurlencode( $this->get_repo_name() ) . '/releases/latest',
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Banoks-POS-System-Updater',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( $cache_key, array(), 10 * MINUTE_IN_SECONDS );
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data['tag_name'] ) ) {
			set_site_transient( $cache_key, array(), 10 * MINUTE_IN_SECONDS );
			return array();
		}

		$release = array(
			'version'     => ltrim( $data['tag_name'], 'vV' ),
			'zipball_url' => isset( $data['zipball_url'] ) ? $data['zipball_url'] : '',
			'html_url'    => isset( $data['html_url'] ) ? $data['html_url'] : 'https://github.com/' . $this->github_repo,
			'body'        => isset( $data['body'] ) ? $data['body'] : '',
		);

		set_site_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Gets the GitHub owner.
	 *
	 * @return string
	 */
	private function get_repo_owner() {
		$parts = explode( '/', $this->github_repo );
		return $parts[0];
	}

	/**
	 * Gets the GitHub repository name.
	 *
	 * @return string
	 */
	private function get_repo_name() {
		$parts = explode( '/', $this->github_repo );
		return $parts[1];
	}
}
