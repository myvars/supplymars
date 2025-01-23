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
    public function testSettersAndGetters(): void
    {
        $customer = $this->createMock(User::class);
        $shippingAddress = $this->createMock(Address::class);
        $billingAddress = $this->createMock(Address::class);
        $shippingMethod = ShippingMethod::NEXT_DAY;
        $dueDate = new \DateTimeImmutable();
        $status = OrderStatus::getDefault();

        $order = (new CustomerOrder())
            ->setCustomer($customer)
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress)
            ->setShippingMethod($shippingMethod)
            ->setDueDate($dueDate)
            ->setShippingPrice('10.00')
            ->setShippingPriceIncVat('12.00')
            ->setCustomerOrderRef('123')
            ->setOrderLock($customer)
            ->setStatus($status);

        $this->assertSame($customer, $order->getCustomer());
        $this->assertSame($shippingAddress, $order->getShippingAddress());
        $this->assertSame($billingAddress, $order->getBillingAddress());
        $this->assertSame($shippingMethod, $order->getShippingMethod());
        $this->assertSame($dueDate, $order->getDueDate());
        $this->assertEquals('10.00', $order->getShippingPrice());
        $this->assertEquals('12.00', $order->getShippingPriceIncVat());
        $this->assertEquals('123', $order->getCustomerOrderRef());
        $this->assertSame($customer, $order->getOrderLock());
        $this->assertSame($status, $order->getStatus());
    }

    public function testAddCustomerOrderItem(): void
    {
        $order = new CustomerOrder();
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

    public function testRemoveCustomerOrderItem(): void
    {
        $order = new CustomerOrder();
        $item = $this->createMock(CustomerOrderItem::class);

        $item->method('getStatus')->willReturn(OrderStatus::getDefault());

        // Add the item first to set up the state
        $order->addCustomerOrderItem($item);

        // Test removing an item
        $item->expects($this->once())
            ->method('getCustomerOrder')
            ->willReturn($order);

        $item->expects($this->once())
            ->method('setCustomerOrder')
            ->with(null);

        $order->removeCustomerOrderItem($item);
        $this->assertCount(0, $order->getCustomerOrderItems());
    }

    public function testAddCustomerOrderItemWhenAllowEditIsFalse(): void
    {
        $order = $this->getMockBuilder(CustomerOrder::class)
            ->onlyMethods(['allowEdit'])
            ->getMock();

        $order->method('allowEdit')->willReturn(false);

        $item = $this->createMock(CustomerOrderItem::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add items to an order with this status');

        $order->addCustomerOrderItem($item);
    }

    public function testRemoveCustomerOrderItemWhenAllowEditIsFalse(): void
    {
        $order = $this->getMockBuilder(CustomerOrder::class)
            ->onlyMethods(['allowEdit'])
            ->getMock();

        $order->method('allowEdit')->willReturn(false);

        $item = $this->createMock(CustomerOrderItem::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot remove items from an order with this status');

        $order->removeCustomerOrderItem($item);
    }

    public function testAddPurchaseOrder(): void
    {
        $order = new CustomerOrder();
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
        $order = new CustomerOrder();
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
        $order = new CustomerOrder();
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

        $this->assertEquals('300', $order->getTotalPrice());
        $this->assertEquals('360', $order->getTotalPriceIncVat());
        $this->assertEquals(30, $order->getTotalWeight());
    }

    public function testSetShippingDetailsFromShippingMethod(): void
    {
        $order = new CustomerOrder();
        $shippingMethod = ShippingMethod::NEXT_DAY;
        $vatRate = $this->createMock(VatRate::class);

        $vatRate->method('getRate')->willReturn('0.20');

        $order->setShippingDetailsFromShippingMethod($shippingMethod, $vatRate);

        $this->assertSame($shippingMethod, $order->getShippingMethod());
        $this->assertEquals(ShippingMethod::NEXT_DAY->getPrice(), $order->getShippingPrice());
        $this->assertEquals(ShippingMethod::NEXT_DAY->getPriceIncVat($vatRate), $order->getShippingPriceIncVat());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getDueDate());
    }

    public function testAllowEdit(): void
    {
        $order = new CustomerOrder();

        $this->assertTrue($order->allowEdit());

        $status = OrderStatus::SHIPPED;
        $order->setStatus($status);

        $this->assertFalse($order->allowEdit());
    }

    public function testAllowCancel(): void
    {
        $order = new CustomerOrder();

        $this->assertTrue($order->allowCancel());

        $status = OrderStatus::SHIPPED;
        $order->setStatus($status);

        $this->assertFalse($order->allowCancel());
    }

    public function testIsCancelled(): void
    {
        $order = new CustomerOrder();

        $this->assertFalse($order->isCancelled());

        $status = OrderStatus::CANCELLED;
        $order->setStatus($status);

        $this->assertTrue($order->isCancelled());
    }

    public function testGetLineCount(): void
    {
        $order = new CustomerOrder();

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
        $order = new CustomerOrder();
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


}