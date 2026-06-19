<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Tests;

use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\WordPress\Settings;

final class SettingsTest extends TestCase
{
    public function test_sanitize_keeps_a_valid_domain(): void
    {
        $out = Settings::sanitize(['domain' => 'example.com']);

        $this->assertSame('example.com', $out['domain']);
    }

    public function test_sanitize_rejects_a_domain_with_invalid_characters(): void
    {
        $out = Settings::sanitize(['domain' => 'evil.com"></script><script>alert(1)</script>']);

        $this->assertSame('', $out['domain']);
    }
}
