<?php
/**
 * Consent-logging: registreert optioneel toestemming met datum/tijd en
 * consentversie (§13). IP-adressen worden nooit in leesbare vorm bewaard.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Logging
 */
class View_Privacy_Logging {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Logging|null
	 */
	private static $instance = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Logging
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
		if ( ! wp_next_scheduled( 'view_privacy_prune_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'view_privacy_prune_logs' );
		}
		add_action( 'view_privacy_prune_logs', array( $this, 'prune_logs' ) );
	}

	/**
	 * Registreer een consentkeuze in de database.
	 *
	 * @param array $categories Associatieve array categorie => bool.
	 * @param int   $version    Consentversie op moment van keuze.
	 * @return bool
	 */
	public function log_consent( $categories, $version ) {
		$settings = View_Privacy_Settings::instance()->get_settings();

		if ( empty( $settings['logging']['enabled'] ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . VIEW_PRIVACY_LOG_TABLE;

		$categories_json = wp_json_encode( $categories );
		$consent_hash    = hash( 'sha256', $categories_json . '|' . $version . '|' . microtime() );

		$ip     = $this->get_client_ip();
		$ip_hash = $ip ? hash( 'sha256', $ip . wp_salt( 'auth' ) ) : null;

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: null;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table,
			array(
				'consent_hash'    => $consent_hash,
				'categories'      => $categories_json,
				'consent_version' => (int) $version,
				'created_at'      => current_time( 'mysql' ),
				'ip_hash'         => $ip_hash,
				'user_agent'      => $user_agent,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Haal het (best mogelijke) IP-adres van de bezoeker op, zonder proxy-
	 * headers blindelings te vertrouwen (alleen REMOTE_ADDR, betrouwbaar).
	 *
	 * @return string
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}

	/**
	 * Verwijder logregels ouder dan de ingestelde bewaartermijn.
	 *
	 * @return void
	 */
	public function prune_logs() {
		$settings = View_Privacy_Settings::instance()->get_settings();
		$days     = isset( $settings['logging']['retain_days'] ) ? (int) $settings['logging']['retain_days'] : 365;

		global $wpdb;
		$table = $wpdb->prefix . VIEW_PRIVACY_LOG_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) )
			)
		);
	}

	/**
	 * Aantal geregistreerde consentregels (voor dashboard).
	 *
	 * @return int
	 */
	public function get_log_count() {
		global $wpdb;
		$table = $wpdb->prefix . VIEW_PRIVACY_LOG_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}
}
