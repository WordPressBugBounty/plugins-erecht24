<?php
/**
 * Main plugin bootstrap.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText;

use ERecht24LegalText\Admin\Admin_Page;
use ERecht24LegalText\Api\Client;
use ERecht24LegalText\Api\Push_Controller;
use ERecht24LegalText\Frontend\Blocks;
use ERecht24LegalText\Frontend\Google_Analytics;
use ERecht24LegalText\Frontend\Shortcodes;
use ERecht24LegalText\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates plugin services.
 */
final class Plugin {


	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * API client.
	 *
	 * @var Client
	 */
	private $api_client;

	/**
	 * Shortcode renderer.
	 *
	 * @var Shortcodes
	 */
	private $shortcodes;

	/**
	 * Return the singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning of the singleton.
	 */
	private function __clone() {}

	/**
	 * Prevent deserialization of the singleton.
	 *
	 * @throws \RuntimeException Always.
	 */
	public function __wakeup(): void {
		throw new \RuntimeException( 'Cannot deserialize singleton.' );
	}

	/**
	 * Activation callback.
	 *
	 * @param bool $network_wide Whether the plugin is network activated.
	 */
	public static function activate( bool $network_wide = false ): void {
		Settings::activate( $network_wide );
	}

	/**
	 * Init WordPress hooks.
	 */
	public function init(): void {
		$this->settings = new Settings();
		$this->settings->migrate_from_v3();
		$this->api_client = new Client( $this->settings );
		$this->shortcodes = new Shortcodes( $this->settings );

		$this->shortcodes->register();

		$blocks = new Blocks( $this->shortcodes, $this->settings );
		$blocks->register_hooks();

		$google_analytics = new Google_Analytics( $this->settings );
		$google_analytics->register_hooks();

		$push_controller = new Push_Controller( $this->settings, $this->api_client );
		add_action( 'rest_api_init', array( $push_controller, 'register_routes' ) );

		if ( is_admin() ) {
			$admin_page = new Admin_Page( $this->settings, $this->api_client );
			$admin_page->register_hooks();
		}

		$elementor = new Elementor();
		$elementor->register_hooks();

		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_action( 'wp_initialize_site', array( $this, 'initialize_new_site' ) );
		add_action( 'shutdown', array( $this->settings, 'flush_logs' ) );
	}

	/**
	 * Initialize plugin settings for a newly created site in the network.
	 *
	 * @param \WP_Site $site The newly created site.
	 */
	public function initialize_new_site( \WP_Site $site ): void {
		if ( ! is_plugin_active_for_network( ERECHT24_LEGAL_TEXT_BASENAME ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		Settings::activate_current_site();
		restore_current_blog();
	}

	/**
	 * Add suggested privacy policy text for the external eRecht24 service.
	 */
	public function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p class="privacy-policy-tutorial">' . esc_html__(
			'eRecht24 Legal Texts verbindet diese Website nur nach Konfiguration durch eine berechtigte Administratorin oder einen berechtigten Administrator mit dem eRecht24-Dienst.',
			'erecht24'
		) . '</p>';

		$content .= '<p>' . wp_kses_post(
			sprintf(
				/* translators: 1: eRecht24 privacy URL. */
				__( 'Wenn der API-Schlüssel gespeichert oder Rechtstexte synchronisiert werden, werden technische Verbindungsdaten sowie die im Plugin angezeigte Push-URL an eRecht24 übertragen. Details zur Verarbeitung durch eRecht24 stehen in der <a href="%1$s" target="_blank" rel="noopener noreferrer">Datenschutzerklärung von eRecht24</a>.', 'erecht24' ),
				esc_url( 'https://www.e-recht24.de/datenschutzerklaerung.html' )
			)
		) . '</p>';
		$content .= '<p>' . esc_html__( 'Wenn Google Analytics in den Plugin-Einstellungen aktiviert wird, lädt die Website das Google-Tag und muss dafür eine passende Einwilligungs- und Datenschutzlösung bereitstellen.', 'erecht24' ) . '</p>';

		wp_add_privacy_policy_content(
			'eRecht24 Legal Texts',
			wp_kses_post( wpautop( $content, false ) )
		);
	}
}
