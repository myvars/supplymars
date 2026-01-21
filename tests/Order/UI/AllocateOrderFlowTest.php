<?php

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class AllocateOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testAllocateOrderSuccessRedirectsToShow(): void
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

        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId . '/allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Order processed.');
    }

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

        // Pre-allocate the order item
        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 5,
        ]);

        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId . '/allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('No items to allocate.');
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
            ->get('/order/' . $publicId . '/allocate')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Cannot allocate all items');
    }
}
