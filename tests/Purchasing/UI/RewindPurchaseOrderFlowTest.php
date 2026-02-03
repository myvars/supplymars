<?php

namespace App\Tests\Purchasing\UI;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class RewindPurchaseOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testRewindConfirmPageShowsConfirmation(): void
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

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $publicId = $purchaseOrder->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/' . $publicId . '/rewind/confirm')
            ->assertSuccessful()
            ->assertSee('Rewind Purchase Order')
            ->assertSee('Are you sure you want to rewind this Purchase Order to pending');
    }

    public function testRewindViaConfirmFlow(): void
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

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $publicId = $purchaseOrder->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/' . $publicId . '/rewind/confirm')
            ->click('Rewind to Pending')
            ->assertOn('/purchase/order/' . $publicId)
            ->assertSee('Purchase order rewound to pending');
    }
}
