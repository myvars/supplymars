<?php

namespace App\EventListener;

use App\Event\OrderStatusChangedEvent;
use App\Service\StatusChangeLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class OrderStatusChangeListener
{
    public function __construct(private readonly StatusChangeLogger $statusChangeLogger)
    {
    }

    #[AsEventListener]
    public function onOrderStatusChange(OrderStatusChangedEvent $event): void
    {
        $this->statusChangeLogger->fromStatusChangeEvent(
            $event,
            $event->getCustomerOrder()->getId(),
            $event->getCustomerOrder()->getStatus()->value
        );
    }
}
