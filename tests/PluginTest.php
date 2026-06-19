<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\WordPress\Plugin;

final class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /** @param array<string,mixed> $settings */
    private function captureSnippet(array $settings, mixed $nonce = null): string
    {
        Functions\when('apply_filters')->justReturn($nonce);
        ob_start();
        (new Plugin($settings))->injectSnippet();

        return (string) ob_get_clean();
    }

    public function test_wants_woocommerce_requires_toggle_key_and_endpoint(): void
    {
        $full = ['woocommerce' => true, 'api_key' => 'k', 'api_endpoint' => 'https://t.example.com'];
        $this->assertTrue((new Plugin($full))->wantsWooCommerce());

        $this->assertFalse((new Plugin(['woocommerce' => false, 'api_key' => 'k', 'api_endpoint' => 'https://t.example.com']))->wantsWooCommerce());
        $this->assertFalse((new Plugin(['woocommerce' => true, 'api_key' => '', 'api_endpoint' => 'https://t.example.com']))->wantsWooCommerce());
        $this->assertFalse((new Plugin(['woocommerce' => true, 'api_key' => 'k', 'api_endpoint' => '']))->wantsWooCommerce());
    }

    public function test_inject_snippet_echoes_the_script_for_a_configured_domain(): void
    {
        $html = $this->captureSnippet(['domain' => 'example.com', 'mode' => 'cdn']);

        $this->assertStringContainsString('data-domain="example.com"', $html);
    }

    public function test_inject_snippet_applies_the_nonce_filter(): void
    {
        $html = $this->captureSnippet(['domain' => 'example.com', 'mode' => 'cdn'], 'n1');

        $this->assertStringContainsString('nonce="n1"', $html);
    }

    public function test_inject_snippet_outputs_nothing_without_a_domain(): void
    {
        $this->assertSame('', $this->captureSnippet(['domain' => '']));
    }
}
