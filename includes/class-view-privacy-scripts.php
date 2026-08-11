<?php
/**
 * Scriptblokkering: blokkeert automatisch inline scripts, externe scripts
 * en iframes/embeds totdat toestemming is gegeven voor de bijbehorende
 * categorie. Werkt via output buffering + DOM-vervanging aan de front-end.
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Scripts
 */
class View_Privacy_Scripts {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Scripts|null
	 */
	private static $instance = null;

	/**
	 * Sleutels van diensten die tijdens dit request daadwerkelijk zijn
	 * gematcht (en dus geblokkeerd) in de uitgaande HTML. Gebruikt om na
	 * afloop van het request de scanstatus voor het dashboard bij te werken.
	 *
	 * @var array<string,bool>
	 */
	private $detected_this_request = array();

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Scripts
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
	private function __construct() {
		// Output buffering aanzetten zodat we HTML kunnen herschrijven vóór verzending.
		add_action( 'template_redirect', array( $this, 'start_buffer' ), 0 );
		add_action( 'shutdown', array( $this, 'end_buffer' ), 0 );

		// Google Consent Mode v2: default-signalen zo vroeg mogelijk in <head>,
		// vóór GTM/GA4 (die zelf al standaard geblokkeerd worden, maar mochten
		// tags via een niet door VIEW! herkende weg toch laden, luisteren ze
		// hierdoor alsnog naar de juiste standaardstatus).
		add_action( 'wp_head', array( $this, 'print_consent_mode_defaults' ), 0 );
	}

	/**
	 * Print de Google Consent Mode v2 default-status. Alles staat standaard
	 * op 'denied' behalve de functionele/beveiligingscategorie, die geen
	 * toestemming vereist. De daadwerkelijke update naar de keuze van de
	 * bezoeker gebeurt client-side in consent-banner.js zodra er consent is.
	 *
	 * @return void
	 */
	public function print_consent_mode_defaults() {
		if ( is_admin() ) {
			return;
		}

		$services  = View_Privacy_Settings::instance()->get_settings()['services'];
		$has_google = false;
		foreach ( $services as $service ) {
			if ( empty( $service['enabled'] ) ) {
				continue;
			}
			foreach ( $service['patterns'] as $pattern ) {
				if ( false !== stripos( $pattern, 'google' ) ) {
					$has_google = true;
					break 2;
				}
			}
		}

		if ( ! $has_google ) {
			return;
		}

		echo "<script data-view-privacy-consent-mode>\n";
		echo "window.dataLayer = window.dataLayer || [];\n";
		echo "function gtag(){dataLayer.push(arguments);}\n";
		echo "gtag('consent','default',{'ad_storage':'denied','analytics_storage':'denied','ad_user_data':'denied','ad_personalization':'denied','functionality_storage':'granted','security_storage':'granted'});\n";
		echo "</script>\n";
	}

	/**
	 * Vooraf ondersteunde diensten met hun herkenningspatronen (§8).
	 * Deze lijst is uitbreidbaar via het filter 'view_privacy_services'.
	 *
	 * @return array
	 */
	public static function default_services() {
		$services = array(
			'google_analytics' => array(
				'label'    => 'Google Analytics 4',
				'category' => 'statistics',
				'enabled'  => true,
				'patterns' => array( 'googletagmanager.com/gtag/js', 'google-analytics.com/analytics.js' ),
			),
			'google_tag_manager' => array(
				'label'     => 'Google Tag Manager',
				'category'  => 'statistics',
				'enabled'   => true,
				'patterns'  => array( 'googletagmanager.com/gtm.js' ),
				'container' => true,
			),
			'google_maps' => array(
				'label'    => 'Google Maps',
				'category' => 'marketing',
				'enabled'  => true,
				'patterns' => array( 'google.com/maps/embed', 'maps.google.com', 'maps.googleapis.com' ),
			),
			'youtube' => array(
				'label'    => 'YouTube',
				'category' => 'marketing',
				'enabled'  => true,
				'patterns' => array( 'youtube.com/embed', 'youtube-nocookie.com/embed', 'youtu.be' ),
			),
			'meta_pixel' => array(
				'label'    => 'Meta Pixel',
				'category' => 'marketing',
				'enabled'  => true,
				'patterns' => array( 'connect.facebook.net' ),
			),
			'hotjar' => array(
				'label'    => 'Hotjar',
				'category' => 'statistics',
				'enabled'  => true,
				'patterns' => array( 'static.hotjar.com', 'script.hotjar.com' ),
			),
			'microsoft_clarity' => array(
				'label'    => 'Microsoft Clarity',
				'category' => 'statistics',
				'enabled'  => true,
				'patterns' => array( 'clarity.ms' ),
			),
		);

		/**
		 * Filter: sta ontwikkelaars toe eigen diensten toe te voegen (§8: "Uitbreidbaar met eigen services").
		 *
		 * @param array $services Lijst met diensten.
		 */
		return apply_filters( 'view_privacy_services', $services );
	}

	/**
	 * Saniteer de door de gebruiker opgeslagen diensteninstellingen.
	 *
	 * @param array $input             Ruwe input.
	 * @param array $current           Huidige (geldige) diensten.
	 * @param array $valid_categories  Geldige categoriesleutels.
	 * @return array
	 */
	public static function sanitize_services( $input, $current, $valid_categories ) {
		$clean = $current;

		foreach ( $current as $key => $service ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}
			$clean[ $key ]['enabled'] = ! empty( $input[ $key ]['enabled'] );

			if ( isset( $input[ $key ]['category'] ) && in_array( $input[ $key ]['category'], $valid_categories, true ) ) {
				$clean[ $key ]['category'] = $input[ $key ]['category'];
			}
		}

		// Eigen, door de gebruiker toegevoegde diensten (uitbreidbaarheid via UI).
		$custom_count = 0;
		foreach ( $input as $key => $service ) {
			if ( isset( $current[ $key ] ) || $custom_count >= 20 ) {
				continue;
			}
			$key = sanitize_key( $key );
			if ( empty( $key ) || empty( $service['patterns'] ) ) {
				continue;
			}

			$patterns = array_map(
				function ( $p ) {
					return sanitize_text_field( wp_unslash( $p ) );
				},
				array_filter( array_map( 'trim', explode( "\n", $service['patterns'] ) ) )
			);

			$clean[ $key ] = array(
				'label'    => isset( $service['label'] ) ? sanitize_text_field( wp_unslash( $service['label'] ) ) : ucfirst( $key ),
				'category' => isset( $service['category'] ) && in_array( $service['category'], $valid_categories, true ) ? $service['category'] : 'marketing',
				'enabled'  => ! empty( $service['enabled'] ),
				'patterns' => $patterns,
			);
			++$custom_count;
		}

		return $clean;
	}

	/**
	 * Start de outputbuffer op de front-end (niet in admin, feeds, REST, etc.).
	 *
	 * @return void
	 */
	public function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		// Zonder gekoppelde privacybeleidpagina is de hele consentflow inactief:
		// dan ook geen scripts blokkeren, anders blijven diensten permanent
		// geblokkeerd zonder dat bezoekers ooit toestemming kunnen geven.
		if ( ! View_Privacy_Consent::instance()->get_privacy_policy_url() ) {
			return;
		}

		/**
		 * Filter om scriptblokkering uit te schakelen, bv. voor specifieke paginatypes.
		 *
		 * @param bool $enabled Of blokkering actief is.
		 */
		if ( ! apply_filters( 'view_privacy_enable_blocking', true ) ) {
			return;
		}

		ob_start( array( $this, 'rewrite_html' ) );
	}

	/**
	 * Sluit een eventueel openstaande buffer netjes af.
	 *
	 * @return void
	 */
	public function end_buffer() {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	/**
	 * Herschrijf de uitgaande HTML: vervang <script>/<iframe> tags die matchen
	 * met een dienst-patroon door geblokkeerde placeholders met
	 * type="text/plain" (zodat de browser ze niet uitvoert) en
	 * data-attributen voor de front-end JS om later te activeren.
	 *
	 * @param string $html De volledige HTML-output van de pagina.
	 * @return string
	 */
	public function rewrite_html( $html ) {
		if ( empty( $html ) || false === stripos( $html, '</html' ) ) {
			// Geen volledige HTML-pagina (bv. een partial of AJAX-fragment); niets doen.
			return $html;
		}

		$services = View_Privacy_Settings::instance()->get_settings()['services'];
		$enabled_services = array_filter(
			$services,
			function ( $s ) {
				return ! empty( $s['enabled'] ) && ! empty( $s['patterns'] );
			}
		);

		if ( empty( $enabled_services ) ) {
			return $html;
		}

		// --- <script src="..."> en inline <script>...</script> ---
		$html = preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script>#is',
			function ( $matches ) use ( $enabled_services ) {
				return $this->maybe_block_script( $matches[1], $matches[2], $enabled_services );
			},
			$html
		);

		// --- <iframe ...></iframe> (embeds zoals YouTube, Maps) ---
		$html = preg_replace_callback(
			'#<iframe\b([^>]*)>(.*?)</iframe>#is',
			function ( $matches ) use ( $enabled_services ) {
				return $this->maybe_block_iframe( $matches[1], $enabled_services );
			},
			$html
		);

		if ( ! empty( $this->detected_this_request ) ) {
			$this->persist_scan_data( array_keys( $this->detected_this_request ) );
		}

		return $html;
	}

	/**
	 * Sla vast welke diensten zojuist daadwerkelijk zijn aangetroffen en
	 * geblokkeerd, t.b.v. de statuskaarten op het dashboard. Rate-gelimiteerd
	 * (max. één keer per 5 minuten) zodat dit geen extra database-write per
	 * paginaweergave veroorzaakt op drukbezochte sites.
	 *
	 * @param array $keys Sleutels van gedetecteerde diensten.
	 * @return void
	 */
	private function persist_scan_data( $keys ) {
		if ( get_transient( 'view_privacy_scan_lock' ) ) {
			return;
		}
		set_transient( 'view_privacy_scan_lock', 1, 5 * MINUTE_IN_SECONDS );

		$data = get_option(
			'view_privacy_scan_data',
			array(
				'last_scan' => 0,
				'services'  => array(),
			)
		);

		$now = time();
		$data['last_scan'] = $now;
		foreach ( $keys as $key ) {
			$data['services'][ $key ] = $now;
		}

		update_option( 'view_privacy_scan_data', $data, false );
	}

	/**
	 * Haal de opgeslagen scangegevens op t.b.v. het dashboard.
	 *
	 * @return array{last_scan:int,services:array<string,int>}
	 */
	public function get_scan_data() {
		return get_option(
			'view_privacy_scan_data',
			array(
				'last_scan' => 0,
				'services'  => array(),
			)
		);
	}

	/**
	 * Bepaal of een <script>-tag geblokkeerd moet worden en geef de
	 * (eventueel herschreven) tag terug.
	 *
	 * @param string $attrs    Attributen van de originele tag.
	 * @param string $content  Inline scriptinhoud (indien geen src).
	 * @param array  $services Actieve diensten.
	 * @return string
	 */
	private function maybe_block_script( $attrs, $content, $services ) {
		// Al door VIEW! Privacy zelf geplaatst of expliciet uitgezonderd: niet aanraken.
		if ( false !== stripos( $attrs, 'data-view-privacy' ) || false !== stripos( $attrs, 'view-privacy-exclude' ) ) {
			return "<script{$attrs}>{$content}</script>";
		}

		$haystack = $attrs . ' ' . $content;

		foreach ( $services as $key => $service ) {
			foreach ( $service['patterns'] as $pattern ) {
				if ( '' !== $pattern && false !== stripos( $haystack, $pattern ) ) {
					$this->detected_this_request[ $key ] = true;
					$safe_attrs = preg_replace( '/\btype=("|\')[^"\']*("|\')/i', '', $attrs );
					return sprintf(
						'<script type="text/plain" data-view-privacy-blocked="%1$s" data-view-privacy-category="%2$s"%3$s>%4$s</script>',
						esc_attr( $key ),
						esc_attr( $service['category'] ),
						$safe_attrs,
						$content
					);
				}
			}
		}

		return "<script{$attrs}>{$content}</script>";
	}

	/**
	 * Bepaal of een <iframe> geblokkeerd moet worden; vervangt door een
	 * placeholder-div die client-side door consent-banner.js wordt getoond.
	 *
	 * @param string $attrs    Attributen van de originele tag.
	 * @param array  $services Actieve diensten.
	 * @return string
	 */
	private function maybe_block_iframe( $attrs, $services ) {
		if ( false !== stripos( $attrs, 'data-view-privacy' ) ) {
			return "<iframe{$attrs}></iframe>";
		}

		if ( ! preg_match( '/\bsrc=("|\')(.*?)\1/i', $attrs, $src_match ) ) {
			return "<iframe{$attrs}></iframe>";
		}

		$src = $src_match[2];

		foreach ( $services as $key => $service ) {
			foreach ( $service['patterns'] as $pattern ) {
				if ( '' !== $pattern && false !== stripos( $src, $pattern ) ) {
					$this->detected_this_request[ $key ] = true;
					$clean_attrs = preg_replace( '/\bsrc=("|\')(.*?)\1/i', '', $attrs );
					return sprintf(
						'<div class="view-privacy-embed-placeholder" data-view-privacy-blocked="%1$s" data-view-privacy-category="%2$s" data-view-privacy-src="%3$s" data-view-privacy-attrs="%4$s"></div>',
						esc_attr( $key ),
						esc_attr( $service['category'] ),
						esc_url( $src ),
						esc_attr( $clean_attrs )
					);
				}
			}
		}

		return "<iframe{$attrs}></iframe>";
	}
}
