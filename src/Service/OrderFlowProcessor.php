<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;

class OrderFlowProcessor
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function updateStatus(CustomerOrder $order, OrderStatus $newStatus, bool $flush = true): void
    {
        $currentStatus = $order->getStatus();
        if ($newStatus === $currentStatus) {
            return;
        }

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new \LogicException(sprintf(
                'Cannot transition from "%s" to "%s"',
                $currentStatus->value,
                $newStatus->value
            ));
        }

        $this->checkOrderItemsAllowTransition($order, $newStatus);
        $order->setStatus($newStatus);
        $this->entityManager->persist($order);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function checkOrderItemsAllowTransition(CustomerOrder $order, OrderStatus $newStatus): void
    {
        if ($newStatus !== OrderStatus::CANCELLED && $order->getCustomerOrderItems()->isEmpty()) {
            throw new \LogicException(sprintf(
                'Cannot transition to "%s", order has no items',
                $newStatus->value
            ));
        }

        foreach ($order->getCustomerOrderItems() as $orderItem) {
            $itemStatus = $orderItem->getStatus();
            if ($itemStatus !== $newStatus) {
                throw new \LogicException(sprintf(
                    'Cannot transition to "%s", order has "%s" item',
                    $newStatus->value,
                    $itemStatus->value
                ));
            }
        }
    }
}