<?php
/**
 * Plugin Name:       VIEW! Privacy
 * Plugin URI:         https://example.com/view-privacy
 * Description:        Lichtgewicht, privacy-first cookieconsent-plugin voor WordPress. Ontwikkeld met als uitgangspunt de AVG/GDPR- en ePrivacy-regelgeving, zonder externe cloudservices of abonnementen.
 * Version:            3.1.6
 * Requires at least:  6.8
 * Requires PHP:       8.1
 * Author:             VIEW!
 * Author URI:         https://example.com
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        view-privacy
 * Domain Path:        /languages
 * Network:            true
 *
 * @package View_Privacy
 */

// Directe toegang blokkeren.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --------------------------------------------------------------------
// Constants
// --------------------------------------------------------------------

define( 'VIEW_PRIVACY_VERSION', '3.1.6' );
define( 'VIEW_PRIVACY_CONSENT_VERSION', 1 ); // Ophogen bij wijziging van categorieën/banner om herconsent te forceren.
define( 'VIEW_PRIVACY_FILE', __FILE__ );
define( 'VIEW_PRIVACY_PATH', plugin_dir_path( __FILE__ ) );
define( 'VIEW_PRIVACY_URL', plugin_dir_url( __FILE__ ) );
define( 'VIEW_PRIVACY_BASENAME', plugin_basename( __FILE__ ) );
define( 'VIEW_PRIVACY_OPTION_SETTINGS', 'view_privacy_settings' );
define( 'VIEW_PRIVACY_OPTION_CONSENT_VERSION', 'view_privacy_consent_version' );
define( 'VIEW_PRIVACY_LOG_TABLE', 'view_privacy_consent_log' );

// --------------------------------------------------------------------
// Minimale eisen controleren
// --------------------------------------------------------------------

/**
 * Controleer of aan de minimale PHP-versie wordt voldaan.
 * Voorkomt fatale errors op oudere servers.
 *
 * @return bool
 */
function view_privacy_php_version_ok() {
	return version_compare( PHP_VERSION, '8.1', '>=' );
}

if ( ! view_privacy_php_version_ok() ) {
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'VIEW! Privacy vereist PHP 8.1 of hoger. De plugin is niet geactiveerd.', 'view-privacy' )
			);
		}
	);
	return;
}

// --------------------------------------------------------------------
// Autoloading van classes
// --------------------------------------------------------------------

require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-core.php';
require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-settings.php';
require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-consent.php';
require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-scripts.php';
require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-logging.php';
require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-rest.php';
require_once VIEW_PRIVACY_PATH . 'includes/class-view-privacy-import-export.php';

if ( is_admin() ) {
	require_once VIEW_PRIVACY_PATH . 'admin/class-view-privacy-admin.php';
}

// --------------------------------------------------------------------
// Activatie / deactivatie / uninstall
// --------------------------------------------------------------------

register_activation_hook( VIEW_PRIVACY_FILE, array( 'View_Privacy_Core', 'activate' ) );
register_deactivation_hook( VIEW_PRIVACY_FILE, array( 'View_Privacy_Core', 'deactivate' ) );

// --------------------------------------------------------------------
// Plugin initialiseren
// --------------------------------------------------------------------

/**
 * Start de plugin nadat alle plugins geladen zijn.
 *
 * @return void
 */
function view_privacy_init() {
	load_plugin_textdomain( 'view-privacy', false, dirname( VIEW_PRIVACY_BASENAME ) . '/languages' );

	View_Privacy_Core::instance();
	View_Privacy_Settings::instance();
	View_Privacy_Consent::instance();
	View_Privacy_Scripts::instance();
	View_Privacy_Logging::instance();
	View_Privacy_REST::instance();
	View_Privacy_Import_Export::instance();

	if ( is_admin() ) {
		View_Privacy_Admin::instance();
	}
}
add_action( 'plugins_loaded', 'view_privacy_init' );

/**
 * Toont een link naar de instellingenpagina op het pluginoverzicht.
 *
 * @param array $links Bestaande actielinks.
 * @return array
 */
function view_privacy_settings_link( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=view-privacy-settings' ) ),
		esc_html__( 'Instellingen', 'view-privacy' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . VIEW_PRIVACY_BASENAME, 'view_privacy_settings_link' );
