<?php
/**
 * Uninstall-routine: wordt alleen uitgevoerd wanneer de gebruiker de
 * plugin via het WordPress-adminpaneel definitief verwijdert.
 *
 * @package View_Privacy
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Verwijder alle plugin-data voor één site.
 *
 * @return void
 */
function view_privacy_uninstall_site() {
	global $wpdb;

	delete_option( 'view_privacy_settings' );
	delete_option( 'view_privacy_consent_version' );
	delete_option( 'view_privacy_activated_version' );
	delete_option( 'view_privacy_missing_translations' );
	delete_option( 'view_privacy_scan_data' );
	delete_transient( 'view_privacy_scan_lock' );

	$table = $wpdb->prefix . 'view_privacy_consent_log';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

	wp_clear_scheduled_hook( 'view_privacy_prune_logs' );
}

delete_site_transient( 'view_privacy_github_release_v2' );
delete_site_transient( 'update_plugins' );

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		view_privacy_uninstall_site();
		restore_current_blog();
	}
} else {
	view_privacy_uninstall_site();
}
