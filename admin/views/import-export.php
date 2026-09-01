<?php
/**
 * Import/export-view (§15).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap view-privacy-wrap">
	<?php
	$vp_header_suffix = __( '— Import / Export', 'view-privacy' );
	require VIEW_PRIVACY_PATH . 'admin/views/partials/header.php';
	?>

	<div class="vp-panel">
		<h2><?php esc_html_e( 'Exporteren', 'view-privacy' ); ?></h2>
		<p><?php esc_html_e( 'Download alle huidige instellingen (banner, categorieën, diensten) als JSON-bestand.', 'view-privacy' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'view_privacy_export' ); ?>
			<input type="hidden" name="action" value="view_privacy_export" />
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Instellingen exporteren', 'view-privacy' ); ?></button>
		</form>
	</div>

	<div class="vp-panel">
		<h2><?php esc_html_e( 'Importeren', 'view-privacy' ); ?></h2>
		<p><?php esc_html_e( 'Upload een eerder geëxporteerd VIEW! Privacy JSON-bestand. Dit overschrijft de huidige instellingen.', 'view-privacy' ); ?></p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'view_privacy_import_settings' ); ?>
			<input type="hidden" name="view_privacy_action" value="import_settings" />
			<input type="file" name="view_privacy_import_file" accept="application/json" required />
			<p class="submit">
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Instellingen importeren', 'view-privacy' ); ?></button>
			</p>
		</form>
	</div>
</div>
