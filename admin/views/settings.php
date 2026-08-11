<?php
/**
 * Instellingen-view (§7 categorieën + §12 bannerinstellingen).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = View_Privacy_Settings::instance()->get_settings();
$banner   = $settings['banner'];
?>
<div class="wrap view-privacy-wrap">
	<?php
	$vp_header_suffix = __( '— Instellingen', 'view-privacy' );
	require VIEW_PRIVACY_PATH . 'admin/views/partials/header.php';
	?>

	<form method="post" enctype="multipart/form-data" class="vp-panel">
		<?php wp_nonce_field( 'view_privacy_save_settings' ); ?>
		<input type="hidden" name="view_privacy_action" value="save_settings" />

		<h2><?php esc_html_e( 'Privacybeleid', 'view-privacy' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vp-privacy-page"><?php esc_html_e( 'Privacybeleidpagina', 'view-privacy' ); ?> <span style="color:#d63638;">*</span></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => 'view_privacy[privacy_page_id]',
							'id'                => 'vp-privacy-page',
							'selected'          => $settings['privacy_page_id'],
							'show_option_none'  => __( '— Kies een pagina —', 'view-privacy' ),
							'option_none_value' => 0,
						)
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Verplicht: zonder een gekozen privacybeleidpagina blijft de cookiebanner uitgeschakeld. Dit is de eigen, door VIEW! Privacy beheerde instelling — onafhankelijk van WordPress → Instellingen → Privacy.', 'view-privacy' ); ?>
					</p>
					<?php if ( function_exists( 'pll_current_language' ) ) : ?>
						<p class="description">
							<?php esc_html_e( 'Polylang gedetecteerd: kies hier de brontaal-pagina. VIEW! Privacy zoekt automatisch de Polylang-vertaling voor de taal die de bezoeker bekijkt; ontbreekt die vertaling, dan wordt teruggevallen op deze bronpagina en verschijnt hierboven op het dashboard een waarschuwing.', 'view-privacy' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Banner', 'view-privacy' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Alleen functionele cookies', 'view-privacy' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="vp-simple-mode" name="view_privacy[banner][simple_mode]" value="1" <?php checked( ! empty( $banner['simple_mode'] ) ); ?> />
						<?php esc_html_e( 'Deze site plaatst uitsluitend noodzakelijke/functionele cookies; toon een eenvoudige melding zonder toestemmingsvraag in plaats van de volledige banner.', 'view-privacy' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Gebruik dit alleen als u zeker weet dat er geen statistiek-, voorkeur- of marketingcookies (zoals Google Analytics, advertentiepixels of embeds die cookies plaatsen) actief zijn. Controleer dit bij twijfel via het tabblad Diensten.', 'view-privacy' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-simple-text"><?php esc_html_e( 'Tekst bij "Alleen functionele cookies"', 'view-privacy' ); ?></label></th>
				<td><textarea id="vp-simple-text" class="large-text" rows="2" name="view_privacy[banner][simple_text]"><?php echo esc_textarea( $banner['simple_text'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-simple-button"><?php esc_html_e( 'Knoptekst', 'view-privacy' ); ?></label></th>
				<td><input type="text" id="vp-simple-button" class="regular-text" name="view_privacy[banner][simple_button_label]" value="<?php echo esc_attr( $banner['simple_button_label'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-tagline"><?php esc_html_e( 'Tagline (boven de titel)', 'view-privacy' ); ?></label></th>
				<td>
					<input type="text" id="vp-tagline" class="regular-text" name="view_privacy[banner][tagline]" value="<?php echo esc_attr( $banner['tagline'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Korte, vriendelijke regel boven de titel. Laat leeg om te verbergen.', 'view-privacy' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-title"><?php esc_html_e( 'Titel', 'view-privacy' ); ?></label></th>
				<td><input type="text" id="vp-title" class="regular-text" name="view_privacy[banner][title]" value="<?php echo esc_attr( $banner['title'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-text"><?php esc_html_e( 'Bannertekst', 'view-privacy' ); ?></label></th>
				<td><textarea id="vp-text" class="large-text" rows="4" name="view_privacy[banner][text]"><?php echo esc_textarea( $banner['text'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-position"><?php esc_html_e( 'Positie', 'view-privacy' ); ?></label></th>
				<td>
					<select id="vp-position" name="view_privacy[banner][position]">
						<option value="bottom" <?php selected( $banner['position'], 'bottom' ); ?>><?php esc_html_e( 'Onderaan', 'view-privacy' ); ?></option>
						<option value="top" <?php selected( $banner['position'], 'top' ); ?>><?php esc_html_e( 'Bovenaan', 'view-privacy' ); ?></option>
						<option value="center" <?php selected( $banner['position'], 'center' ); ?>><?php esc_html_e( 'Gecentreerd (modaal)', 'view-privacy' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Kleuren', 'view-privacy' ); ?></th>
				<td>
					<label><?php esc_html_e( 'Achtergrond', 'view-privacy' ); ?> <input type="text" class="vp-color-field" name="view_privacy[banner][color_bg]" value="<?php echo esc_attr( $banner['color_bg'] ); ?>" /></label><br />
					<label><?php esc_html_e( 'Tekst', 'view-privacy' ); ?> <input type="text" class="vp-color-field" name="view_privacy[banner][color_text]" value="<?php echo esc_attr( $banner['color_text'] ); ?>" /></label><br />
					<label><?php esc_html_e( 'Accent (knoppen)', 'view-privacy' ); ?> <input type="text" class="vp-color-field" name="view_privacy[banner][color_accent]" value="<?php echo esc_attr( $banner['color_accent'] ); ?>" /></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-font-size"><?php esc_html_e( 'Lettergrootte (px)', 'view-privacy' ); ?></label></th>
				<td><input type="number" id="vp-font-size" min="10" max="24" name="view_privacy[banner][font_size]" value="<?php echo esc_attr( $banner['font_size'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="vp-border-radius"><?php esc_html_e( 'Border-radius (px)', 'view-privacy' ); ?></label></th>
				<td><input type="number" id="vp-border-radius" min="0" max="40" name="view_privacy[banner][border_radius]" value="<?php echo esc_attr( $banner['border_radius'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Overig', 'view-privacy' ); ?></th>
				<td>
					<label><input type="checkbox" name="view_privacy[banner][shadow]" value="1" <?php checked( $banner['shadow'] ); ?> /> <?php esc_html_e( 'Schaduw', 'view-privacy' ); ?></label><br />
					<label><input type="checkbox" name="view_privacy[banner][animation]" value="1" <?php checked( $banner['animation'] ); ?> /> <?php esc_html_e( 'Subtiele fade-in animatie', 'view-privacy' ); ?></label><br />
					<label><input type="checkbox" name="view_privacy[banner][use_theme_font]" value="1" <?php checked( $banner['use_theme_font'] ); ?> /> <?php esc_html_e( 'Lettertype van actief thema overnemen', 'view-privacy' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Herstelknop-afbeelding', 'view-privacy' ); ?></th>
				<td>
					<p class="description"><?php esc_html_e( 'Standaard verschijnt de stroopwafel-foto naast de herstelknop. U kunt hier optioneel een eigen afbeelding uploaden. Gebruik een geschikte, herkenbare afbeelding (bij voorkeur vierkant); ter bescherming van de laadtijd wordt automatisch de kleine, door WordPress gegenereerde miniatuurversie gebruikt, ongeacht de grootte van het geüploade bestand.', 'view-privacy' ); ?></p>
					<div id="vp-reopen-icon-preview" style="margin: 10px 0;">
						<?php if ( ! empty( $banner['reopen_icon'] ) ) : ?>
							<img src="<?php echo esc_url( $banner['reopen_icon'] ); ?>" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:50%;background:#f5f4f4;" />
						<?php else : ?>
							<img src="<?php echo esc_url( VIEW_PRIVACY_URL . 'public/images/stroopwafel.png' ); ?>" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:50%;background:#f5f4f4;" />
							<span class="vp-badge vp-badge-neutral"><?php esc_html_e( 'Standaardafbeelding', 'view-privacy' ); ?></span>
						<?php endif; ?>
					</div>
					<input type="hidden" id="vp-reopen-icon-url" name="view_privacy[banner][reopen_icon]" value="<?php echo esc_attr( $banner['reopen_icon'] ); ?>" />
					<button
						type="button"
						class="button"
						id="vp-reopen-icon-upload"
						data-title="<?php echo esc_attr__( 'Kies een afbeelding voor de herstelknop', 'view-privacy' ); ?>"
						data-button="<?php echo esc_attr__( 'Gebruiken', 'view-privacy' ); ?>"
						data-default-url="<?php echo esc_attr( VIEW_PRIVACY_URL . 'public/images/stroopwafel.png' ); ?>"
						data-default-badge="<?php echo esc_attr__( 'Standaardafbeelding', 'view-privacy' ); ?>"
					><?php esc_html_e( 'Eigen afbeelding uploaden', 'view-privacy' ); ?></button>
					<button type="button" class="button" id="vp-reopen-icon-reset" <?php echo empty( $banner['reopen_icon'] ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Terug naar standaard (stroopwafel)', 'view-privacy' ); ?></button>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Cookiecategorieën', 'view-privacy' ); ?></h2>
		<p class="description"><?php esc_html_e( 'De categorie "Noodzakelijk" is altijd actief en kan niet worden uitgeschakeld door bezoekers.', 'view-privacy' ); ?></p>
		<table class="widefat striped vp-categories-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Categorie', 'view-privacy' ); ?></th>
					<th><?php esc_html_e( 'Naam', 'view-privacy' ); ?></th>
					<th><?php esc_html_e( 'Beschrijving', 'view-privacy' ); ?></th>
					<th><?php esc_html_e( 'Standaard aan', 'view-privacy' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $settings['categories'] as $key => $cat ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code><?php if ( ! empty( $cat['locked'] ) ) : ?> <span class="vp-badge vp-badge-neutral"><?php esc_html_e( 'vergrendeld', 'view-privacy' ); ?></span><?php endif; ?></td>
						<td><input type="text" class="regular-text" name="view_privacy[categories][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $cat['label'] ); ?>" /></td>
						<td><textarea class="large-text" rows="2" name="view_privacy[categories][<?php echo esc_attr( $key ); ?>][description]"><?php echo esc_textarea( $cat['description'] ); ?></textarea></td>
						<td>
							<?php if ( ! empty( $cat['locked'] ) ) : ?>
								<input type="checkbox" checked disabled />
							<?php else : ?>
								<input type="checkbox" name="view_privacy[categories][<?php echo esc_attr( $key ); ?>][default]" value="1" <?php checked( ! empty( $cat['default'] ) ); ?> />
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Logging', 'view-privacy' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Toestemming registreren', 'view-privacy' ); ?></th>
				<td>
					<label><input type="checkbox" name="view_privacy[logging][enabled]" value="1" <?php checked( ! empty( $settings['logging']['enabled'] ) ); ?> /> <?php esc_html_e( 'Registreer datum/tijd en consentversie bij elke keuze (§13)', 'view-privacy' ); ?></label>
					<p class="description"><?php esc_html_e( 'Logregels worden automatisch na 365 dagen verwijderd.', 'view-privacy' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Instellingen opslaan', 'view-privacy' ); ?></button>
		</p>
	</form>

	<form method="post" class="vp-panel vp-danger-zone" onsubmit="return confirm('<?php echo esc_js( __( 'Weet u zeker dat u alle instellingen wilt terugzetten naar de standaardwaarden?', 'view-privacy' ) ); ?>');">
		<?php wp_nonce_field( 'view_privacy_reset_settings' ); ?>
		<input type="hidden" name="view_privacy_action" value="reset_settings" />
		<h2><?php esc_html_e( 'Terugzetten', 'view-privacy' ); ?></h2>
		<p><?php esc_html_e( 'Zet alle VIEW! Privacy-instellingen terug naar de fabrieksstandaard.', 'view-privacy' ); ?></p>
		<button type="submit" class="button button-secondary"><?php esc_html_e( 'Standaardwaarden herstellen', 'view-privacy' ); ?></button>
	</form>
</div>
