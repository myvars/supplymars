<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\AllocateOrder;
use App\Order\Application\Handler\AllocateOrderHandler;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderRepository;
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

class AllocateOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private AllocateOrderHandler $handler;

    private OrderRepository $orders;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(AllocateOrderHandler::class);
        $this->orders = self::getContainer()->get(OrderRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
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

    #[WithStory(StaffUserStory::class)]
    public function testHandleFailsWhenOrderNotFound(): void
    {
        $fakePublicId = OrderPublicId::new();

        $command = new AllocateOrder($fakePublicId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order not found', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
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

        // Pre-allocate by creating a purchase order item linked to this customer order item
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new AllocateOrder($order->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertSame('No items to allocate.', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
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
