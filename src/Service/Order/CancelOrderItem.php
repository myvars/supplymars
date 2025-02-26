<?php

namespace App\Service\Order;

use App\Entity\CustomerOrderItem;
use App\Enum\OrderStatus;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CancelOrderItem implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $customerOrderItem = $crudOptions->getEntity();
        if (!$customerOrderItem instanceof CustomerOrderItem) {
            throw new \InvalidArgumentException('Entity must be an instance of CustomerOrderItem');
        }

        $this->cancel($customerOrderItem);
    }

    public function cancel(CustomerOrderItem $customerOrderItem): void
    {
        if (OrderStatus::CANCELLED === $customerOrderItem->getStatus()) {
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
            $customerOrderItem->getCustomerOrder(),
        ]);
    }
}
