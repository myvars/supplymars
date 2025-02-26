<?php

namespace App\Tests\Application\Controller;

use App\Enum\PurchaseOrderStatus;
use App\Factory\PurchaseOrderFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class PurchaseOrderItemControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexPurchaseOrderItem(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/purchase/order/item/')
            ->assertSuccessful()
            ->assertOn('/purchase/order/');
    }

    public function testPurchaseOrderItemSecurity(): void
    {
        $this->browser()
            ->get('/purchase/order/item/')
            ->assertOn('/login');
    }

    public function testShowPurchaseOrderItem(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Test Product']);
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'supplier' => $supplierProduct->getSupplier(),
            'product' => $supplierProduct->getProduct(),
            'supplierProduct' => $supplierProduct
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId())
            ->assertSuccessful()
            ->assertSee('Test Product');
    }

    public function testPurchaseOrderItemNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/999")
            ->assertSee("Purchase order item not found!");
    }

    public function testEditPurchaseOrderItem(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['quantity' => 2]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_purchase_order_item[quantity]', 1)
            ->click('Update Purchase Order Item')
            ->assertOn('/purchase/order/' . $purchaseOrderItem->getPurchaseOrder()->getId())
            ->assertSee('Qty: 1')
            ->assertSee('Purchase order item updated')
            ->get("/order/" . $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getId())
            ->assertSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()))
            ->assertSee('Qty: 1 of 2');
    }

    public function testEditPurchaseOrderItemValidation(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['quantity' => 2]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_purchase_order_item[quantity]', '')
            ->click('Update Purchase Order Item')
            ->assertOn("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->assertSee('Please enter a product quantity');
    }

    public function testEditPurchaseOrderItemWithZeroQty(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['quantity' => 2]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getId())
            ->assertSuccessful()
            ->assertSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()))
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_purchase_order_item[quantity]', '0')
            ->click('Update Purchase Order Item')
            ->assertOn("/order/" . $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getId())
            ->assertSee('Purchase order item updated')
            ->assertNotSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()))
            ->assertSee('Qty: 2');
    }

    public function testEditPurchaseOrderItemWithGreaterThanMaxPurchaseOrderItemQty(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['quantity' => 2]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_purchase_order_item[quantity]', 3)
            ->click('Update Purchase Order Item')
            ->assertOn("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->assertSee('maximum quantity is 2');
    }

    public function testEditPurchaseOrderItemNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/999/edit")
            ->assertStatus(404);
    }

    public function testEditPurchaseOrderItemStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit/status")
            ->fillField(
                'change_purchase_order_item_status[purchaseOrderItemStatus]',
                PurchaseOrderStatus::PROCESSING->value
            )
            ->click('Update Purchase Order Item')
            ->assertOn('/purchase/order/' . $purchaseOrderItem->getPurchaseOrder()->getId())
            ->assertSee('Processing')
            ->assertSee('PO Item status updated!');
    }

    public function testEditPurchaseOrderItemStatusWithInvalidStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit/status")
            ->fillField(
                'change_purchase_order_item_status[purchaseOrderItemStatus]',
                PurchaseOrderStatus::SHIPPED->value
            )
            ->click('Update Purchase Order Item')
            ->assertOn("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit/status")
            ->assertSee('Status can not be set to Shipped.');
    }

    public function testEditPurchaseOrderItemStatusNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/999/edit/status")
            ->assertStatus(404);
    }
}