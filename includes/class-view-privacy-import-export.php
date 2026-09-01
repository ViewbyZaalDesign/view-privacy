<?php
/**
 * Import/export van instellingen als JSON (§15).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Import_Export
 */
class View_Privacy_Import_Export {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Import_Export|null
	 */
	private static $instance = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Import_Export
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
		add_action( 'admin_post_view_privacy_export', array( $this, 'handle_export' ) );
	}

	/**
	 * Stuur de huidige instellingen als downloadbaar JSON-bestand.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Onvoldoende rechten.', 'view-privacy' ) );
		}

		check_admin_referer( 'view_privacy_export' );

		$settings = View_Privacy_Settings::instance()->get_settings();
		$payload  = array(
			'plugin'  => 'view-privacy',
			'version' => VIEW_PRIVACY_VERSION,
			'exported_at' => gmdate( 'c' ),
			'settings' => $settings,
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="view-privacy-settings-' . gmdate( 'Y-m-d' ) . '.json"' );

		echo wp_json_encode( $payload, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Importeer instellingen vanuit een geüpload JSON-bestand.
	 *
	 * @param array $file Een $_FILES-item.
	 * @return true|WP_Error
	 */
	public function import_from_file( $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'view_privacy_import_invalid', __( 'Geen geldig bestand ontvangen.', 'view-privacy' ) );
		}

		if ( ! empty( $file['size'] ) && $file['size'] > 512000 ) {
			return new WP_Error( 'view_privacy_import_too_large', __( 'Bestand is te groot.', 'view-privacy' ) );
		}

		$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $contents ) {
			return new WP_Error( 'view_privacy_import_read_failed', __( 'Bestand kon niet worden gelezen.', 'view-privacy' ) );
		}

		$data = json_decode( $contents, true );

		if ( null === $data || ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			return new WP_Error( 'view_privacy_import_malformed', __( 'Bestand bevat geen geldige VIEW! Privacy-instellingen.', 'view-privacy' ) );
		}

		$settings_service = View_Privacy_Settings::instance();
		$current           = $settings_service->get_settings();
		$sanitized         = $settings_service->sanitize( $data['settings'], $current );

		$settings_service->save_settings( $sanitized );

		return true;
	}
}
