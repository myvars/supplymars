<?php

declare(strict_types=1);

namespace App\Order\Application\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/** Tell a lock's holder that another user released it. Sent by NotifyLockHolderOfRelease. */
#[AsMessage('async')]
final readonly class NotifyOrderLockReleased
{
    public function __construct(
        public string $orderId,
        public string $holderId,
        public string $releasedById,
    ) {
    }
}
