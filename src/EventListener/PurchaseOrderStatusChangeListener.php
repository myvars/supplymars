<?php

namespace App\EventListener;

use App\Event\PurchaseOrderStatusChangedEvent;
use App\Service\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class PurchaseOrderStatusChangeListener
{
    public function __construct(private readonly StatusChangeLogger $statusChangeLogger)
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
