<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\CreateOrderItem;
use App\Order\Application\Handler\CreateOrderItemHandler;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderItemHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateOrderItemHandler $handler;

    private OrderItemRepository $orderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateOrderItemHandler::class);
        $this->orderItems = self::getContainer()->get(OrderItemRepository::class);
    }

    public function testHandleCreatesOrderItem(): void
    {
        $order = CustomerOrderFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();

        $command = new CreateOrderItem(
            orderId: $order->getPublicId(),
            productId: $product->getId(),
            quantity: 5,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $orderItem = $this->orderItems->getByPublicId($result->payload);
        self::assertNotNull($orderItem);
        self::assertSame($product->getId(), $orderItem->getProduct()->getId());
        self::assertSame(5, $orderItem->getQuantity());
        self::assertSame($product->getSellPrice(), $orderItem->getPrice());
        self::assertSame($product->getSellPriceIncVat(), $orderItem->getPriceIncVat());
        self::assertSame(bcmul('5', $product->getSellPrice(), 2), $orderItem->getTotalPrice());
        self::assertSame(OrderStatus::PENDING, $orderItem->getStatus());
    }

    public function testHandleFailsWhenOrderNotFound(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();

        $command = new CreateOrderItem(
            orderId: OrderPublicId::fromString((string) new Ulid()),
            productId: $product->getId(),
            quantity: 5,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order not found', $result->message);
    }

    public function testHandleFailsWhenProductNotFound(): void
    {
        $order = CustomerOrderFactory::createOne();

        $command = new CreateOrderItem(
            orderId: $order->getPublicId(),
            productId: 999999,
            quantity: 5,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product not found', $result->message);
    }

    public function testHandleFailsOnInvalidQuantity(): void
    {
        $order = CustomerOrderFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();

        $command = new CreateOrderItem(
            orderId: $order->getPublicId(),
            productId: $product->getId(),
            quantity: 0,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The quantity must be positive');

        ($this->handler)($command);
    }
}
