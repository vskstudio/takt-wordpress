<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress;

/**
 * Orchestrator: reads the stored settings and wires WordPress hooks — the snippet
 * into wp_head and, when configured, WooCommerce purchases into a server-to-server
 * event. Kept thin; the work lives in the dedicated units it composes.
 */
final class Plugin
{
    public const OPTION = 'takt_settings';

    /** @param array<string,mixed> $settings */
    public function __construct(private array $settings)
    {
    }

    public static function boot(): void
    {
        $settings = Settings::sanitize((array) \get_option(self::OPTION, []));
        // The API key may live in wp-config.php (TAKT_API_KEY) instead of the DB,
        // so the secret never sits in an autoloaded option.
        if (\defined('TAKT_API_KEY')) {
            $settings['api_key'] = (string) \constant('TAKT_API_KEY');
        }

        (new self($settings))->register();
    }

    public function register(): void
    {
        \add_action('wp_head', [$this, 'injectSnippet']);

        $takt = TaktClientFactory::fromSettings($this->settings);
        if ($takt !== null && $this->wantsWooCommerce() && \class_exists('WooCommerce')) {
            $wc = new WooCommerce($takt, (string) ($this->settings['wc_trigger_status'] ?? 'completed'));
            \add_action('woocommerce_order_status_changed', [$wc, 'onStatusChanged'], 10, 4);
        }
    }

    public function injectSnippet(): void
    {
        $nonce = \apply_filters('takt_snippet_nonce', null);
        echo SnippetInjector::markup($this->settings, is_string($nonce) ? $nonce : null);
    }

    public function wantsWooCommerce(): bool
    {
        return ($this->settings['woocommerce'] ?? false)
            && ($this->settings['api_key'] ?? '') !== ''
            && ($this->settings['api_endpoint'] ?? '') !== '';
    }
}
