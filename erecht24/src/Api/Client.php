<?php
/**
 * API client for eRecht24.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Api;

use ERecht24LegalText\Settings;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Handles outgoing requests to the documented eRecht24 API endpoints.
 */
final class Client {

	private const API_BASE = 'https://api.e-recht24.de';

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Check whether an API key can access the API.
	 *
	 * @param string $api_key API key.
	 *
	 * @return true|WP_Error
	 */
	public function validate_key( string $api_key ) {
		$response = $this->list_clients( $api_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all push clients registered for this API key's project.
	 *
	 * @param string $api_key API key.
	 *
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function list_clients( string $api_key ) {
		return $this->request( 'GET', 'v1/clients', $api_key );
	}

	/**
	 * Register this WordPress site as an eRecht24 API client for push updates.
	 *
	 * @param string $api_key API key.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function register_client( string $api_key ) {
		$response = $this->request(
			'POST',
			'v1/clients',
			$api_key,
			$this->build_client_body()
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'client_id' => isset( $response['client_id'] ) ? absint( $response['client_id'] ) : 0,
			'secret'    => isset( $response['secret'] ) ? sanitize_text_field( (string) $response['secret'] ) : '',
		);
	}

	/**
	 * Delete the registered API client.
	 *
	 * @param string $api_key API key.
	 * @param int    $client_id Client id.
	 *
	 * @return true|WP_Error
	 */
	public function delete_client( string $api_key, int $client_id ) {
		if ( '' === $api_key || 1 > $client_id ) {
			return true;
		}

		$response = $this->request( 'DELETE', 'v1/clients/' . absint( $client_id ), $api_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Fetch a legal document from eRecht24.
	 *
	 * @param string $document_type Normalized document type.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function fetch_document( string $document_type ) {
		$api_key  = $this->settings->get_api_key();
		$api_path = Settings::document_type_to_api_path( Settings::normalize_document_type( $document_type ) );

		if ( '' === $api_key ) {
			return new WP_Error(
				'erecht24_missing_api_key',
				__( 'Es ist kein API-Schlüssel gespeichert.', 'erecht24' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $this->settings->can_render_documents() ) {
			return new WP_Error(
				'erecht24_invalid_api_key_state',
				__( 'Der gespeicherte API-Schlüssel ist ungültig. Bitte speichere einen neuen API-Schlüssel.', 'erecht24' ),
				array( 'status' => 403 )
			);
		}

		if ( '' === $api_path ) {
			return new WP_Error(
				'erecht24_invalid_document_type',
				__( 'Der angeforderte Rechtstext-Typ ist nicht erlaubt.', 'erecht24' ),
				array( 'status' => 400 )
			);
		}

		return $this->request( 'GET', 'v1/' . $api_path, $api_key );
	}

	/**
	 * Ask eRecht24 to test whether the registered push endpoint is reachable.
	 *
	 * @return true|WP_Error
	 */
	public function test_push_ping() {
		$api_key   = $this->settings->get_api_key();
		$client_id = $this->settings->get_client_id();

		if ( '' === $api_key || 1 > $client_id ) {
			return new WP_Error(
				'erecht24_missing_push_client',
				__( 'Kein registrierter API-Client für den Push-Test vorhanden.', 'erecht24' ),
				array( 'status' => 400 )
			);
		}

		$response = $this->request( 'POST', 'v1/clients/' . absint( $client_id ) . '/testPush', $api_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Create body for client registration.
	 *
	 * @return array<string,mixed>
	 */
	private function build_client_body(): array {
		global $wp_version;

		return array(
			'push_method'    => class_exists( '\WP_REST_Server' ) ? \WP_REST_Server::CREATABLE : 'POST',
			'push_uri'       => rest_url( 'erecht24/v1/push' ),
			'cms'            => 'WordPress',
			'cms_version'    => (string) $wp_version,
			'plugin_name'    => 'eRecht24 Legal Texts',
			'plugin_version' => ERECHT24_LEGAL_TEXT_VERSION,
			'locale'         => get_locale(),
		);
	}

	/**
	 * Perform an HTTP request.
	 *
	 * @param string                   $method HTTP method.
	 * @param string                   $path API path.
	 * @param string                   $api_key API key.
	 * @param array<string,mixed>|null $body Optional JSON body.
	 *
	 * @return array<int|string,mixed>|WP_Error
	 */
	private function request( string $method, string $path, string $api_key, ?array $body = null ) {
		$url     = esc_url_raw( self::API_BASE . '/' . ltrim( $path, '/' ) );
		$headers = array(
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json; charset=utf-8',
			'eRecht24'     => $api_key,
			'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
		);

		$args = array(
			'timeout'     => 15,
			'redirection' => 3,
			'headers'     => $headers,
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $args );
		} elseif ( 'POST' === $method ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$args['method'] = $method;
			$response       = wp_remote_request( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			$this->settings->add_log( 'HTTP error: ' . $response->get_error_message() );

			return new WP_Error(
				'erecht24_http_error',
				sprintf(
					/* translators: %s: HTTP API error message. */
					__( 'Die Verbindung zur eRecht24-API ist fehlgeschlagen: %s', 'erecht24' ),
					$response->get_error_message()
				),
				array( 'status' => 503 )
			);
		}

		$status_code   = (int) wp_remote_retrieve_response_code( $response );
		$response_body = (string) wp_remote_retrieve_body( $response );
		$data          = array();

		if ( '' !== $response_body ) {
			$decoded = json_decode( $response_body, true );

			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				$this->settings->add_log( 'Invalid JSON response from eRecht24 API.' );

				return new WP_Error(
					'erecht24_invalid_json',
					__( 'Die eRecht24-API hat eine ungültige Antwort gesendet.', 'erecht24' ),
					array( 'status' => 502 )
				);
			}

			$data = $decoded;
		}

		if ( 200 > $status_code || 300 <= $status_code ) {
			$error_message = $this->get_error_message( $data );
			$api_code      = isset( $data['code'] ) && is_scalar( $data['code'] ) ? $data['code'] : null;
			$known_secrets = array( $api_key, $this->settings->get_client_secret() );
			$this->settings->add_log(
				'API error ' . $status_code . ' (code=' . wp_json_encode( $api_code, JSON_UNESCAPED_UNICODE ) . '): ' . $error_message
				. ' | raw response: ' . wp_json_encode( $this->redact_sensitive_fields( $data, $known_secrets ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			);

			if ( in_array( $status_code, array( 401, 403 ), true ) ) {
				$this->settings->mark_api_key_invalid();
			}

			return new WP_Error(
				'erecht24_api_error',
				$error_message,
				array(
					'status'   => $status_code,
					'api_code' => $api_code,
				)
			);
		}

		return $data;
	}

	/**
	 * Redact sensitive values before an API response is logged, in case
	 * eRecht24 ever echoes a secret/key back in an error body — this log is
	 * exportable by the site owner via the Status tab.
	 *
	 * Redacts both by known field name (covers structured fields) and by
	 * matching this site's actual key/secret values wherever they appear,
	 * including inside free-text fields such as an error message.
	 *
	 * @param array<string,mixed> $data          Response data.
	 * @param array<int,string>   $known_secrets This site's own key/secret values to scrub if echoed back.
	 *
	 * @return array<string,mixed>
	 */
	private function redact_sensitive_fields( array $data, array $known_secrets ): array {
		static $sensitive_keys = array( 'secret', 'api_key', 'client_secret', 'erecht24_secret', 'password', 'token' );

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->redact_sensitive_fields( $value, $known_secrets );
			} elseif ( is_string( $key ) && in_array( strtolower( $key ), $sensitive_keys, true ) ) {
				$data[ $key ] = '(redacted)';
			} elseif ( is_string( $value ) ) {
				foreach ( $known_secrets as $secret ) {
					if ( '' !== $secret ) {
						$value = str_replace( $secret, '(redacted)', $value );
					}
				}

				$data[ $key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Extract a localized API error message.
	 *
	 * @param array<string,mixed> $data Response data.
	 */
	private function get_error_message( array $data ): string {
		$is_german = 0 === strpos( get_locale(), 'de_' );

		if ( $is_german && ! empty( $data['message_de'] ) && is_scalar( $data['message_de'] ) ) {
			return sanitize_text_field( (string) $data['message_de'] );
		}

		if ( ! empty( $data['message'] ) && is_scalar( $data['message'] ) ) {
			return sanitize_text_field( (string) $data['message'] );
		}

		return __( 'Die eRecht24-API hat den Request abgelehnt.', 'erecht24' );
	}
}
