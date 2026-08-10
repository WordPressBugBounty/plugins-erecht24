<?php

/**
 * Admin settings page.
 *
 * @package ERecht24LegalText
 */

namespace ERecht24LegalText\Admin;

use ERecht24LegalText\Api\Client;
use ERecht24LegalText\Plugin;
use ERecht24LegalText\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and handles the plugin admin page.
 */
final class Admin_Page {




	private const MENU_SLUG               = 'erecht24';
	private const HELP_DOCUMENTATION_FILE = 'src/Admin/docs/documentation.php';

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
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 * @param Client   $api_client API client.
	 */
	public function __construct( Settings $settings, Client $api_client ) {
		$this->settings   = $settings;
		$this->api_client = $api_client;
	}

	/**
	 * Register admin hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_erecht24_legal_text_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_erecht24_legal_text_sync', array( $this, 'handle_sync' ) );
		add_action( 'admin_post_erecht24_legal_text_push_test', array( $this, 'handle_push_test' ) );
		add_action( 'admin_post_erecht24_legal_text_force_reregister', array( $this, 'handle_force_reregister' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_action( 'admin_notices', array( $this, 'render_push_orphaned_notice' ) );
		add_filter( 'plugin_action_links_' . ERECHT24_LEGAL_TEXT_BASENAME, array( $this, 'add_action_link' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_page(): void {
		add_options_page(
			__( 'eRecht24 Rechtstexte', 'erecht24' ),
			__( 'eRecht24 Rechtstexte', 'erecht24' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on this settings page.
	 *
	 * @param string $hook_suffix Admin hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'plugins.php' === $hook_suffix ) {
			add_thickbox();
			return;
		}

		if ( 'settings_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		static $admin_script_version = null;

		if ( null === $admin_script_version ) {
			$admin_script_file    = ERECHT24_LEGAL_TEXT_PATH . 'assets/js/admin.js';
			$admin_script_version = is_readable( $admin_script_file )
				? (string) filemtime( $admin_script_file )
				: ERECHT24_LEGAL_TEXT_VERSION;
		}

		wp_enqueue_style(
			'dashicons'
		);

		wp_enqueue_style(
			'erecht24-legal-texts-admin',
			ERECHT24_LEGAL_TEXT_URL . 'assets/css/admin.css',
			array(),
			ERECHT24_LEGAL_TEXT_VERSION
		);

		wp_enqueue_script(
			'erecht24-legal-texts-admin',
			ERECHT24_LEGAL_TEXT_URL . 'assets/js/admin.js',
			array( 'wp-i18n' ),
			$admin_script_version,
			true
		);

		wp_set_script_translations( 'erecht24-legal-texts-admin', 'erecht24', ERECHT24_LEGAL_TEXT_PATH . 'languages' );
	}

	/**
	 * Add settings link in plugins table.
	 *
	 * @param array<int,string> $links Existing links.
	 *
	 * @return array<int,string>
	 */
	public function add_action_link( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $this->page_url() ),
				esc_html__( 'Einstellungen', 'erecht24' )
			)
		);

		return $links;
	}

	/**
	 * Render admin notices.
	 */
	public function render_notice(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change
		if ( ! isset( $_GET['page'] ) || self::MENU_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$notice_key = $this->notice_key();
		$notice     = get_transient( $notice_key );

		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		delete_transient( $notice_key );

		$type  = in_array( $notice['type'] ?? '', array( 'success', 'warning', 'error' ), true ) ? $notice['type'] : 'success';
		$class = 'error' === $type ? 'notice-error' : ( 'warning' === $type ? 'notice-warning' : 'notice-success' );

		printf(
			'<div class="notice %1$s erecht24-prominent-notice is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			wp_kses_post( (string) $notice['message'] )
		);
	}

	/**
	 * Warn when an API key is stored but no push client is registered.
	 */
	public function render_push_orphaned_notice(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change
		if ( ! isset( $_GET['page'] ) || self::MENU_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( '' === $this->settings->get_api_key() ) {
			return;
		}

		$secret_missing = '' === $this->settings->get_client_secret();

		if ( ! $secret_missing && ! $this->settings->get_push_test_failed() ) {
			return;
		}

		if ( $secret_missing ) {
			/* translators: %s: URL to the plugin's Status tab. */
			$message = __( 'Ein API-Schlüssel ist gespeichert, aber Push-Updates sind nicht registriert. Gehen Sie zum <a href="%s">Status-Tab</a> und klicken Sie auf „Push-Client jetzt neu registrieren".', 'erecht24' );
		} else {
			/* translators: %s: URL to the plugin's Status tab. */
			$message = __( 'Ein Remote-Push-Test ist zuletzt fehlgeschlagen. Gehen Sie zum <a href="%s">Status-Tab</a> und klicken Sie auf „Push-Client jetzt neu registrieren".', 'erecht24' );
		}

		printf(
			'<div class="notice notice-warning erecht24-prominent-notice"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					$message,
					esc_url( $this->page_url( 'status' ) )
				)
			)
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'erecht24' ) );
		}

		$tab = $this->get_active_tab();

		echo '<div class="wrap erecht24-legal-texts-admin">';
		echo '<header class="erecht24-admin-header">';
		echo '<div><h1>' . esc_html__( 'eRecht24 Rechtstexte', 'erecht24' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'API-Schlüssel verwalten, Rechtstexte abrufen und per Shortcode oder Gutenberg-Block ausgeben.', 'erecht24' ) . '</p></div>';
		echo '<img class="erecht24-admin-logo" src="' . esc_url( ERECHT24_LEGAL_TEXT_URL . 'assets/logo-erecht24-long-72-rgb.png' ) . '" alt="eRecht24">';
		echo '</header>';

		$this->render_tabs( $tab );

		if ( 'settings' === $tab ) {
			$this->render_settings_tab();
		} elseif ( 'google_analytics' === $tab ) {
			$this->render_google_analytics_tab();
		} elseif ( 'status' === $tab ) {
			$this->render_status_tab();
		} elseif ( 'help' === $tab ) {
			$this->render_help_tab();
		} else {
			$this->render_document_tab( $tab );
		}

		echo '</div>';
	}

	/**
	 * Handle settings save.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'erecht24' ) );
		}

		check_admin_referer( 'erecht24_legal_text_save' );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'settings';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field-by-field in Settings
		$data = isset( $_POST['erecht24'] ) && is_array( $_POST['erecht24'] ) ? wp_unslash( $_POST['erecht24'] ) : array();

		if ( ! empty( $data['delete_api_key'] ) ) {
			$delete_result = $this->api_client->delete_client( $this->settings->get_api_key(), $this->settings->get_client_id() );
			$this->settings->clear_api_connection();

			if ( is_wp_error( $delete_result ) ) {
				$this->redirect_with_notice(
					__( 'Der lokale API-Schlüssel wurde entfernt. Der API-Client konnte bei eRecht24 nicht gelöscht werden.', 'erecht24' ),
					'warning',
					'settings'
				);
			}

			$this->redirect_with_notice( __( 'Der API-Schlüssel wurde entfernt.', 'erecht24' ), 'success', 'settings' );
		}

		if ( isset( $data['api_key'] ) && '' !== trim( (string) $data['api_key'] ) ) {
			$new_api_key = Settings::sanitize_api_key( $data['api_key'] );
			$validation  = $this->api_client->validate_key( $new_api_key );

			if ( is_wp_error( $validation ) ) {
				$this->redirect_with_notice( wp_strip_all_tags( $validation->get_error_message() ), 'error', 'settings' );
			}

			$registration = $this->api_client->register_client( $new_api_key );

			if ( is_wp_error( $registration ) ) {
				$this->settings->save_api_connection( $new_api_key );
				$this->redirect_with_notice(
					__( 'Der API-Schlüssel wurde gespeichert. Push-Updates konnten nicht registriert werden; manuelle Synchronisierung funktioniert weiterhin.', 'erecht24' ),
					'warning',
					'settings'
				);
			}

			$this->settings->save_api_connection(
				$new_api_key,
				absint( $registration['client_id'] ?? 0 ),
				(string) ( $registration['secret'] ?? '' )
			);

			$this->redirect_with_notice( __( 'Der API-Schlüssel wurde gespeichert und der API-Client wurde registriert.', 'erecht24' ), 'success', 'settings' );
		}

		if ( ! empty( $data['documents'] ) && is_array( $data['documents'] ) ) {
			if ( ! empty( $data['sync_document'] ) ) {
				// Stage form data in cache only; save_remote_document() writes everything in one go.
				$this->settings->stage_documents_from_request( $data['documents'] );
				$this->sync_document_and_redirect( sanitize_key( wp_unslash( $data['sync_document'] ) ) );
			}

			$this->settings->save_documents_from_request( $data['documents'] );

			if ( ! empty( $data['copy_remote_to_local_document'] ) ) {
				$this->copy_remote_document_to_local_and_redirect( sanitize_key( wp_unslash( $data['copy_remote_to_local_document'] ) ) );
			}

			$this->redirect_with_notice( __( 'Die Rechtstext-Einstellungen wurden gespeichert.', 'erecht24' ), 'success', $tab );
		}

		if ( ! empty( $data['google_analytics'] ) && is_array( $data['google_analytics'] ) ) {
			$this->settings->save_google_analytics_from_request( $data['google_analytics'] );
			$this->redirect_with_notice( __( 'Die Google-Analytics-Einstellungen wurden gespeichert.', 'erecht24' ), 'success', 'google_analytics' );
		}

		$this->redirect_with_notice( __( 'Es wurden keine Änderungen erkannt.', 'erecht24' ), 'warning', $tab );
	}

	/**
	 * Handle manual document synchronization.
	 */
	public function handle_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'erecht24' ) );
		}

		check_admin_referer( 'erecht24_legal_text_sync' );

		$document = isset( $_POST['document'] ) ? sanitize_key( wp_unslash( $_POST['document'] ) ) : 'all';
		$types    = 'all' === $document ? array_keys( Settings::DOCUMENT_TYPES ) : array( Settings::normalize_document_type( $document ) );
		$failed   = array();
		$success  = array();
		$labels   = Settings::document_labels();

		foreach ( $types as $type ) {
			if ( '' === $type ) {
				continue;
			}

			$response = $this->api_client->fetch_document( $type );

			if ( is_wp_error( $response ) ) {
				$failed[] = sprintf(
					'%1$s: %2$s',
					esc_html( $labels[ $type ] ?? $type ),
					esc_html( $response->get_error_message() )
				);
				continue;
			}

			$this->settings->save_remote_document( $type, $response );
			$success[] = esc_html( $labels[ $type ] ?? $type );
		}

		if ( ! empty( $failed ) ) {
			$message = __( 'Einige Rechtstexte konnten nicht synchronisiert werden:', 'erecht24' ) . '<br>' . implode( '<br>', $failed );
			$this->redirect_with_notice( $message, 'error', 'all' === $document ? 'settings' : $document );
		}

		if ( ! empty( $success ) ) {
			$message = sprintf(
				/* translators: %s: List of document labels. */
				__( 'Synchronisiert: %s', 'erecht24' ),
				implode( ', ', $success )
			);
			$this->redirect_with_notice( $message, 'success', 'all' === $document ? 'settings' : $document );
		}

		$this->redirect_with_notice( __( 'Es wurde kein gültiger Rechtstext-Typ übergeben.', 'erecht24' ), 'error', 'settings' );
	}

	/**
	 * Run the remote push reachability test after an explicit admin action.
	 */
	public function handle_push_test(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'erecht24' ) );
		}

		check_admin_referer( 'erecht24_legal_text_push_test' );

		if ( ! $this->settings->can_render_documents() || ! $this->settings->get_client_id() ) {
			$this->redirect_with_notice(
				__( 'Push-Test übersprungen: kein gültiger API-Schlüssel oder kein registrierter Client.', 'erecht24' ),
				'warning',
				'status'
			);
		}

		$push_response = $this->api_client->test_push_ping();

		if ( is_wp_error( $push_response ) ) {
			$message          = $push_response->get_error_message();
			$client_not_found = false !== strpos( $message, 'kein Client mit der übergebenen client_id gefunden' );

			if ( $client_not_found ) {
				$this->settings->add_log( 'Remote push test: client_id unknown at eRecht24. Clearing local registration and re-registering.' );
				$this->settings->save_api_connection( $this->settings->get_api_key(), 0, '' );
				delete_transient( 'erecht24_push_reregister_attempted' );
				$succeeded = Plugin::instance()->maybe_reregister_push_client();

				if ( $succeeded ) {
					$this->redirect_with_notice(
						__( 'Der registrierte Client war bei eRecht24 nicht mehr bekannt (z. B. durch eine parallel installierte weitere Plugin-Version entfernt). Der Push-Client wurde automatisch neu registriert.', 'erecht24' ),
						'success',
						'status'
					);
				}

				$this->settings->set_push_test_failed( true );
				$this->redirect_with_notice(
					__( 'Der registrierte Client war bei eRecht24 nicht mehr bekannt. Die automatische Neu-Registrierung ist fehlgeschlagen. Bitte über den Button „Push-Client jetzt neu registrieren" erneut versuchen.', 'erecht24' ),
					'error',
					'status'
				);
			}

			$this->settings->set_push_test_failed( true );
			$this->redirect_with_notice( wp_strip_all_tags( $push_response->get_error_message() ), 'error', 'status' );
		}

		$this->settings->set_push_test_failed( false );
		$this->redirect_with_notice(
			__( 'Der eRecht24 Server kann den WordPress-Push-Endpoint erreichen.', 'erecht24' ),
			'success',
			'status'
		);
	}

	/**
	 * Bypass the rate limit and immediately retry push client re-registration
	 * after an explicit admin action (e.g. for staging systems where waiting
	 * for the automatic retry window is impractical).
	 */
	public function handle_force_reregister(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'erecht24' ) );
		}

		check_admin_referer( 'erecht24_legal_text_force_reregister' );

		delete_transient( 'erecht24_push_reregister_attempted' );
		$succeeded = Plugin::instance()->maybe_reregister_push_client( true );

		if ( $succeeded ) {
			$this->redirect_with_notice( __( 'Push-Client wurde erfolgreich neu registriert.', 'erecht24' ), 'success', 'status' );
		}

		$this->redirect_with_notice(
			__( 'Die Neu-Registrierung ist fehlgeschlagen. Details siehe Log im Status-Bereich.', 'erecht24' ),
			'error',
			'status'
		);
	}

	/**
	 * Render tab navigation.
	 *
	 * @param string $active_tab Active tab.
	 */
	private function render_tabs( string $active_tab ): void {
		$tabs = array(
			'settings' => array(
				'label' => __( 'API-Schlüssel', 'erecht24' ),
				'icon'  => 'dashicons-admin-network',
			),
		);

		foreach ( Settings::document_labels() as $tab => $label ) {
			$tabs[ $tab ] = array(
				'label' => $label,
				'icon'  => 'imprint' === $tab ? 'dashicons-media-text' : ( 'privacy_policy' === $tab ? 'dashicons-shield-alt' : 'dashicons-share' ),
			);
		}

		$tabs['google_analytics'] = array(
			'label' => __( 'Google Analytics', 'erecht24' ),
			'icon'  => 'dashicons-chart-line',
		);
		$tabs['status']           = array(
			'label' => __( 'Status', 'erecht24' ),
			'icon'  => 'dashicons-clipboard',
		);
		$tabs['help']             = array(
			'label' => __( 'Hilfe', 'erecht24' ),
			'icon'  => 'dashicons-editor-help',
		);

		echo '<nav class="nav-tab-wrapper">';

		foreach ( $tabs as $tab => $tab_data ) {
			printf(
				'<a class="nav-tab %1$s" href="%2$s"><span class="dashicons %3$s"></span> %4$s</a>',
				$active_tab === $tab ? 'nav-tab-active' : '',
				esc_url( $this->page_url( $tab ) ),
				esc_attr( $tab_data['icon'] ),
				esc_html( $tab_data['label'] )
			);
		}

		echo '</nav>';
	}

	/**
	 * Render API settings tab.
	 */
	private function render_settings_tab(): void {
		$settings     = $this->settings->get_all();
		$has_api_key  = '' !== $this->settings->get_api_key();
		$status       = $this->settings->get_api_key_status();
		$client_id    = absint( $settings['client_id'] );
		$push_enabled = '' !== (string) $settings['client_secret'];

		echo '<section class="erecht24-panel">';
		echo '<h2>' . esc_html__( 'API-Schlüssel', 'erecht24' ) . '</h2>';
		echo '<p>' . esc_html__( 'Externe API-Requests werden nur durch Speichern des API-Schlüssels, manuelle Synchronisierung oder einen autorisierten eRecht24-Push ausgelöst.', 'erecht24' ) . '</p>';

		echo '<form id="erecht24-save-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'erecht24_legal_text_save' );
		echo '<input type="hidden" name="action" value="erecht24_legal_text_save">';
		echo '<input type="hidden" name="tab" value="settings">';

		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="erecht24-api-key">' . esc_html__( 'API-Schlüssel', 'erecht24' ) . '</label></th><td>';
		printf(
			'<input id="erecht24-api-key" class="regular-text" type="password" name="erecht24[api_key]" value="" autocomplete="off" placeholder="%1$s">',
			esc_attr( $has_api_key ? __( 'Gespeicherter API-Schlüssel unverändert', 'erecht24' ) : __( 'API-Schlüssel eintragen', 'erecht24' ) )
		);

		if ( $has_api_key ) {
			echo '<p class="description">' . esc_html__( 'Gespeichert:', 'erecht24' ) . ' <code>' . esc_html( Settings::mask_secret( $this->settings->get_api_key() ) ) . '</code></p>';
			echo '<input type="checkbox" id="erecht24-delete-api-key" name="erecht24[delete_api_key]" value="1" class="erecht24-hidden">';
			echo '<button type="button" class="button erecht24-delete-api-key-btn" id="erecht24-delete-api-key-btn">' . esc_html__( 'API-Schlüssel entfernen', 'erecht24' ) . '</button>';
		} else {
			echo '<p class="description">' . wp_kses_post(
				sprintf(
					/* translators: %s: eRecht24 project manager URL. */
					__( 'Geben Sie hier Ihren API-Schlüssel ein, welchen Sie für Ihre Website im <a href="%s" target="_blank" rel="noopener noreferrer">eRecht24 Projekt Manager für Websites</a> erzeugt haben.', 'erecht24' ),
					esc_url( 'https://www.e-recht24.de/mitglieder/tools/projekt-manager/' )
				)
			) . '</p>';
		}

		echo '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Status', 'erecht24' ) . '</th><td>';
		echo '<span class="erecht24-status-pill erecht24-status-' . esc_attr( $status ) . '">' . esc_html( $this->get_api_status_label( $status ) ) . '</span>';
		if ( 'invalid' === $status ) {
			echo '<p class="description erecht24-danger-text">' . esc_html__( 'Remote-Synchronisierung ist deaktiviert, bis ein gültiger API-Schlüssel gespeichert wurde. Bereits gespeicherte Rechtstexte werden weiterhin ausgegeben.', 'erecht24' ) . '</p>';
		}
		echo '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Push-Endpoint', 'erecht24' ) . '</th><td>';
		echo '<code>' . esc_html( rest_url( 'erecht24/v1/push' ) ) . '</code>';
		echo '<p class="description">' . esc_html( $push_enabled ? __( 'Push-Updates sind registriert.', 'erecht24' ) : __( 'Push-Updates sind nicht registriert. Manuelle Synchronisierung ist davon unabhängig.', 'erecht24' ) ) . '</p>';
		if ( $client_id ) {
			echo '<p class="description">' . esc_html__( 'Client-ID:', 'erecht24' ) . ' ' . esc_html( (string) $client_id ) . '</p>';
		}
		echo '</td></tr>';
		echo '</tbody></table>';
		echo '</form>';

		echo '<div class="erecht24-form-actions">';
		printf(
			'<button type="submit" form="erecht24-save-form" class="button button-primary">%s</button>',
			esc_html__( 'API-Einstellungen speichern', 'erecht24' )
		);

		if ( $has_api_key ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'erecht24_legal_text_sync' );
			echo '<input type="hidden" name="action" value="erecht24_legal_text_sync">';
			echo '<input type="hidden" name="document" value="all">';
			printf(
				'<button type="submit" class="button button-secondary">%s</button>',
				esc_html__( 'Alle Rechtstexte synchronisieren', 'erecht24' )
			);
			echo '</form>';
		}
		echo '</div>';

		echo '</section>';
	}

	/**
	 * Render one document tab.
	 *
	 * @param string $type Document type.
	 */
	private function render_document_tab( string $type ): void {
		$type              = Settings::normalize_document_type( $type );
		$labels            = Settings::document_labels();
		$document          = $this->settings->get_document( $type );
		$descriptions      = Settings::document_labels_description();
		$description       = $descriptions[ $type ];
		$can_render        = $this->settings->can_render_documents();
		$selected_source   = 'local' === ( $document['source'] ?? '' ) ? 'local' : 'remote';
		$sync_hidden_class = 'remote' === $selected_source ? '' : ' erecht24-hidden';
		$copy_hidden_class = 'local' === $selected_source ? '' : ' erecht24-hidden';

		if ( '' === $type || empty( $document ) ) {
			echo '<p>' . esc_html__( 'Unbekannter Rechtstext.', 'erecht24' ) . '</p>';
			return;
		}

		echo '<section class="erecht24-panel">';
		echo '<h2>' . esc_html( $labels[ $type ] ) . '</h2>';
		if ( ! empty( $description ) ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}

		$this->render_shortcode_examples( $type );

		if ( ! $can_render ) {
			echo '<div class="erecht24-admin-warning">' . esc_html__( 'Kein gültiger API-Schlüssel aktiv. Remote-Synchronisierung ist deaktiviert. Bereits synchronisierte und lokale Rechtstexte werden weiterhin ausgegeben.', 'erecht24' ) . '</div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="erecht24-document-form">';
		wp_nonce_field( 'erecht24_legal_text_save' );
		echo '<input type="hidden" name="action" value="erecht24_legal_text_save">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $type ) . '">';

		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row">' . esc_html__( 'Datenquelle', 'erecht24' ) . '</th><td>';
		$this->render_source_toggle( $type, $selected_source );
		echo '</td></tr>';

		$this->render_textarea_row( $type, 'remote', 'de', __( 'HTML-Code (DE)', 'erecht24' ), (string) $document['remote']['de'], true );
		$this->render_textarea_row( $type, 'remote', 'en', __( 'HTML-Code (EN)', 'erecht24' ), (string) $document['remote']['en'], true );
		$this->render_textarea_row( $type, 'local', 'de', __( 'Lokales HTML (DE)', 'erecht24' ), (string) $document['local']['de'], false );
		$this->render_textarea_row( $type, 'local', 'en', __( 'Lokales HTML (EN)', 'erecht24' ), (string) $document['local']['en'], false );

		echo '<tr data-erecht24-source-row="remote"><th scope="row">' . esc_html__( 'Letzte Änderung im eRecht24 Projekt Manager', 'erecht24' ) . '</th><td><code>' . esc_html( (string) $document['remote']['modified'] ) . '</code></td></tr>';
		echo '<tr data-erecht24-source-row="local"><th scope="row">' . esc_html__( 'Letzte lokale Änderung', 'erecht24' ) . '</th><td><code>' . esc_html( (string) $document['local']['modified'] ) . '</code></td></tr>';
		echo '</tbody></table>';

		echo '<p class="submit">';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Rechtstext-Einstellungen speichern', 'erecht24' ) . '</button> ';
		printf(
			'<button type="submit" class="button button-secondary%2$s" name="erecht24[sync_document]" value="%1$s" data-erecht24-source-action="remote" %3$s>%4$s</button>',
			esc_attr( $type ),
			esc_attr( $sync_hidden_class ),
			disabled( ! $can_render, true, false ),
			esc_html__( 'Diesen Rechtstext synchronisieren und speichern', 'erecht24' )
		);
		echo ' ';
		printf(
			'<button type="submit" class="button button-secondary erecht24-copy-remote-to-local%2$s" name="erecht24[copy_remote_to_local_document]" value="%1$s" data-erecht24-source-action="local">%3$s</button>',
			esc_attr( $type ),
			esc_attr( $copy_hidden_class ),
			esc_html__( 'Lokale Daten mit den zuletzt synchronisierten Texten überschreiben', 'erecht24' )
		);
		echo '</p>';
		echo '</form>';
		echo '</section>';
	}

	/**
	 * Render help tab.
	 */
	private function render_help_tab(): void {
		echo '<section class="erecht24-panel erecht24-help-content">';

		$documentation = $this->get_help_documentation_html();

		if ( '' !== $documentation ) {
			$result = $this->build_help_toc( $documentation );

			if ( ! empty( $result['toc'] ) ) {
				echo '<nav class="erecht24-help-toc" aria-label="' . esc_attr__( 'Seitennavigation', 'erecht24' ) . '">';
				echo '<strong class="erecht24-help-toc-title">' . esc_html_x( 'Inhalt', 'Überschrift des Inhaltsverzeichnisses in der Hilfe', 'erecht24' ) . '</strong>';
				echo '<ol class="erecht24-help-toc-list">';
				foreach ( $result['toc'] as $item ) {
					printf(
						'<li class="erecht24-help-toc-h%1$d"><a href="#%2$s">%3$s</a></li>',
						(int) $item['level'],
						esc_attr( (string) $item['id'] ),
						esc_html( (string) $item['text'] )
					);
				}
				echo '</ol></nav>';
			}

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() ist die Escape-Funktion; Inhalt stammt aus lokaler Markdown-Datei
			echo wp_kses_post( $result['html'] );
		} else {
			echo '<h2>' . esc_html__( 'Hilfe', 'erecht24' ) . '</h2>';
			echo '<p>' . esc_html__( 'Die Hilfedatei konnte nicht geladen werden.', 'erecht24' ) . '</p>';
		}

		echo '</section>';
	}

	/**
	 * Inject id attributes into h2/h3 headings and collect TOC items.
	 *
	 * @param string $html Rendered help HTML.
	 * @return array{toc: array<int, array{level: int, id: string, text: string}>, html: string}
	 */
	private function build_help_toc( string $html ): array {
		$toc_items = array();
		$seen      = array();

		$body = (string) preg_replace_callback(
			'/<(h[23])>(.*?)<\/h[23]>/si',
			function ( array $m ) use ( &$toc_items, &$seen ): string {
				$tag   = $m[1];
				$inner = $m[2];
				$level = (int) substr( $tag, 1 );
				$text  = wp_strip_all_tags( $inner );
				$base  = 'help-' . sanitize_title( $text );
				$id    = $base;

				if ( isset( $seen[ $base ] ) ) {
					++$seen[ $base ];
					$id = $base . '-' . $seen[ $base ];
				} else {
					$seen[ $base ] = 0;
				}

				$toc_items[] = array(
					'level' => $level,
					'id'    => $id,
					'text'  => $text,
				);

				return sprintf( '<%1$s id="%2$s">%3$s</%1$s>', $tag, esc_attr( $id ), $inner );
			},
			$html
		);

		return array(
			'toc'  => $toc_items,
			'html' => $body,
		);
	}

	/**
	 * Load and convert the bundled Markdown documentation.
	 */
	private function get_help_documentation_html(): string {
		$file  = ERECHT24_LEGAL_TEXT_PATH . self::HELP_DOCUMENTATION_FILE;
		$mtime = is_readable( $file ) ? filemtime( $file ) : false;

		if ( false === $mtime ) {
			return '';
		}

		$cache_key = 'erecht24_help_html_' . ERECHT24_LEGAL_TEXT_VERSION . '_' . $mtime;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

        // phpcs:ignore WordPress.Security.EscapeOutput -- static local file path built from plugin constant
		$content = include $file;
		$html    = is_string( $content ) ? $this->markdown_to_html( $content ) : '';

		set_transient( $cache_key, $html, DAY_IN_SECONDS );

		return $html;
	}

	/**
	 * Convert the static help Markdown into a safe HTML subset.
	 *
	 * @param string $markdown Markdown content.
	 */
	private function markdown_to_html( string $markdown ): string {
		$lines = preg_split( "/\r\n|\n|\r/", str_replace( "\t", '    ', $markdown ) );

		if ( ! is_array( $lines ) ) {
			return '';
		}

		$html       = array();
		$paragraph  = array();
		$list_type  = '';
		$list_open  = false;
		$line_count = count( $lines );

		$flush_paragraph = function () use ( &$html, &$paragraph ): void {
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- phpstan annotation only
			/** @phpstan-ignore empty.variable ($paragraph wird via use-by-reference von außen befüllt) */
			if ( empty( $paragraph ) ) {
				return;
			}

			// @phpstan-ignore deadCode.unreachable
			$html[]    = '<p>' . $this->markdown_inline_to_html( implode( ' ', $paragraph ) ) . '</p>';
			$paragraph = array();
		};

		$close_list = function () use ( &$html, &$list_type, &$list_open ): void {
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- phpstan annotation only
			/** @phpstan-ignore booleanNot.alwaysTrue ($list_open wird via use-by-reference von außen gesetzt) */
			if ( ! $list_open ) {
				return;
			}

			// @phpstan-ignore deadCode.unreachable
			$html[]    = '</li></' . $list_type . '>';
			$list_type = '';
			$list_open = false;
		};

		for ( $index = 0; $index < $line_count; $index++ ) {
			$line    = rtrim( (string) $lines[ $index ] );
			$trimmed = trim( $line );

			if ( '' === $trimmed ) {
				$flush_paragraph();
				$close_list();
				continue;
			}

			$next_line = $index + 1 < $line_count ? trim( (string) $lines[ $index + 1 ] ) : '';

			if ( '' !== $next_line && preg_match( '/^(=+|-+)$/', $next_line, $setext_matches ) ) {
				$flush_paragraph();
				$close_list();

				$level  = '=' === $setext_matches[1][0] ? 2 : 3;
				$html[] = sprintf(
					'<h%1$d>%2$s</h%1$d>',
					$level,
					$this->markdown_inline_to_html( $trimmed )
				);
				++$index;
				continue;
			}

			if ( preg_match( '/^(#{1,6})\s+(.+)$/', $trimmed, $heading_matches ) ) {
				$flush_paragraph();
				$close_list();

				$level  = min( 6, strlen( $heading_matches[1] ) + 1 );
				$html[] = sprintf(
					'<h%1$d>%2$s</h%1$d>',
					$level,
					$this->markdown_inline_to_html( trim( $heading_matches[2], " \t#" ) )
				);
				continue;
			}

			if ( preg_match( '/^\s*(\d+)\.\s+(.+)$/', $line, $ordered_matches ) ) {
				$flush_paragraph();

				if ( 'ol' !== $list_type ) {
					$close_list();
					$html[]    = '<ol>';
					$list_type = 'ol';
					$list_open = true;
				} else {
					$html[] = '</li>';
				}

				$html[] = '<li>' . $this->markdown_inline_to_html( trim( $ordered_matches[2] ) );
				continue;
			}

			if ( preg_match( '/^\s*[*+-]\s+(.+)$/', $line, $unordered_matches ) ) {
				$flush_paragraph();

				if ( 'ul' !== $list_type ) {
					$close_list();
					$html[]    = '<ul>';
					$list_type = 'ul';
					$list_open = true;
				} else {
					$html[] = '</li>';
				}

				$html[] = '<li>' . $this->markdown_inline_to_html( trim( $unordered_matches[1] ) );
				continue;
			}

			if ( $list_open && preg_match( '/^\s{2,}(.+)$/', $line, $continuation_matches ) ) {
				$html[] = '<br>' . $this->markdown_inline_to_html( trim( $continuation_matches[1] ) );
				continue;
			}

			$close_list();
			$paragraph[] = $trimmed;
		}

		$flush_paragraph();
		$close_list();

		return wp_kses_post( implode( "\n", $html ) );
	}

	/**
	 * Convert inline Markdown syntax into escaped HTML.
	 *
	 * @param string $text Inline Markdown text.
	 */
	private function markdown_inline_to_html( string $text ): string {
		$tokens = array();
		$store  = static function ( string $html ) use ( &$tokens ): string {
			$token            = '%%ERECHT24MD' . count( $tokens ) . '%%';
			$tokens[ $token ] = $html;

			return $token;
		};

		foreach (
			array(
				'\_' => '_',
				'\*' => '*',
				'\-' => '-',
				'\[' => '[',
				'\]' => ']',
				'\(' => '(',
				'\)' => ')',
				'\.' => '.',
			) as $escaped => $literal
		) {
			$text = str_replace( $escaped, $store( esc_html( $literal ) ), $text );
		}

		$text = preg_replace_callback(
			'/`([^`]+)`/u',
			static function ( array $matches ) use ( $store ): string {
				return $store( '<code>' . esc_html( $matches[1] ) . '</code>' );
			},
			$text
		);

		if ( null === $text ) {
			return '';
		}

		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)(?:\s+"[^"]*")?\)/u',
			function ( array $matches ) use ( $store ): string {
				$label = esc_html( $this->markdown_unescape( $matches[1] ) );
				$url   = esc_url( $this->markdown_unescape( $matches[2] ) );

				if ( '' === $url ) {
					return $label;
				}

				return $store(
					sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						$url,
						$label
					)
				);
			},
			$text
		);

		if ( null === $text ) {
			return '';
		}

		$html = esc_html( $text );
		$html = (string) preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html );
		$html = (string) preg_replace( '/(?<!\w)_(.+?)_(?!\w)/s', '<em>$1</em>', $html );
		$html = (string) preg_replace( '/(?<!\*)\*([^*]+)\*(?!\*)/s', '<em>$1</em>', $html );
		$html = $this->markdown_unescape( $html );

		foreach ( $tokens as $token => $replacement ) {
			$html = str_replace( $token, $replacement, $html );
		}

		return wp_kses_post( $html );
	}

	/**
	 * Remove backslashes used for Markdown punctuation escaping.
	 *
	 * @param string $text Escaped Markdown text.
	 */
	private function markdown_unescape( string $text ): string {
		return str_replace(
			array( '\_', '\*', '\-', '\[', '\]', '\(', '\)', '\.', '\"' ),
			array( '_', '*', '-', '[', ']', '(', ')', '.', '"' ),
			$text
		);
	}

	/**
	 * Render Google Analytics tab.
	 */
	private function render_google_analytics_tab(): void {
		$options = $this->settings->get_google_analytics();

		echo '<section class="erecht24-panel">';
		echo '<h2>' . esc_html__( 'Google Analytics', 'erecht24' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Sie können den vollständigen Google Analytics Tracking-Code inkl. Codesnippet für das Setzen des Opt-Out-Cookies auch über dieses Plugin generieren lassen.', 'erecht24' ) . '</p>';
		echo '<div class="erecht24-admin-warning">' . esc_html__( 'Google Analytics ist standardmäßig deaktiviert. Aktiviere es nur, wenn deine Website die notwendige Einwilligung einholt.', 'erecht24' ) . '</div>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'erecht24_legal_text_save' );
		echo '<input type="hidden" name="action" value="erecht24_legal_text_save">';
		echo '<input type="hidden" name="tab" value="google_analytics">';
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="erecht24-ga-id">' . esc_html__( 'Measurement-ID', 'erecht24' ) . '</label></th><td>';
		echo '<input id="erecht24-ga-id" class="regular-text" name="erecht24[google_analytics][measurement]" value="' . esc_attr( (string) $options['measurement'] ) . '" placeholder="G-XXXXXXXX">';
		echo '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Optionen', 'erecht24' ) . '</th><td>';
		echo '<label><input type="checkbox" name="erecht24[google_analytics][enabled]" value="1" ' . checked( ! empty( $options['enabled'] ), true, false ) . '> ' . esc_html__( 'Google Analytics Code ausgeben', 'erecht24' ) . '</label><br>';
		echo '<label><input type="checkbox" name="erecht24[google_analytics][usercentrics]" value="1" ' . checked( ! empty( $options['usercentrics'] ), true, false ) . '> ' . esc_html__( 'Usercentrics-kompatibel als deaktiviertes Script ausgeben', 'erecht24' ) . '</label><br>';
		echo '<label><input type="checkbox" name="erecht24[google_analytics][opt_out]" value="1" ' . checked( ! empty( $options['opt_out'] ), true, false ) . '> ' . esc_html__( 'Opt-out Helper bereitstellen', 'erecht24' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'Opt-out Link:', 'erecht24' ) . ' <code>&lt;a onclick=&quot;eRecht24GaOptout();&quot;&gt;Google Analytics deaktivieren&lt;/a&gt;</code></p>';
		echo '</td></tr>';
		echo '</tbody></table>';
		submit_button( __( 'Google-Analytics-Einstellungen speichern', 'erecht24' ) );
		echo '</form>';
		echo '</section>';
	}

	/**
	 * Render status tab with local debug export.
	 */
	private function render_status_tab(): void {
		$debug_data = $this->get_debug_data();

		echo '<section class="erecht24-panel">';
		echo '<h2>' . esc_html__( 'Pluginstatus', 'erecht24' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Hier können Sie prüfen, ob Ihr Server die Systemvoraussetzungen für dieses Plugin erfüllt und ob die eRecht24-Server erreichbar sind.', 'erecht24' ) . '</p>';
		echo '<p>' . esc_html__( 'Die Daten werden nur lokal angezeigt und erst durch Klick in die Zwischenablage kopiert. API-Schlüssel und Secret sind maskiert.', 'erecht24' ) . '</p>';
		echo '<table class="widefat striped erecht24-status-table"><tbody>';
		foreach ( $debug_data['status'] as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
		echo '</tbody></table>';

		if ( $this->settings->can_render_documents() && $this->settings->get_client_id() ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="erecht24-inline-form">';
			wp_nonce_field( 'erecht24_legal_text_push_test' );
			echo '<input type="hidden" name="action" value="erecht24_legal_text_push_test">';
			submit_button( __( 'Remote-Push-Test ausführen', 'erecht24' ), 'secondary', 'submit', false );
			echo '</form>';
		}

		if ( '' !== $this->settings->get_api_key() && ( '' === $this->settings->get_client_secret() || $this->settings->get_push_test_failed() ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="erecht24-inline-form">';
			wp_nonce_field( 'erecht24_legal_text_force_reregister' );
			echo '<input type="hidden" name="action" value="erecht24_legal_text_force_reregister">';
			submit_button( __( 'Push-Client jetzt neu registrieren', 'erecht24' ), 'secondary', 'submit', false );
			echo '</form>';
		}

		echo '<p><button type="button" class="button button-secondary" id="erecht24-copy-debug">' . esc_html__( 'Log- und Systeminfos kopieren', 'erecht24' ) . '</button> <span id="erecht24-copy-debug-result" class="description"></span></p>';
		echo '<textarea id="erecht24-debug-data" class="large-text code" rows="14" readonly>' . esc_textarea( wp_json_encode( $debug_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</textarea>';
		echo '</section>';
	}

	/**
	 * Render shortcode example.
	 *
	 * @param string $type Document type.
	 */
	private function render_shortcode_examples( string $type ): void {
		echo '<div class="erecht24-shortcodes"><strong>' . esc_html__( 'Shortcode-Beispiel', 'erecht24' ) . '</strong>';
		echo '<code>' . esc_html( sprintf( '[erecht24 type="%s" lang="de"]', $type ) ) . '</code>';
		echo '</div>';
	}

	/**
	 * Synchronize a single document after saving the current form.
	 *
	 * @param string $document Document type.
	 */
	private function sync_document_and_redirect( string $document ): void {
		$type   = Settings::normalize_document_type( $document );
		$labels = Settings::document_labels();

		if ( '' === $type ) {
			$this->redirect_with_notice( __( 'Es wurde kein gültiger Rechtstext-Typ übergeben.', 'erecht24' ), 'error', 'settings' );
		}

		$response = $this->api_client->fetch_document( $type );

		if ( is_wp_error( $response ) ) {
			$this->redirect_with_notice( wp_strip_all_tags( $response->get_error_message() ), 'error', $type );
		}

		$this->settings->save_remote_document( $type, $response );

		$this->redirect_with_notice(
			sprintf(
				/* translators: %s: document label. */
				__( '%s wurde synchronisiert und die lokalen Einstellungen wurden gespeichert.', 'erecht24' ),
				esc_html( $labels[ $type ] ?? $type )
			),
			'success',
			$type
		);
	}

	/**
	 * Copy a synchronized document into the local editable fields.
	 *
	 * @param string $document Document type.
	 */
	private function copy_remote_document_to_local_and_redirect( string $document ): void {
		$type   = Settings::normalize_document_type( $document );
		$labels = Settings::document_labels();

		if ( '' === $type ) {
			$this->redirect_with_notice( __( 'Es wurde kein gültiger Rechtstext-Typ übergeben.', 'erecht24' ), 'error', 'settings' );
		}

		$copied = $this->settings->copy_remote_document_to_local( $type );

		if ( empty( $copied ) ) {
			$this->redirect_with_notice(
				__( 'Es wurden keine zuletzt synchronisierten Texte gefunden. Bitte synchronisieren Sie diesen Rechtstext zuerst.', 'erecht24' ),
				'error',
				$type
			);
		}

		$this->redirect_with_notice(
			sprintf(
				/* translators: %s: document label. */
				__( '%s: Lokale Daten wurden mit den zuletzt synchronisierten Texten überschrieben.', 'erecht24' ),
				esc_html( $labels[ $type ] ?? $type )
			),
			'success',
			$type
		);
	}

	/**
	 * Render segmented source toggle.
	 *
	 * @param string $type Document type.
	 * @param string $selected Selected value.
	 * @param bool   $disabled Whether the control is disabled.
	 */
	private function render_source_toggle( string $type, string $selected, bool $disabled = false ): void {
		$options = array(
			'remote' => __( 'eRecht24 Projekt Manager', 'erecht24' ),
			'local'  => __( 'Lokaler Text', 'erecht24' ),
		);

		echo '<div class="erecht24-segmented" role="radiogroup">';
		foreach ( $options as $value => $label ) {
			printf(
				'<label class="%1$s"><input type="radio" name="erecht24[documents][%2$s][source]" value="%3$s" %4$s %5$s> <span>%6$s</span></label>',
				esc_attr( $selected === $value ? 'is-active' : '' ),
				esc_attr( $type ),
				esc_attr( $value ),
				checked( $value, $selected, false ),
				disabled( $disabled, true, false ),
				esc_html( $label )
			);
		}
		echo '</div>';

		if ( $disabled ) {
			echo '<input type="hidden" name="erecht24[documents][' . esc_attr( $type ) . '][source]" value="' . esc_attr( $selected ) . '">';
		}
	}

	/**
	 * Render textarea row.
	 *
	 * @param string $type Document type.
	 * @param string $source Source key.
	 * @param string $language Language key.
	 * @param string $label Field label.
	 * @param string $value Current value.
	 * @param bool   $is_readonly Whether field is readonly.
	 */
	private function render_textarea_row( string $type, string $source, string $language, string $label, string $value, bool $is_readonly ): void {
		$name = sprintf( 'erecht24[documents][%s][%s][%s]', $type, $source, $language );

		echo '<tr data-erecht24-source-row="' . esc_attr( $source ) . '"><th scope="row"><label for="' . esc_attr( $source . '-' . $language ) . '">' . esc_html( $label ) . '</label></th><td>';
		printf(
			'<textarea id="%1$s" class="large-text code" rows="8" name="%2$s" %3$s>%4$s</textarea>',
			esc_attr( $source . '-' . $language ),
			esc_attr( $name ),
			$is_readonly ? 'readonly' : '',
			esc_textarea( $value )
		);
		echo '</td></tr>';
	}

	/**
	 * Return active tab.
	 */
	private function get_active_tab(): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation, no state change
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';

		if ( in_array( $tab, array( 'settings', 'google_analytics', 'status', 'help' ), true ) || isset( Settings::DOCUMENT_TYPES[ $tab ] ) ) {
			return $tab;
		}

		return 'settings';
	}

	/**
	 * Build admin page URL.
	 *
	 * @param string $tab Optional tab.
	 */
	private function page_url( string $tab = 'settings' ): string {
		return add_query_arg(
			array(
				'page' => self::MENU_SLUG,
				'tab'  => $tab,
			),
			admin_url( 'options-general.php' )
		);
	}

	/**
	 * Store a notice and redirect back to the settings page.
	 *
	 * @param string $message Notice message.
	 * @param string $type Notice type.
	 * @param string $tab Destination tab.
	 */
	private function redirect_with_notice( string $message, string $type = 'success', string $tab = 'settings' ): void {
		set_transient(
			$this->notice_key(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			60
		);

		wp_safe_redirect( $this->page_url( $tab ) );
		exit;
	}

	/**
	 * Build notice transient key.
	 */
	private function notice_key(): string {
		return 'erecht24_legal_text_notice_' . get_current_blog_id() . '_' . get_current_user_id();
	}

	/**
	 * Return readable API status label.
	 *
	 * @param string $status API status.
	 */
	private function get_api_status_label( string $status ): string {
		$labels = array(
			'missing'   => __( 'Kein API-Schlüssel', 'erecht24' ),
			'unchecked' => __( 'Nicht erneut geprüft', 'erecht24' ),
			'valid'     => __( 'Gültig', 'erecht24' ),
			'invalid'   => __( 'Ungültig', 'erecht24' ),
		);

		return $labels[ $status ] ?? $labels['missing'];
	}

	/**
	 * Build redacted debug data for local clipboard export.
	 *
	 * @return array<string,mixed>
	 */
	private function get_debug_data(): array {
		global $wp_version;

		$settings      = $this->settings->get_all();
		$documents     = array();
		$status_checks = $this->run_status_checks();

		foreach ( Settings::document_labels() as $type => $label ) {
			$document           = $this->settings->get_document( $type );
			$documents[ $type ] = array(
				'label'           => $label,
				'source'          => $document['source'] ?? '',
				'remote_modified' => $document['remote']['modified'] ?? '',
				'local_modified'  => $document['local']['modified'] ?? '',
				'has_remote_de'   => ! empty( $document['remote']['de'] ),
				'has_remote_en'   => ! empty( $document['remote']['en'] ),
				'has_local_de'    => ! empty( $document['local']['de'] ),
				'has_local_en'    => ! empty( $document['local']['en'] ),
			);
		}

		return array(
			'status'    => array(
				'WordPress'        => implode( '.', array_slice( explode( '.', (string) $wp_version ), 0, 2 ) ),
				'PHP'              => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
				'Plugin'           => ERECHT24_LEGAL_TEXT_VERSION,
				'API-Key-Status'   => $this->get_api_status_label( $this->settings->get_api_key_status() ),
				'API-Key'          => Settings::mask_secret( $this->settings->get_api_key() ),
				'Client-ID'        => (string) $this->settings->get_client_id(),
				'Push-Endpoint'    => rest_url( 'erecht24/v1/push' ),
				'Home-URL'         => home_url( '/' ),
				'Site-URL'         => site_url( '/' ),
				'WP HTTP API'      => $status_checks['wp_remote']['message'],
				'Remote-Push-Test' => $status_checks['push']['message'],
				'REST API'         => rest_url(),
			),
			'documents' => $documents,
			'logs'      => $this->settings->get_logs(),
			'checks'    => $status_checks,
			'ga'        => array(
				'enabled'      => ! empty( $settings['google_analytics']['enabled'] ),
				'measurement'  => ! empty( $settings['google_analytics']['measurement'] ) ? Settings::mask_secret( (string) $settings['google_analytics']['measurement'] ) : '',
				'usercentrics' => ! empty( $settings['google_analytics']['usercentrics'] ),
				'opt_out'      => ! empty( $settings['google_analytics']['opt_out'] ),
			),
		);
	}

	/**
	 * Build local status checks without automatic external requests.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function run_status_checks(): array {
		$wp_remote_ok      = function_exists( 'wp_remote_request' );
		$wp_remote_message = $wp_remote_ok
			? __( 'WP HTTP API ist verfügbar.', 'erecht24' )
			: __( 'WP HTTP API ist nicht verfügbar.', 'erecht24' );

		$push_ok      = false;
		$push_message = __( 'Push-Test übersprungen: kein gültiger API-Schlüssel oder kein registrierter Client.', 'erecht24' );

		if ( $this->settings->can_render_documents() && $this->settings->get_client_id() ) {
			$push_message = __( 'Push-Test nicht automatisch ausgeführt. Starte den Test über den Button im Status-Reiter.', 'erecht24' );
		}

		return array(
			'wp_remote' => array(
				'success' => $wp_remote_ok,
				'message' => $wp_remote_message,
			),
			'push'      => array(
				'success' => $push_ok,
				'message' => $push_message,
			),
		);
	}
}
