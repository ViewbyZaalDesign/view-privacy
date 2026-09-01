<?php
/**
 * Herbruikbare pagina-header met VIEW!-logo.
 * Verwacht variabelen: $vp_header_suffix (string, bv. "Dashboard"),
 * optioneel $vp_header_subtitle (string).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="vp-header">
	<h1 class="vp-header__title">
		<img src="<?php echo esc_url( VIEW_PRIVACY_URL . 'admin/images/view-logo.png' ); ?>" alt="<?php esc_attr_e( 'View!', 'view-privacy' ); ?>" class="vp-logo-title" />
		<?php if ( ! empty( $vp_header_suffix ) ) : ?>
			<span class="vp-header__suffix"><?php echo esc_html( $vp_header_suffix ); ?></span>
		<?php endif; ?>
	</h1>
	<?php if ( ! empty( $vp_header_subtitle ) ) : ?>
		<p class="vp-subtitle"><?php echo esc_html( $vp_header_subtitle ); ?></p>
	<?php endif; ?>
</div>
