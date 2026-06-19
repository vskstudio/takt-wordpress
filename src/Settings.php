<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress;

final class Settings
{
    /**
     * @param array<string,mixed> $raw
     * @return array<string,mixed>
     */
    public static function sanitize(array $raw): array
    {
        $domain = is_string($raw['domain'] ?? null) ? trim($raw['domain']) : '';

        return ['domain' => self::isValidHostname($domain) ? $domain : ''];
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
