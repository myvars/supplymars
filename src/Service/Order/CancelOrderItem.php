<?php

namespace App\Service\Order;

use App\Entity\CustomerOrderItem;
use App\Enum\OrderStatus;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CancelOrderItem
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function cancel(CustomerOrderItem $customerOrderItem): void
    {
        if ($customerOrderItem->getStatus() === OrderStatus::CANCELLED) {
            throw new \InvalidArgumentException('Order item is already cancelled');
        }

        if (!$customerOrderItem->allowCancel()) {
            throw new \InvalidArgumentException('Order item cannot be cancelled');
        }

        $customerOrder = $customerOrderItem->getCustomerOrder();

        $customerOrderItem->cancelItem();
        $customerOrder->generateStatus();

        $this->entityManager->persist($customerOrder);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents([
            $customerOrderItem,
            $customerOrderItem->getCustomerOrder()
        ]);
    }
}