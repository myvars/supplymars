<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: CustomerOrderItem::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: CustomerOrderItem::class)]
class OrderItemUpdater
{
    /** @var CustomerOrder[] */
    private array $changedCustomerOrders = [];

    public function preUpdate(CustomerOrderItem $customerOrderItem, PreUpdateEventArgs $eventArgs): void
    {
        if (
            $eventArgs->hasChangedField('quantity')
            || $eventArgs->hasChangedField('price')
            || $eventArgs->hasChangedField('priceIncVat')
            || $eventArgs->hasChangedField('weight')
        ) {
            $customerOrderItem->recalculateTotal();
            $this->setChangedCustomerOrders($customerOrderItem->getCustomerOrder());
        }
    }

    public function postUpdate(CustomerOrderItem $customerOrderItem): void
    {
        if ([] === $this->changedCustomerOrders) {
            return;
        }

        foreach ($this->changedCustomerOrders as $customerOrder) {
            $customerOrder->recalculateTotal();
        }

        $this->changedCustomerOrders = [];
    }

    public function setChangedCustomerOrders(CustomerOrder $customerOrder): void
    {
        $this->changedCustomerOrders[$customerOrder->getId()] = $customerOrder;
    }

    public function getChangedCustomerOrders(): array
    {
        return $this->changedCustomerOrders;
    }
}
