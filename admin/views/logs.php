<?php
/**
 * Logboek-view (§13).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table    = $wpdb->prefix . VIEW_PRIVACY_LOG_TABLE;
$per_page = 20;
$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$offset   = ( $paged - 1 ) * $per_page;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$per_page,
		$offset
	)
);

$settings        = View_Privacy_Settings::instance()->get_settings();
$logging_enabled = ! empty( $settings['logging']['enabled'] );
?>
<div class="wrap view-privacy-wrap">
	<?php
	$vp_header_suffix = __( '— Logboek', 'view-privacy' );
	require VIEW_PRIVACY_PATH . 'admin/views/partials/header.php';
	?>

	<?php if ( ! $logging_enabled ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Logging staat momenteel uit. Ga naar Instellingen om dit in te schakelen.', 'view-privacy' ); ?></p></div>
	<?php endif; ?>

	<div class="vp-panel">
		<?php if ( empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'Nog geen consentkeuzes geregistreerd.', 'view-privacy' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Datum/tijd', 'view-privacy' ); ?></th>
						<th><?php esc_html_e( 'Consentversie', 'view-privacy' ); ?></th>
						<th><?php esc_html_e( 'Categorieën', 'view-privacy' ); ?></th>
						<th><?php esc_html_e( 'Referentie', 'view-privacy' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php $categories = json_decode( $row->categories, true ); ?>
						<tr>
							<td><?php echo esc_html( mysql2date( 'd-m-Y H:i', $row->created_at ) ); ?></td>
							<td>v<?php echo esc_html( $row->consent_version ); ?></td>
							<td>
								<?php
								if ( is_array( $categories ) ) {
									$labels = array();
									foreach ( $categories as $cat_key => $accepted ) {
										if ( $accepted ) {
											$labels[] = esc_html( $settings['categories'][ $cat_key ]['label'] ?? $cat_key );
										}
									}
									echo esc_html( implode( ', ', $labels ) );
								}
								?>
							</td>
							<td><code><?php echo esc_html( substr( $row->consent_hash, 0, 12 ) ); ?>…</code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php
			$total_pages = (int) ceil( $total / $per_page );
			if ( $total_pages > 1 ) :
				?>
				<div class="vp-pagination">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $total_pages,
								'prev_text' => __( '&laquo; Vorige', 'view-privacy' ),
								'next_text' => __( 'Volgende &raquo;', 'view-privacy' ),
							)
						)
					);
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
