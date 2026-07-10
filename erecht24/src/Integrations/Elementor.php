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
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widget' ) );
	}

	/**
	 * Register widget.
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

		$widget = new Elementor_Widget();

		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $widget );
			$this->registered = true;
			return;
		}

		if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( $widget );
			$this->registered = true;
		}
	}
}
