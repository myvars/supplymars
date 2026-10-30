<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Domain\Repository\OrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class UpdateOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAnOrderItemIdPostedInTheForm(string $postedId): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $other = CustomerOrderItemFactory::createOne(['product' => $product, 'quantity' => 5]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $product, 'quantity' => 5]);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/order/item/' . $orderItem->getPublicId()->value() . '/edit',
            'Update Order Item',
            'orderItemId',
            $postedId,
            $other->getPublicId()->value(),
            ['quantity' => '10'],
        );

        CustomerOrderItemFactory::assert()->exists(['id' => $other->getId(), 'quantity' => 5]);
        CustomerOrderItemFactory::assert()->exists(['id' => $orderItem->getId(), 'quantity' => 5]);
    }

    public function testSuccessfulEditViaForm(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);
        $orderId = $orderItem->getCustomerOrder()->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->assertSuccessful()
            ->fillField('update_order_item[quantity]', '10')
            ->fillField('update_order_item[priceIncVat]', '30.00')
            ->click('Update Order Item')
            ->assertOn('/order/' . $orderId);
    }

    public function testValidationErrorOnEmptyQuantity(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->fillField('update_order_item[quantity]', '')
            ->click('Update Order Item')
            ->assertOn('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->assertSee('Please enter a quantity');
    }

    public function testValidationErrorOnNegativeQuantity(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->fillField('update_order_item[quantity]', '-1')
            ->click('Update Order Item')
            ->assertOn('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->assertSee('Please enter a valid quantity');
    }

    #[WithStory(StaffUserStory::class)]
    public function testValidationErrorOnBelowAllocatedQuantity(): void
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

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->fillField('update_order_item[quantity]', '2')
            ->click('Update Order Item')
            ->assertOn('/order/item/' . $orderItem->getPublicId()->value() . '/edit')
            ->assertSee('minimum quantity is 3');
    }

    public function testZeroQuantityRemovesItem(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);
        $publicId = $orderItem->getPublicId();
        $orderId = $orderItem->getCustomerOrder()->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $publicId->value() . '/edit')
            ->fillField('update_order_item[quantity]', '0')
            ->click('Update Order Item')
            ->assertOn('/order/' . $orderId);

        $orderItems = self::getContainer()->get(OrderItemRepository::class);
        $removed = $orderItems->getByPublicId($publicId);
        self::assertNull($removed);
    }
}
