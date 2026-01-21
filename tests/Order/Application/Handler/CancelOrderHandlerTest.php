<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\CancelOrder;
use App\Order\Application\Handler\CancelOrderHandler;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private CancelOrderHandler $handler;

    private OrderRepository $orders;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CancelOrderHandler::class);
        $this->orders = self::getContainer()->get(OrderRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleCancelsOrderWithItems(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $command = new CancelOrder($order->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order cancelled.', $result->message);

        $reloaded = $this->orders->getByPublicId($order->getPublicId());
        self::assertSame(OrderStatus::CANCELLED, $reloaded->getStatus());
    }

    public function testHandleFailsWhenOrderNotFound(): void
    {
        $fakePublicId = OrderPublicId::new();

        $command = new CancelOrder($fakePublicId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order not found', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleFailsWhenOrderAlreadyCancelled(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        // Cancel the order first
        $firstResult = ($this->handler)(new CancelOrder($order->getPublicId()));
        self::assertTrue($firstResult->ok);

        // Try to cancel again
        $result = ($this->handler)(new CancelOrder($order->getPublicId()));

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order already cancelled', $result->message);
    }
}
