<?php
/**
 * Elementor widget.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Integrations;

use ERecht24LegalText\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor wrapper for the shortcode renderer.
 */
final class Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget id.
	 */
	public function get_name() {
		return 'erecht24_legal_text';
	}

	/**
	 * Widget title.
	 */
	public function get_title() {
		return __( 'eRecht24 Rechtstext', 'erecht24' );
	}

	/**
	 * Widget icon.
	 */
	public function get_icon() {
		return 'eicon-document-file';
	}

	/**
	 * Widget categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$labels = Settings::document_labels();

		$this->start_controls_section(
			'content_section',
			array(
				'label' => _x( 'Inhalt', 'Elementor Widget-Tab', 'erecht24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'type',
			array(
				'label'   => __( 'Rechtstext', 'erecht24' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $labels,
				'default' => 'imprint',
			)
		);

		$this->add_control(
			'lang',
			array(
				'label'   => __( 'Sprache', 'erecht24' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'de' => __( 'Deutsch', 'erecht24' ),
					'en' => __( 'Englisch', 'erecht24' ),
				),
				'default' => 'de',
			)
		);

		$this->add_control(
			'strip_title',
			array(
				'label'        => __( 'H1 entfernen', 'erecht24' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'default'      => '',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Compatibility with older Elementor versions.
	 */
	protected function _register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Elementor API requires this method name
		$this->register_controls();
	}

	/**
	 * Render widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$type     = Settings::normalize_document_type( (string) ( $settings['type'] ?? 'imprint' ) );
		$lang     = 'en' === ( $settings['lang'] ?? 'de' ) ? 'en' : 'de';
		$strip    = ! empty( $settings['strip_title'] ) ? 'true' : 'false';

		if ( '' === $type ) {
			$type = 'imprint';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ausgabe durch Shortcode-Renderer mit wp_kses_post() gesichert
		echo do_shortcode(
			sprintf(
				'[erecht24 type="%1$s" lang="%2$s" strip_title="%3$s"]',
				esc_attr( $type ),
				esc_attr( $lang ),
				esc_attr( $strip )
			)
		);
	}
}
