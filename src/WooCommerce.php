<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress;

use Vskstudio\Takt\Revenue;
use Vskstudio\Takt\Takt;

/**
 * Turns a completed WooCommerce order into a server-to-server "Purchase" event.
 * The buyer's IP/User-Agent are read from the order (recorded at checkout), not
 * the current request, since the trigger may fire from wp-admin.
 */
final class WooCommerce
{
    private const META_KEY = '_takt_tracked';

    public function __construct(
        private Takt $takt,
        private string $triggerStatus,
    ) {
    }

    public function onStatusChanged(int $orderId, string $from, string $to, object $order): void
    {
        if ($to === $this->triggerStatus) {
            $this->sendPurchase($order);
        }
    }

    public function sendPurchase(object $order): void
    {
        if ($order->get_meta(self::META_KEY) !== '') {
            return;
        }

        $revenue = new Revenue(
            number_format((float) $order->get_total(), 2, '.', ''),
            strtoupper($order->get_currency()),
        );

        $this->takt
            ->withVisitor(
                $order->get_customer_ip_address() ?: null,
                $order->get_customer_user_agent() ?: null,
            )
            ->event('Purchase', [
                'order_id' => (string) $order->get_id(),
                'items' => (string) $order->get_item_count(),
            ], $revenue);

        $order->update_meta_data(self::META_KEY, '1');
        $order->save();
    }
}
