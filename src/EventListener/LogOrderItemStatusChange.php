<?php

namespace App\EventListener;

use App\Event\OrderItemStatusChangedEvent;
use App\Service\OrderProcessing\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class LogOrderItemStatusChange
{
    public function __construct(private StatusChangeLogger $statusChangeLogger)
    {
    }

    #[AsEventListener]
    public function onOrderItemStatusChange(OrderItemStatusChangedEvent $event): void
    {
        $this->statusChangeLogger->fromStatusChangeEvent(
            $event,
            $event->getCustomerOrderItem()->getId(),
            $event->getCustomerOrderItem()->getStatus()->value
        );
    }
}
