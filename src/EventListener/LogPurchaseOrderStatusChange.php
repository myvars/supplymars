<?php

namespace App\EventListener;

use App\Event\PurchaseOrderStatusChangedEvent;
use App\Service\OrderProcessing\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class LogPurchaseOrderStatusChange
{
    public function __construct(private StatusChangeLogger $statusChangeLogger)
    {
    }

    #[AsEventListener]
    public function onPurchaseOrderStatusChange(PurchaseOrderStatusChangedEvent $event): void
    {
        $this->statusChangeLogger->fromStatusChangeEvent(
            $event,
            $event->getPurchaseOrder()->getId(),
            $event->getPurchaseOrder()->getStatus()->value
        );
    }
}
