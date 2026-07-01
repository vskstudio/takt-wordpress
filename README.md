# Takt Analytics for WordPress

The official WordPress plugin for [Takt](https://github.com/vskstudio/takt-wordpress), privacy-first web analytics. It injects the Takt browser snippet into your site and — when WooCommerce is active — reports completed orders as server-to-server purchase events with revenue.

- **Snippet injection** into `wp_head` (inline, CDN, self-hosted asset or ES-module SDK).
- **Autocapture** for outbound links, file downloads, tagged events and 404s.
- **WooCommerce purchases** sent server-to-server, so revenue is tracked even when an order completes off-session (IPN, admin, cron).
- **No bundled HTTP stack** — server-to-server requests go through WordPress' own `wp_remote_post`.
- **Dependency-isolated** — all Composer dependencies are namespaced under `Takt\WP\Vendor\`, so the plugin never clashes with another plugin's PSR-7/HTTP libraries.

## Requirements

- WordPress 6.0+
- PHP 8.1+
- WooCommerce (optional, for purchase tracking)
- A Takt endpoint and API key

## Installation

1. Download `takt-analytics.zip` from the [latest release](https://github.com/vskstudio/takt-wordpress/releases).
2. In **WP Admin → Plugins → Add New → Upload Plugin**, upload the ZIP and activate it.
3. Open **Settings → Takt Analytics** and configure your domain and endpoint. For the hosted service, set the API endpoint to `https://taktlytics.com`.

Or via WP-CLI:

```bash
wp plugin install takt-analytics.zip --activate
```

The release ZIP is self-contained: it bundles `takt-core-php` and its PSR-7 dependencies (scoped), so no `composer install` is needed on the server.

## Configuration

**Settings → Takt Analytics**

| Setting | Description |
| --- | --- |
| Domain | The site identifier sent with every event (e.g. `example.com`). |
| Mode | `inline` (snippet embedded), `cdn`, `asset` (self-hosted) or `sdk` (ES-module, needed for URL scrubbing). |
| Outbound / Downloads / Tagged / 404 | Autocapture toggles for the browser tracker. |
| Exclude localhost | Skip tracking on local hostnames (on for production). |
| File extensions | Which extensions count as downloads. |
| Script origin | Base URL for `cdn`/`asset` modes. |
| Sampling rate | Keep a fraction of hits, e.g. `0.5` for ~50%. Empty tracks everything. |
| Keep query string | Keep the raw query string + hash in tracked URLs (off strips them). |
| Kept query params | Allowlist of params to keep when *Keep query string* is off (e.g. `utm_source, utm_medium`). |
| Excluded paths | Path prefixes never tracked (e.g. `/app, /account`). SDK mode only — ignored in other modes. |
| Ignore Do-Not-Track | Stop honoring the browser Do-Not-Track header. |
| Pause tracking | Kill-switch: disable tracking entirely without removing the plugin. |
| WooCommerce | Send purchase events on order completion. |
| Trigger status | Order status that fires the purchase (`completed` or `processing`). |
| API endpoint | Your Takt ingest endpoint (server-to-server). Use the hosted Takt origin `https://taktlytics.com` unless you run a self-hosted instance. |
| API key | Bearer token for server-to-server events (write-only field). |

### API key via `wp-config.php`

For environments where secrets must not live in the database, define the key as a constant. It takes precedence over the stored value and the admin field becomes read-only:

```php
define('TAKT_API_KEY', 'your-key');
```

### URL scrubbing via `wp-config.php`

`scrubUrl` is a raw JavaScript function injected verbatim into the page, so it is **dev-controlled only** — it lives in a constant, never in the database or the admin UI, and only takes effect in `sdk` mode:

```php
define('TAKT_SCRUB_URL', '(u) => u.split("#")[0]');
```

### Privacy

WooCommerce purchase events are sent from your server to your Takt endpoint and include the customer's IP address and user agent for attribution. They are forwarded, never logged by the plugin. The browser tracker honours `Do Not Track` and a `takt_ignore` localStorage opt-out.

## Development

```bash
composer install
composer check        # php-cs-fixer + phpstan + phpunit
composer test         # unit tests only
```

### Building the release ZIP

```bash
bin/build.sh          # → dist/takt-analytics.zip
```

This installs production dependencies, scopes them under `Takt\WP\Vendor\` with [PHP-Scoper](https://github.com/humbug/php-scoper), regenerates the autoloader, smoke-tests it and packages the ZIP.

### End-to-end tests

The E2E suite spins up a real WordPress + WooCommerce via [`wp-env`](https://www.npmjs.com/package/@wordpress/env), runs [Playwright](https://playwright.dev) against it and asserts the snippet, the pageview beacon and a WooCommerce purchase event (captured by a mock ingest server):

```bash
npm install
npx playwright install chromium
npm run env:start
npx wp-env run cli wp eval-file wp-content/plugins/takt-wordpress/e2e/setup-wp.php
npm run e2e
```

## License

MIT
