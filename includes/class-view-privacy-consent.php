<?php
/**
 * Consentlogica: levert configuratie aan de front-end en biedt
 * developer-helpers zoals view_privacy_has_consent().
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_Consent
 */
class View_Privacy_Consent {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_Consent|null
	 */
	private static $instance = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_Consent
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
		add_action( 'init', array( $this, 'register_polylang_strings' ) );
	}

	/**
	 * Huidige consentversie (§14 Herziening).
	 *
	 * @return int
	 */
	public function get_consent_version() {
		return (int) get_option( VIEW_PRIVACY_OPTION_CONSENT_VERSION, VIEW_PRIVACY_CONSENT_VERSION );
	}

	/**
	 * Haal de URL op van de te gebruiken privacybeleidpagina. Retourneert
	 * een lege string als er geen (geldige) bronpagina is ingesteld.
	 *
	 * De bronpagina is een eigen, verplichte instelling van VIEW! Privacy
	 * (Instellingen → Privacybeleidpagina) — bewust los van WordPress'
	 * eigen 'wp_page_for_privacy_policy' (Instellingen → Privacy). Die
	 * WP-optie bleek in combinatie met Polylang niet betrouwbaar: op
	 * niet-standaardtaalpagina's kan deze door Polylang op 0 worden
	 * teruggegeven, waardoor de banner ten onrechte niet initialiseerde.
	 *
	 * Met Polylang actief wordt de vertaling van de bronpagina voor de
	 * huidige taal gebruikt, zodat de banner op iedere taalversie
	 * verschijnt. Ontbreekt die vertaling, dan valt de functie terug op de
	 * bronpagina zelf — zodat de banner nooit stilzwijgend verdwijnt — en
	 * wordt dit geregistreerd voor een waarschuwing op het dashboard.
	 * Zonder Polylang wordt de bronpagina direct gebruikt.
	 *
	 * @return string
	 */
	public function get_privacy_policy_url() {
		$page_id = $this->get_privacy_policy_page_id();
		$url     = $page_id ? (string) get_permalink( $page_id ) : '';

		// Dezelfde filter die WP-core's get_privacy_policy_url() toepast,
		// zodat integraties die hierop haken (caching, URL-herschrijving,
		// e.d.) blijven werken.
		return (string) apply_filters( 'privacy_policy_url', $url, $page_id );
	}

	/**
	 * Bepaalt het post-ID van de te gebruiken privacybeleidpagina voor de
	 * huidige taal, uitgaande van de eigen VIEW! Privacy-bronpagina. Zie
	 * get_privacy_policy_url() voor het volledige gedrag.
	 *
	 * @return int
	 */
	private function get_privacy_policy_page_id() {
		$settings = View_Privacy_Settings::instance()->get_settings();
		$page_id  = (int) $settings['privacy_page_id'];

		if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
			return 0;
		}

		// Zonder Polylang: de ingestelde bronpagina direct gebruiken.
		if ( ! function_exists( 'pll_get_post' ) ) {
			return $page_id;
		}

		$current_lang = $this->determine_current_polylang_language();

		if ( ! $current_lang ) {
			return $page_id;
		}

		$translated_id = pll_get_post( $page_id, $current_lang );

		if ( $translated_id && 'publish' === get_post_status( $translated_id ) ) {
			$this->clear_missing_privacy_translation( $current_lang );
			return (int) $translated_id;
		}

		// Vertaling ontbreekt voor deze taal: val terug op de bronpagina
		// zodat de banner blijft verschijnen, en registreer dit voor een
		// waarschuwing op het dashboard.
		$this->flag_missing_privacy_translation( $current_lang );

		return $page_id;
	}

	/**
	 * Bepaalt de actief bekeken Polylang-taal, met een robuuste terugval.
	 *
	 * pll_current_language() is de standaardroute en werkt normaliter
	 * betrouwbaar. Uit onderzoek (heme.nl) bleek echter dat op sommige
	 * sites/configuraties pll_current_language() op het moment van
	 * aanroepen niet de daadwerkelijk bekeken taal opleverde, terwijl de
	 * vertaalkoppeling zelf (pll_get_post_translations()) wél correct was
	 * — met als gevolg dat get_privacy_policy_url() altijd op de
	 * brontaalpagina bleef hangen, ook als er een geldige vertaling
	 * beschikbaar was.
	 *
	 * Als onafhankelijke tweede controle wordt daarom ook gekeken naar de
	 * taal van de daadwerkelijk opgevraagde pagina/post zelf
	 * (pll_get_post_language() op get_queried_object_id()) — dit werkt
	 * volledig los van pll_current_language() en dekt zo af wanneer die
	 * laatste om wat voor reden dan ook geen waarde teruggeeft.
	 *
	 * @return string
	 */
	private function determine_current_polylang_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language();
			if ( $lang ) {
				return $lang;
			}
		}

		if ( function_exists( 'pll_get_post_language' ) ) {
			$queried_id = get_queried_object_id();
			if ( $queried_id ) {
				$lang = pll_get_post_language( $queried_id );
				if ( $lang ) {
					return $lang;
				}
			}
		}

		return '';
	}

	/**
	 * Registreert dat de privacybeleidpagina voor een taal geen vertaling
	 * heeft, t.b.v. een waarschuwing op het dashboard (§Polylang).
	 *
	 * @param string $lang Polylang-taalcode.
	 * @return void
	 */
	private function flag_missing_privacy_translation( $lang ) {
		$missing = get_option( 'view_privacy_missing_translations', array() );
		if ( ! is_array( $missing ) ) {
			$missing = array();
		}
		if ( ! in_array( $lang, $missing, true ) ) {
			$missing[] = $lang;
			update_option( 'view_privacy_missing_translations', $missing );
		}
	}

	/**
	 * Verwijdert een taal uit de lijst met ontbrekende vertalingen, zodra
	 * die alsnog beschikbaar blijkt (zelfherstellend, geen handmatige reset
	 * nodig).
	 *
	 * @param string $lang Polylang-taalcode.
	 * @return void
	 */
	private function clear_missing_privacy_translation( $lang ) {
		$missing = get_option( 'view_privacy_missing_translations', array() );
		if ( ! is_array( $missing ) || ! in_array( $lang, $missing, true ) ) {
			return;
		}
		update_option( 'view_privacy_missing_translations', array_values( array_diff( $missing, array( $lang ) ) ) );
	}

	/**
	 * Lijst met taalcodes waarvoor de ingestelde privacybeleidpagina geen
	 * Polylang-vertaling heeft (t.b.v. dashboardwaarschuwing).
	 *
	 * @return array
	 */
	public function get_missing_privacy_translations() {
		if ( ! function_exists( 'pll_current_language' ) ) {
			return array();
		}
		$missing = get_option( 'view_privacy_missing_translations', array() );
		return is_array( $missing ) ? $missing : array();
	}

	/**
	 * Registreert alle front-end teksten van VIEW! Privacy bij Polylang
	 * (groep "VIEW! Privacy"), zodat de beheerder ze kan vertalen via de
	 * bestaande Polylang-functionaliteit: Talen → Vertaling van strings.
	 * Voert niets uit als Polylang niet actief is; het bestaande gedrag
	 * (gewone WP-gettext via __() e.d.) blijft dan ongewijzigd en
	 * Nederlands blijft de bron- en standaardtaal.
	 *
	 * @return void
	 */
	public function register_polylang_strings() {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		$settings = View_Privacy_Settings::instance()->get_settings();
		$group    = 'VIEW! Privacy';

		// Door de beheerder ingestelde bannerteksten (geen software-strings).
		pll_register_string( 'vp_banner_tagline', $settings['banner']['tagline'], $group );
		pll_register_string( 'vp_banner_title', $settings['banner']['title'], $group );
		pll_register_string( 'vp_banner_text', $settings['banner']['text'], $group, true );
		$button_fields = array(
			'button_accept_all' => 'vp_button_accept_all',
			'button_reject_all' => 'vp_button_reject_all',
			'button_preferences' => 'vp_button_preferences',
			'button_save' => 'vp_button_save',
			'button_back' => 'vp_button_back',
			'button_blocked' => 'vp_button_blocked',
			'button_accept_service' => 'vp_button_accept_service',
		);
		foreach ( $button_fields as $field => $name ) {
			pll_register_string( $name, $settings['banner'][ $field ], $group );
		}

		// Cookiecategorieën.
		foreach ( $settings['categories'] as $key => $cat ) {
			pll_register_string( 'vp_category_' . $key . '_label', $cat['label'], $group );
			pll_register_string( 'vp_category_' . $key . '_description', $cat['description'], $group, true );
		}

		// Diensten (alleen de daadwerkelijk ingeschakelde, zichtbare labels).
		foreach ( $settings['services'] as $key => $service ) {
			if ( empty( $service['enabled'] ) ) {
				continue;
			}
			pll_register_string( 'vp_service_' . $key . '_label', $service['label'], $group );
		}

		// Vaste software-strings: knoppen, placeholders, meldingen, tooltip.
		$static_strings = array(
			'vp_accept_all'            => __( 'Alles accepteren', 'view-privacy' ),
			'vp_reject_all'            => __( 'Alles weigeren', 'view-privacy' ),
			'vp_preferences'           => __( 'Voorkeuren', 'view-privacy' ),
			'vp_save'                  => __( 'Voorkeuren opslaan', 'view-privacy' ),
			'vp_back'                  => __( 'Terug', 'view-privacy' ),
			'vp_blocked_title'         => __( 'Inhoud geblokkeerd', 'view-privacy' ),
			'vp_blocked_button'        => __( 'Cookies aanpassen', 'view-privacy' ),
			'vp_accept_service'        => __( 'Cookies accepteren', 'view-privacy' ),
			/* translators: %s: naam van de dienst, bv. Google Maps. */
			'vp_load_service'          => __( '%s laden', 'view-privacy' ),
			'vp_privacy_link'          => __( 'Privacybeleid', 'view-privacy' ),
			'vp_reopen_label'          => __( 'Cookievoorkeuren aanpassen', 'view-privacy' ),
			'vp_reopen_text'           => __( 'Privacy-instellingen', 'view-privacy' ),
			'vp_blocked_intro'         => __( 'Uw privacy staat voorop.', 'view-privacy' ),
			/* translators: %s: type inhoud, bv. video of kaart. */
			'vp_blocked_text'          => __( 'U bepaalt zelf wanneer deze %s wordt geladen.', 'view-privacy' ),
			'vp_retention_note'        => __( 'Uw keuze wordt door VIEW! Privacy 365 dagen bewaard. U kunt dit op elk moment opnieuw instellen via de privacy-instellingen.', 'view-privacy' ),
			'vp_load_label_youtube'    => __( 'Video', 'view-privacy' ),
			'vp_load_label_maps'       => __( 'Kaart', 'view-privacy' ),
			'vp_content_label_youtube' => __( 'video', 'view-privacy' ),
			'vp_content_label_maps'    => __( 'kaart', 'view-privacy' ),
			'vp_content_label_default' => __( 'inhoud', 'view-privacy' ),
			/* translators: 1: jaartal, 2: versienummer — blijven als placeholders staan bij het vertalen. */
			'vp_footer_text_template'  => __( 'Copyright %1$d View! by Zaal Design. — v%2$s', 'view-privacy' ),
		);

		foreach ( $static_strings as $name => $value ) {
			pll_register_string( $name, $value, $group );
		}
	}

	/**
	 * Vertaalt een front-end string naar de actief bekeken Polylang-taal.
	 * Zonder Polylang, of zolang de beheerder nog geen vertaling heeft
	 * ingevoerd, wordt de oorspronkelijke (Nederlandse) tekst teruggegeven
	 * — er wordt nooit automatisch machinaal vertaald.
	 *
	 * @param string $string Oorspronkelijke (Nederlandse) tekst.
	 * @return string
	 */
	private function pll_translate( $string ) {
		if ( ! function_exists( 'pll__' ) ) {
			return $string;
		}
		return pll__( $string );
	}

	/**
	 * Vertaalt een door de beheerder instelbaar contentveld (bannertekst,
	 * categorielabel, -omschrijving, e.d.). Dit zijn géén __()-aanroepen
	 * maar losse databasewaarden, die WordPress' eigen gettext normaal
	 * gesproken niet automatisch meeneemt — ze blijven na installatie
	 * permanent in de taal waarin ze voor het eerst zijn opgeslagen.
	 *
	 * Komt de opgeslagen waarde exact overeen met de originele (Nederlandse)
	 * standaardtekst van de plugin, dan gaat de functie ervan uit dat de
	 * beheerder deze niet zelf heeft aangepast, en wordt alsnog de gewone
	 * WordPress-vertaling (__()) toegepast — zodat bijvoorbeeld de standaard
	 * bannertekst op een Duitstalige site na installatie correct Duits is,
	 * ook zonder Polylang. Wijkt de waarde af (de beheerder heeft het zelf
	 * aangepast), dan blijft die eigen tekst ongewijzigd staan — daar heeft
	 * de plugin geen vertaling voor. Polylang-vertaling (indien actief)
	 * wordt in beide gevallen alsnog toegepast via pll_translate().
	 *
	 * @param string $value             Opgeslagen waarde uit de instellingen.
	 * @param string $original_default  Originele (Nederlandse) standaardtekst.
	 * @return string
	 */
	private function translate_content_field( $value, $original_default ) {
		if ( $value === $original_default ) {
			return $this->pll_translate( __( $original_default, 'view-privacy' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
		return $this->pll_translate( $value );
	}

	/**
	 * Bouwt de configuratie die naar de front-end wordt gestuurd via
	 * wp_localize_script. Bevat geen persoonsgegevens.
	 *
	 * @return array
	 */
	public function get_frontend_config() {
		$settings = View_Privacy_Settings::instance()->get_settings();

		// Het logo is niet door de gebruiker instelbaar: altijd het vaste VIEW!-beeldmerk tonen.
		$settings['banner']['logo'] = VIEW_PRIVACY_URL . 'public/images/view-logo.png';

		// Door de beheerder ingestelde bannerteksten: als deze nog de
		// onaangepaste standaardtekst zijn, alsnog via gettext vertalen
		// (werkt ook zonder Polylang); eigen tekst van de beheerder blijft
		// ongewijzigd. Zie translate_content_field().
		$settings['banner']['tagline']             = $this->translate_content_field( $settings['banner']['tagline'], 'Uw privacy. Uw keuze.' );
		$settings['banner']['title']               = $this->translate_content_field( $settings['banner']['title'], 'Wij respecteren uw privacy' );
		$settings['banner']['text']                = $this->translate_content_field( $settings['banner']['text'], 'Wij gebruiken cookies om onze website te laten functioneren en, met uw toestemming, om statistieken te verzamelen en content te personaliseren. U kunt uw voorkeuren op elk moment aanpassen.' );
		$button_defaults = array(
			'button_accept_all' => 'Alles accepteren',
			'button_reject_all' => 'Alles weigeren',
			'button_preferences' => 'Voorkeuren',
			'button_save' => 'Voorkeuren opslaan',
			'button_back' => 'Terug',
			'button_blocked' => 'Cookies aanpassen',
			'button_accept_service' => 'Cookies accepteren',
		);
		foreach ( $button_defaults as $field => $default ) {
			$settings['banner'][ $field ] = $this->translate_content_field( $settings['banner'][ $field ], $default );
		}

		$category_defaults = array(
			'necessary'   => array(
				'label'       => 'Noodzakelijk',
				'description' => 'Deze cookies zijn essentieel voor het functioneren van de website en kunnen niet worden uitgeschakeld.',
			),
			'preferences' => array(
				'label'       => 'Externe media',
				'description' => 'Deze cookies horen bij ingesloten content van externe partijen, zoals video\'s en kaarten.',
			),
			'statistics'  => array(
				'label'       => 'Statistieken',
				'description' => 'Deze cookies helpen ons te begrijpen hoe bezoekers de website gebruiken.',
			),
			'marketing'   => array(
				'label'       => 'Marketing',
				'description' => 'Deze cookies worden gebruikt om advertenties relevanter te maken voor u.',
			),
		);

		$categories = array();
		foreach ( $settings['categories'] as $key => $cat ) {
			$defaults            = isset( $category_defaults[ $key ] ) ? $category_defaults[ $key ] : null;
			$categories[ $key ] = array(
				'label'       => $defaults ? $this->translate_content_field( $cat['label'], $defaults['label'] ) : $this->pll_translate( $cat['label'] ),
				'description' => $defaults ? $this->translate_content_field( $cat['description'], $defaults['description'] ) : $this->pll_translate( $cat['description'] ),
				'locked'      => ! empty( $cat['locked'] ),
				'default'     => ! empty( $cat['default'] ),
			);
		}

		$services = array();
		foreach ( $settings['services'] as $key => $service ) {
			if ( empty( $service['enabled'] ) ) {
				continue;
			}
			$services[ $key ] = array(
				'label'     => $this->pll_translate( $service['label'] ),
				'category'  => $service['category'],
				'container' => ! empty( $service['container'] ),
			);
		}

		return array(
			'restUrl'          => esc_url_raw( rest_url( 'view-privacy/v1/consent' ) ),
			'nonce'            => wp_create_nonce( 'wp_rest' ),
			'consentVersion'   => $this->get_consent_version(),
			'cookieName'       => 'view_privacy_consent',
			'cookieExpiryDays' => 365,
			'loggingEnabled'   => ! empty( $settings['logging']['enabled'] ),
			'privacyPolicyUrl' => esc_url( $this->get_privacy_policy_url() ),
			'loadLabels'      => array(
				'youtube'     => $this->pll_translate( __( 'Video', 'view-privacy' ) ),
				'google_maps' => $this->pll_translate( __( 'Kaart', 'view-privacy' ) ),
			),
			'contentLabels'   => array(
				'youtube'     => $this->pll_translate( __( 'video', 'view-privacy' ) ),
				'google_maps' => $this->pll_translate( __( 'kaart', 'view-privacy' ) ),
				'default'     => $this->pll_translate( __( 'inhoud', 'view-privacy' ) ),
			),
			'reopenIconUrl'   => ! empty( $settings['banner']['reopen_icon'] ) ? esc_url( $settings['banner']['reopen_icon'] ) : esc_url( VIEW_PRIVACY_URL . 'public/images/stroopwafel.png' ),
			'banner'          => $settings['banner'],
			'categories'      => $categories,
			'services'        => $services,
			'footerText'      => sprintf(
				/* translators: 1: huidig jaartal, 2: versienummer van de plugin. */
				$this->pll_translate( __( 'Copyright %1$d View! by Zaal Design. — v%2$s', 'view-privacy' ) ),
				(int) current_time( 'Y' ),
				VIEW_PRIVACY_VERSION
			),
			'i18n'            => array(
				'acceptAll'     => $settings['banner']['button_accept_all'],
				'rejectAll'     => $settings['banner']['button_reject_all'],
				'preferences'   => $settings['banner']['button_preferences'],
				'save'          => $settings['banner']['button_save'],
				'back'          => $settings['banner']['button_back'],
				'blockedTitle'  => $this->pll_translate( __( 'Inhoud geblokkeerd', 'view-privacy' ) ),
				'blockedButton' => $settings['banner']['button_blocked'],
				'acceptService' => $settings['banner']['button_accept_service'],
				/* translators: %s: naam van de dienst, bv. Google Maps. */
				'loadService'   => $this->pll_translate( __( '%s laden', 'view-privacy' ) ),
				'privacyLink'   => $this->pll_translate( __( 'Privacybeleid', 'view-privacy' ) ),
				'reopenLabel'   => $this->pll_translate( __( 'Cookievoorkeuren aanpassen', 'view-privacy' ) ),
				'reopenText'    => $this->pll_translate( __( 'Privacy-instellingen', 'view-privacy' ) ),
				'blockedIntro'  => $this->pll_translate( __( 'Uw privacy staat voorop.', 'view-privacy' ) ),
				/* translators: %s: type inhoud, bv. video of kaart. */
				'blockedText'   => $this->pll_translate( __( 'U bepaalt zelf wanneer deze %s wordt geladen.', 'view-privacy' ) ),
				'retentionNote' => $this->pll_translate( __( 'Uw keuze wordt door VIEW! Privacy 365 dagen bewaard. U kunt dit op elk moment opnieuw instellen via de privacy-instellingen.', 'view-privacy' ) ),
			),
		);
	}

	/**
	 * Server-side helper om te bepalen of een categorie toestemming heeft.
	 * Leest de consent-cookie die door de front-end wordt gezet.
	 *
	 * @param string $category Categorie-sleutel, bv. 'statistics'.
	 * @return bool
	 */
	public function has_consent( $category ) {
		if ( 'necessary' === $category ) {
			return true;
		}

		if ( empty( $_COOKIE['view_privacy_consent'] ) ) {
			return false;
		}

		$raw = sanitize_text_field( wp_unslash( $_COOKIE['view_privacy_consent'] ) );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['categories'] ) || empty( $data['v'] ) ) {
			return false;
		}

		if ( (int) $data['v'] !== $this->get_consent_version() ) {
			return false;
		}

		return ! empty( $data['categories'][ $category ] );
	}
}

/**
 * Publieke helperfunctie voor thema's/plugins om consent te controleren.
 *
 * @param string $category Categorie-sleutel: preferences | statistics | marketing.
 * @return bool
 */
function view_privacy_has_consent( $category ) {
	return View_Privacy_Consent::instance()->has_consent( $category );
}
