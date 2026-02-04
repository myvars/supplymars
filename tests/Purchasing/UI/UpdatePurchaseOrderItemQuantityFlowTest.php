<?php

namespace App\Tests\Purchasing\UI;

use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(StaffUserStory::class)]
final class UpdatePurchaseOrderItemQuantityFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $customerOrderPublicId = $customerOrder->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/item/' . $purchaseOrderItem->getPublicId()->value() . '/edit')
            ->assertSuccessful()
            ->fillField('purchase_order_item_quantity[quantity]', '5')
            ->click('Update')
            ->assertOn('/order/' . $customerOrderPublicId);
    }

    public function testValidationErrorOnEmptyQuantity(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $poiPublicId = $purchaseOrderItem->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/item/' . $poiPublicId . '/edit')
            ->fillField('purchase_order_item_quantity[quantity]', '')
            ->click('Update')
            ->assertOn('/purchase/order/item/' . $poiPublicId . '/edit')
            ->assertSee('Please enter a product quantity');
    }

    public function testValidationErrorOnNegativeQuantity(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $poiPublicId = $purchaseOrderItem->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/item/' . $poiPublicId . '/edit')
            ->fillField('purchase_order_item_quantity[quantity]', '-1')
            ->click('Update')
            ->assertOn('/purchase/order/item/' . $poiPublicId . '/edit')
            ->assertSee('Quantity must be between');
    }

    public function testZeroQuantityRemovesItem(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        // Create two PO items so the PO still exists after removal
        $purchaseOrderItem1 = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $purchaseOrder = $purchaseOrderItem1->getPurchaseOrder();
        $customerOrderPublicId = $customerOrder->getPublicId()->value();

        $customerOrderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem2,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'purchaseOrder' => $purchaseOrder,
            'quantity' => 2,
        ]);

        $publicId = $purchaseOrderItem1->getPublicId();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/item/' . $publicId->value() . '/edit')
            ->fillField('purchase_order_item_quantity[quantity]', '0')
            ->click('Update')
            ->assertOn('/order/' . $customerOrderPublicId);

        $purchaseOrderItems = self::getContainer()->get(PurchaseOrderItemRepository::class);
        $removed = $purchaseOrderItems->getByPublicId($publicId);
        self::assertNull($removed);
    }
}
