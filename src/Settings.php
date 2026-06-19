<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress;

use Vskstudio\Takt\Options;

final class Settings
{
    /**
     * @param array<string,mixed> $raw
     * @return array<string,mixed>
     */
    public static function sanitize(array $raw): array
    {
        $domain = is_string($raw['domain'] ?? null) ? trim($raw['domain']) : '';
        $mode = in_array($raw['mode'] ?? null, ['cdn', 'asset', 'sdk'], true) ? $raw['mode'] : 'inline';

        return [
            'domain' => self::isValidHostname($domain) ? $domain : '',
            'mode' => $mode,
            'outbound' => !empty($raw['outbound']),
            'files' => !empty($raw['files']),
            'not_found' => !empty($raw['not_found']),
            'tagged' => !empty($raw['tagged']),
            'exclude_localhost' => !empty($raw['exclude_localhost']),
            'file_extensions' => self::parseExtensions($raw['file_extensions'] ?? ''),
            'script_origin' => self::sanitizeUrl($raw['script_origin'] ?? ''),
            // Advanced options. respect_dnt/enabled default ON in the tracker, so
            // they are surfaced as opt-out flags (ignore_dnt / disable_tracking)
            // — a fresh, never-saved install keeps tracking on and DNT honored.
            'sample_rate' => self::sanitizeRate($raw['sample_rate'] ?? ''),
            'track_query' => !empty($raw['track_query']),
            'query_params' => self::parseParams($raw['query_params'] ?? ''),
            'ignore_dnt' => !empty($raw['ignore_dnt']),
            'disable_tracking' => !empty($raw['disable_tracking']),
            'woocommerce' => !empty($raw['woocommerce']),
            'wc_trigger_status' => in_array($raw['wc_trigger_status'] ?? null, ['completed', 'processing'], true)
                ? $raw['wc_trigger_status']
                : 'completed',
            'api_key' => is_string($raw['api_key'] ?? null) ? trim($raw['api_key']) : '',
            'api_endpoint' => self::sanitizeUrl($raw['api_endpoint'] ?? ''),
        ];
    }

    /**
     * Build the core-php Options (snippet config) from stored settings. The CSP
     * nonce is supplied at render time (via filter), not stored.
     *
     * @param array<string,mixed> $s
     */
    public static function toOptions(array $s, ?string $nonce = null): Options
    {
        $scriptOrigin = is_string($s['script_origin'] ?? null) ? $s['script_origin'] : '';
        $mode = $s['mode'] ?? 'inline';
        // scrubUrl is a raw JS function injected verbatim, dev-controlled via the
        // TAKT_SCRUB_URL constant (see Plugin::boot). It is only expressible in
        // sdk mode; outside it core-php would throw, so we drop it silently to
        // avoid fataling every page.
        $scrubRaw = is_string($s['scrub_url'] ?? null) ? trim($s['scrub_url']) : '';
        $scrubUrl = ($mode === 'sdk' && $scrubRaw !== '') ? $scrubRaw : null;

        return Options::fromArray([
            'domain' => $s['domain'] ?? '',
            'mode' => $mode,
            'outbound' => $s['outbound'] ?? false,
            'files' => $s['files'] ?? false,
            'not_found' => $s['not_found'] ?? false,
            'tagged' => $s['tagged'] ?? false,
            'exclude_localhost' => $s['exclude_localhost'] ?? true,
            'file_extensions' => $s['file_extensions'] ?? [],
            'scriptOrigin' => $scriptOrigin !== '' ? $scriptOrigin : null,
            'nonce' => $nonce,
            'sampleRate' => $s['sample_rate'] ?? null,
            'trackQuery' => !empty($s['track_query']) ? true : null,
            'queryParams' => $s['query_params'] ?? [],
            'respectDnt' => !empty($s['ignore_dnt']) ? false : null,
            'enabled' => !empty($s['disable_tracking']) ? false : null,
            'scrubUrl' => $scrubUrl,
        ]);
    }

    /** A sampling rate in (0, 1] — else null (track everything). */
    private static function sanitizeRate(mixed $raw): ?float
    {
        if (!is_scalar($raw) || ($s = trim((string) $raw)) === '' || !is_numeric($s)) {
            return null;
        }
        $rate = (float) $s;

        return ($rate > 0 && $rate <= 1) ? $rate : null;
    }

    /**
     * "utm_source, utm_medium ,bad token" → ["utm_source", "utm_medium"]: a
     * comma-separated allowlist of query-param names. Only [A-Za-z0-9_-] tokens
     * survive — the rest would not be valid param names anyway.
     *
     * @return list<string>
     */
    private static function parseParams(mixed $raw): array
    {
        if (!is_string($raw)) {
            return [];
        }
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $name = trim($part);
            if ($name !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                $out[] = $name;
            }
        }

        return $out;
    }

    /** A http(s) URL with a host, trailing slash dropped — else ''. */
    private static function sanitizeUrl(mixed $raw): string
    {
        if (!is_string($raw) || ($url = trim($raw)) === '') {
            return '';
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || parse_url($url, PHP_URL_HOST) === null) {
            return '';
        }

        return rtrim($url, '/');
    }

    /**
     * "PDF, zip , .docx" → ["pdf", "zip", "docx"]: lower-cased, trimmed, a
     * leading dot dropped, blanks removed. Matches how takt.auto.js compares
     * download extensions (lower-cased, no dot).
     *
     * @return list<string>
     */
    private static function parseExtensions(mixed $raw): array
    {
        if (!is_string($raw)) {
            return [];
        }
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $ext = ltrim(strtolower(trim($part)), '.');
            if ($ext !== '') {
                $out[] = $ext;
            }
        }

        return $out;
    }

    /**
     * A hostname like "example.com" or "shop.example.co.uk": dot-separated
     * labels of letters, digits and hyphens (not leading/trailing), ≤253 chars.
     * Anything else (markup, spaces, schemes) is rejected — the snippet is
     * injected into every page's <head>, so the domain must never carry markup.
     */
    private static function isValidHostname(string $value): bool
    {
        if ($value === '' || strlen($value) > 253) {
            return false;
        }

        return (bool) preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i',
            $value,
        );
    }
}
