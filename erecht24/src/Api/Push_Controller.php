<?php
/**
 * REST push endpoint for eRecht24.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Api;

use ERecht24LegalText\Settings;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Receives authorized push notifications from eRecht24.
 */
final class Push_Controller extends WP_REST_Controller {

	private const REST_NAMESPACE = 'erecht24/v1';
	private const REST_ROUTE     = '/push';

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * API client.
	 *
	 * @var Client
	 */
	private $api_client;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 * @param Client   $api_client API client.
	 */
	public function __construct( Settings $settings, Client $api_client ) {
		$this->settings   = $settings;
		$this->api_client = $api_client;
	}

	/**
	 * Register REST route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'show_in_index'       => false,
				'args'                => array(
					'erecht24_secret' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'erecht24_type'   => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Verify push secret.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return true|WP_Error
	 */
	public function permission_callback( WP_REST_Request $request ) {
		// IP-based rate limit.
		$raw_ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$safe_ip  = filter_var( $raw_ip, FILTER_VALIDATE_IP ) ? $raw_ip : 'unknown';
		$ip_key   = 'erecht24_push_rl_' . md5( $safe_ip );
		$attempts = (int) get_transient( $ip_key );

		if ( $attempts >= 10 ) {
			return new WP_Error(
				'erecht24_rest_rate_limited',
				'Too many requests.',
				array( 'status' => 429 )
			);
		}

		// Global rate limit – distributed brute-force protection.
		$global_key      = 'erecht24_push_rl_global';
		$global_attempts = (int) get_transient( $global_key );

		if ( $global_attempts >= 200 ) {
			return new WP_Error(
				'erecht24_rest_rate_limited',
				'Too many requests.',
				array( 'status' => 429 )
			);
		}

		$stored_secret = $this->settings->get_client_secret();
		$sent_secret   = $this->get_secret_from_request( $request );

		if ( '' === $stored_secret || '' === $sent_secret || ! hash_equals( $stored_secret, $sent_secret ) ) {
			set_transient( $ip_key, $attempts + 1, 300 );
			set_transient( $global_key, $global_attempts + 1, 300 );
			return new WP_Error(
				'erecht24_rest_forbidden',
				__( 'Der eRecht24-Push-Request ist nicht autorisiert.', 'erecht24' ),
				array( 'status' => 401 )
			);
		}

		if ( $attempts > 0 ) {
			delete_transient( $ip_key );
		}

		return true;
	}

	/**
	 * Handle push request.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$raw_type = $this->get_scalar_request_value( $request, 'erecht24_type' );
		$type     = Settings::normalize_document_type( $raw_type );

		if ( 'ping' === sanitize_key( $raw_type ) ) {
			return new WP_REST_Response( array( 'message' => 'pong' ), 200 );
		}

		if ( '' === $type ) {
			return new WP_REST_Response(
				array(
					'message'    => 'Unknown type.',
					'message_de' => 'Unbekannter Typ.',
				),
				400
			);
		}

		$response = $this->api_client->fetch_document( $type );

		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 400;

			return new WP_REST_Response(
				array(
					'message' => $response->get_error_message(),
				),
				$status
			);
		}

		$this->settings->save_remote_document( $type, $response );

		return new WP_REST_Response(
			array(
				'message'    => 'Document successfully imported.',
				'message_de' => 'Dokument erfolgreich importiert.',
			),
			200
		);
	}

	/**
	 * Read the shared push secret from the request header or POST/JSON body.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	private function get_secret_from_request( WP_REST_Request $request ): string {
		$header_secret = $request->get_header( 'x-erecht24-secret' );

		if ( is_scalar( $header_secret ) && '' !== trim( (string) $header_secret ) ) {
			return preg_replace( '/[^a-zA-Z0-9\-_=+\/]/', '', wp_unslash( (string) $header_secret ) );
		}

		return $this->get_scalar_request_value( $request, 'erecht24_secret', false );
	}

	/**
	 * Read a scalar request value from JSON/body, optionally falling back to merged REST params.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param string          $key Parameter key.
	 * @param bool            $allow_fallback Whether to fall back to the merged REST parameter bag.
	 */
	private function get_scalar_request_value( WP_REST_Request $request, string $key, bool $allow_fallback = true ): string {
		$json_params = $request->get_json_params();

		if ( is_array( $json_params ) && isset( $json_params[ $key ] ) && is_scalar( $json_params[ $key ] ) ) {
			return sanitize_text_field( wp_unslash( (string) $json_params[ $key ] ) );
		}

		$body_params = $request->get_body_params();

		if ( is_array( $body_params ) && isset( $body_params[ $key ] ) && is_scalar( $body_params[ $key ] ) ) {
			return sanitize_text_field( wp_unslash( (string) $body_params[ $key ] ) );
		}

		if ( $allow_fallback ) {
			$value = $request->get_param( $key );

			if ( is_scalar( $value ) ) {
				return sanitize_text_field( wp_unslash( (string) $value ) );
			}
		}

		return '';
	}
}
