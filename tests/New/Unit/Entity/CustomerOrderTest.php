<?php

namespace App\Tests\New\Unit\Entity;

use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\User;
use App\Entity\VatRate;
use App\Enum\OrderStatus;
use App\Enum\ShippingMethod;
use PHPUnit\Framework\TestCase;

class CustomerOrderTest extends TestCase
{
    public function testCreateFromCustomer(): void
    {
        $customer = $this->createMock(User::class);
        $address = $this->createMock(Address::class);
        $customer->method('getShippingAddress')->willReturn($address);
        $customer->method('getBillingAddress')->willReturn($address);
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $order = CustomerOrder::createFromCustomer(
            $customer,
            ShippingMethod::NEXT_DAY,
            $vatRate,
            'TEST-123'
        );

        $this->assertSame($customer, $order->getCustomer());
        $this->assertSame($address, $order->getShippingAddress());
        $this->assertSame($address, $order->getBillingAddress());
        $this->assertEquals(ShippingMethod::NEXT_DAY, $order->getShippingMethod());
        $this->assertEquals('TEST-123', $order->getCustomerOrderRef());
    }

    public function testCreateFromOrderWithInvalidBillingAddress(): void
    {
        $customer = $this->createMock(User::class);
        $address = $this->createMock(Address::class);
        $customer->method('getShippingAddress')->willReturn($address);
        $customer->method('getBillingAddress')->willReturn(null);
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $this->expectException(\TypeError::class);
        CustomerOrder::createFromCustomer(
            $customer,
            ShippingMethod::NEXT_DAY,
            $vatRate,
            'TEST-123'
        );
    }

    public function testCreateFromOrderWithInvalidShippingAddress(): void
    {
        $customer = $this->createMock(User::class);
        $address = $this->createMock(Address::class);
        $customer->method('getShippingAddress')->willReturn(null);
        $customer->method('getBillingAddress')->willReturn($address);
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $this->expectException(\TypeError::class);
        CustomerOrder::createFromCustomer(
            $customer,
            ShippingMethod::NEXT_DAY,
            $vatRate,
            'TEST-123'
        );
    }

    public function testAddCustomerOrderItem(): void
    {
        $order = $this->createCustomerOrder();
        $item = $this->createMock(CustomerOrderItem::class);

        $item->method('getStatus')->willReturn(OrderStatus::getDefault());

        // Test adding an item
        $item->expects($this->once())
            ->method('setCustomerOrder')
            ->with($order);

        $order->addCustomerOrderItem($item);
        $this->assertCount(1, $order->getCustomerOrderItems());
        $this->assertTrue($order->getCustomerOrderItems()->contains($item));
    }

    public function testAddCustomerOrderItemWithInvalidQty(): void
    {
        $order = $this->createCustomerOrder();

        $item = $this->createMock(CustomerOrderItem::class);
        $item->method('getTotalPrice')->willReturn('-100');
        $item->method('getTotalPriceIncVat')->willReturn('120');
        $item->method('getTotalWeight')->willReturn(10);
        $item->method('getStatus')->willReturn(OrderStatus::getDefault());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The total price must be greater than 0');
        $order->addCustomerOrderItem($item);
    }

    public function testAddCustomerOrderItemWithInvalidWeight(): void
    {
        $order = $this->createCustomerOrder();

        $item = $this->createMock(CustomerOrderItem::class);
        $item->method('getTotalPrice')->willReturn('100');
        $item->method('getTotalPriceIncVat')->willReturn('120');
        $item->method('getTotalWeight')->willReturn(-1);
        $item->method('getStatus')->willReturn(OrderStatus::getDefault());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The total weight must be greater than 0');
        $order->addCustomerOrderItem($item);
    }

    public function testRemoveCustomerOrderItem(): void
    {
        $order = $this->createCustomerOrder();
        $item = $this->createMock(CustomerOrderItem::class);

        $item->method('getStatus')->willReturn(OrderStatus::getDefault());

        // Add the item first to set up the state
        $order->addCustomerOrderItem($item);

        // Test removing an item
        $item->expects($this->once())
            ->method('getCustomerOrder')
            ->willReturn($order);

        $order->removeCustomerOrderItem($item);
        $this->assertCount(0, $order->getCustomerOrderItems());
    }

    public function testAddCustomerOrderItemWhenAllowEditIsFalse(): void
    {
        $order = $this->createCustomerOrder();

        $item = $this->createMock(CustomerOrderItem::class);
        $item->method('getTotalPrice')->willReturn('100');
        $item->method('getTotalPriceIncVat')->willReturn('120');
        $item->method('getTotalWeight')->willReturn(10);
        $item->method('getStatus')->willReturn(OrderStatus::DELIVERED);
        $order->addCustomerOrderItem($item);
        $order->cancelOrder();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add items to an order with this status');
        $order->addCustomerOrderItem($item);
    }

    public function testRemoveCustomerOrderItemWhenAllowEditIsFalse(): void
    {
        $order = $this->createCustomerOrder();

        $item = $this->createMock(CustomerOrderItem::class);
        $item->method('getTotalPrice')->willReturn('100');
        $item->method('getTotalPriceIncVat')->willReturn('120');
        $item->method('getTotalWeight')->willReturn(10);
        $item->method('getStatus')->willReturn(OrderStatus::DELIVERED);
        $order->addCustomerOrderItem($item);
        $order->cancelOrder();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot remove items from an order with this status');
        $order->removeCustomerOrderItem($item);
    }

    public function testAddPurchaseOrder(): void
    {
        $order = $this->createCustomerOrder();
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        $purchaseOrder->expects($this->once())
            ->method('setCustomerOrder')
            ->with($order);

        $order->addPurchaseOrder($purchaseOrder);

        $this->assertCount(1, $order->getPurchaseOrders());
        $this->assertTrue($order->getPurchaseOrders()->contains($purchaseOrder));
    }

    public function testRemovePurchaseOrder(): void
    {
        $order = $this->createCustomerOrder();
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        $order->addPurchaseOrder($purchaseOrder);

        $purchaseOrder->expects($this->once())
            ->method('getCustomerOrder')
            ->willReturn($order);

        $purchaseOrder->expects($this->once())
            ->method('setCustomerOrder')
            ->with(null);

        $order->removePurchaseOrder($purchaseOrder);

        $this->assertCount(0, $order->getPurchaseOrders());
        $this->assertFalse($order->getPurchaseOrders()->contains($purchaseOrder));
    }

    public function testRecalculateTotal(): void
    {
        $order = $this->createCustomerOrder();

        $item1 = $this->createMock(CustomerOrderItem::class);
        $item2 = $this->createMock(CustomerOrderItem::class);

        $item1->method('getTotalPrice')->willReturn('100');
        $item1->method('getTotalPriceIncVat')->willReturn('120');
        $item1->method('getTotalWeight')->willReturn(10);
        $item1->method('getStatus')->willReturn(OrderStatus::getDefault());

        $item2->method('getTotalPrice')->willReturn('200');
        $item2->method('getTotalPriceIncVat')->willReturn('240');
        $item2->method('getTotalWeight')->willReturn(20);
        $item2->method('getStatus')->willReturn(OrderStatus::getDefault());

        $order->addCustomerOrderItem($item1);
        $order->addCustomerOrderItem($item2);

        $order->recalculateTotal();

        $this->assertEquals('309.99', $order->getTotalPrice());
        $this->assertEquals('371.99', $order->getTotalPriceIncVat());
        $this->assertEquals(30, $order->getTotalWeight());
    }

    public function testLockOrder(): void
    {
        $staff = $this->createMock(User::class);
        $order = $this->createCustomerOrder();
        $order->lockOrder($staff);

        $this->assertEquals('TEST-123', $order->getCustomerOrderRef());
        $this->assertSame($staff, $order->getOrderLock());
    }

    public function testAllowEdit(): void
    {
        $order = $this->createCustomerOrder();

        $this->assertTrue($order->allowEdit());

        $order->cancelOrder();

        $this->assertFalse($order->allowEdit());
    }

    public function testAllowCancel(): void
    {
        $order = $this->createCustomerOrder();

        $this->assertTrue($order->allowCancel());

        $order->cancelOrder();

        $this->assertFalse($order->allowCancel());
    }

    public function testIsCancelled(): void
    {
        $order = $this->createCustomerOrder();

        $this->assertFalse($order->isCancelled());

        $order->cancelOrder();

        $this->assertTrue($order->isCancelled());
    }

    public function testGetLineCount(): void
    {
        $order = $this->createCustomerOrder();

        $item1 = $this->createMock(CustomerOrderItem::class);
        $item2 = $this->createMock(CustomerOrderItem::class);

        $item1->method('getStatus')->willReturn(OrderStatus::getDefault());
        $item2->method('getStatus')->willReturn(OrderStatus::getDefault());

        $order->addCustomerOrderItem($item1);
        $order->addCustomerOrderItem($item2);

        $this->assertEquals(2, $order->getLineCount());
    }

    public function testGetItemCount(): void
    {
        $order = $this->createCustomerOrder();
        $item1 = $this->createMock(CustomerOrderItem::class);
        $item2 = $this->createMock(CustomerOrderItem::class);

        $item1->method('getStatus')->willReturn(OrderStatus::getDefault());
        $item1->method('getQuantity')->willReturn(1);
        $item2->method('getStatus')->willReturn(OrderStatus::getDefault());
        $item2->method('getQuantity')->willReturn(2);

        $order->addCustomerOrderItem($item1);
        $order->addCustomerOrderItem($item2);

        $this->assertEquals(3, $order->getItemCount());
    }

    private function createCustomerOrder(): CustomerOrder
    {
        $customer = $this->createMock(User::class);
        $address = $this->createMock(Address::class);
        $customer->method('getShippingAddress')->willReturn($address);
        $customer->method('getBillingAddress')->willReturn($address);
        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        return CustomerOrder::createFromCustomer(
            $customer,
            ShippingMethod::NEXT_DAY,
            $vatRate,
            'TEST-123'
        );
    }
}