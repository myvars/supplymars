<?php

declare(strict_types=1);

namespace App\Order\Application\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/** Refresh the order's page for everyone viewing it. Sent by RefreshOrderPageForViewers, one per order per flush. */
#[AsMessage('async')]
final readonly class RefreshOrderPage
{
    public function __construct(public string $orderId)
    {
    }
}
