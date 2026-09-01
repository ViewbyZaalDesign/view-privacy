<?php
/** Minimal dependency-free regression tests for the external update contract. */
define( 'ABSPATH', __DIR__ );
define( 'VIEW_PRIVACY_VERSION', '4.0.1' );
define( 'VIEW_PRIVACY_REQUIRES_WP', '6.8' );
define( 'VIEW_PRIVACY_REQUIRES_PHP', '8.1' );
define( 'VIEW_PRIVACY_TESTED_WP', '6.8' );
define( 'VIEW_PRIVACY_UPDATE_URI', 'https://github.com/ViewbyZaalDesign/view-privacy' );
define( 'VIEW_PRIVACY_RELEASE_ASSET_PREFIX', 'view-privacy-' );
define( 'VIEW_PRIVACY_BASENAME', 'view-privacy/view-privacy.php' );
define( 'DAY_IN_SECONDS', 86400 );

$transients = array();
$remote_release = array();
function add_filter() {}
function add_action() {}
function get_site_transient( $key ) { global $transients; return $transients[ $key ] ?? false; }
function set_site_transient( $key, $value ) { global $transients; $transients[ $key ] = $value; }
function delete_site_transient( $key ) { global $transients; unset( $transients[ $key ] ); }
function wp_remote_get() { global $remote_release; return array( 'code' => 200, 'body' => json_encode( $remote_release ), 'headers' => array() ); }
function is_wp_error() { return false; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_header( $response, $header ) { return $response['headers'][ $header ] ?? ''; }
function esc_url_raw( $value ) { return $value; }
function sanitize_text_field( $value ) { return $value; }
function wpautop( $value ) { return $value; }
function wp_kses_post( $value ) { return $value; }

require dirname( __DIR__ ) . '/includes/class-view-privacy-updater.php';
function expect( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } }

$remote_release = array(
	'tag_name' => 'v4.0.2', 'html_url' => 'https://example.test/release', 'published_at' => '2026-09-01T00:00:00Z',
	'assets' => array( array( 'name' => 'view-privacy-4.0.2.zip', 'content_type' => 'application/zip', 'browser_download_url' => 'https://example.test/view-privacy-4.0.2.zip' ) ),
);
$updater = View_Privacy_Updater::instance();
$result = $updater->check_for_update( (object) array() );
expect( isset( $result->response[ VIEW_PRIVACY_BASENAME ] ), 'newer release must populate response' );
$item = $result->response[ VIEW_PRIVACY_BASENAME ];
foreach ( array( 'id', 'plugin', 'new_version', 'package', 'requires', 'requires_php', 'tested', 'autoupdate' ) as $field ) { expect( property_exists( $item, $field ), "response item misses $field" ); }
expect( '4.0.2' === $item->new_version, 'release version must be parsed' );

$remote_release['tag_name'] = 'v4.0.1';
$remote_release['assets'][0]['name'] = 'view-privacy-4.0.1.zip';
$transients = array();
$result = $updater->check_for_update( (object) array( 'response' => array( VIEW_PRIVACY_BASENAME => (object) array() ) ) );
expect( isset( $result->no_update[ VIEW_PRIVACY_BASENAME ] ), 'current release must populate no_update' );
expect( ! isset( $result->response[ VIEW_PRIVACY_BASENAME ] ), 'stale response must be removed' );

$reflection = new ReflectionClass( $updater );
$version = $reflection->getMethod( 'get_release_version' );
$version->setAccessible( true );
expect( '4.0.2' === $version->invoke( $updater, array( 'tag_name' => 'v4.0.2' ) ), 'lowercase v tag must parse' );
expect( '4.0.0' === $version->invoke( $updater, array( 'tag_name' => 'V4.0.0' ) ), 'legacy uppercase tag must remain readable' );
expect( '' === $version->invoke( $updater, array( 'tag_name' => 'release-4.0.2' ) ), 'invalid tag must be rejected' );
fwrite( STDOUT, "Updater tests passed.\n" );
