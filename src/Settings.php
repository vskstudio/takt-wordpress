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
        $mode = in_array($raw['mode'] ?? null, ['cdn', 'asset'], true) ? $raw['mode'] : 'inline';

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

        return Options::fromArray([
            'domain' => $s['domain'] ?? '',
            'mode' => $s['mode'] ?? 'inline',
            'outbound' => $s['outbound'] ?? false,
            'files' => $s['files'] ?? false,
            'not_found' => $s['not_found'] ?? false,
            'tagged' => $s['tagged'] ?? false,
            'exclude_localhost' => $s['exclude_localhost'] ?? true,
            'file_extensions' => $s['file_extensions'] ?? [],
            'scriptOrigin' => $scriptOrigin !== '' ? $scriptOrigin : null,
            'nonce' => $nonce,
        ]);
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
