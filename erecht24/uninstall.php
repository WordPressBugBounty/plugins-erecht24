<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package ERecht24LegalText
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$erecht24_option_name = 'erecht24_legal_text_settings';

/**
 * Decrypt a value stored by Settings::encrypt_value() – standalone copy for uninstall context.
 *
 * @param string $value Stored value.
 * @return string
 */
$erecht24_decrypt = static function ( string $value ): string {
	if ( '' === $value || 0 !== strpos( $value, 'enc::' ) ) {
		return $value;
	}
	if ( ! function_exists( 'openssl_decrypt' ) || ! defined( 'AUTH_KEY' ) || ! defined( 'AUTH_SALT' ) ) {
		return '';
	}
	$decoded = base64_decode( substr( $value, 5 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding binary IV+ciphertext, not obfuscation
	if ( false === $decoded || strlen( $decoded ) < 17 ) {
		return '';
	}
	$key = substr( hash( 'sha256', AUTH_KEY . AUTH_SALT ), 0, 32 );
	$iv  = substr( $decoded, 0, 16 );
	$enc = substr( $decoded, 16 );
	$dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
	return false !== $dec ? $dec : '';
};

$erecht24_delete_registered_client = static function () use ( $erecht24_option_name, $erecht24_decrypt ): void {
	$settings = get_option( $erecht24_option_name, array() );

	if ( ! is_array( $settings ) ) {
		return;
	}

	$raw_key   = ! empty( $settings['api_key'] ) && is_scalar( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
	$api_key   = sanitize_text_field( $erecht24_decrypt( $raw_key ) );
	$client_id = ! empty( $settings['client_id'] ) ? absint( $settings['client_id'] ) : 0;

	if ( '' === $api_key || 1 > $client_id || ! function_exists( 'wp_remote_request' ) ) {
		return;
	}

	wp_remote_request(
		'https://api.e-recht24.de/v1/clients/' . absint( $client_id ),
		array(
			'method'      => 'DELETE',
			'timeout'     => 5,
			'redirection' => 3,
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'eRecht24'     => $api_key,
				'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
			),
		)
	);
};

if ( is_multisite() ) {
	$erecht24_sites = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $erecht24_sites as $erecht24_site_id ) {
		switch_to_blog( (int) $erecht24_site_id );
		$erecht24_delete_registered_client();
		delete_option( $erecht24_option_name );
		delete_option( 'erecht24_v3_migrated' );
		delete_option( 'erecht24_api_key_settings' );
		delete_option( 'erecht24_api_client_settings' );
		delete_option( 'erecht24_google_analytics_settings' );
		delete_option( 'erecht24_imprint_settings' );
		delete_option( 'erecht24_privacy_policy_settings' );
		delete_option( 'erecht24_privacy_policy_social_media_settings' );
		restore_current_blog();
	}

	return;
}

$erecht24_delete_registered_client();
delete_option( $erecht24_option_name );
delete_option( 'erecht24_v3_migrated' );
delete_option( 'erecht24_api_key_settings' );
delete_option( 'erecht24_api_client_settings' );
delete_option( 'erecht24_google_analytics_settings' );
delete_option( 'erecht24_imprint_settings' );
delete_option( 'erecht24_privacy_policy_settings' );
delete_option( 'erecht24_privacy_policy_social_media_settings' );
