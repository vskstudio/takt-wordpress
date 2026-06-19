<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\WordPress\AdminPage;

final class AdminPageTest extends TestCase
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

    public function test_sanitize_keeps_the_stored_key_when_the_field_is_submitted_blank(): void
    {
        Functions\when('get_option')->justReturn(['api_key' => 'kept-secret']);

        $out = AdminPage::sanitize(['domain' => 'example.com', 'api_key' => '']);

        $this->assertSame('kept-secret', $out['api_key']);
    }

    public function test_sanitize_uses_the_submitted_key_when_provided(): void
    {
        Functions\when('get_option')->justReturn(['api_key' => 'old']);

        $out = AdminPage::sanitize(['domain' => 'example.com', 'api_key' => 'new-secret']);

        $this->assertSame('new-secret', $out['api_key']);
    }
}
