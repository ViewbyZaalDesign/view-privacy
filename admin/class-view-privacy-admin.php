<?php
/**
 * Admin-omgeving: menu, dashboard, instellingenpagina en formulierverwerking.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Admin
 */
class View_Privacy_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Admin|null
	 */
	private static $instance = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Admin
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'notice_privacy_policy_missing' ) );
	}

	/**
	 * Registreer het adminmenu en submenu's.
	 *
	 * @return void
	 */
	public function register_menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10c0-.68-.07-1.34-.2-2-.5.87-1.44 1.46-2.52 1.46-1.6 0-2.9-1.3-2.9-2.9 0-.87.38-1.65.98-2.19A9.96 9.96 0 0012 2zm-4.5 7a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm3 5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm5-3a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>'
		);

		add_menu_page(
			__( 'VIEW! Privacy', 'view-privacy' ),
			__( 'VIEW! Privacy', 'view-privacy' ),
			'manage_options',
			'view-privacy-dashboard',
			array( $this, 'render_dashboard' ),
			$icon,
			80
		);

		add_submenu_page(
			'view-privacy-dashboard',
			__( 'Dashboard', 'view-privacy' ),
			__( 'Dashboard', 'view-privacy' ),
			'manage_options',
			'view-privacy-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'view-privacy-dashboard',
			__( 'Instellingen', 'view-privacy' ),
			__( 'Instellingen', 'view-privacy' ),
			'manage_options',
			'view-privacy-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'view-privacy-dashboard',
			__( 'Diensten', 'view-privacy' ),
			__( 'Diensten', 'view-privacy' ),
			'manage_options',
			'view-privacy-services',
			array( $this, 'render_services' )
		);

		add_submenu_page(
			'view-privacy-dashboard',
			__( 'Logboek', 'view-privacy' ),
			__( 'Logboek', 'view-privacy' ),
			'manage_options',
			'view-privacy-logs',
			array( $this, 'render_logs' )
		);

		add_submenu_page(
			'view-privacy-dashboard',
			__( 'Import / Export', 'view-privacy' ),
			__( 'Import / Export', 'view-privacy' ),
			'manage_options',
			'view-privacy-import-export',
			array( $this, 'render_import_export' )
		);
	}

	/**
	 * Enqueue admin CSS/JS enkel op de eigen pagina's.
	 *
	 * @param string $hook De huidige adminpagina-hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'view-privacy' ) ) {
			return;
		}

		wp_enqueue_style( 'view-privacy-admin', VIEW_PRIVACY_URL . 'admin/css/admin.css', array(), VIEW_PRIVACY_VERSION );
		wp_enqueue_script( 'view-privacy-admin', VIEW_PRIVACY_URL . 'admin/js/admin.js', array(), VIEW_PRIVACY_VERSION, true );

		// De mediabibliotheek (wp.media) is alleen nodig op de instellingenpagina,
		// voor de optionele upload van een eigen herstelknop-afbeelding.
		if ( isset( $_GET['page'] ) && 'view-privacy-settings' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_media();
		}
	}

	/**
	 * Verwerk alle POST-formulieren van de plugin (instellingen, diensten, import).
	 *
	 * @return void
	 */
	public function handle_form_submissions() {
		if ( ! isset( $_POST['view_privacy_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['view_privacy_action'] ) );

		switch ( $action ) {
			case 'save_settings':
				check_admin_referer( 'view_privacy_save_settings' );
				$input = isset( $_POST['view_privacy'] ) ? wp_unslash( $_POST['view_privacy'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				View_Privacy_Settings::instance()->save_settings( $this->sanitize_recursive_strings( $input ) );
				add_action( 'admin_notices', array( $this, 'notice_settings_saved' ) );
				break;

			case 'save_services':
				check_admin_referer( 'view_privacy_save_services' );
				$input    = array( 'services' => isset( $_POST['services'] ) ? wp_unslash( $_POST['services'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$new_key  = isset( $_POST['vp_new_service_key'] ) ? sanitize_key( wp_unslash( $_POST['vp_new_service_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$new_data = isset( $_POST['vp_new_service'] ) ? wp_unslash( $_POST['vp_new_service'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( $new_key && is_array( $new_data ) && ! empty( $new_data['patterns'] ) ) {
					$input['services'][ $new_key ] = $new_data;
				}
				View_Privacy_Settings::instance()->save_settings( $this->sanitize_recursive_strings( $input ) );
				add_action( 'admin_notices', array( $this, 'notice_settings_saved' ) );
				break;

			case 'reset_settings':
				check_admin_referer( 'view_privacy_reset_settings' );
				View_Privacy_Settings::instance()->reset_to_defaults();
				add_action( 'admin_notices', array( $this, 'notice_settings_reset' ) );
				break;

			case 'import_settings':
				check_admin_referer( 'view_privacy_import_settings' );
				if ( ! empty( $_FILES['view_privacy_import_file'] ) ) {
					$result = View_Privacy_Import_Export::instance()->import_from_file( $_FILES['view_privacy_import_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					if ( is_wp_error( $result ) ) {
						$this->import_error = $result;
						add_action( 'admin_notices', array( $this, 'notice_import_failed' ) );
					} else {
						add_action( 'admin_notices', array( $this, 'notice_import_succeeded' ) );
					}
				}
				break;
		}
	}

	/**
	 * Recursieve, lichte sanitizing op stringniveau; strengere sanitizing
	 * gebeurt per veld in View_Privacy_Settings::sanitize().
	 *
	 * @param mixed $data Input data.
	 * @return mixed
	 */
	private function sanitize_recursive_strings( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize_recursive_strings' ), $data );
		}
		return is_string( $data ) ? $data : $data;
	}

	/** Admin notice: instellingen opgeslagen. */
	public function notice_settings_saved() {
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Instellingen opgeslagen.', 'view-privacy' ) );
	}

	/** Admin notice: instellingen gereset. */
	public function notice_settings_reset() {
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Instellingen zijn teruggezet naar de standaardwaarden.', 'view-privacy' ) );
	}

	/** Admin notice: import geslaagd. */
	public function notice_import_succeeded() {
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Instellingen succesvol geïmporteerd.', 'view-privacy' ) );
	}

	/** Admin notice: import mislukt. */
	public function notice_import_failed() {
		$message = isset( $this->import_error ) && is_wp_error( $this->import_error )
			? $this->import_error->get_error_message()
			: __( 'Import mislukt.', 'view-privacy' );
		printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $message ) );
	}

	/**
	 * Globale admin-melding: zonder gekoppelde privacybeleidpagina is de
	 * cookiebanner uitgeschakeld. Zichtbaar op elke beheerpagina, zodat dit
	 * niet gemist kan worden, met uitzondering van de VIEW! Privacy-
	 * instellingenpagina zelf (daar is de melding overbodig).
	 */
	public function notice_privacy_policy_missing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'view-privacy-settings', 'view-privacy-dashboard' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( View_Privacy_Consent::instance()->get_privacy_policy_url() ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong></p><p><a href="%2$s" class="button button-primary">%3$s</a></p></div>',
			esc_html__( 'Er is geen privacybeleidpagina ingesteld. Daarom wordt de cookiebanner niet weergegeven. Ga naar VIEW! Privacy → Instellingen en wijs eerst een privacybeleidpagina toe.', 'view-privacy' ),
			esc_url( admin_url( 'admin.php?page=view-privacy-settings' ) ),
			esc_html__( 'Open VIEW! Privacy → Instellingen', 'view-privacy' )
		);
	}

	/** Render dashboard. */
	public function render_dashboard() {
		require VIEW_PRIVACY_PATH . 'admin/views/dashboard.php';
	}

	/** Render instellingenpagina. */
	public function render_settings() {
		require VIEW_PRIVACY_PATH . 'admin/views/settings.php';
	}

	/** Render dienstenpagina. */
	public function render_services() {
		require VIEW_PRIVACY_PATH . 'admin/views/services.php';
	}

	/** Render logboekpagina. */
	public function render_logs() {
		require VIEW_PRIVACY_PATH . 'admin/views/logs.php';
	}

	/** Render import/export pagina. */
	public function render_import_export() {
		require VIEW_PRIVACY_PATH . 'admin/views/import-export.php';
	}
}
