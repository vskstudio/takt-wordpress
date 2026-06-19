<?php

/**
 * Removes the plugin's stored settings when WordPress uninstalls it.
 *
 * @package Vskstudio\Takt\WordPress
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('takt_settings');
