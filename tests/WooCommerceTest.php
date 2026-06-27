<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\Takt;
use Vskstudio\Takt\WordPress\Tests\Support\CapturingClient;
use Vskstudio\Takt\WordPress\WooCommerce;

final class WooCommerceTest extends TestCase
{
    private function takt(CapturingClient $client): Takt
    {
        $factory = new Psr17Factory();

        return new Takt('https://takt.example.com', 'shop.example.com', 'api-key', $client, $factory, $factory);
    }

    /** @param array<string,mixed> $overrides */
    private function order(array $overrides = []): object
    {
        $data = array_merge([
            'id' => 42,
            'total' => '99.90',
            'currency' => 'EUR',
            'items' => 3,
            'ip' => '',
            'ua' => '',
            'tracked' => '',
        ], $overrides);

        return new class ($data) {
            public bool $saved = false;
            /** @var array<string,mixed> */
            public array $meta = [];

            /** @param array<string,mixed> $d */
            public function __construct(private array $d)
            {
            }

            public function get_id(): int
            {
                return (int) $this->d['id'];
            }

            public function get_total(): string
            {
                return (string) $this->d['total'];
            }

            public function get_currency(): string
            {
                return (string) $this->d['currency'];
            }

            public function get_item_count(): int
            {
                return (int) $this->d['items'];
            }

            public function get_customer_ip_address(): string
            {
                return (string) $this->d['ip'];
            }

            public function get_customer_user_agent(): string
            {
                return (string) $this->d['ua'];
            }

            public function get_meta(string $key): string
            {
                return (string) ($this->meta[$key] ?? $this->d['tracked']);
            }

            public function update_meta_data(string $key, string $value): void
            {
                $this->meta[$key] = $value;
            }

            public function save(): void
            {
                $this->saved = true;
            }
        };
    }

    public function test_send_purchase_emits_a_purchase_event_with_revenue(): void
    {
        $client = new CapturingClient();
        (new WooCommerce($this->takt($client), 'completed'))->sendPurchase($this->order());

        $payload = $client->lastPayload();
        $this->assertSame(1, $client->calls);
        $this->assertSame('Purchase', $payload['n']);
        $this->assertSame('shop.example.com', $payload['d']);
        $this->assertSame('42', $payload['p']['order_id']);
        $this->assertSame('3', $payload['p']['items']);
        $this->assertSame('99.90', $payload['$']['a']);
        $this->assertSame('EUR', $payload['$']['c']);
    }

    public function test_send_purchase_marks_the_order_so_it_fires_once(): void
    {
        $client = new CapturingClient();
        $wc = new WooCommerce($this->takt($client), 'completed');
        $order = $this->order();

        $wc->sendPurchase($order);
        $wc->sendPurchase($order);

        $this->assertSame(1, $client->calls);
        $this->assertSame('1', $order->get_meta('_takt_tracked'));
        $this->assertTrue($order->saved);
    }

    public function test_send_purchase_skips_an_already_tracked_order(): void
    {
        $client = new CapturingClient();
        (new WooCommerce($this->takt($client), 'completed'))->sendPurchase($this->order(['tracked' => '1']));

        $this->assertSame(0, $client->calls);
    }

    public function test_send_purchase_forwards_the_buyer_ip_and_user_agent(): void
    {
        $client = new CapturingClient();
        (new WooCommerce($this->takt($client), 'completed'))->sendPurchase($this->order([
            'ip' => '203.0.113.7',
            'ua' => 'Mozilla/5.0 (buyer)',
        ]));

        $this->assertSame('203.0.113.7', $client->lastRequest->getHeaderLine('X-Forwarded-For'));
        $this->assertSame('Mozilla/5.0 (buyer)', $client->lastRequest->getHeaderLine('User-Agent'));
    }

    public function test_send_purchase_marks_the_order_before_sending(): void
    {
        $client = new CapturingClient();
        $order = $this->order();
        (new WooCommerce($this->takt($client), 'completed'))->sendPurchase($order);

        // Claimed (marked + saved) so a re-entrant status hook counts it once.
        $this->assertSame('1', $order->get_meta('_takt_tracked'));
        $this->assertTrue($order->saved);
        $this->assertSame(1, $client->calls);
    }

    public function test_on_status_changed_only_fires_on_the_trigger_status(): void
    {
        $client = new CapturingClient();
        $wc = new WooCommerce($this->takt($client), 'completed');

        $wc->onStatusChanged(42, 'pending', 'processing', $this->order());
        $this->assertSame(0, $client->calls);

        $wc->onStatusChanged(42, 'processing', 'completed', $this->order());
        $this->assertSame(1, $client->calls);
    }
}
