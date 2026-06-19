<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress;

/**
 * Settings screen under Settings → Takt Analytics. Values are stored in the
 * single Plugin::OPTION array and sanitized through Settings::sanitize; the API
 * key is write-only in the UI (never echoed back) and kept when left blank.
 */
final class AdminPage
{
    private const GROUP = 'takt_group';

    public function register(): void
    {
        \add_action('admin_menu', [$this, 'addPage']);
        \add_action('admin_init', [$this, 'registerSetting']);
    }

    public function addPage(): void
    {
        \add_options_page(
            'Takt Analytics',
            'Takt Analytics',
            'manage_options',
            'takt-analytics',
            [$this, 'render'],
        );
    }

    public function registerSetting(): void
    {
        \register_setting(self::GROUP, Plugin::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => [],
        ]);
    }

    /**
     * @param array<string,mixed> $raw
     * @return array<string,mixed>
     */
    public static function sanitize(array $raw): array
    {
        // Write-only key: a blank submission means "unchanged", not "erase".
        if (($raw['api_key'] ?? '') === '') {
            $stored = (array) \get_option(Plugin::OPTION, []);
            if (!empty($stored['api_key'])) {
                $raw['api_key'] = $stored['api_key'];
            }
        }

        return Settings::sanitize($raw);
    }

    public function render(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $s = Settings::sanitize((array) \get_option(Plugin::OPTION, []));
        $keyLocked = \defined('TAKT_API_KEY');
        $hasKey = $keyLocked || ($s['api_key'] ?? '') !== '';

        echo '<div class="wrap"><h1>Takt Analytics</h1><form method="post" action="options.php">';
        \settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation">';

        $this->textRow('domain', 'Domaine', $s['domain'], 'example.com');
        $this->selectRow('mode', 'Mode du script', $s['mode'], [
            'inline' => 'Inline (anti-adblock)',
            'cdn' => 'CDN (jsDelivr)',
            'asset' => 'Fichier hébergé',
        ]);
        $this->textRow('script_origin', 'Origine du script', $s['script_origin'], 'https://cdn.exemple.com');
        $this->checkboxRow('exclude_localhost', 'Exclure localhost', $s['exclude_localhost']);

        $this->checkboxRow('outbound', 'Clics sortants', $s['outbound']);
        $this->checkboxRow('files', 'Téléchargements', $s['files']);
        $this->checkboxRow('tagged', 'Événements balisés (HTML)', $s['tagged']);
        $this->checkboxRow('not_found', 'Pages 404', $s['not_found']);
        $this->textRow('file_extensions', 'Extensions téléchargeables', implode(', ', $s['file_extensions']), 'pdf, zip, docx');

        $this->checkboxRow('woocommerce', 'Suivi WooCommerce (achats)', $s['woocommerce']);
        $this->selectRow('wc_trigger_status', 'Déclencheur de la commande', $s['wc_trigger_status'], [
            'completed' => 'Terminée',
            'processing' => 'En traitement',
        ]);
        $this->textRow('api_endpoint', "Endpoint d'ingestion", $s['api_endpoint'], 'https://takt.exemple.com');
        $this->apiKeyRow($hasKey, $keyLocked);

        echo '</table>';
        \submit_button();
        echo '</form></div>';
    }

    private function field(string $key): string
    {
        return Plugin::OPTION . '[' . $key . ']';
    }

    private function textRow(string $key, string $label, string $value, string $placeholder): void
    {
        printf(
            '<tr><th scope="row"><label for="takt_%1$s">%2$s</label></th><td>'
                . '<input type="text" id="takt_%1$s" name="%3$s" value="%4$s" placeholder="%5$s" class="regular-text" /></td></tr>',
            \esc_attr($key),
            \esc_html($label),
            \esc_attr($this->field($key)),
            \esc_attr($value),
            \esc_attr($placeholder),
        );
    }

    private function checkboxRow(string $key, string $label, bool $checked): void
    {
        printf(
            '<tr><th scope="row">%2$s</th><td><label><input type="checkbox" name="%3$s" value="1" %4$s /> %2$s</label></td></tr>',
            \esc_attr($key),
            \esc_html($label),
            \esc_attr($this->field($key)),
            \checked($checked, true, false),
        );
    }

    /** @param array<string,string> $choices */
    private function selectRow(string $key, string $label, string $value, array $choices): void
    {
        printf(
            '<tr><th scope="row"><label for="takt_%1$s">%2$s</label></th><td><select id="takt_%1$s" name="%3$s">',
            \esc_attr($key),
            \esc_html($label),
            \esc_attr($this->field($key)),
        );
        foreach ($choices as $val => $text) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                \esc_attr($val),
                \selected($value, $val, false),
                \esc_html($text),
            );
        }
        echo '</select></td></tr>';
    }

    private function apiKeyRow(bool $hasKey, bool $locked): void
    {
        $note = $locked
            ? '<p class="description">Définie via la constante <code>TAKT_API_KEY</code> (wp-config.php).</p>'
            : '';
        printf(
            '<tr><th scope="row"><label for="takt_api_key">Clé API</label></th><td>'
                . '<input type="password" id="takt_api_key" name="%1$s" value="" placeholder="%2$s" autocomplete="new-password" class="regular-text" %3$s />%4$s</td></tr>',
            \esc_attr($this->field('api_key')),
            $hasKey ? '••••••••' : '',
            $locked ? 'disabled' : '',
            $note,
        );
    }
}
