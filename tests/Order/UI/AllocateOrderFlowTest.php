<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use App\Tests\Shared\UI\ActionFormToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class AllocateOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testAllocateOrderSuccessRedirectsToShow(): void
    {
        $publicId = $this->allocatableOrder()->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->click('Allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Order processed.');
    }

    #[WithStory(StaffUserStory::class)]
    public function testAllocateOrderShowsErrorWhenAlreadyAllocated(): void
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

        // Pre-allocate the order item, already sent to the supplier
        $poItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->click('Allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('No items to allocate.');
    }

    public function testAllocateOrderSendsHandAllocatedItemsToSuppliers(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        // Allocated by hand: nothing outstanding, the PO item still Pending
        $poItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->click('Allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Order processed.');

        self::assertSame(
            PurchaseOrderStatus::PROCESSING,
            PurchaseOrderItemFactory::repository()->find($poItem->getId())?->getStatus()
        );
    }

    public function testAllocateOrderShowsErrorWhenCannotAllocate(): void
    {
        // Create a product without any supplier product source
        $product = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->click('Allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Cannot allocate all items');
    }

    public function testAllocateIsNotReachableWithGet(): void
    {
        $publicId = $this->allocatableOrder()->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId . '/allocate')
            ->assertStatus(405);

        PurchaseOrderItemFactory::assert()->empty();
    }

    public function testAllocateRejectsAMissingTokenOrAnotherOrdersToken(): void
    {
        $other = CustomerOrderFactory::createOne()->getPublicId()->value();
        $publicId = $this->allocatableOrder()->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/order/' . $other, '/order/' . $other . '/allocate');

        $browser
            ->post('/order/' . $publicId . '/allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Invalid CSRF token.')
            ->post('/order/' . $publicId . '/allocate', ['body' => ['_token' => $otherToken]])
            ->assertOn('/order/' . $publicId)
            ->assertSee('Invalid CSRF token.');

        PurchaseOrderItemFactory::assert()->empty();
    }

    private function allocatableOrder(): CustomerOrder
    {
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::createOne(['isActive' => true]),
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        return $order;
    }
}
