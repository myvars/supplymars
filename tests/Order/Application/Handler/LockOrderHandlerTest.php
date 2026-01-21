<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\LockOrder;
use App\Order\Application\Handler\LockOrderHandler;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class LockOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private LockOrderHandler $handler;

    private OrderRepository $orders;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(LockOrderHandler::class);
        $this->orders = self::getContainer()->get(OrderRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleLocksOrder(): void
    {
        $order = CustomerOrderFactory::createOne();
        self::assertNull($order->getOrderLock());

        $command = new LockOrder($order->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $reloaded = $this->orders->getByPublicId($order->getPublicId());
        self::assertNotNull($reloaded->getOrderLock());
        self::assertSame('adam@admin.com', $reloaded->getOrderLock()->getUserIdentifier());
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleUnlocksOrder(): void
    {
        $order = CustomerOrderFactory::createOne();

        // Lock the order first
        $firstResult = ($this->handler)(new LockOrder($order->getPublicId()));
        self::assertTrue($firstResult->ok);

        $reloaded = $this->orders->getByPublicId($order->getPublicId());
        self::assertNotNull($reloaded->getOrderLock());

        // Toggle lock again to unlock
        $result = ($this->handler)(new LockOrder($order->getPublicId()));

        self::assertTrue($result->ok);

        $reloadedAgain = $this->orders->getByPublicId($order->getPublicId());
        self::assertNull($reloadedAgain->getOrderLock());
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleFailsWhenOrderNotFound(): void
    {
        $fakePublicId = OrderPublicId::fromString((string) new Ulid());

        $command = new LockOrder($fakePublicId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order not found', $result->message);
    }
}
