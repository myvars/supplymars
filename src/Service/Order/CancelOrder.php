<?php

namespace App\Service\Order;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CancelOrder implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $customerOrder = $crudOptions->getEntity();
        if (!$customerOrder instanceof CustomerOrder) {
            throw new \InvalidArgumentException('Entity must be instance of CustomerOrder');
        }

        $this->cancel($customerOrder);
    }

    public function cancel(CustomerOrder $customerOrder): void
    {
        if (OrderStatus::CANCELLED === $customerOrder->getStatus()) {
            throw new \InvalidArgumentException('Order is already cancelled');
        }

        if (!$customerOrder->allowCancel()) {
            throw new \InvalidArgumentException('Order cannot be cancelled');
        }

        foreach ($customerOrder->getCustomerOrderItems() as $customerOrderItem) {
            $customerOrderItem->cancelItem();

            $this->entityManager->persist($customerOrderItem);
            $this->domainEventDispatcher->dispatchProviderEvents($customerOrderItem);
        }

        $customerOrder->cancelOrder();
        $customerOrder->generateStatus();

        $this->entityManager->persist($customerOrder);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($customerOrder);
    }
}
