<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\UpdateOrderItem;
use App\Order\Application\Handler\UpdateOrderItemHandler;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class UpdateOrderItemHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateOrderItemHandler $handler;

    private OrderItemRepository $orderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateOrderItemHandler::class);
        $this->orderItems = self::getContainer()->get(OrderItemRepository::class);
    }

    public function testHandleUpdatesOrderItem(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new UpdateOrderItem(
            orderItemId: $orderItem->getPublicId(),
            quantity: 10,
            priceIncVat: '30.00',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order item updated', $result->message);

        $persisted = $this->orderItems->getByPublicId($orderItem->getPublicId());
        self::assertSame(10, $persisted->getQuantity());
        self::assertSame('30.00', $persisted->getPriceIncVat());
    }

    public function testFailsWhenOrderItemNotFound(): void
    {
        $missingId = OrderItemPublicId::new();

        $command = new UpdateOrderItem(
            orderItemId: $missingId,
            quantity: 5,
            priceIncVat: '25.00',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order item not found.', $result->message);
    }

    public function testFailsWhenQuantityIsNegative(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new UpdateOrderItem(
            orderItemId: $orderItem->getPublicId(),
            quantity: -1,
            priceIncVat: '25.00',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Quantity must be >= 0', $result->message);
    }

    public function testZeroQuantityRemovesOrderItem(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);
        $publicId = $orderItem->getPublicId();

        $command = new UpdateOrderItem(
            orderItemId: $publicId,
            quantity: 0,
            priceIncVat: '25.00',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order item removed', $result->message);

        $removed = $this->orderItems->getByPublicId($publicId);
        self::assertNull($removed);
    }

    #[WithStory(StaffUserStory::class)]
    public function testZeroQuantityFailsWithPurchaseOrderAllocation(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $orderItem,
            'product' => $product,
            'quantity' => 3,
        ]);

        $command = new UpdateOrderItem(
            orderItemId: $orderItem->getPublicId(),
            quantity: 0,
            priceIncVat: '25.00',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Allocated PO qty > 0', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testFailsWhenQuantityBelowAllocated(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $orderItem,
            'product' => $product,
            'quantity' => 3,
        ]);

        $command = new UpdateOrderItem(
            orderItemId: $orderItem->getPublicId(),
            quantity: 2,
            priceIncVat: '25.00',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Below allocated qty', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testUpdateSucceedsWhenQuantityEqualsAllocated(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $orderItem,
            'product' => $product,
            'quantity' => 3,
        ]);

        $command = new UpdateOrderItem(
            orderItemId: $orderItem->getPublicId(),
            quantity: 3,
            priceIncVat: '25.00',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order item updated', $result->message);

        $persisted = $this->orderItems->getByPublicId($orderItem->getPublicId());
        self::assertSame(3, $persisted->getQuantity());
    }
}
