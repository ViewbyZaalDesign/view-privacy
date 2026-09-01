<?php
/**
 * Instellingenbeheer: ophalen, opslaan en saniteren van pluginopties.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Settings
 */
class View_Privacy_Settings {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Settings|null
	 */
	private static $instance = null;

	/**
	 * Cache van instellingen binnen het request.
	 *
	 * @var array|null
	 */
	private $settings_cache = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Settings
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
	private function __construct() {}

	/**
	 * Haal alle instellingen op, samengevoegd met de standaardwaarden.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}

		$defaults = View_Privacy_Core::default_settings();
		$saved    = get_option( VIEW_PRIVACY_OPTION_SETTINGS, array() );

		$this->settings_cache = $this->deep_merge( $defaults, is_array( $saved ) ? $saved : array() );

		return $this->settings_cache;
	}

	/**
	 * Sla nieuwe instellingen op na sanitizing. Verhoogt consentversie
	 * automatisch als categorieën of bannerinhoud wijzigen.
	 *
	 * @param array $input Ruwe input, doorgaans uit $_POST.
	 * @return bool|WP_Error
	 */
	public function save_settings( $input ) {
		$current    = $this->get_settings();
		$sanitized  = $this->sanitize( $input, $current );
		$needs_bump = $this->requires_consent_bump( $current, $sanitized );

		$updated = update_option( VIEW_PRIVACY_OPTION_SETTINGS, $sanitized );

		// update_option geeft false terug als de waarde niet gewijzigd is; dat is geen fout.
		$this->settings_cache = $sanitized;

		if ( $needs_bump ) {
			$version = (int) get_option( VIEW_PRIVACY_OPTION_CONSENT_VERSION, 1 );
			update_option( VIEW_PRIVACY_OPTION_CONSENT_VERSION, $version + 1 );
		}

		return true;
	}

	/**
	 * Bepaal of wijzigingen in categorieën of bannertekst een nieuwe
	 * consentversie vereisen (§14 Herziening).
	 *
	 * @param array $old Oude instellingen.
	 * @param array $new Nieuwe instellingen.
	 * @return bool
	 */
	private function requires_consent_bump( $old, $new ) {
		$old_categories = wp_json_encode( $old['categories'] ?? array() );
		$new_categories = wp_json_encode( $new['categories'] ?? array() );

		$old_banner_text = ( $old['banner']['text'] ?? '' ) . ( $old['banner']['title'] ?? '' ) . ( $old['banner']['heading'] ?? '' );
		$new_banner_text = ( $new['banner']['text'] ?? '' ) . ( $new['banner']['title'] ?? '' ) . ( $new['banner']['heading'] ?? '' );
		$button_fields = array( 'button_accept_all', 'button_reject_all', 'button_preferences', 'button_save', 'button_back', 'button_blocked', 'button_accept_service' );
		foreach ( $button_fields as $button_field ) {
			$old_banner_text .= $old['banner'][ $button_field ] ?? '';
			$new_banner_text .= $new['banner'][ $button_field ] ?? '';
		}

		$old_services = wp_json_encode( $old['services'] ?? array() );
		$new_services = wp_json_encode( $new['services'] ?? array() );

		return ( $old_categories !== $new_categories )
			|| ( $old_banner_text !== $new_banner_text )
			|| ( $old_services !== $new_services );
	}

	/**
	 * Saniteer inkomende instellingen veld-voor-veld.
	 *
	 * @param array $input   Ruwe input.
	 * @param array $current Huidige (geldige) instellingen als fallback.
	 * @return array
	 */
	public function sanitize( $input, $current ) {
		$clean = $current;

		if ( isset( $input['privacy_page_id'] ) ) {
			$posted_page_id = (int) $input['privacy_page_id'];
			$clean['privacy_page_id'] = ( $posted_page_id && 'page' === get_post_type( $posted_page_id ) && 'publish' === get_post_status( $posted_page_id ) )
				? $posted_page_id
				: 0;
		}

		if ( isset( $input['banner'] ) && is_array( $input['banner'] ) ) {
			$b = $input['banner'];

			$clean['banner']['tagline']        = isset( $b['tagline'] ) ? sanitize_text_field( wp_unslash( $b['tagline'] ) ) : $current['banner']['tagline'];
			$clean['banner']['title']          = isset( $b['title'] ) ? sanitize_text_field( wp_unslash( $b['title'] ) ) : $current['banner']['title'];
			$clean['banner']['heading']        = isset( $b['heading'] ) ? sanitize_text_field( wp_unslash( $b['heading'] ) ) : ( $current['banner']['heading'] ?? '' );
			$clean['banner']['text']           = isset( $b['text'] ) ? sanitize_textarea_field( wp_unslash( $b['text'] ) ) : $current['banner']['text'];
			// Logo is niet instelbaar: blijft altijd het vaste VIEW!-beeldmerk, ongeacht binnenkomende input.
			$clean['banner']['logo']           = $current['banner']['logo'];
			$clean['banner']['reopen_icon']    = isset( $b['reopen_icon'] ) ? esc_url_raw( wp_unslash( $b['reopen_icon'] ) ) : $current['banner']['reopen_icon'];
			$clean['banner']['position']       = 'left';
			$clean['banner']['color_bg']       = isset( $b['color_bg'] ) ? sanitize_hex_color( $b['color_bg'] ) : $current['banner']['color_bg'];
			$clean['banner']['color_text']     = isset( $b['color_text'] ) ? sanitize_hex_color( $b['color_text'] ) : $current['banner']['color_text'];
			$clean['banner']['color_accent']   = isset( $b['color_accent'] ) ? sanitize_hex_color( $b['color_accent'] ) : $current['banner']['color_accent'];
			$clean['banner']['button_primary_text']   = isset( $b['button_primary_text'] ) ? sanitize_hex_color( $b['button_primary_text'] ) : $current['banner']['button_primary_text'];
			$clean['banner']['button_primary_bg']     = isset( $b['button_primary_bg'] ) ? sanitize_hex_color( $b['button_primary_bg'] ) : $current['banner']['button_primary_bg'];
			$clean['banner']['button_primary_border'] = isset( $b['button_primary_border'] ) ? sanitize_hex_color( $b['button_primary_border'] ) : $current['banner']['button_primary_border'];
			$clean['banner']['button_secondary_text']   = isset( $b['button_secondary_text'] ) ? sanitize_hex_color( $b['button_secondary_text'] ) : $current['banner']['button_secondary_text'];
			$clean['banner']['button_secondary_bg']     = isset( $b['button_secondary_bg'] ) ? sanitize_hex_color( $b['button_secondary_bg'] ) : $current['banner']['button_secondary_bg'];
			$clean['banner']['button_secondary_border'] = isset( $b['button_secondary_border'] ) ? sanitize_hex_color( $b['button_secondary_border'] ) : $current['banner']['button_secondary_border'];
			$clean['banner']['font_size']      = isset( $b['font_size'] ) ? max( 10, min( 24, (int) $b['font_size'] ) ) : $current['banner']['font_size'];
			$clean['banner']['border_radius']  = isset( $b['border_radius'] ) ? max( 0, min( 40, (int) $b['border_radius'] ) ) : $current['banner']['border_radius'];
			$clean['banner']['shadow']         = ! empty( $b['shadow'] );
			$clean['banner']['animation']      = ! empty( $b['animation'] );
			$clean['banner']['use_theme_font'] = ! empty( $b['use_theme_font'] );
			// Deze drie velden zijn sinds 3.x publiek ondersteund; behoud ze
			// expliciet voor bestaande installaties en imports.
			$clean['banner']['simple_mode']         = ! empty( $b['simple_mode'] );
			$clean['banner']['simple_text']         = isset( $b['simple_text'] ) ? sanitize_textarea_field( wp_unslash( $b['simple_text'] ) ) : $current['banner']['simple_text'];
			$clean['banner']['simple_button_label'] = isset( $b['simple_button_label'] ) ? sanitize_text_field( wp_unslash( $b['simple_button_label'] ) ) : $current['banner']['simple_button_label'];
			$button_fields = array( 'button_accept_all', 'button_reject_all', 'button_preferences', 'button_save', 'button_back', 'button_blocked', 'button_accept_service' );
			foreach ( $button_fields as $button_field ) {
				$clean['banner'][ $button_field ] = isset( $b[ $button_field ] ) ? sanitize_text_field( wp_unslash( $b[ $button_field ] ) ) : $current['banner'][ $button_field ];
			}
		}

		if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
			foreach ( $current['categories'] as $key => $cat ) {
				if ( ! isset( $input['categories'][ $key ] ) ) {
					continue;
				}
				$incoming = $input['categories'][ $key ];

				$clean['categories'][ $key ]['label']       = isset( $incoming['label'] ) ? sanitize_text_field( wp_unslash( $incoming['label'] ) ) : $cat['label'];
				$clean['categories'][ $key ]['description'] = isset( $incoming['description'] ) ? sanitize_textarea_field( wp_unslash( $incoming['description'] ) ) : $cat['description'];

				// 'necessary' blijft altijd verplicht/actief; niet aanpasbaar.
				if ( 'necessary' !== $key ) {
					$clean['categories'][ $key ]['default'] = ! empty( $incoming['default'] );
				}
			}

			// Vrije, door de gebruiker toegevoegde categorieën toestaan (max 10 extra, voorkomt misbruik).
			$existing_keys = array_keys( $current['categories'] );
			$custom_count  = 0;
			foreach ( $input['categories'] as $key => $incoming ) {
				if ( in_array( $key, $existing_keys, true ) || $custom_count >= 10 ) {
					continue;
				}
				$key = sanitize_key( $key );
				if ( empty( $key ) ) {
					continue;
				}
				$clean['categories'][ $key ] = array(
					'label'       => isset( $incoming['label'] ) ? sanitize_text_field( wp_unslash( $incoming['label'] ) ) : ucfirst( $key ),
					'description' => isset( $incoming['description'] ) ? sanitize_textarea_field( wp_unslash( $incoming['description'] ) ) : '',
					'locked'      => false,
					'default'     => false,
				);
				++$custom_count;
			}
		}

		if ( isset( $input['services'] ) && is_array( $input['services'] ) ) {
			$clean['services'] = View_Privacy_Scripts::sanitize_services( $input['services'], $current['services'], array_keys( $clean['categories'] ) );
		}

		if ( isset( $input['logging'] ) && is_array( $input['logging'] ) ) {
			$clean['logging']['enabled']      = ! empty( $input['logging']['enabled'] );
			$clean['logging']['retain_days']  = isset( $input['logging']['retain_days'] ) ? max( 1, min( 3650, (int) $input['logging']['retain_days'] ) ) : $current['logging']['retain_days'];
		}

		return $clean;
	}

	/**
	 * Recursieve merge waarbij $override voorrang krijgt op $base, maar de
	 * sleutelstructuur van $base leidend blijft voor niet-aanwezige waarden.
	 *
	 * @param array $base     Basisarray (standaardinstellingen).
	 * @param array $override Overschrijvende array (opgeslagen instellingen).
	 * @return array
	 */
	private function deep_merge( $base, $override ) {
		foreach ( $override as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = $this->deep_merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	/**
	 * Reset instellingen naar fabrieksstandaard.
	 *
	 * @return void
	 */
	public function reset_to_defaults() {
		$this->settings_cache = View_Privacy_Core::default_settings();
		update_option( VIEW_PRIVACY_OPTION_SETTINGS, $this->settings_cache );
	}
}
