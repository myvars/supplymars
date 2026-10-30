<?php

declare(strict_types=1);

namespace App\Tests\Review\Application\Handler;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Review\Application\Command\CreateReview;
use App\Review\Application\Handler\CreateReviewHandler;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateReviewHandlerTest extends KernelTestCase
{
    private CreateReviewHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateReviewHandler::class);
    }

    public function testSuccessfulCreation(): void
    {
        [$customer, $product, $order] = $this->createDeliveredPurchase();

        $result = ($this->handler)($this->command($customer, $product, $order));

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

    public function testFailsWhenOrderBelongsToAnotherCustomer(): void
    {
        [, $product, $order] = $this->createDeliveredPurchase();
        $otherCustomer = UserFactory::createOne();

        $result = ($this->handler)($this->command($otherCustomer, $product, $order));

        self::assertFalse($result->ok);
        self::assertSame('Order does not belong to this customer.', $result->message);
    }

    public function testFailsWhenOrderIsNotDelivered(): void
    {
        $customer = UserFactory::createOne();
        $product = ProductFactory::createOne();
        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product]);

        $result = ($this->handler)($this->command($customer, $product, $order));

        self::assertFalse($result->ok);
        self::assertSame('Delivered product not found in this order.', $result->message);
    }

    public function testFailsWhenProductIsNotInOrder(): void
    {
        [$customer, , $order] = $this->createDeliveredPurchase();
        $otherProduct = ProductFactory::createOne();

        $result = ($this->handler)($this->command($customer, $otherProduct, $order));

        self::assertFalse($result->ok);
        self::assertSame('Delivered product not found in this order.', $result->message);
    }

    public function testFailsWhenCustomerAlreadyReviewedProduct(): void
    {
        [$customer, $product, $order] = $this->createDeliveredPurchase();
        ProductReviewFactory::createOne(['customer' => $customer, 'product' => $product, 'customerOrder' => $order]);

        $result = ($this->handler)($this->command($customer, $product, $order));

        self::assertFalse($result->ok);
        self::assertSame('A review already exists for this customer and product.', $result->message);
    }

    private function command(User $customer, Product $product, CustomerOrder $order): CreateReview
    {
        return new CreateReview(
            customerId: $customer->getId(),
            productId: $product->getId(),
            orderId: $order->getId(),
            rating: 4,
            title: 'Great product',
            body: 'Really enjoyed it',
        );
    }

    /**
     * @return array{User, Product, CustomerOrder}
     */
    private function createDeliveredPurchase(): array
    {
        $customer = UserFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();
        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 1,
        ]);

        $poItem = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => PurchaseOrderFactory::createOne(['customerOrder' => $order]),
            'customerOrderItem' => $orderItem,
            'product' => $product,
            'quantity' => 1,
        ]);
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);
        $poItem->updateItemStatus(PurchaseOrderStatus::DELIVERED);
        self::getContainer()->get('doctrine')->getManager()->flush();

        self::assertSame(OrderStatus::DELIVERED, $order->getStatus());

        return [$customer, $product, $order];
    }
}
