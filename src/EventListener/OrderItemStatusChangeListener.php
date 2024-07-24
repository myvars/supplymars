<?php

namespace App\EventListener;

use App\Event\OrderItemStatusChangedEvent;
use App\Service\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class OrderItemStatusChangeListener
{
    public function __construct(private readonly StatusChangeLogger $statusChangeLogger)
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
