<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// WordPress / WooCommerce ship their global functions, classes and constants at
// runtime, so they must never be prefixed. The lists are staged into
// .scoper-excludes/ by bin/build.sh (they come from a dev-only package that the
// production install removes), with a fallback to the vendored copy.
$excludes = static function (string $file): array {
    foreach ([__DIR__ . '/.scoper-excludes/', __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/'] as $dir) {
        $path = $dir . $file;
        if (is_readable($path)) {
            return (array) json_decode((string) file_get_contents($path), true);
        }
    }

    return [];
};

// Bundles the Composer dependencies under a private namespace so the released
// plugin never clashes with another plugin shipping its own PSR-7/HTTP stack
// or a different copy of takt-core-php.
return [
    'prefix' => 'Takt\\WP\\Vendor',

    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->exclude(['tests', 'Tests', 'test', 'doc', 'docs', 'examples', '.github'])
            ->notName('/.*\.(dist|md|markdown|txt|neon|xml)$/')
            ->in('vendor'),
        Finder::create()
            ->files()
            ->in('src'),
        Finder::create()
            ->files()
            ->name(['takt.php', 'uninstall.php'])
            ->depth(0)
            ->in('.'),
    ],

    // Keep the plugin's own classes on their real namespace: the bootstrap and
    // the WordPress autoloader stay predictable across releases.
    'exclude-namespaces' => [
        '~^Vskstudio\\\\Takt\\\\WordPress(\\\\|$)~',
    ],

    'exclude-classes' => array_merge(
        $excludes('exclude-wordpress-classes.json'),
        $excludes('exclude-wordpress-interfaces.json'),
        // WooCommerce is detected at runtime via class_exists(); php-scoper must
        // not rewrite that string literal or the integration silently no-ops.
        ['WooCommerce', 'WC_Order', 'WC_Product', 'WC_Product_Simple'],
    ),
    'exclude-functions' => $excludes('exclude-wordpress-functions.json'),
    'exclude-constants' => array_merge(
        $excludes('exclude-wordpress-constants.json'),
        ['TAKT_WP_VERSION', 'TAKT_WP_FILE', 'TAKT_API_KEY', 'ABSPATH', 'WP_UNINSTALL_PLUGIN'],
    ),
];
