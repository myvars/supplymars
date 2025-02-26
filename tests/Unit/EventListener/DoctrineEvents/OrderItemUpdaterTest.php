<?php

namespace App\Tests\Unit\EventListener\DoctrineEvents;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\EventListener\DoctrineEvents\OrderItemUpdater;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class OrderItemUpdaterTest extends TestCase
{
    public function testPreUpdateRecalculatesTotalWhenFieldsChange(): void
    {
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getId')->willReturn(1);

        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(fn($fieldName): bool => in_array($fieldName, ['quantity', 'price', 'priceIncVat', 'weight']));

        $customerOrderItem->expects($this->once())->method('recalculateTotal');

        $listener = new OrderItemUpdater();
        $listener->preUpdate($customerOrderItem, $eventArgsMock);

        $this->assertArrayHasKey($customerOrder->getId(), $listener->getChangedCustomerOrders());
    }

    public function testPreUpdateSkipsRecalculationWhenNoRelevantFieldsChange(): void
    {
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(fn($fieldName): bool => $fieldName == 'none');

        $customerOrderItem->expects($this->never())->method('recalculateTotal');

        $listener = new OrderItemUpdater();
        $listener->preUpdate($customerOrderItem, $eventArgsMock);

        $this->assertEmpty($listener->getChangedCustomerOrders());
    }

    public function testPostUpdateRecalculatesTotalForChangedCustomerOrders(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $customerOrder->method('getId')->willReturn(1);

        $customerOrder->expects($this->once())->method('recalculateTotal');

        $listener = new OrderItemUpdater();
        $listener->setChangedCustomerOrders($customerOrder);

        $listener->postUpdate($this->createMock(CustomerOrderItem::class));

        $this->assertEmpty($listener->getChangedCustomerOrders());
    }
}