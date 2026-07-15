<?php
/**
 * Legacy-compatible Elementor widget.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the widget under the name used by the pre-4.0 plugin ('erecht24'),
 * so pages built with that widget keep rendering after the upgrade.
 */
final class Elementor_Widget_Legacy extends Elementor_Widget {

	/**
	 * Widget id used by the pre-4.0 plugin.
	 */
	public function get_name() {
		return 'erecht24';
	}
}
