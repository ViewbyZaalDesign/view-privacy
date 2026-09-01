<?php
/**
 * Core functionaliteit: activatie, deactivatie en gedeelde helpers.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Core
 */
class View_Privacy_Core {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Core|null
	 */
	private static $instance = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Core
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
		self::maybe_upgrade();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'wp_initialize_site', array( __CLASS__, 'initialize_new_site' ), 10, 2 );
		add_action( 'wpmu_new_blog', array( __CLASS__, 'initialize_legacy_new_site' ), 10, 1 );
	}

	/**
	 * Plugin-activatie: maak logtabel aan en zet standaardinstellingen.
	 *
	 * @param bool $network_wide Of de plugin netwerkbreed wordt geactiveerd.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_single_site();
				restore_current_blog();
			}
			return;
		}

		self::activate_single_site();
	}

	/**
	 * Activatielogica per site.
	 *
	 * @return void
	 */
	private static function activate_single_site() {
		self::create_log_table();

		if ( false === get_option( VIEW_PRIVACY_OPTION_SETTINGS ) ) {
			add_option( VIEW_PRIVACY_OPTION_SETTINGS, self::default_settings() );
		}

		if ( false === get_option( VIEW_PRIVACY_OPTION_CONSENT_VERSION ) ) {
			add_option( VIEW_PRIVACY_OPTION_CONSENT_VERSION, VIEW_PRIVACY_CONSENT_VERSION );
		}

		update_option( 'view_privacy_activated_version', VIEW_PRIVACY_VERSION );
	}

	/** Initialise a site created after network activation. */
	public static function initialize_new_site( $new_site, $args ) {
		if ( ! self::is_network_active() ) {
			return;
		}
		switch_to_blog( (int) $new_site->blog_id );
		self::activate_single_site();
		restore_current_blog();
	}

	/** Backwards-compatible hook for WordPress versions that still emit it. */
	public static function initialize_legacy_new_site( $blog_id ) {
		if ( ! self::is_network_active() ) {
			return;
		}
		switch_to_blog( (int) $blog_id );
		self::activate_single_site();
		restore_current_blog();
	}

	/** @return bool */
	private static function is_network_active() {
		if ( ! is_multisite() ) {
			return false;
		}
		if ( function_exists( 'is_plugin_active_for_network' ) ) {
			return is_plugin_active_for_network( VIEW_PRIVACY_BASENAME );
		}
		$active = (array) get_site_option( 'active_sitewide_plugins', array() );
		return isset( $active[ VIEW_PRIVACY_BASENAME ] );
	}

	/**
	 * Eenmalige migratie bij upgrade (v3.1.2): VIEW! Privacy kreeg een eigen,
	 * verplichte "bronpagina"-instelling voor het privacybeleid, los van
	 * WordPress' eigen 'wp_page_for_privacy_policy' (zie §Polylang — die
	 * WP-optie bleek in combinatie met Polylang niet altijd betrouwbaar).
	 * Bestaande installaties krijgen hun eigen bronpagina automatisch
	 * gevuld vanuit de destijds gekoppelde WP-privacypagina, zodat de
	 * banner na deze update niet plotseling verdwijnt. Draait maar één
	 * keer per site, op basis van het opgeslagen 'view_privacy_activated_version'.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$stored_version = get_option( 'view_privacy_activated_version', '0' );

		if ( version_compare( $stored_version, VIEW_PRIVACY_VERSION, '>=' ) ) {
			return;
		}

		$settings = get_option( VIEW_PRIVACY_OPTION_SETTINGS );
		if ( is_array( $settings ) && empty( $settings['privacy_page_id'] ) ) {
			$wp_privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
			if ( $wp_privacy_page_id ) {
				$settings['privacy_page_id'] = $wp_privacy_page_id;
				update_option( VIEW_PRIVACY_OPTION_SETTINGS, $settings );
			}
		}

		// 4.0.0 accidentally stopped using these 3.x settings. Keep both the
		// stored values and their behaviour; no consent data needs migration.
		if ( is_array( $settings ) && isset( $settings['banner'] ) && is_array( $settings['banner'] ) ) {
			$changed = false;
			$legacy_defaults = array(
				'simple_mode'         => false,
				'simple_text'         => __( 'Deze site plaatst alleen functionele cookies waarvoor geen toestemming nodig is.', 'view-privacy' ),
				'simple_button_label' => __( 'Ga verder naar de site', 'view-privacy' ),
			);
			foreach ( $legacy_defaults as $key => $value ) {
				if ( ! array_key_exists( $key, $settings['banner'] ) ) {
					$settings['banner'][ $key ] = $value;
					$changed = true;
				}
			}
			if ( $changed ) {
				update_option( VIEW_PRIVACY_OPTION_SETTINGS, $settings );
			}
		}

		update_option( 'view_privacy_activated_version', VIEW_PRIVACY_VERSION );
	}

	/**
	 * Plugin-deactivatie. Verwijdert geen data, enkel opgeplande taken.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'view_privacy_prune_logs' );
	}

	/**
	 * Maak de consent-log tabel aan via dbDelta.
	 *
	 * @return void
	 */
	public static function create_log_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = $wpdb->prefix . VIEW_PRIVACY_LOG_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			consent_hash VARCHAR(64) NOT NULL,
			categories TEXT NOT NULL,
			consent_version SMALLINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			ip_hash VARCHAR(64) DEFAULT NULL,
			user_agent VARCHAR(255) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY consent_hash (consent_hash),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Standaardinstellingen van de plugin.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'privacy_page_id' => 0, // Verplichte, door VIEW! Privacy zelf beheerde bronpagina (zie §Polylang).
			'banner'     => array(
				'tagline'        => __( 'Uw privacy. Uw keuze.', 'view-privacy' ),
				'title'          => __( 'Wij respecteren uw privacy', 'view-privacy' ),
				'heading'        => '',
				'text'           => __( 'Wij gebruiken cookies om onze website te laten functioneren en, met uw toestemming, om statistieken te verzamelen en content te personaliseren. U kunt uw voorkeuren op elk moment aanpassen.', 'view-privacy' ),
				'logo'           => '',
				'reopen_icon'    => '',
				'position'       => 'left', // left only; bottom-aligned.
				'color_bg'       => '#ffffff',
				'color_text'     => '#1a1a1a',
				'color_accent'   => '#0b6e4f',
				'button_primary_text'   => '#ffffff',
				'button_primary_bg'     => '#0b6e4f',
				'button_primary_border' => '#0b6e4f',
				'button_secondary_text'   => '#1a1a1a',
				'button_secondary_bg'     => '#ffffff',
				'button_secondary_border' => '#0b6e4f',
				'font_size'      => '15',
				'border_radius'  => '12',
				'shadow'         => true,
				'animation'      => true,
				'use_theme_font' => true,
				'simple_mode'         => false,
				'simple_text'         => __( 'Deze site plaatst alleen functionele cookies waarvoor geen toestemming nodig is.', 'view-privacy' ),
				'simple_button_label' => __( 'Ga verder naar de site', 'view-privacy' ),
				'button_accept_all'   => __( 'Alles accepteren', 'view-privacy' ),
				'button_reject_all'   => __( 'Alles weigeren', 'view-privacy' ),
				'button_preferences'  => __( 'Voorkeuren', 'view-privacy' ),
				'button_save'         => __( 'Voorkeuren opslaan', 'view-privacy' ),
				'button_back'         => __( 'Terug', 'view-privacy' ),
				'button_blocked'      => __( 'Cookies aanpassen', 'view-privacy' ),
				'button_accept_service' => __( 'Cookies accepteren', 'view-privacy' ),
			),
			'categories' => array(
				'necessary'   => array(
					'label'       => __( 'Noodzakelijk', 'view-privacy' ),
					'description' => __( 'Deze cookies zijn essentieel voor het functioneren van de website en kunnen niet worden uitgeschakeld.', 'view-privacy' ),
					'locked'      => true,
					'default'     => true,
				),
				'preferences' => array(
					'label'       => __( 'Externe media', 'view-privacy' ),
					'description' => __( 'Deze cookies horen bij ingesloten content van externe partijen, zoals video\'s en kaarten.', 'view-privacy' ),
					'locked'      => false,
					'default'     => false,
				),
				'statistics'  => array(
					'label'       => __( 'Statistieken', 'view-privacy' ),
					'description' => __( 'Deze cookies helpen ons te begrijpen hoe bezoekers de website gebruiken.', 'view-privacy' ),
					'locked'      => false,
					'default'     => false,
				),
				'marketing'   => array(
					'label'       => __( 'Marketing', 'view-privacy' ),
					'description' => __( 'Deze cookies worden gebruikt om advertenties relevanter te maken voor u.', 'view-privacy' ),
					'locked'      => false,
					'default'     => false,
				),
			),
			'services'   => View_Privacy_Scripts::default_services(),
			'logging'    => array(
				'enabled'    => true,
				'retain_days' => 365,
			),
		);
	}

	/**
	 * Enqueue van publieke assets (banner CSS/JS) op de front-end.
	 *
	 * @return void
	 */
	public function enqueue_public_assets() {
		// Zonder gekoppelde privacybeleidpagina mag de banner niet actief zijn:
		// zie View_Privacy_Consent::get_privacy_policy_url().
		if ( ! View_Privacy_Consent::instance()->get_privacy_policy_url() ) {
			return;
		}

		wp_register_style(
			'view-privacy-banner',
			VIEW_PRIVACY_URL . 'public/css/consent-banner.css',
			array(),
			VIEW_PRIVACY_VERSION
		);

		wp_register_script(
			'view-privacy-banner',
			VIEW_PRIVACY_URL . 'public/js/consent-banner.js',
			array(),
			VIEW_PRIVACY_VERSION,
			true
		);

		wp_enqueue_style( 'view-privacy-banner' );
		wp_enqueue_script( 'view-privacy-banner' );

		wp_localize_script(
			'view-privacy-banner',
			'viewPrivacyConfig',
			View_Privacy_Consent::instance()->get_frontend_config()
		);
	}
}
