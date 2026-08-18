<?php
/**
 * Option handling and migration.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText;

defined( 'ABSPATH' ) || exit;

/**
 * Central access to plugin settings.
 */
final class Settings {


	public const OPTION_NAME = 'erecht24_legal_text_settings';

	/**
	 * Default Google Analytics sub-settings.
	 * Mirrors the 'google_analytics' key in defaults() to avoid
	 * building the full defaults array on every GA getter call.
	 *
	 * @var array<string,mixed>
	 */
	private const GA_DEFAULTS = array(
		'enabled'      => false,
		'measurement'  => '',
		'usercentrics' => false,
		'opt_out'      => false,
	);

	/**
	 * Option cache.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $cache = null;

	/**
	 * Queued log entries.
	 *
	 * @var array<int,array<string,string>>
	 */
	private array $pending_logs = array();

	/**
	 * Supported legal document type keys.
	 *
	 * Not for display — use document_labels() for translated labels.
	 */
	public const DOCUMENT_TYPES = array( 'imprint', 'privacy_policy', 'privacy_policy_social_media' );

	/**
	 * Activate plugin settings and migrate legacy values.
	 *
	 * @param bool $network_wide Whether the plugin is network activated.
	 */
	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::activate_current_site();
				restore_current_blog();
			}

			return;
		}

		self::activate_current_site();
	}

	/**
	 * Return translated document labels.
	 *
	 * @return array<string,string>
	 */
	public static function document_labels(): array {
		return array(
			'imprint'                     => __( 'Impressum', 'erecht24' ),
			'privacy_policy'              => __( 'Datenschutzerklärung', 'erecht24' ),
			'privacy_policy_social_media' => __( 'Datenschutzerklärung für Social Media', 'erecht24' ),
		);
	}

	/**
	 * Return short introductory descriptions for each document type.
	 *
	 * @return array<string,string>
	 */
	public static function document_labels_description(): array {
		return array(
			'imprint'                     => __( 'Hinterlegen Sie hier Ihr Impressum – synchronisiert aus dem eRecht24 Projekt Manager oder als selbst gepflegter lokaler Text.', 'erecht24' ),
			'privacy_policy'              => __( 'Verwalten Sie hier Ihre Datenschutzerklärung – automatisch aktuell über den eRecht24 Projekt Manager oder manuell als lokaler Text gepflegt.', 'erecht24' ),
			'privacy_policy_social_media' => __( 'Diese Datenschutzerklärung ist speziell für Ihre Social-Media-Auftritte gedacht und kann unabhängig von der allgemeinen Datenschutzerklärung ausgegeben werden.', 'erecht24' ),
		);
	}

	/**
	 * Normalize API, shortcode and legacy document type names.
	 *
	 * @param string $type Raw document type.
	 */
	public static function normalize_document_type( string $type ): string {
		$type = sanitize_key( str_replace( '-', '_', $type ) );

		$map = array(
			'privacy'                     => 'privacy_policy',
			'datenschutz'                 => 'privacy_policy',
			'privacypolicy'               => 'privacy_policy',
			'privacy_policy'              => 'privacy_policy',
			'privacy_policy_social'       => 'privacy_policy_social_media',
			'privacypolicysocialmedia'    => 'privacy_policy_social_media',
			'privacy_policy_social_media' => 'privacy_policy_social_media',
			'impressum'                   => 'imprint',
			'imprint'                     => 'imprint',
		);

		return $map[ $type ] ?? '';
	}

	/**
	 * Convert normalized document type to eRecht24 API path fragment.
	 *
	 * @param string $type Normalized document type.
	 */
	public static function document_type_to_api_path( string $type ): string {
		$map = array(
			'imprint'                     => 'imprint',
			'privacy_policy'              => 'privacyPolicy',
			'privacy_policy_social_media' => 'privacyPolicySocialMedia',
		);

		return $map[ $type ] ?? '';
	}

	/**
	 * Default option value.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$documents = array();

		foreach ( self::DOCUMENT_TYPES as $type ) {
			$documents[ $type ] = array(
				'source' => 'remote',
				'remote' => array(
					'de'       => '',
					'en'       => '',
					'modified' => '',
				),
				'local'  => array(
					'de'       => '',
					'en'       => '',
					'modified' => '',
				),
			);
		}

		return array(
			'api_key'          => '',
			'api_key_status'   => 'missing',
			'client_id'        => 0,
			'client_secret'    => '',
			'push_test_failed' => false,
			'documents'        => $documents,
			'google_analytics' => array(
				'enabled'      => false,
				'measurement'  => '',
				'usercentrics' => false,
				'opt_out'      => false,
			),
			'logs'             => array(),
			'version'          => ERECHT24_LEGAL_TEXT_VERSION,
		);
	}

	/**
	 * Load all settings.
	 *
	 * @return array<string,mixed>
	 */
	public function get_all(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$raw = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$settings = array_replace_recursive( self::defaults(), $raw );

		if ( '' !== (string) $settings['api_key'] && 'missing' === (string) $settings['api_key_status'] ) {
			$settings['api_key_status'] = 'unchecked';
		}

		$this->cache = $settings;

		return $this->cache;
	}

	/**
	 * Persist settings.
	 *
	 * @param array<string,mixed> $settings New settings.
	 */
	public function update_all( array $settings ): void {
		$settings            = array_intersect_key( $settings, self::defaults() );
		$settings['version'] = ERECHT24_LEGAL_TEXT_VERSION;
		$merged              = array_replace_recursive( self::defaults(), $settings );
		$this->cache         = $merged;

		update_option( self::OPTION_NAME, $merged, false );
	}

	/**
	 * Return stored API key.
	 */
	public function get_api_key(): string {
		$settings = $this->get_all();
		return self::decrypt_value( (string) $settings['api_key'] );
	}

	/**
	 * Return API key status.
	 */
	public function get_api_key_status(): string {
		$settings = $this->get_all();
		$status   = (string) ( $settings['api_key_status'] ?? 'missing' );

		return in_array( $status, array( 'missing', 'unchecked', 'valid', 'invalid' ), true ) ? $status : 'missing';
	}

	/**
	 * Documents may only render when an API key exists and is not marked invalid.
	 */
	public function can_render_documents(): bool {
		return '' !== $this->get_api_key() && in_array( $this->get_api_key_status(), array( 'valid', 'unchecked' ), true );
	}

	/**
	 * Return stored API client id.
	 */
	public function get_client_id(): int {
		$settings = $this->get_all();
		return absint( $settings['client_id'] );
	}

	/**
	 * Return stored push secret.
	 */
	public function get_client_secret(): string {
		$settings = $this->get_all();
		return self::decrypt_value( (string) $settings['client_secret'] );
	}

	/**
	 * Whether the last Remote Push Test failed.
	 */
	public function get_push_test_failed(): bool {
		$settings = $this->get_all();
		return ! empty( $settings['push_test_failed'] );
	}

	/**
	 * Record the outcome of a Remote Push Test.
	 *
	 * @param bool $failed Whether the test failed.
	 */
	public function set_push_test_failed( bool $failed ): void {
		$settings                     = $this->get_all();
		$settings['push_test_failed'] = $failed;
		$this->update_all( $settings );
	}

	/**
	 * Store API connection data.
	 *
	 * @param string $api_key API key.
	 * @param int    $client_id API client id.
	 * @param string $client_secret Push secret.
	 */
	public function save_api_connection( string $api_key, int $client_id = 0, string $client_secret = '' ): void {
		$settings                   = $this->get_all();
		$clean_key                  = self::sanitize_api_key( $api_key );
		$settings['api_key']        = self::encrypt_value( $clean_key );
		$settings['api_key_status'] = '' === $clean_key ? 'missing' : 'valid';
		$settings['client_id']      = absint( $client_id );
		$settings['client_secret']  = self::encrypt_value( preg_replace( '/[^a-zA-Z0-9\-_=+\/]/', '', $client_secret ) );

		$this->update_all( $settings );
	}

	/**
	 * Remove local API connection data.
	 */
	public function clear_api_connection(): void {
		$settings                   = $this->get_all();
		$settings['api_key']        = '';
		$settings['api_key_status'] = 'missing';
		$settings['client_id']      = 0;
		$settings['client_secret']  = '';

		$this->update_all( $settings );
		update_option( 'erecht24_v3_migrated', '1', false );
	}

	/**
	 * Mark the current API key as invalid.
	 */
	public function mark_api_key_invalid(): void {
		$settings = $this->get_all();

		if ( '' !== (string) $settings['api_key'] ) {
			$settings['api_key_status'] = 'invalid';
			$this->update_all( $settings );
		}
	}

	/**
	 * Store Google Analytics settings from admin request.
	 *
	 * @param array<string,mixed> $input Raw request data.
	 */
	public function save_google_analytics_from_request( array $input ): void {
		$settings = $this->get_all();

		$measurement = isset( $input['measurement'] ) && is_scalar( $input['measurement'] ) ? sanitize_text_field( wp_unslash( (string) $input['measurement'] ) ) : '';
		$measurement = preg_match( '/^(G|UA)-[A-Z0-9-]+$/i', $measurement ) ? strtoupper( $measurement ) : '';

		$settings['google_analytics'] = array(
			'enabled'      => ! empty( $input['enabled'] ) && '' !== $measurement,
			'measurement'  => $measurement,
			'usercentrics' => ! empty( $input['usercentrics'] ),
			'opt_out'      => ! empty( $input['opt_out'] ),
		);

		$this->update_all( $settings );
	}

	/**
	 * Return Google Analytics settings.
	 *
	 * @return array<string,mixed>
	 */
	public function get_google_analytics(): array {
		$settings = $this->get_all();
		return is_array( $settings['google_analytics'] )
			? array_replace( self::GA_DEFAULTS, $settings['google_analytics'] )
			: self::GA_DEFAULTS;
	}

	/**
	 * Store an internal diagnostic log line.
	 *
	 * Messages are intentionally kept in English/technical wording, not
	 * translated: this log is exported verbatim via the Status tab so it can
	 * be pasted into a support ticket, and a language-independent log is
	 * easier to read across support staff and eRecht24 developers regardless
	 * of the site's locale.
	 *
	 * @param string $message Log message.
	 */
	public function add_log( string $message ): void {
		$this->pending_logs[] = array(
			'time'    => current_time( 'mysql' ),
			'message' => sanitize_text_field( $message ),
		);
	}

	/**
	 * Write all buffered log entries to the database in a single update.
	 *
	 * Call this at the end of any operation that may produce log entries
	 * (e.g. an API request). A shutdown hook in Plugin::init() guarantees
	 * that logs are always flushed even if the caller forgets.
	 */
	public function flush_logs(): void {
		if ( empty( $this->pending_logs ) ) {
			return;
		}

		$settings           = $this->get_all();
		$logs               = is_array( $settings['logs'] ?? null ) ? $settings['logs'] : array();
		$settings['logs']   = array_slice( array_merge( $logs, $this->pending_logs ), -15 );
		$this->pending_logs = array();
		$this->update_all( $settings );
	}

	/**
	 * Return internal diagnostic logs.
	 *
	 * @return array<int,array<string,string>>
	 */
	public function get_logs(): array {
		$settings = $this->get_all();
		return is_array( $settings['logs'] ?? null ) ? $settings['logs'] : array();
	}

	/**
	 * Store admin-edited document settings.
	 *
	 * @param array<string,mixed> $documents Raw document data.
	 */
	public function save_documents_from_request( array $documents ): void {
		$this->stage_documents_from_request( $documents );
		$this->update_all( $this->cache ?? self::defaults() );
	}

	/**
	 * Apply admin-edited document data to the in-memory cache without writing to the database.
	 *
	 * Use before an operation that will call update_all() itself (e.g. save_remote_document)
	 * so that both changes land in a single database write.
	 *
	 * @param array<string,mixed> $documents Raw document data.
	 */
	public function stage_documents_from_request( array $documents ): void {
		$settings = $this->get_all();

		foreach ( $documents as $raw_type => $document ) {
			$type = self::normalize_document_type( (string) $raw_type );

			if ( ! $type || ! isset( $settings['documents'][ $type ] ) || ! is_array( $document ) ) {
				continue;
			}

			$source = isset( $document['source'] ) ? sanitize_key( wp_unslash( $document['source'] ) ) : 'remote';

			if ( in_array( $source, array( 'remote', 'local' ), true ) ) {
				$settings['documents'][ $type ]['source'] = $source;
			}

			$content_changed = false;

			foreach ( array( 'de', 'en' ) as $language ) {
				if ( isset( $document['local'][ $language ] ) ) {
					$new_html = self::sanitize_html( $document['local'][ $language ] );

					if ( ( $settings['documents'][ $type ]['local'][ $language ] ?? '' ) !== $new_html ) {
						$content_changed = true;
					}

					$settings['documents'][ $type ]['local'][ $language ] = $new_html;
				}
			}

			if ( $content_changed ) {
				$settings['documents'][ $type ]['local']['modified'] = current_time( 'mysql' );
			}
		}

		$this->cache = $settings;
	}

	/**
	 * Store one remote document response.
	 *
	 * @param string              $type Normalized document type.
	 * @param array<string,mixed> $api_data API response data.
	 */
	public function save_remote_document( string $type, array $api_data ): void {
		$type = self::normalize_document_type( $type );

		if ( ! $type ) {
			return;
		}

		$settings = $this->get_all();
		$modified = isset( $api_data['modified'] ) ? sanitize_text_field( (string) $api_data['modified'] ) : current_time( 'mysql' );

		$settings['documents'][ $type ]['remote']['de']       = isset( $api_data['html_de'] ) ? self::sanitize_html( $api_data['html_de'] ) : '';
		$settings['documents'][ $type ]['remote']['en']       = isset( $api_data['html_en'] ) ? self::sanitize_html( $api_data['html_en'] ) : '';
		$settings['documents'][ $type ]['remote']['modified'] = $modified;

		$this->update_all( $settings );
	}

	/**
	 * Copy the last synchronized remote document values into the local fields.
	 *
	 * @param string $type Document type.
	 *
	 * @return array<int,string> Languages written to the local document.
	 */
	public function copy_remote_document_to_local( string $type ): array {
		$type = self::normalize_document_type( $type );

		if ( ! $type ) {
			return array();
		}

		$settings = $this->get_all();

		if ( ! isset( $settings['documents'][ $type ] ) ) {
			return array();
		}

		$has_remote_text = false;
		$remote_values   = array();

		foreach ( array( 'de', 'en' ) as $language ) {
			$remote_html                = (string) ( $settings['documents'][ $type ]['remote'][ $language ] ?? '' );
			$remote_values[ $language ] = $remote_html;

			if ( '' !== $remote_html ) {
				$has_remote_text = true;
			}
		}

		if ( ! $has_remote_text ) {
			return array();
		}

		foreach ( $remote_values as $language => $remote_html ) {
			$settings['documents'][ $type ]['local'][ $language ] = self::sanitize_html( $remote_html );
		}

		$settings['documents'][ $type ]['local']['modified'] = current_time( 'mysql' );

		$this->update_all( $settings );

		return array_keys( $remote_values );
	}

	/**
	 * Return document settings.
	 *
	 * @param string $type Document type.
	 *
	 * @return array<string,mixed>
	 */
	public function get_document( string $type ): array {
		$type     = self::normalize_document_type( $type );
		$settings = $this->get_all();

		if ( ! $type || ! isset( $settings['documents'][ $type ] ) ) {
			return array();
		}

		return $settings['documents'][ $type ];
	}

	/**
	 * Return the HTML displayed for a document.
	 *
	 * @param string $type Document type.
	 * @param string $language Language key.
	 */
	public function get_document_html( string $type, string $language = 'de' ): string {
		$document = $this->get_document( $type );
		$language = 'en' === strtolower( $language ) ? 'en' : 'de';

		if ( empty( $document ) ) {
			return '';
		}

		$source = 'local' === ( $document['source'] ?? '' ) ? 'local' : 'remote';

		return (string) ( $document[ $source ][ $language ] ?? '' );
	}

	/**
	 * Return local document HTML independent from API key state.
	 *
	 * @param string $type Document type.
	 * @param string $language Language key.
	 */
	public function get_local_document_html( string $type, string $language = 'de' ): string {
		$document = $this->get_document( $type );
		$language = 'en' === strtolower( $language ) ? 'en' : 'de';

		if ( empty( $document ) ) {
			return '';
		}

		return (string) ( $document['local'][ $language ] ?? '' );
	}

	/**
	 * Mask API credentials for display.
	 *
	 * @param string $value Secret value.
	 */
	public static function mask_secret( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		$tail = substr( $value, -4 );

		return str_repeat( '*', max( 8, strlen( $value ) - 4 ) ) . $tail;
	}

	/**
	 * Encrypt a sensitive value for storage using AES-256-CBC with AUTH_KEY/AUTH_SALT as key material.
	 * Falls back to plaintext when openssl or the WordPress secret constants are unavailable.
	 *
	 * @param string $value Plaintext value to encrypt.
	 */
	public static function encrypt_value( string $value ): string {
		if ( '' === $value
			|| ! function_exists( 'openssl_encrypt' )
			|| ! defined( 'AUTH_KEY' )
			|| ! defined( 'AUTH_SALT' )
		) {
			return $value;
		}

		$key = substr( hash( 'sha256', \AUTH_KEY . \AUTH_SALT ), 0, 32 );
		$iv  = random_bytes( 16 );
		$enc = openssl_encrypt( $value, 'AES-256-CBC', $key, \OPENSSL_RAW_DATA, $iv );

		if ( false === $enc ) {
			return $value;
		}

		return 'enc::' . base64_encode( $iv . $enc ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary IV+ciphertext encoding, not obfuscation
	}

	/**
	 * Decrypt a value previously encrypted with encrypt_value().
	 * Returns plaintext unchanged if no encryption prefix is present (backward compatibility).
	 *
	 * @param string $value Stored value (encrypted or legacy plaintext).
	 */
	public static function decrypt_value( string $value ): string {
		if ( '' === $value || 0 !== strpos( $value, 'enc::' ) ) {
			return $value;
		}

		if ( ! function_exists( 'openssl_decrypt' )
			|| ! defined( 'AUTH_KEY' )
			|| ! defined( 'AUTH_SALT' )
		) {
			return '';
		}

		$decoded = base64_decode( substr( $value, 5 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding binary IV+ciphertext, not obfuscation

		if ( false === $decoded || strlen( $decoded ) < 17 ) {
			return '';
		}

		$key = substr( hash( 'sha256', \AUTH_KEY . \AUTH_SALT ), 0, 32 );
		$iv  = substr( $decoded, 0, 16 );
		$enc = substr( $decoded, 16 );
		$dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, \OPENSSL_RAW_DATA, $iv );

		return false !== $dec ? $dec : '';
	}

	/**
	 * Sanitize API key.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_api_key( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Sanitize legal text HTML.
	 *
	 * @param mixed $html Raw HTML.
	 */
	public static function sanitize_html( $html ): string {
		if ( is_array( $html ) || is_object( $html ) ) {
			return '';
		}

		return wp_kses_post( wp_unslash( (string) $html ) );
	}

	/**
	 * Migrate options from v3 when no API key is present in the new format.
	 *
	 * Runs at plugins_loaded so auto-updates (which skip the activation hook)
	 * are also covered. Bails immediately once a key exists (cache lookup only).
	 * The flag erecht24_v3_migrated is set when no old key is found, so the
	 * extra get_option call for erecht24_api_key_settings is skipped on all
	 * subsequent requests once it is clear that no v3 data exists to import.
	 */
	public function migrate_from_v3(): void {
		if ( '' !== $this->get_api_key() ) {
			return;
		}

		if ( get_option( 'erecht24_v3_migrated' ) ) {
			return;
		}

		$old = get_option( 'erecht24_api_key_settings', null );

		if ( ! is_array( $old ) || empty( $old['api_key'] ) ) {
			update_option( 'erecht24_v3_migrated', '1', false );
			return;
		}

		$migrated    = self::migrate_legacy_options();
		$this->cache = $migrated;
		update_option( self::OPTION_NAME, $migrated, false );
		update_option( 'erecht24_v3_migrated', '1', false );
	}

	/**
	 * Initialize settings for the current site.
	 */
	public static function activate_current_site(): void {
		$existing = get_option( self::OPTION_NAME, null );

		if ( is_array( $existing ) && ! empty( $existing['api_key'] ) ) {
			// Existing key — preserve config and ensure schema is complete.
			update_option( self::OPTION_NAME, array_replace_recursive( self::defaults(), $existing ), false );
			return;
		}

		// No key yet — try to import from v3 or initialise with defaults.
		update_option( self::OPTION_NAME, self::migrate_legacy_options(), false );
	}

	/**
	 * Copy values from the previous eRecht24 plugin without deleting them.
	 *
	 * @return array<string,mixed>
	 */
	private static function migrate_legacy_options(): array {
		$settings = self::defaults();

		$api_key_option = get_option( 'erecht24_api_key_settings', array() );
		if ( is_array( $api_key_option ) && ! empty( $api_key_option['api_key'] ) ) {
			$clean_key                  = self::sanitize_api_key( $api_key_option['api_key'] );
			$settings['api_key']        = self::encrypt_value( $clean_key );
			$settings['api_key_status'] = 'unchecked';
		}

		$google_analytics = get_option( 'erecht24_google_analytics_settings', array() );
		if ( is_array( $google_analytics ) ) {
			$settings['google_analytics'] = array(
				'enabled'      => ! empty( $google_analytics['google_analytics_enabled'] ),
				'measurement'  => ! empty( $google_analytics['google_analytics_key'] ) ? sanitize_text_field( (string) $google_analytics['google_analytics_key'] ) : '',
				'usercentrics' => ! empty( $google_analytics['google_analytics_usercentrics'] ),
				'opt_out'      => ! empty( $google_analytics['google_analytics_opt_out'] ),
			);
		}

		// Deliberately not migrating client_id/client_secret from the previous plugin: a push
		// client registered by a different codebase (potentially with a different push_uri or
		// push mechanism) should never be trusted as-is. Leaving these at their empty defaults
		// means Plugin::maybe_reregister_push_client() will register a fresh, 4.x-native client
		// automatically on the next request, with no action required from the site owner.

		$legacy_map = array(
			'imprint'                     => 'erecht24_imprint_settings',
			'privacy_policy'              => 'erecht24_privacy_policy_settings',
			'privacy_policy_social_media' => 'erecht24_privacy_policy_social_media_settings',
		);

		foreach ( $legacy_map as $type => $option_name ) {
			$legacy = get_option( $option_name, array() );

			if ( ! is_array( $legacy ) ) {
				continue;
			}

			$settings['documents'][ $type ]['source']             = ! empty( $legacy['document_source'] ) ? 'local' : 'remote';
			$settings['documents'][ $type ]['remote']['de']       = isset( $legacy['document_de_remote'] ) ? self::sanitize_html( $legacy['document_de_remote'] ) : '';
			$settings['documents'][ $type ]['remote']['en']       = isset( $legacy['document_en_remote'] ) ? self::sanitize_html( $legacy['document_en_remote'] ) : '';
			$settings['documents'][ $type ]['remote']['modified'] = isset( $legacy['document_de_last_update_remote'] ) ? sanitize_text_field( (string) $legacy['document_de_last_update_remote'] ) : '';
			$settings['documents'][ $type ]['local']['de']        = isset( $legacy['document_de_local'] ) ? self::sanitize_html( $legacy['document_de_local'] ) : '';
			$settings['documents'][ $type ]['local']['en']        = isset( $legacy['document_en_local'] ) ? self::sanitize_html( $legacy['document_en_local'] ) : '';
			$settings['documents'][ $type ]['local']['modified']  = isset( $legacy['document_de_last_update_local'] ) ? sanitize_text_field( (string) $legacy['document_de_last_update_local'] ) : '';
		}

		$old_imprint = get_option( 'imprint_text', '' );
		if ( $old_imprint && empty( $settings['documents']['imprint']['local']['de'] ) ) {
			$settings['documents']['imprint']['source']      = 'local';
			$settings['documents']['imprint']['local']['de'] = self::sanitize_html( $old_imprint );
		}

		$old_privacy = get_option( 'privacy_text', '' );
		if ( $old_privacy && empty( $settings['documents']['privacy_policy']['local']['de'] ) ) {
			$settings['documents']['privacy_policy']['source']      = 'local';
			$settings['documents']['privacy_policy']['local']['de'] = self::sanitize_html( $old_privacy );
		}

		return $settings;
	}
}
