<?php

namespace App\Tests\Purchasing\UI;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
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
final class UpdatePurchaseOrderItemStatusFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulStatusChangeViaForm(): void
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

        $poPublicId = $purchaseOrderItem->getPurchaseOrder()->getPublicId()->value();

        // Status is PENDING by default, transition to PROCESSING (valid transition)
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/item/' . $purchaseOrderItem->getPublicId()->value() . '/edit/status')
            ->assertSuccessful()
            ->fillField('purchase_order_item_status[purchaseOrderItemStatus]', PurchaseOrderStatus::PROCESSING->value)
            ->click('Update')
            ->assertOn('/purchase/order/' . $poPublicId);
    }

    public function testFailsOnInvalidStatusTransition(): void
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

        // Status is PENDING by default, try to transition directly to DELIVERED (invalid)
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/item/' . $poiPublicId . '/edit/status')
            ->fillField('purchase_order_item_status[purchaseOrderItemStatus]', PurchaseOrderStatus::DELIVERED->value)
            ->click('Update')
            ->assertOn('/purchase/order/item/' . $poiPublicId . '/edit/status')
            ->assertSee('Status can not be set to');
    }
}
