<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Tests\Http;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Vskstudio\Takt\WordPress\Http\WpRemotePostClient;

final class WpRemotePostClientTest extends TestCase
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

    public function test_returns_a_psr7_response_with_the_http_status(): void
    {
        Functions\when('wp_remote_post')->justReturn(['response' => ['code' => 202]]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(202);
        Functions\when('wp_remote_retrieve_body')->justReturn('');

        $request = (new Psr17Factory())->createRequest('POST', 'https://takt.example.com/api/event');
        $response = (new WpRemotePostClient())->sendRequest($request);

        $this->assertSame(202, $response->getStatusCode());
    }

    public function test_forwards_method_url_body_and_headers(): void
    {
        $captured = null;
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(202);
        Functions\when('wp_remote_retrieve_body')->justReturn('');
        Functions\when('wp_remote_post')->alias(function ($url, $args) use (&$captured) {
            $captured = [$url, $args];

            return ['response' => ['code' => 202]];
        });

        $factory = new Psr17Factory();
        $request = $factory->createRequest('POST', 'https://takt.example.com/api/event')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer k')
            ->withBody($factory->createStream('{"n":"Purchase"}'));

        (new WpRemotePostClient())->sendRequest($request);

        $this->assertSame('https://takt.example.com/api/event', $captured[0]);
        $this->assertSame('POST', $captured[1]['method']);
        $this->assertSame('{"n":"Purchase"}', $captured[1]['body']);
        $this->assertSame('application/json', $captured[1]['headers']['Content-Type']);
        $this->assertSame('Bearer k', $captured[1]['headers']['Authorization']);
    }

    public function test_throws_a_psr18_exception_on_transport_error(): void
    {
        $wpError = new class () {
            public function get_error_message(): string
            {
                return 'cURL error 28: timeout';
            }
        };
        Functions\when('wp_remote_post')->justReturn($wpError);
        Functions\when('is_wp_error')->justReturn(true);

        $request = (new Psr17Factory())->createRequest('POST', 'https://takt.example.com/api/event');

        try {
            (new WpRemotePostClient())->sendRequest($request);
            $this->fail('Expected a PSR-18 network exception.');
        } catch (NetworkExceptionInterface $e) {
            $this->assertStringContainsString('timeout', $e->getMessage());
            $this->assertSame($request, $e->getRequest());
        }
    }
}
