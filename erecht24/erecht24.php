<?php
/**
 * Plugin Name:       eRecht24 Legal Texts
 * Plugin URI:        https://www.e-recht24.de/
 * Description:       This plugin allows the easy integration of imprint and privacy policy of eRecht24.
 * Version:           4.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            eRecht24
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       erecht24
 * Domain Path:       /languages
 *
 * @package ERecht24LegalText
 */

defined( 'ABSPATH' ) || exit;

define( 'ERECHT24_LEGAL_TEXT_VERSION', '4.1.0' );
define( 'ERECHT24_LEGAL_TEXT_FILE', __FILE__ );
define( 'ERECHT24_LEGAL_TEXT_PATH', plugin_dir_path( __FILE__ ) );
define( 'ERECHT24_LEGAL_TEXT_URL', plugin_dir_url( __FILE__ ) );
define( 'ERECHT24_LEGAL_TEXT_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'ERecht24LegalText\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$relative_path  = str_replace( '\\', '/', $relative_class );
		$file           = ERECHT24_LEGAL_TEXT_PATH . 'src/' . $relative_path . '.php';
		$real           = realpath( $file );
		$src_base       = realpath( ERECHT24_LEGAL_TEXT_PATH . 'src' );

		if ( false === $real || false === $src_base || 0 !== strpos( $real, $src_base . DIRECTORY_SEPARATOR ) ) {
			return;
		}

		require_once $real;
	}
);

register_activation_hook( __FILE__, array( 'ERecht24LegalText\\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		ERecht24LegalText\Plugin::instance()->init();
	}
);
