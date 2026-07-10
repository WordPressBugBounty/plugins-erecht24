<?php
/**
 * Gutenberg block support.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Frontend;

use ERecht24LegalText\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a dynamic Gutenberg block that delegates rendering to the shortcode.
 */
final class Blocks {

	/**
	 * Shortcode renderer.
	 *
	 * @var Shortcodes
	 */
	private $shortcodes;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Shortcodes $shortcodes Shortcode renderer.
	 * @param Settings   $settings Settings service.
	 */
	public function __construct( Shortcodes $shortcodes, Settings $settings ) {
		$this->shortcodes = $shortcodes;
		$this->settings   = $settings;
	}

	/**
	 * Register block hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register dynamic block.
	 */
	public function register_block(): void {
		static $script_asset_version = null;

		if ( null === $script_asset_version ) {
			$script_file          = ERECHT24_LEGAL_TEXT_PATH . 'assets/js/block.js';
			$script_asset_version = is_readable( $script_file )
				? (string) filemtime( $script_file )
				: ERECHT24_LEGAL_TEXT_VERSION;
		}

		wp_register_script(
			'erecht24-legal-texts-block',
			ERECHT24_LEGAL_TEXT_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			$script_asset_version,
			true
		);

		wp_register_style(
			'erecht24-legal-texts-block-editor',
			ERECHT24_LEGAL_TEXT_URL . 'assets/css/block-editor.css',
			array(),
			ERECHT24_LEGAL_TEXT_VERSION
		);

		wp_localize_script(
			'erecht24-legal-texts-block',
			'eRecht24LegalTextBlock',
			array(
				'canRender'     => true,
				'canSyncRemote' => $this->settings->can_render_documents(),
				'message'       => __( 'Remote-Synchronisierung ist deaktiviert, bis ein gültiger eRecht24 API-Schlüssel gespeichert wurde. Lokale Rechtstexte können weiterhin ausgegeben werden.', 'erecht24' ),
			)
		);

		wp_set_script_translations( 'erecht24-legal-texts-block', 'erecht24', ERECHT24_LEGAL_TEXT_PATH . 'languages' );

		register_block_type(
			'erecht24/legal-text',
			array(
				'api_version'     => '3',
				'editor_script'   => 'erecht24-legal-texts-block',
				'editor_style'    => 'erecht24-legal-texts-block-editor',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'type'        => array(
						'type'    => 'string',
						'default' => 'imprint',
					),
					'lang'        => array(
						'type'    => 'string',
						'default' => 'de',
					),
					'strip_title' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		// Legacy alias for blocks saved by the v3 plugin as 'erecht24/erecht24'.
		register_block_type(
			'erecht24/erecht24',
			array(
				'api_version'     => '3',
				'editor_script'   => 'erecht24-legal-texts-block',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'type'        => array(
						'type'    => 'string',
						'default' => 'imprint',
					),
					'lang'        => array(
						'type'    => 'string',
						'default' => 'de',
					),
					'strip_title' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * Render dynamic block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function render_block( array $attributes ): string {
		return $this->shortcodes->render( $attributes );
	}
}
