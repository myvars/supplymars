<?php

declare(strict_types=1);

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
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class UpdatePurchaseOrderItemStatusFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

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

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAPurchaseOrderItemIdPostedInTheForm(string $postedId): void
    {
        $other = PurchaseOrderItemFactory::createOne();
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/purchase/order/item/' . $purchaseOrderItem->getPublicId()->value() . '/edit/status',
            'Update',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['purchaseOrderItemStatus' => PurchaseOrderStatus::PROCESSING->value],
        );

        PurchaseOrderItemFactory::assert()->exists(['id' => $other->getId(), 'status' => PurchaseOrderStatus::PENDING]);
        PurchaseOrderItemFactory::assert()->exists(['id' => $purchaseOrderItem->getId(), 'status' => PurchaseOrderStatus::PENDING]);
    }
}
