<?php
/**
 * Shortcode rendering.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Frontend;

use ERecht24LegalText\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders shortcodes.
 */
final class Shortcodes {

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
	 * Register shortcodes.
	 */
	public function register(): void {
		add_shortcode( 'erecht24', array( $this, 'render' ) );
		add_shortcode( 'erecht24_widget', array( $this, 'render' ) );
	}

	/**
	 * Render legal text shortcode.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @param string|null         $content Enclosed content, unused.
	 * @param string              $shortcode_tag Shortcode tag.
	 */
	public function render( $atts = array(), ?string $content = null, string $shortcode_tag = 'erecht24' ): string {
		unset( $content, $shortcode_tag );

		$atts = shortcode_atts(
			array(
				'type'        => 'imprint',
				'lang'        => 'de',
				'strip_title' => false,
			),
			is_array( $atts ) ? $atts : array(),
			'erecht24'
		);

		$type        = Settings::normalize_document_type( (string) $atts['type'] );
		$language    = 'en' === strtolower( (string) $atts['lang'] ) ? 'en' : 'de';
		$strip_title = wp_validate_boolean( $atts['strip_title'] );

		if ( '' === $type ) {
			return esc_html__( 'Der angeforderte Rechtstext-Typ ist nicht erlaubt.', 'erecht24' );
		}

		$html = $this->settings->get_document_html( $type, $language );

		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return '';
		}

		if ( $strip_title ) {
			$html = preg_replace( '#<h1\b[^>]*>.*?</h1>#is', '', $html, 1 );
		}

		return sprintf(
			'<div class="erecht24-legal-texts erecht24-legal-texts-%1$s" style="word-wrap: break-word;">%2$s</div>',
			esc_attr( $type ),
			wp_kses_post( $html )
		);
	}
}
