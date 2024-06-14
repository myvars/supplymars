<?php

namespace App\Service\Order;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;

final class CancelOrder
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function cancel(CustomerOrder $customerOrder, bool $flush = true): void
    {
        if ($customerOrder->getStatus() === OrderStatus::CANCELLED) {
            throw new \InvalidArgumentException('Order is already cancelled');
        }

        if (!$customerOrder->allowCancel()) {
            throw new \InvalidArgumentException('Order cannot be cancelled');
        }

        $customerOrder->cancelOrder();

        $this->entityManager->persist($customerOrder);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}