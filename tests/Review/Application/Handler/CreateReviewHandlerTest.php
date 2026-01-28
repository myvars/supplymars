<?php

namespace App\Tests\Review\Application\Handler;

use App\Review\Application\Command\CreateReview;
use App\Review\Application\Handler\CreateReviewHandler;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreateReviewHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateReviewHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateReviewHandler::class);
    }

    public function testSuccessfulCreation(): void
    {
        VatRateFactory::new()->withStandardRate()->create();
        $customer = UserFactory::createOne();
        $product = ProductFactory::createOne();
        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
        ]);

        $command = new CreateReview(
            customerId: $customer->getId(),
            productId: $product->getId(),
            orderId: $order->getId(),
            rating: 4,
            title: 'Great product',
            body: 'Really enjoyed it',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Review created', $result->message);
    }

    public function testFailsWhenCustomerNotFound(): void
    {
        $command = new CreateReview(
            customerId: 999999,
            productId: 1,
            orderId: 1,
            rating: 4,
            title: 'Test',
            body: null,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Customer not found', $result->message);
    }
}
