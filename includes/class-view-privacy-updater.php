<?php
/**
 * GitHub updater voor VIEW! Privacy.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controleert op nieuwe publieke GitHub Releases en koppelt deze aan
 * de standaard WordPress plugin-updater.
 */
class View_Privacy_Updater {

	/**
	 * GitHub repository eigenaar.
	 *
	 * @var string
	 */
	private const GITHUB_OWNER = 'ViewbyZaalDesign';

	/**
	 * GitHub repository naam.
	 *
	 * @var string
	 */
	private const GITHUB_REPO = 'view-privacy';

	/**
	 * Cacheduur voor de GitHub API-response.
	 *
	 * @var int
	 */
	private const CACHE_TTL = 43200; // 12 uur.

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Updater|null
	 */
	private static $instance = null;

	/**
	 * Start de updater.
	 *
	 * @return View_Privacy_Updater
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 20, 3 );
	}

	/**
	 * Controleer of er een nieuwe release beschikbaar is.
	 *
	 * @param object $transient WordPress update transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( ! $release ) {
			return $transient;
		}

		$version = $this->get_release_version( $release );
		$package = $this->get_release_package( $release, $version );

		if ( ! $version || ! $package ) {
			return $transient;
		}

		if ( version_compare( $version, VIEW_PRIVACY_VERSION, '>' ) ) {
			$transient->response[ VIEW_PRIVACY_BASENAME ] = (object) array(
				'slug'        => dirname( VIEW_PRIVACY_BASENAME ),
				'plugin'      => VIEW_PRIVACY_BASENAME,
				'new_version' => $version,
				'url'         => $release['html_url'],
				'package'     => $package,
				'tested'      => '6.8',
			);
		}

		return $transient;
	}

	/**
	 * Lever informatie voor het scherm "Details bekijken".
	 *
	 * @param false|object|array $result Bestaand resultaat.
	 * @param string             $action Actie.
	 * @param object             $args Plugin-informatie.
	 * @return false|object|array
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'view-privacy' !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( ! $release ) {
			return $result;
		}

		$version = $this->get_release_version( $release );
		$package = $this->get_release_package( $release, $version );

		if ( ! $version || ! $package ) {
			return $result;
		}

		return (object) array(
			'name'           => 'VIEW! Privacy',
			'slug'           => 'view-privacy',
			'version'        => $version,
			'author'         => '<a href="https://zaaldesign.nl">VIEW! by Zaal Design</a>',
			'homepage'       => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
			'download_link'  => $package,
			'requires'       => '6.8',
			'requires_php'   => '8.1',
			'last_updated'   => isset( $release['published_at'] ) ? $release['published_at'] : '',
			'external'       => true,
			'short_description' => 'Lichtgewicht, privacy-first cookieconsent-plugin voor WordPress.',
			'description'    => ! empty( $release['body'] ) ? wpautop( wp_kses_post( $release['body'] ) ) : '<p>VIEW! Privacy is een privacy-first cookieconsent-plugin voor WordPress.</p>',
			'icons'          => array(),
		);
	}

	/**
	 * Haal de laatste publieke GitHub release op.
	 *
	 * @return array|null
	 */
	private function get_latest_release() {
		$cache_key = 'view_privacy_github_release';
		$cached    = get_site_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			rawurlencode( self::GITHUB_OWNER ),
			rawurlencode( self::GITHUB_REPO )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'headers'    => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'VIEW-Privacy-Updater/' . VIEW_PRIVACY_VERSION,
				),
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( $cache_key, array( 'error' => true ), 1800 );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['tag_name'] ) || ! empty( $body['draft'] ) || ! empty( $body['prerelease'] ) ) {
			return null;
		}

		set_site_transient( $cache_key, $body, self::CACHE_TTL );

		return $body;
	}

	/**
	 * Bepaal de semantische versie uit de release tag.
	 *
	 * @param array $release GitHub release.
	 * @return string
	 */
	private function get_release_version( $release ) {
		$tag = isset( $release['tag_name'] ) ? trim( (string) $release['tag_name'] ) : '';
		$tag = preg_replace( '/^v/i', '', $tag );

		return preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $tag ) ? $tag : '';
	}

	/**
	 * Zoek het expliciete release-ZIP-bestand.
	 *
	 * @param array  $release GitHub release.
	 * @param string $version Releaseversie.
	 * @return string
	 */
	private function get_release_package( $release, $version ) {
		if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
			return '';
		}

		$expected = 'view-privacy-' . $version . '.zip';

		foreach ( $release['assets'] as $asset ) {
			if ( isset( $asset['name'], $asset['browser_download_url'] ) && $expected === $asset['name'] ) {
				return esc_url_raw( $asset['browser_download_url'] );
			}
		}

		return '';
	}
}
