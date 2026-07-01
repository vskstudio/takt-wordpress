=== Takt Analytics ===
Contributors: vskstudio
Tags: analytics, privacy, woocommerce, statistics, tracking
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.3.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Privacy-first analytics for WordPress: injects the Takt snippet and reports WooCommerce purchases as server-to-server events.

== Description ==

Takt Analytics connects your WordPress site to Takt, privacy-first web analytics.

* Injects the Takt browser snippet into every page (inline, CDN, self-hosted asset or ES-module SDK).
* Autocapture for outbound links, file downloads, tagged events and 404s.
* Advanced controls: sampling rate, query-string handling with a param allowlist, Do-Not-Track override and a tracking kill-switch.
* Sends WooCommerce orders as server-to-server purchase events with revenue, so sales are tracked even when an order completes off-session (payment callback, admin, cron).
* Uses WordPress' own HTTP API for server-to-server requests — no bundled HTTP libraries.
* All dependencies are namespace-isolated, so the plugin never conflicts with other plugins.

A Takt endpoint and API key are required.

== Installation ==

1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin, or extract it into wp-content/plugins/.
2. Activate the plugin.
3. Go to Settings → Takt Analytics and enter your domain, endpoint and API key.

For server-to-server purchase tracking, keep WooCommerce active and choose the order status that should fire the event.

You can also define the API key in wp-config.php so it never lives in the database:

`define('TAKT_API_KEY', 'your-key');`

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No. WooCommerce is only required for purchase (revenue) tracking. The browser snippet works on any site.

= Is customer data shared? =

Purchase events are sent from your server to your own Takt endpoint and include the customer's IP and user agent for attribution. They are never logged by the plugin. The browser tracker honours Do Not Track.

= Where is my API key stored? =

In the WordPress options table, or in a wp-config.php constant (TAKT_API_KEY) which takes precedence. The settings field is write-only and never echoes the stored key.

== Changelog ==

= 0.1.0 =
* Initial release: snippet injection and WooCommerce server-to-server purchase events.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
