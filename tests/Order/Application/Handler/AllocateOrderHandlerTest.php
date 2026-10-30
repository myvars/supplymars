<?php

declare(strict_types=1);

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\AllocateOrder;
use App\Order\Application\Handler\AllocateOrderHandler;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(StaffUserStory::class)]
final class AllocateOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private AllocateOrderHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(AllocateOrderHandler::class);
    }

    public function testHandleAllocatesOrderSuccessfully(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new AllocateOrder($order->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order processed.', $result->message);
    }

    public function testHandleFailsWhenOrderNotFound(): void
    {
        $fakePublicId = OrderPublicId::new();

        $command = new AllocateOrder($fakePublicId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order not found', $result->message);
    }

    public function testHandleFailsWhenAllItemsAlreadyAllocated(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Pre-allocate by creating a purchase order item linked to this customer order item, already sent
        $poItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $command = new AllocateOrder($order->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertSame('No items to allocate.', $result->message);
    }

    public function testHandleSendsHandAllocatedPendingItemsToSuppliers(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Every line allocated by hand: nothing outstanding, the PO item still PENDING
        $poItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);
        self::assertSame(PurchaseOrderStatus::PENDING, $poItem->getStatus());

        $result = ($this->handler)(new AllocateOrder($order->getPublicId()));

        self::assertTrue($result->ok);
        self::assertSame('Order processed.', $result->message);
        self::assertSame(PurchaseOrderStatus::PROCESSING, $poItem->getStatus());
        self::assertSame(OrderStatus::PROCESSING, $order->getStatus());
    }

    public function testHandleLeavesCancelledItemOffPurchaseOrders(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);
        // No supplier stocks this product, so its line could never be allocated
        $unsourcedProduct = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);
        $cancelledItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $unsourcedProduct,
            'quantity' => 2,
        ]);
        $cancelledItem->cancelItem();

        $result = ($this->handler)(new AllocateOrder($order->getPublicId()));

        self::assertTrue($result->ok);
        self::assertSame('Order processed.', $result->message);
        self::assertCount(1, $orderItem->getPurchaseOrderItems());
        self::assertCount(0, $cancelledItem->getPurchaseOrderItems());
        self::assertSame(0, $cancelledItem->getOutstandingQty());
    }

    public function testHandleFailsWhenCannotAllocateAllItems(): void
    {
        // Create a product without any supplier product source
        $product = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new AllocateOrder($order->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Cannot allocate all items', $result->message);
    }
}
