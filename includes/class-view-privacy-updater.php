<?php
/**
 * GitHub Release-updater voor VIEW! Privacy.
 *
 * Alleen expliciete release-assets worden gebruikt. GitHub source archives
 * zijn bewust uitgesloten: hun rootmap bevat de tagnaam en is dus ongeschikt
 * voor een in-place WordPress-pluginupdate.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class View_Privacy_Updater {
	private const GITHUB_OWNER = 'ViewbyZaalDesign';
	private const GITHUB_REPO = 'view-privacy';
	private const CACHE_KEY = 'view_privacy_github_release_v2';
	private const VALIDATORS_KEY = 'view_privacy_github_release_validators_v2';
	private const CACHE_TTL = 21600;
	private const NEGATIVE_CACHE_TTL = 900;
	private static $instance = null;
	private $activation_mode_before_upgrade = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 20, 3 );
		// Run before Plugin_Upgrader's priority-10 deactivation hook.
		add_filter( 'upgrader_pre_install', array( $this, 'remember_activation_state_before_upgrade' ), 9, 2 );
		add_filter( 'upgrader_post_install', array( $this, 'restore_activation_state_after_upgrade' ), 11, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_upgrade' ), 10, 2 );
	}

	/** Records this plugin's activation mode before WordPress deactivates it for an interactive update. */
	public function remember_activation_state_before_upgrade( $response, $hook_extra ) {
		if ( ! $this->is_view_privacy_upgrade( $hook_extra ) || is_wp_error( $response ) ) {
			return $response;
		}

		$this->activation_mode_before_upgrade = null;
		if ( is_multisite() && is_plugin_active_for_network( VIEW_PRIVACY_BASENAME ) ) {
			$this->activation_mode_before_upgrade = 'network';
		} elseif ( is_plugin_active( VIEW_PRIVACY_BASENAME ) ) {
			$this->activation_mode_before_upgrade = 'site';
		}

		return $response;
	}

	/** Restores a previously active plugin only after a successful installation. */
	public function restore_activation_state_after_upgrade( $response, $hook_extra ) {
		if ( ! $this->is_view_privacy_upgrade( $hook_extra ) ) {
			return $response;
		}

		$activation_mode = $this->activation_mode_before_upgrade;
		$this->activation_mode_before_upgrade = null;
		if ( true !== $response || ! $activation_mode ) {
			return $response;
		}

		if ( 'network' === $activation_mode ) {
			if ( ! is_plugin_active_for_network( VIEW_PRIVACY_BASENAME ) ) {
				activate_plugin( VIEW_PRIVACY_BASENAME, '', true, true );
			}
		} elseif ( ! is_plugin_active( VIEW_PRIVACY_BASENAME ) ) {
			activate_plugin( VIEW_PRIVACY_BASENAME, '', false, true );
		}

		return $response;
	}

	/** @param array $hook_extra @return bool */
	private function is_view_privacy_upgrade( $hook_extra ) {
		if ( ! is_array( $hook_extra ) || 'plugin' !== ( $hook_extra['type'] ?? '' ) || 'update' !== ( $hook_extra['action'] ?? '' ) ) {
			return false;
		}
		if ( VIEW_PRIVACY_BASENAME === ( $hook_extra['plugin'] ?? '' ) ) {
			return true;
		}
		return isset( $hook_extra['plugins'] ) && in_array( VIEW_PRIVACY_BASENAME, (array) $hook_extra['plugins'], true );
	}

	/** Adds a complete native update object to response or no_update. */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}
		unset( $transient->response[ VIEW_PRIVACY_BASENAME ], $transient->no_update[ VIEW_PRIVACY_BASENAME ] );

		$release = $this->get_latest_release();
		if ( ! is_array( $release ) ) {
			return $transient;
		}
		$version = $this->get_release_version( $release );
		$package = $this->get_release_package( $release, $version );
		if ( ! $version || ! $package ) {
			return $transient;
		}

		$item = $this->build_update_item( $release, $version, $package );
		if ( version_compare( $version, VIEW_PRIVACY_VERSION, '>' ) ) {
			$transient->response[ VIEW_PRIVACY_BASENAME ] = $item;
		} else {
			$transient->no_update[ VIEW_PRIVACY_BASENAME ] = $item;
		}
		return $transient;
	}

	/** @param array $release @param string $version @param string $package @return object */
	private function build_update_item( $release, $version, $package ) {
		return (object) array(
			'id'           => VIEW_PRIVACY_UPDATE_URI,
			'slug'         => dirname( VIEW_PRIVACY_BASENAME ),
			'plugin'       => VIEW_PRIVACY_BASENAME,
			'new_version'  => $version,
			'url'          => isset( $release['html_url'] ) ? esc_url_raw( $release['html_url'] ) : VIEW_PRIVACY_UPDATE_URI,
			'package'      => $package,
			'tested'       => VIEW_PRIVACY_TESTED_WP,
			'requires'     => VIEW_PRIVACY_REQUIRES_WP,
			'requires_php' => VIEW_PRIVACY_REQUIRES_PHP,
			'autoupdate'   => false,
			'icons'        => array(),
			'banners'      => array(),
			'banners_rtl'  => array(),
		);
	}

	/** @param false|object|array $result @param string $action @param object $args @return false|object|array */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( VIEW_PRIVACY_BASENAME ) !== $args->slug ) {
			return $result;
		}
		$release = $this->get_latest_release();
		$version = is_array( $release ) ? $this->get_release_version( $release ) : '';
		$package = is_array( $release ) ? $this->get_release_package( $release, $version ) : '';
		if ( ! $version || ! $package ) {
			return $result;
		}
		$item = $this->build_update_item( $release, $version, $package );
		$item->name = 'VIEW! Privacy';
		$item->version = $version;
		$item->author = '<a href="https://zaaldesign.nl">VIEW! by Zaal Design</a>';
		$item->homepage = VIEW_PRIVACY_UPDATE_URI;
		$item->download_link = $package;
		$item->last_updated = isset( $release['published_at'] ) ? sanitize_text_field( $release['published_at'] ) : '';
		$item->external = true;
		$item->short_description = 'Lichtgewicht, privacy-first cookieconsent-plugin voor WordPress.';
		$item->description = ! empty( $release['body'] ) ? wpautop( wp_kses_post( $release['body'] ) ) : '<p>VIEW! Privacy is een privacy-first cookieconsent-plugin voor WordPress.</p>';
		return $item;
	}

	/** Clears both caches only after this exact plugin was updated. */
	public function clear_cache_after_upgrade( $upgrader, $options ) {
		if ( empty( $options['action'] ) || 'update' !== $options['action'] || empty( $options['type'] ) || 'plugin' !== $options['type'] || empty( $options['plugins'] ) ) {
			return;
		}
		if ( in_array( VIEW_PRIVACY_BASENAME, (array) $options['plugins'], true ) ) {
			self::clear_update_cache();
		}
	}

	public static function clear_update_cache() {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( self::VALIDATORS_KEY );
		delete_site_transient( 'update_plugins' );
	}

	/** @return array|null */
	private function get_latest_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && isset( $cached['release'] ) && is_array( $cached['release'] ) ) {
			return $cached['release'];
		}
		if ( is_array( $cached ) && ! empty( $cached['negative'] ) ) {
			return null;
		}
		$validators = get_site_transient( self::VALIDATORS_KEY );
		$headers = array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'VIEW-Privacy-Updater/' . VIEW_PRIVACY_VERSION );
		if ( is_array( $validators ) && ! empty( $validators['etag'] ) ) {
			$headers['If-None-Match'] = $validators['etag'];
		}
		if ( is_array( $validators ) && ! empty( $validators['last_modified'] ) ) {
			$headers['If-Modified-Since'] = $validators['last_modified'];
		}
		$response = wp_remote_get( sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', rawurlencode( self::GITHUB_OWNER ), rawurlencode( self::GITHUB_REPO ) ), array( 'timeout' => 10, 'headers' => $headers, 'redirection' => 3 ) );
		if ( is_wp_error( $response ) ) {
			return $this->cache_failure( $cached );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 304 === $code && is_array( $validators ) && isset( $validators['release'] ) && is_array( $validators['release'] ) ) {
			set_site_transient( self::CACHE_KEY, array( 'release' => $validators['release'] ), self::CACHE_TTL );
			return $validators['release'];
		}
		if ( 200 !== $code ) {
			return $this->cache_failure( $cached );
		}
		$release = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $release ) || empty( $release['tag_name'] ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) || ! $this->get_release_version( $release ) ) {
			set_site_transient( self::CACHE_KEY, array( 'negative' => true ), self::NEGATIVE_CACHE_TTL );
			return null;
		}
		set_site_transient( self::CACHE_KEY, array( 'release' => $release ), self::CACHE_TTL );
		set_site_transient( self::VALIDATORS_KEY, array( 'release' => $release, 'etag' => wp_remote_retrieve_header( $response, 'etag' ), 'last_modified' => wp_remote_retrieve_header( $response, 'last-modified' ) ), 7 * DAY_IN_SECONDS );
		return $release;
	}

	/** @param mixed $cached @return array|null */
	private function cache_failure( $cached ) {
		if ( is_array( $cached ) && isset( $cached['release'] ) && is_array( $cached['release'] ) ) {
			return $cached['release'];
		}
		set_site_transient( self::CACHE_KEY, array( 'negative' => true ), self::NEGATIVE_CACHE_TTL );
		return null;
	}

	/** Tags must be lowercase vX.Y.Z; V4.0.0 remains readable for existing installations. */
	private function get_release_version( $release ) {
		$tag = isset( $release['tag_name'] ) ? trim( (string) $release['tag_name'] ) : '';
		if ( preg_match( '/^v(\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?)$/', $tag, $matches ) ) {
			return $matches[1];
		}
		return 'V4.0.0' === $tag ? '4.0.0' : '';
	}

	/** Explicit assets only; never accept GitHub's zipball_url or tarball_url. */
	private function get_release_package( $release, $version ) {
		if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) || ! $version ) {
			return '';
		}
		$expected = VIEW_PRIVACY_RELEASE_ASSET_PREFIX . $version . '.zip';
		foreach ( $release['assets'] as $asset ) {
			if ( ! empty( $asset['name'] ) && $expected === $asset['name'] && ! empty( $asset['browser_download_url'] ) && 'application/zip' === ( $asset['content_type'] ?? 'application/zip' ) ) {
				return esc_url_raw( $asset['browser_download_url'] );
			}
		}
		return '';
	}
}
