<?php
/**
 * Optional Elementor integration.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Registers an Elementor widget when Elementor is active.
 */
final class Elementor {

	/**
	 * Prevent duplicate widget registration when old and new Elementor hooks run.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_register' ), 100 );
	}

	/**
	 * Register Elementor widget hooks.
	 */
	public function maybe_register(): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Register widget(s).
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 */
	public function register_widget( $widgets_manager ): void {
		if ( $this->registered ) {
			return;
		}

		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		$widgets = array(
			new Elementor_Widget(),
			// Legacy alias for widgets placed by the pre-4.0 plugin as 'erecht24'.
			new Elementor_Widget_Legacy(),
		);

		foreach ( $widgets as $widget ) {
			if ( method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( $widget );
			} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
				$widgets_manager->register_widget_type( $widget );
			}
		}

		$this->registered = true;
	}
}
