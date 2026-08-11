<?php
/**
 * REST API: ontvangt consentkeuzes van de front-end banner voor logging.
 * De cookie zelf wordt client-side gezet (vanilla JS); dit endpoint is
 * uitsluitend voor optionele server-side logging (§13).
 *
 * @package View_Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class View_Privacy_REST
 */
class View_Privacy_REST {

	/**
	 * Singleton instance.
	 *
	 * @var View_Privacy_REST|null
	 */
	private static $instance = null;

	/**
	 * Haal de singleton instantie op.
	 *
	 * @return View_Privacy_REST
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registreer REST-routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'view-privacy/v1',
			'/consent',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_consent' ),
				'permission_callback' => '__return_true', // Publiek endpoint; beveiligd via nonce hieronder.
				'args'                => array(
					'categories' => array(
						'required' => true,
						'type'     => 'object',
					),
					'version'    => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	/**
	 * Verwerk een consentkeuze: valideer nonce en sla optioneel op in de log.
	 *
	 * @param WP_REST_Request $request Het inkomende verzoek.
	 * @return WP_REST_Response
	 */
	public function handle_consent( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Ongeldig verzoek.', 'view-privacy' ) ),
				403
			);
		}

		$raw_categories = $request->get_param( 'categories' );
		$version        = (int) $request->get_param( 'version' );

		if ( ! is_array( $raw_categories ) ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Ongeldige categorieën.', 'view-privacy' ) ),
				400
			);
		}

		$settings         = View_Privacy_Settings::instance()->get_settings();
		$valid_categories = array_keys( $settings['categories'] );
		$categories       = array();

		foreach ( $valid_categories as $key ) {
			$categories[ $key ] = isset( $raw_categories[ $key ] ) ? (bool) $raw_categories[ $key ] : false;
		}
		$categories['necessary'] = true;

		View_Privacy_Logging::instance()->log_consent( $categories, $version );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
