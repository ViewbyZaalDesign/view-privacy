<?php
/**
 * Dashboard-view (§11).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings        = View_Privacy_Settings::instance()->get_settings();
$consent_version = View_Privacy_Consent::instance()->get_consent_version();
$log_count       = View_Privacy_Logging::instance()->get_log_count();
$privacy_policy_url = View_Privacy_Consent::instance()->get_privacy_policy_url();
$scan_data       = View_Privacy_Scripts::instance()->get_scan_data();
$last_scan       = (int) $scan_data['last_scan'];
$detected        = $scan_data['services'];

$active_services = array_filter(
	$settings['services'],
	function ( $s ) {
		return ! empty( $s['enabled'] );
	}
);

/**
 * Bepaal de status ('success' | 'warning' | 'neutral') van een dienstgroep:
 * - neutral: geen van de bijbehorende diensten is geactiveerd.
 * - success: geactiveerd én daadwerkelijk aangetroffen bij de laatste scan.
 * - warning: geactiveerd, maar nog niet aangetroffen (kan kloppen als de
 *   dienst simpelweg nergens op de site wordt gebruikt, of duidt op een
 *   verouderde/ontbrekende scan).
 *
 * @param array $service_keys Sleutels uit $settings['services'] om te controleren.
 * @return string
 */
$vp_group_status = function ( $service_keys ) use ( $settings, $detected ) {
	$enabled = false;
	foreach ( $service_keys as $k ) {
		if ( ! empty( $settings['services'][ $k ]['enabled'] ) ) {
			$enabled = true;
			break;
		}
	}
	if ( ! $enabled ) {
		return 'neutral';
	}
	foreach ( $service_keys as $k ) {
		if ( ! empty( $detected[ $k ] ) ) {
			return 'success';
		}
	}
	return 'warning';
};

$status_privacy   = $privacy_policy_url ? 'success' : 'error';
$status_analytics = $vp_group_status( array( 'google_analytics', 'google_tag_manager' ) );
$status_youtube   = $vp_group_status( array( 'youtube' ) );
$status_maps      = $vp_group_status( array( 'google_maps' ) );

$scan_age_days = $last_scan ? floor( ( time() - $last_scan ) / DAY_IN_SECONDS ) : null;
if ( ! $last_scan ) {
	$status_scan = 'neutral';
} elseif ( $scan_age_days <= 7 ) {
	$status_scan = 'success';
} elseif ( $scan_age_days <= 30 ) {
	$status_scan = 'warning';
} else {
	$status_scan = 'warning';
}

$vp_badge_class = array(
	'success' => 'vp-badge-success',
	'warning' => 'vp-badge-warning',
	'error'   => 'vp-badge-error',
	'neutral' => 'vp-badge-neutral',
);

$vp_status_label = function ( $status, $on, $off_enabled, $off_disabled ) {
	if ( 'success' === $status ) {
		return $on;
	}
	if ( 'warning' === $status ) {
		return $off_enabled;
	}
	return $off_disabled;
};

// Banner is alleen actief mét gekoppelde privacybeleidpagina (zie
// View_Privacy_Core::enqueue_public_assets() en View_Privacy_Scripts::start_buffer()).
$status_banner_active = $privacy_policy_url ? 'success' : 'error';

// "Diensten beveiligd": groen als alle geactiveerde diensten ook daadwerkelijk
// zijn aangetroffen bij de laatste scan (of als er simpelweg geen diensten
// actief zijn — dan is er niets te beveiligen). Anders geel.
$status_services_secured = 'success';
if ( ! empty( $active_services ) ) {
	foreach ( $active_services as $key => $service ) {
		if ( empty( $detected[ $key ] ) ) {
			$status_services_secured = 'warning';
			break;
		}
	}
}

$checks = array(
	array(
		'label' => __( 'PHP-versie', 'view-privacy' ),
		'ok'    => version_compare( PHP_VERSION, '8.1', '>=' ),
		'value' => PHP_VERSION,
	),
	array(
		'label' => __( 'WordPress-versie', 'view-privacy' ),
		'ok'    => version_compare( get_bloginfo( 'version' ), '6.8', '>=' ),
		'value' => get_bloginfo( 'version' ),
	),
	array(
		'label' => __( 'Logtabel aanwezig', 'view-privacy' ),
		'ok'    => (function () {
			global $wpdb;
			$table = $wpdb->prefix . VIEW_PRIVACY_LOG_TABLE;
			return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		})(),
		'value' => '',
	),
	array(
		'label' => __( 'REST API bereikbaar', 'view-privacy' ),
		'ok'    => ! empty( rest_url() ),
		'value' => '',
	),
);
$missing_translations = View_Privacy_Consent::instance()->get_missing_privacy_translations();
$missing_lang_names   = array();
if ( ! empty( $missing_translations ) && function_exists( 'pll_languages_list' ) ) {
	$slugs = pll_languages_list( array( 'fields' => 'slug' ) );
	$names = pll_languages_list( array( 'fields' => 'name' ) );
	if ( is_array( $slugs ) && is_array( $names ) && count( $slugs ) === count( $names ) ) {
		$missing_lang_names = array_combine( $slugs, $names );
	}
}
?>
<div class="wrap view-privacy-wrap">
	<?php
	$vp_header_suffix  = __( '— Dashboard', 'view-privacy' );
	$vp_header_subtitle = __( 'Privacy-first cookieconsent, zonder externe cloudservices.', 'view-privacy' );
	require VIEW_PRIVACY_PATH . 'admin/views/partials/header.php';
	?>

	<?php if ( ! empty( $missing_translations ) ) : ?>
		<div class="notice notice-warning inline" style="margin: 0 0 16px;">
			<p>
				<strong><?php esc_html_e( 'Privacybeleidpagina niet voor alle talen vertaald (Polylang)', 'view-privacy' ); ?></strong>
			</p>
			<p>
				<?php
				$missing_lang_labels = array_map(
					function ( $slug ) use ( $missing_lang_names ) {
						return isset( $missing_lang_names[ $slug ] ) ? $missing_lang_names[ $slug ] : strtoupper( $slug );
					},
					$missing_translations
				);
				printf(
					/* translators: %s: kommagescheiden lijst van taalnamen. */
					esc_html__( 'Voor de volgende taal/talen ontbreekt een vertaling van de ingestelde privacybeleidpagina: %s. De cookiebanner blijft zichtbaar en verwijst voorlopig naar de oorspronkelijke privacybeleidpagina. Maak de ontbrekende vertaling(en) aan via Polylang voor een correcte koppeling per taal.', 'view-privacy' ),
					esc_html( implode( ', ', $missing_lang_labels ) )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="vp-status-panel">
		<h2 class="vp-status-panel__title">VIEW! Privacy Status</h2>
		<ul class="vp-status-panel__list">
			<li>
				<span class="vp-status-dot vp-status-dot--<?php echo esc_attr( $status_banner_active ); ?>"></span>
				<?php esc_html_e( 'Banner actief', 'view-privacy' ); ?><?php echo 'error' === $status_banner_active ? ' — ' . esc_html__( 'uitgeschakeld', 'view-privacy' ) : ''; ?>
			</li>
			<li>
				<span class="vp-status-dot vp-status-dot--<?php echo esc_attr( $status_privacy ); ?>"></span>
				<?php esc_html_e( 'Privacybeleid gekoppeld', 'view-privacy' ); ?><?php echo 'error' === $status_privacy ? ' — ' . esc_html__( 'ontbreekt', 'view-privacy' ) : ''; ?>
			</li>
			<li>
				<span class="vp-status-dot vp-status-dot--<?php echo esc_attr( $status_services_secured ); ?>"></span>
				<?php esc_html_e( 'Diensten beveiligd', 'view-privacy' ); ?><?php echo 'warning' === $status_services_secured ? ' — ' . esc_html__( 'nog niet alles bevestigd', 'view-privacy' ) : ''; ?>
			</li>
			<li>
				<span class="vp-status-dot vp-status-dot--<?php echo esc_attr( $status_scan ); ?>"></span>
				<?php esc_html_e( 'Laatste controle:', 'view-privacy' ); ?>
				<?php
				if ( ! $last_scan ) {
					esc_html_e( 'nog geen scan', 'view-privacy' );
				} elseif ( 0 === (int) $scan_age_days ) {
					esc_html_e( 'vandaag', 'view-privacy' );
				} else {
					/* translators: %d: aantal dagen geleden. */
					printf( esc_html( _n( '%d dag geleden', '%d dagen geleden', $scan_age_days, 'view-privacy' ) ), (int) $scan_age_days );
				}
				?>
			</li>
		</ul>
	</div>

	<?php if ( ! $privacy_policy_url ) : ?>
		<div class="notice notice-error inline vp-notice-privacy">
			<p>
				<strong><?php esc_html_e( 'Geen privacybeleidpagina gekoppeld: de cookiebanner wordt niet getoond.', 'view-privacy' ); ?></strong>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=view-privacy-settings' ) ); ?>" class="button button-primary button-small"><?php esc_html_e( 'Nu instellen', 'view-privacy' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<div class="vp-grid">
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Privacybeleid', 'view-privacy' ); ?></span>
			<span class="vp-card-value">
				<span class="vp-badge <?php echo esc_attr( $vp_badge_class[ $status_privacy ] ); ?>">
					<?php echo esc_html( $vp_status_label( $status_privacy, __( 'Gekoppeld', 'view-privacy' ), '', __( 'Ontbreekt', 'view-privacy' ) ) ); ?>
				</span>
			</span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Analytics', 'view-privacy' ); ?></span>
			<span class="vp-card-value">
				<span class="vp-badge <?php echo esc_attr( $vp_badge_class[ $status_analytics ] ); ?>">
					<?php echo esc_html( $vp_status_label( $status_analytics, __( 'Gedetecteerd', 'view-privacy' ), __( 'Nog niet gezien', 'view-privacy' ), __( 'Niet actief', 'view-privacy' ) ) ); ?>
				</span>
			</span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'YouTube', 'view-privacy' ); ?></span>
			<span class="vp-card-value">
				<span class="vp-badge <?php echo esc_attr( $vp_badge_class[ $status_youtube ] ); ?>">
					<?php echo esc_html( $vp_status_label( $status_youtube, __( 'Gedetecteerd', 'view-privacy' ), __( 'Nog niet gezien', 'view-privacy' ), __( 'Niet actief', 'view-privacy' ) ) ); ?>
				</span>
			</span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Google Maps', 'view-privacy' ); ?></span>
			<span class="vp-card-value">
				<span class="vp-badge <?php echo esc_attr( $vp_badge_class[ $status_maps ] ); ?>">
					<?php echo esc_html( $vp_status_label( $status_maps, __( 'Gedetecteerd', 'view-privacy' ), __( 'Nog niet gezien', 'view-privacy' ), __( 'Niet actief', 'view-privacy' ) ) ); ?>
				</span>
			</span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Laatste scan', 'view-privacy' ); ?></span>
			<span class="vp-card-value">
				<span class="vp-badge <?php echo esc_attr( $vp_badge_class[ $status_scan ] ); ?>">
					<?php
					if ( ! $last_scan ) {
						esc_html_e( 'Nog geen scan', 'view-privacy' );
					} elseif ( 0 === (int) $scan_age_days ) {
						esc_html_e( 'Vandaag', 'view-privacy' );
					} else {
						/* translators: %d: aantal dagen geleden. */
						printf( esc_html( _n( '%d dag geleden', '%d dagen geleden', $scan_age_days, 'view-privacy' ) ), (int) $scan_age_days );
					}
					?>
				</span>
			</span>
		</div>
	</div>

	<div class="vp-grid">
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Bannermodus', 'view-privacy' ); ?></span>
			<span class="vp-card-value">
				<span class="vp-badge vp-badge-success"><?php esc_html_e( 'Volledig', 'view-privacy' ); ?></span>
			</span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Actieve diensten', 'view-privacy' ); ?></span>
			<span class="vp-card-value"><?php echo esc_html( count( $active_services ) ); ?></span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Consentkeuzes', 'view-privacy' ); ?></span>
			<span class="vp-card-value"><?php echo esc_html( number_format_i18n( $log_count ) ); ?></span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Consentversie', 'view-privacy' ); ?></span>
			<span class="vp-card-value">v<?php echo esc_html( $consent_version ); ?></span>
		</div>
		<div class="vp-card">
			<span class="vp-card-label"><?php esc_html_e( 'Pluginversie', 'view-privacy' ); ?></span>
			<span class="vp-card-value"><?php echo esc_html( VIEW_PRIVACY_VERSION ); ?></span>
		</div>
	</div>

	<div class="vp-panel">
		<div class="vp-inline-checks">
			<?php foreach ( $checks as $check ) : ?>
				<span class="vp-badge <?php echo $check['ok'] ? 'vp-badge-success' : 'vp-badge-error'; ?>">
					<?php echo esc_html( $check['label'] ); ?><?php echo ! empty( $check['value'] ) ? ' ' . esc_html( $check['value'] ) : ''; ?><?php echo $check['ok'] ? '' : ' — ' . esc_html__( 'controleer', 'view-privacy' ); ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="vp-panel vp-panel-quicklinks">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=view-privacy-settings' ) ); ?>"><?php esc_html_e( 'Banner &amp; instellingen', 'view-privacy' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=view-privacy-services' ) ); ?>"><?php esc_html_e( 'Diensten', 'view-privacy' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=view-privacy-logs' ) ); ?>"><?php esc_html_e( 'Logboek', 'view-privacy' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=view-privacy-import-export' ) ); ?>"><?php esc_html_e( 'Import / Export', 'view-privacy' ); ?></a>
	</div>

	<div class="vp-footer">
		<p class="vp-footer__copyright">
			<?php
			printf(
				/* translators: %d: huidig jaartal, wordt automatisch bijgewerkt. */
				esc_html__( 'Copyright %d View! by Zaal Design.', 'view-privacy' ),
				(int) current_time( 'Y' )
			);
			?>
		</p>
		<div class="vp-footer__right">
			<span class="vp-footer__version">
				<?php
				printf(
					/* translators: %s: versienummer van de plugin, bv. 2.0.0. */
					esc_html__( 'Versie %s — Stable', 'view-privacy' ),
					esc_html( VIEW_PRIVACY_VERSION )
				);
				?>
			</span>
			<div class="vp-footer__palette">
				<span class="vp-swatch" style="background:#261b62;" title="Blauw #261b62"></span>
				<span class="vp-swatch" style="background:#009c3a;" title="Groen #009c3a"></span>
				<span class="vp-swatch" style="background:#1d1d1b;" title="Zwart #1d1d1b"></span>
				<span class="vp-swatch vp-swatch--bordered" style="background:#f5f4f4;" title="Grijs #f5f4f4"></span>
			</div>
		</div>
	</div>
</div>

