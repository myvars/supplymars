<?php

namespace App\Service\Order;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CancelOrder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function cancel(CustomerOrder $customerOrder): void
    {
        if ($customerOrder->getStatus() === OrderStatus::CANCELLED) {
            throw new \InvalidArgumentException('Order is already cancelled');
        }

        if (!$customerOrder->allowCancel()) {
            throw new \InvalidArgumentException('Order cannot be cancelled');
        }

        $customerOrder->cancelOrder();

        $this->entityManager->persist($customerOrder);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($customerOrder);
    }
}