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
	 * Prevent constructing the singleton from outside {@see instance()}.
	 */
	private function __construct() {}

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

		add_action( 'init', array( $this, 'maybe_reregister_push_client' ) );

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
	 * Re-register the push client whenever an API key is stored without push
	 * credentials — regardless of how that state arose (fresh v3 migration, an
	 * already-affected install updating, or a previously failed registration
	 * attempt). Rate-limited via transient so a persistently failing eRecht24
	 * API doesn't retry on every single request.
	 *
	 * Hooked to `init` rather than called directly from `init()` (which runs on
	 * `plugins_loaded`): register_client() calls rest_url(), which requires the
	 * global $wp_rewrite to be set up — that happens later in WordPress's own
	 * bootstrap, so calling it during plugins_loaded fatals with
	 * "Call to a member function using_index_permalinks() on null".
	 *
	 * @param bool $force Skip the "client_secret is empty" check — used for an
	 *                     explicit admin action re-registering a client that already
	 *                     has *a* secret, e.g. one inherited from a v3.x migration
	 *                     that predates this codebase's own registration, or one a
	 *                     Remote Push Test found to be failing despite looking valid.
	 *
	 * @return bool True if a registration attempt was made and succeeded.
	 */
	public function maybe_reregister_push_client( bool $force = false ): bool {
		$api_key = $this->settings->get_api_key();

		if ( '' === $api_key ) {
			return false;
		}

		if ( ! $force && '' !== $this->settings->get_client_secret() ) {
			return false;
		}

		if ( get_transient( 'erecht24_push_reregister_attempted' ) ) {
			return false;
		}

		set_transient( 'erecht24_push_reregister_attempted', '1', HOUR_IN_SECONDS );

		try {
			$registration = $this->api_client->register_client( $api_key );
		} catch ( \Throwable $exception ) {
			$this->settings->add_log( 'Automatic push client re-registration crashed: ' . $exception->getMessage() );
			return false;
		}

		if ( is_wp_error( $registration ) ) {
			$this->settings->add_log( 'Automatic push client re-registration failed: ' . $registration->get_error_message() );
			return false;
		}

		$secret = (string) ( $registration['secret'] ?? '' );

		$this->settings->save_api_connection(
			$api_key,
			absint( $registration['client_id'] ?? 0 ),
			$secret
		);

		if ( '' === $secret ) {
			$this->settings->add_log( 'Push client re-registration returned no push secret.' );
			return false;
		}

		$this->settings->add_log( 'Push client automatically re-registered after detecting a missing push secret.' );
		$this->settings->set_push_test_failed( false );

		return true;
	}

	/**
	 * Initialize plugin settings for a newly created site in the network.
	 *
	 * @param \WP_Site $site The newly created site.
	 */
	public function initialize_new_site( \WP_Site $site ): void {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

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
