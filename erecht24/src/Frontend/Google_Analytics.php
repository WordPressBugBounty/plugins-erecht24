<?php
/**
 * Optional Google Analytics output.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Frontend;

use ERecht24LegalText\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs Google Analytics only after explicit admin activation.
 */
final class Google_Analytics {

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
	 * Register frontend hooks.
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue Google Analytics scripts via the WordPress script API.
	 */
	public function enqueue_scripts(): void {
		$options = $this->settings->get_google_analytics();

		if ( empty( $options['enabled'] ) || empty( $options['measurement'] ) ) {
			return;
		}

		$measurement      = (string) $options['measurement'];
		$use_usercentrics = ! empty( $options['usercentrics'] );

		if ( ! empty( $options['opt_out'] ) ) {
			wp_register_script( 'erecht24-ga-optout', false, array(), ERECHT24_LEGAL_TEXT_VERSION, array( 'in_footer' => false ) );
			wp_enqueue_script( 'erecht24-ga-optout' );
			wp_add_inline_script(
				'erecht24-ga-optout',
				sprintf(
					"window.eRecht24GaProperty = '%s';\n" .
					"window.eRecht24GaDisable = 'ga-disable-' + window.eRecht24GaProperty;\n" .
					"if (document.cookie.indexOf(window.eRecht24GaDisable + '=true') > -1) { window[window.eRecht24GaDisable] = true; }\n" .
					"window.eRecht24GaOptout = function () {\n" .
					"\tdocument.cookie = window.eRecht24GaDisable + '=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/; SameSite=Lax';\n" .
					"\twindow[window.eRecht24GaDisable] = true;\n" .
					"\twindow.alert('%s');\n" .
					'};',
					esc_js( $measurement ),
					esc_js( __( 'Google Analytics wurde für diesen Browser deaktiviert.', 'erecht24' ) )
				)
			);
		}

		$deps = ! empty( $options['opt_out'] ) ? array( 'erecht24-ga-optout' ) : array();
		wp_register_script(
			'erecht24-ga',
			'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $measurement ),
			$deps,
			ERECHT24_LEGAL_TEXT_VERSION,
			array(
				'in_footer' => false,
				'strategy'  => 'async',
			)
		);
		wp_enqueue_script( 'erecht24-ga' );

		$config = 0 === stripos( $measurement, 'UA-' ) ? array( 'anonymize_ip' => true ) : new \stdClass();
		wp_add_inline_script(
			'erecht24-ga',
			sprintf(
				"window.dataLayer = window.dataLayer || [];\n" .
				"function gtag(){dataLayer.push(arguments);}\n" .
				"gtag('js', new Date());\n" .
				"gtag('config', '%s', %s);",
				esc_js( $measurement ),
				wp_json_encode( $config )
			)
		);

		if ( $use_usercentrics ) {
			add_filter( 'script_loader_tag', array( $this, 'add_usercentrics_attributes' ), 10, 2 );
		}
	}

	/**
	 * Add Usercentrics consent attributes to the GA loader tag.
	 *
	 * Sets type="text/plain" so Usercentrics blocks execution until consent,
	 * and adds data-usercentrics to identify the service.
	 *
	 * @param string $tag    Script HTML tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function add_usercentrics_attributes( string $tag, string $handle ): string {
		if ( 'erecht24-ga' !== $handle ) {
			return $tag;
		}
		$tag = preg_replace( '/\s+type=[\'"][^\'"]*[\'"]/', '', $tag );
		$tag = str_replace( '<script ', '<script type="text/plain" data-usercentrics="Google Analytics" ', $tag );
		return $tag;
	}
}
