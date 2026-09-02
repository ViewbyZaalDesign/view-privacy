<?php
/**
 * Diensten-view (§8: cookiebeheer per dienst, uitbreidbaar).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = View_Privacy_Settings::instance()->get_settings();
$services   = $settings['services'];
$categories = $settings['categories'];
?>
<div class="wrap view-privacy-wrap">
	<?php
	$vp_header_suffix  = __( '— Diensten', 'view-privacy' );
	$vp_header_subtitle = __( 'Beheer welke diensten automatisch worden geblokkeerd totdat toestemming is gegeven.', 'view-privacy' );
	require VIEW_PRIVACY_PATH . 'admin/views/partials/header.php';
	?>

	<form method="post" class="vp-panel">
		<?php wp_nonce_field( 'view_privacy_save_services' ); ?>
		<input type="hidden" name="view_privacy_action" value="save_services" />

		<table class="widefat striped vp-services-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Actief', 'view-privacy' ); ?></th>
					<th><?php esc_html_e( 'Dienst', 'view-privacy' ); ?></th>
					<th><?php esc_html_e( 'Categorie', 'view-privacy' ); ?></th>
					<th><?php esc_html_e( 'Herkenningspatronen', 'view-privacy' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $services as $key => $service ) : ?>
					<tr>
						<td><input type="checkbox" name="services[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $service['enabled'] ) ); ?> /></td>
						<td>
							<strong><?php echo esc_html( $service['label'] ); ?></strong>
							<?php if ( ! empty( $service['container'] ) ) : ?>
								<br /><span class="vp-badge vp-badge-neutral"><?php esc_html_e( 'Container: laadt zodra één van de niet-noodzakelijke categorieën is toegestaan', 'view-privacy' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<select name="services[<?php echo esc_attr( $key ); ?>][category]">
								<?php foreach ( $categories as $cat_key => $cat ) : ?>
									<?php if ( 'necessary' === $cat_key ) { continue; } ?>
									<option value="<?php echo esc_attr( $cat_key ); ?>" <?php selected( $service['category'], $cat_key ); ?>><?php echo esc_html( $cat['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><code><?php echo esc_html( implode( ', ', $service['patterns'] ) ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Eigen dienst toevoegen', 'view-privacy' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Voeg een dienst toe die niet in de standaardlijst staat. Eén herkenningspatroon per regel (bv. een domeinnaam die in de scripturl voorkomt).', 'view-privacy' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vp-new-service-key"><?php esc_html_e( 'Sleutel (uniek, kleine letters, geen spaties)', 'view-privacy' ); ?></label></th>
				<td><input type="text" id="vp-new-service-key" name="vp_new_service_key" class="regular-text" placeholder="bijv. mijn_dienst" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-new-service-label"><?php esc_html_e( 'Naam', 'view-privacy' ); ?></label></th>
				<td><input type="text" id="vp-new-service-label" name="vp_new_service[label]" class="regular-text" placeholder="bijv. Matomo" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-new-service-category"><?php esc_html_e( 'Categorie', 'view-privacy' ); ?></label></th>
				<td><select id="vp-new-service-category" name="vp_new_service[category]"><?php foreach ( $categories as $cat_key => $cat ) : if ( 'necessary' !== $cat_key ) : ?><option value="<?php echo esc_attr( $cat_key ); ?>"><?php echo esc_html( $cat['label'] ); ?></option><?php endif; endforeach; ?></select></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-new-service-patterns"><?php esc_html_e( 'Herkenningspatronen', 'view-privacy' ); ?></label></th>
				<td><textarea id="vp-new-service-patterns" name="vp_new_service[patterns]" class="large-text" rows="3" placeholder="example.com/script.js"></textarea><p class="description"><?php esc_html_e( 'Eén patroon per regel.', 'view-privacy' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Actief', 'view-privacy' ); ?></th>
				<td><label><input type="checkbox" name="vp_new_service[enabled]" value="1" checked /> <?php esc_html_e( 'Direct inschakelen', 'view-privacy' ); ?></label></td>
			</tr>
		</table>
		<p class="description"><?php esc_html_e( 'Sla het formulier op en de nieuwe dienst verschijnt hierboven om verder te configureren.', 'view-privacy' ); ?></p>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Diensten opslaan', 'view-privacy' ); ?></button>
		</p>
	</form>

	<div class="vp-panel">
		<h2><?php esc_html_e( 'Voor ontwikkelaars', 'view-privacy' ); ?></h2>
		<p><?php esc_html_e( 'Eigen diensten kunnen ook programmatisch worden toegevoegd via het filter view_privacy_services, en consent kan serverside gecontroleerd worden met:', 'view-privacy' ); ?></p>
		<pre class="vp-code-block">if ( view_privacy_has_consent( 'statistics' ) ) {
    // Laad statistiekscript.
}</pre>
	</div>
</div>
