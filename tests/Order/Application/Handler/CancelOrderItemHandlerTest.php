<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\CancelOrderItem;
use App\Order\Application\Handler\CancelOrderItemHandler;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderItemHandlerTest extends KernelTestCase
{
    use Factories;

    private CancelOrderItemHandler $handler;

    private OrderItemRepository $orderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CancelOrderItemHandler::class);
        $this->orderItems = self::getContainer()->get(OrderItemRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleCancelsOrderItem(): void
    {
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $command = new CancelOrderItem($orderItem->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order item cancelled.', $result->message);

        $reloaded = $this->orderItems->getByPublicId($orderItem->getPublicId());
        self::assertSame(OrderStatus::CANCELLED, $reloaded->getStatus());
    }

    public function testHandleFailsWhenOrderItemNotFound(): void
    {
        $fakePublicId = OrderItemPublicId::new();

        $command = new CancelOrderItem($fakePublicId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order item not found', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleFailsWhenOrderItemAlreadyCancelled(): void
    {
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        // Cancel the order item first
        $firstResult = ($this->handler)(new CancelOrderItem($orderItem->getPublicId()));
        self::assertTrue($firstResult->ok);

        // Try to cancel again
        $result = ($this->handler)(new CancelOrderItem($orderItem->getPublicId()));

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order item already cancelled', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleFailsWhenOrderItemHasPurchaseOrderAllocations(): void
    {
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        // Create a purchase order item linked to the customer order item
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
        ]);

        $command = new CancelOrderItem($orderItem->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order item cannot be cancelled', $result->message);
    }
}
