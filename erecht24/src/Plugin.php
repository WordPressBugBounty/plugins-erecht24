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

		add_action(
			'init',
			function (): void {
				$this->maybe_reregister_push_client();
			}
		);

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

		$secret    = (string) ( $registration['secret'] ?? '' );
		$client_id = absint( $registration['client_id'] ?? 0 );

		$this->settings->save_api_connection( $api_key, $client_id, $secret );

		if ( '' === $secret || 0 === $client_id ) {
			$this->settings->add_log( 'Push client re-registration returned an incomplete response (missing secret or client_id).' );
			return false;
		}

		$this->settings->add_log( 'Push client automatically re-registered after detecting a missing push secret.' );
		$this->settings->set_push_test_failed( false );

		$this->cleanup_duplicate_clients( $api_key, $client_id );

		return true;
	}

	/**
	 * Delete any other push clients at eRecht24 that share this site's push_uri
	 * (e.g. one left over from the previous v3.x plugin, or one created by an
	 * earlier failed repair attempt). Having more than one client registered
	 * for the same push_uri is a real problem, not just clutter: eRecht24 may
	 * push to any of them, and an old client can have a stale `push_method`
	 * (e.g. GET from the v3.x plugin) that this route no longer accepts,
	 * producing a genuine `rest_no_route` failure that has nothing to do with
	 * the current, correctly registered client.
	 *
	 * @param string $api_key           API key.
	 * @param int    $current_client_id The client id to keep — never deleted.
	 *
	 * @return int Number of duplicate clients actually deleted.
	 */
	public function cleanup_duplicate_clients( string $api_key, int $current_client_id ): int {
		if ( 1 > $current_client_id ) {
			// Without a valid id of our own there is no safe exclusion criterion —
			// never delete anything rather than risk removing a legitimate client.
			$this->settings->add_log( 'Duplicate push client cleanup skipped: no valid current client id to keep.' );
			return 0;
		}

		try {
			$clients = $this->api_client->list_clients( $api_key );
		} catch ( \Throwable $exception ) {
			$this->settings->add_log( 'Duplicate push client cleanup crashed while listing clients: ' . $exception->getMessage() );
			return 0;
		}

		if ( is_wp_error( $clients ) || ! is_array( $clients ) ) {
			return 0;
		}

		$removed = 0;

		$push_uri = rest_url( 'erecht24/v1/push' );

		foreach ( $clients as $client ) {
			if ( ! is_array( $client ) ) {
				continue;
			}

			$client_id = absint( $client['client_id'] ?? 0 );

			if ( 0 === $client_id || $current_client_id === $client_id ) {
				continue;
			}

			if ( ( $client['push_uri'] ?? '' ) !== $push_uri ) {
				continue;
			}

			try {
				$deleted = $this->api_client->delete_client( $api_key, $client_id );
			} catch ( \Throwable $exception ) {
				$this->settings->add_log( 'Duplicate push client cleanup crashed deleting client ' . $client_id . ': ' . $exception->getMessage() );
				continue;
			}

			if ( is_wp_error( $deleted ) ) {
				$this->settings->add_log( 'Duplicate push client cleanup failed for client ' . $client_id . ': ' . $deleted->get_error_message() );
				continue;
			}

			$this->settings->add_log( 'Duplicate push client (id ' . $client_id . ', same push_uri) deleted at eRecht24.' );
			++$removed;
		}

		return $removed;
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
