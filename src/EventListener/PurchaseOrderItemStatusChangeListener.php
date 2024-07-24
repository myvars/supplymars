<?php

namespace App\EventListener;

use App\Event\PurchaseOrderItemStatusChangedEvent;
use App\Service\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class PurchaseOrderItemStatusChangeListener
{
    public function __construct(private readonly StatusChangeLogger $statusChangeLogger)
    {
    }

    #[AsEventListener]
    public function onPurchaseOrderItemStatusChange(PurchaseOrderItemStatusChangedEvent $event): void
    {
        $this->statusChangeLogger->fromStatusChangeEvent(
            $event,
            $event->getPurchaseOrderItem()->getId(),
            $event->getPurchaseOrderItem()->getStatus()->value
        );
    }
}
