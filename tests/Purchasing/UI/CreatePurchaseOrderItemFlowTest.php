<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(StaffUserStory::class)]
class CreatePurchaseOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaGet(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
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

        $orderItemPublicId = $orderItem->getPublicId()->value();
        $supplierProductPublicId = $supplierProduct->getPublicId()->value();
        $orderPublicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItemPublicId . '/supplier/product/' . $supplierProductPublicId . '/po/add')
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Purchase order item updated');
    }

    public function testFailsWithInvalidOrderItem(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $invalidOrderItemId = (string) new Ulid();
        $supplierProductPublicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $invalidOrderItemId . '/supplier/product/' . $supplierProductPublicId . '/po/add')
            ->assertStatus(404);
    }

    public function testFailsWithInvalidSupplierProduct(): void
    {
        $product = ProductFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $orderItemPublicId = $orderItem->getPublicId()->value();
        $invalidSupplierProductId = (string) new Ulid();
        $orderPublicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItemPublicId . '/supplier/product/' . $invalidSupplierProductId . '/po/add')
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Supplier product not found');
    }

    public function testFailsWhenSupplierProductNotSourceForOrderItem(): void
    {
        // Create two different products
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);

        // Create supplier product for product2
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        // Create order item for product1
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $orderItemPublicId = $orderItem->getPublicId()->value();
        $supplierProductPublicId = $supplierProduct->getPublicId()->value();
        $orderPublicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItemPublicId . '/supplier/product/' . $supplierProductPublicId . '/po/add')
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Supplier product source missing');
    }
}
