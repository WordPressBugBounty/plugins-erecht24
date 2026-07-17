=== eRecht24 Legal Texts ===
Contributors: erecht24, zandererecht24
Tags: legal, imprint, privacy, shortcode, gutenberg
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 4.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Outputs legal texts via shortcode and Gutenberg block — manually entered or synchronised from the eRecht24 API.

== Description ==

**This plugin is designed for the German-speaking market (Germany, Austria, Switzerland).** It is intended for websites subject to German law. The admin interface is in German.

eRecht24 Legal Texts outputs legal texts (imprint, privacy policy) via shortcode or Gutenberg block. Legal texts are stored in the local WordPress database and displayed from there — no external request is needed for output.

The plugin works in two modes:

* **Without an eRecht24 account:** Enter your legal texts manually in the plugin settings. All shortcode and block output is fully functional.
* **With an eRecht24 account:** Connect the plugin to the eRecht24 API to synchronise legal texts automatically. Once synchronised, texts are stored locally and continue to be displayed even if the API key is later removed.

The shortcode is the core feature. This makes the output work in the Classic Editor, Gutenberg, Elementor, Divi, WPBakery, and other page builders that support WordPress shortcodes.

External requests are only triggered when an authorized administrator saves or removes the API key, starts a synchronization or remote push test, explicitly enables Google Analytics, uninstalls the plugin with a stored eRecht24 client registration, or an authorized eRecht24 push with a valid secret is received.

When an eRecht24 account is connected, the plugin uses the eRecht24 API to retrieve legal texts. The legal texts (imprint, privacy policy) are authored, reviewed, and maintained by eRecht24's legal team on their servers. This content cannot be generated locally by the plugin. Retrieved texts are stored in the local WordPress database; the API is only contacted during synchronisation, not during output.

* Service: eRecht24 API
* Provider: eRecht24 GmbH & Co. KG
* API host: https://api.e-recht24.de
* Terms of service: https://www.e-recht24.de/agb/
* Privacy policy: https://www.e-recht24.de/datenschutzerklaerung.html
* Website: https://www.e-recht24.de/

Optionally, the plugin can output a Google Analytics tracking snippet if an administrator explicitly enables this feature. Visitor data is collected and processed by Google on their servers; this cannot be done locally by the plugin.

* Service: Google Tag / Google Analytics
* Script host: https://www.googletagmanager.com
* Terms of service: https://policies.google.com/terms
* Privacy policy: https://policies.google.com/privacy


== Screenshots ==

1. API key registration
2. Synchronising a legal text
3. Google Analytics integration
4. Status information

== Installation ==

1. If a previous eRecht24 plugin version (v3.x) is installed, deactivate it first, before installing or updating to this version.
2. Copy the plugin directory to `wp-content/plugins/erecht24`.
3. Activate the plugin in WordPress.
4. Go to Settings > eRecht24 Legal Texts and either enter your legal texts manually, or save your eRecht24 API key to synchronise them automatically.
5. Use the shortcode or Gutenberg block in pages, posts, or page builders.

== Usage ==

Shortcode examples:

Imprint in German
`[erecht24 type="imprint" lang="de"]`

Privacy policy in German
`[erecht24 type="privacy_policy" lang="de"]`

Imprint in English, heading stripped (e.g. because it already exists on the page)
`[erecht24 type="imprint" lang="en" strip_title="true"]`


== Frequently Asked Questions ==

= Does the plugin work with Elementor, Divi, and WPBakery? =

Yes. Output is handled via WordPress shortcodes. Elementor also gets a small optional widget, but the core feature does not depend on any page builder.

= Are external scripts loaded in the frontend? =

No by default. Google Analytics is only output when an administrator enables the feature in the plugin settings.

= Is the API key exposed in the frontend? =

No. The API key is stored server-side in a WordPress option and used only for API requests.

= Are old options migrated? =

Yes. On activation, known options from the previous eRecht24 plugin are read and copied into the new option. The old options are not deleted.

= Can the previous eRecht24 plugin remain active at the same time? =

No. Both plugins register some of the same shortcodes, in particular `[erecht24]` and `[erecht24_widget]`. Deactivate the previous plugin before installing or updating to this version, not just afterwards.

== Privacy ==

When saving or removing the API key, during manual synchronisation, during an explicitly triggered remote push test, during authorised push updates, and when uninstalling with a stored eRecht24 client registration, this site's server communicates with the eRecht24 API. Technical connection data, the API key in the request header, and the registered push URL may be transmitted.

Authorised push updates are processed via the WordPress REST endpoint `erecht24/v1/push` using POST. The push secret is expected as the header `X-eRecht24-Secret` or in the POST/JSON body and is never read from URL query parameters.

When Google Analytics is enabled, the plugin loads the Google Tag from `www.googletagmanager.com`. This feature is disabled by default and must be consciously switched on by an administrator.

The plugin adds a suggested privacy policy text via the WordPress privacy policy feature.

== Upgrade Notice ==

= 4.0.0 =

Complete rewrite. Settings are migrated automatically. Deactivate the previous eRecht24 plugin to avoid shortcode conflicts. Legacy shortcodes [impressum]/[datenschutz] are no longer supported — use [erecht24 type="imprint"] / [erecht24 type="privacy_policy"].

== Changelog ==

= 4.0.3 =

* Fixed: Sites migrated from the previous eRecht24 plugin could end up with a stored API key but no registered push client.
* Added: A persistent admin notice now warns when an API key is stored but no push client is registered, so this can be resolved by re-saving the API key.

= 4.0.2 =

* Fixed: The Elementor widget now also registers under the legacy widget name used by plugin versions prior to 4.0; display issues resolved.
* Removed subscription to the deprecated `elementor/widgets/widgets_registered` Elementor hook.

= 4.0.1 =

* Fixed Contributors list and installation path in the readme.

= 4.0.0 =

* Complete rewrite with a modernised, accessible admin interface.
* All legal texts (imprint, privacy policy, social media privacy policy) are now stored in a single encrypted WordPress option instead of several separate options.
* API key is encrypted at rest using a site-specific key.
* Automatic migration of API key, push client credentials, Google Analytics settings, and all local and remote legal text content from the previous plugin on first activation.
* New Gutenberg block for inserting legal texts directly in the block editor.
* Elementor widget added as an optional integration.
* Push updates from the eRecht24 server are received via a dedicated REST API endpoint (`erecht24/v1/push`) with secret-based authorisation.
* Manual synchronisation of individual or all legal texts directly from the admin page.
* Google Analytics output is disabled by default and must be explicitly enabled by an administrator.
* Status tab with system requirements check, push test, and exportable debug log.
* Requires WordPress 6.3 and PHP 7.4 or higher.
* Legacy sites still relying on the unprefixed shortcodes `[impressum]` and `[datenschutz]` are no longer supported, to avoid naming conflicts with other plugins and themes. Use `[erecht24 type="imprint"]` or `[erecht24 type="privacy_policy"]` instead.

