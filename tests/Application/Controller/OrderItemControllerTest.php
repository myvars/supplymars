<?php

namespace App\Tests\Application\Controller;

use App\Enum\PurchaseOrderStatus;
use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class OrderItemControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexOrderItem(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/item/')
            ->assertSuccessful()
            ->assertOn('/order/');
    }

    public function testOrderItemSecurity(): void
    {
        $this->browser()
            ->get('/order/item/')
            ->assertOn('/login');
    }

    public function testShowOrderItem(): void
    {
        $product = ProductFactory::new()->create(['name' => 'Test Product']);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $product]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/" . $orderItem->getId())
            ->assertSuccessful()
            ->assertSee('Test Product');
    }

    public function testOrderItemNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/999")
            ->assertStatus(404);
    }

    public function testNewOrderItem(): void
    {
        $order = CustomerOrderFactory::createOne();
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $order->getId() . '/item/new')
            ->assertSuccessful()
            ->fillField('create_order_item[productId]', $supplierProduct->getProduct()->getId())
            ->fillField('create_order_item[quantity]', 2)
            ->click('Create Order Item')
            ->assertOn('/order/' . $order->getId())
            ->assertSee('1 line')
            ->assertSee('2 items')
            ->assertSee($supplierProduct->getProduct()->getName());
    }

    public function testNewOrderItemValidation(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $order->getId() . '/item/new')
            ->assertSuccessful()
            ->fillField('create_order_item[quantity]', '')
            ->click('Create Order Item')
            ->assertOn('/order/' . $order->getId() . '/item/new')
            ->assertSee('Please enter a product Id')
            ->assertSee('Please enter a product quantity');
    }

    public function testNewOrderItemInvalidProduct(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $order->getId() . '/item/new')
            ->assertSuccessful()
            ->fillField('create_order_item[productId]', '999')
            ->fillField('create_order_item[quantity]', '1')
            ->click('Create Order Item')
            ->assertOn('/order/' . $order->getId() . '/item/new')
            ->assertSee('Product with Id "999" not found.');
    }

    public function testNewOrderItemNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/item/999/new')
            ->assertStatus(404);
    }

    public function testEditOrderItem(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('1 line')
            ->assertSee('1 item')
            ->get("/order/item/" . $orderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_order_item[quantity]', 2)
            ->fillField('edit_order_item[priceIncVat]', '100.00')
            ->click('Update Order Item')
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('1 line')
            ->assertSee('2 items')
            ->assertSee('100.00');
    }

    public function testEditOrderItemValidation(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('1 line')
            ->assertSee('1 item')
            ->get("/order/item/" . $orderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_order_item[quantity]', '')
            ->fillField('edit_order_item[priceIncVat]', '')
            ->click('Update Order Item')
            ->assertOn("/order/item/" . $orderItem->getId() . "/edit")
            ->assertSee('Please enter a product quantity')
            ->assertSee('Please enter a product price inc VAT');
    }

    public function testEditOrderWithZeroQuantity(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('1 line')
            ->assertSee('1 item')
            ->get("/order/item/" . $orderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_order_item[quantity]', 0)
            ->click('Update Order Item')
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('0 line')
            ->assertSee('0 items');
    }

    public function testEditOrderItemWithMinOrderQuantity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne()->_real();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSuccessful()
            ->assertSee('1 line')
            ->assertSee('1 item')
            ->get("/order/item/" . $orderItem->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('edit_order_item[quantity]', 0)
            ->click('Update Order Item')
            ->assertOn("/order/item/" . $orderItem->getId() . "/edit")
            ->assertSee('minimum quantity is 1');

    }

    public function testEditOrderItemNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/item/999/edit')
            ->assertStatus(404);
    }

    public function testAddToPurchaseOrder(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/" . $orderItem->getId() . "/supplier/product/" . $supplierProduct->getId() . "/po/add")
            ->assertSuccessful()
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee($supplierProduct->getSupplier()->getName())
            ->assertSee($supplierProduct->getProduct()->getName())
            ->assertSee('PO item added');
    }

    public function testAddToPurchaseOrderNotFound(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/999" . "/supplier/product/" . $supplierProduct->getId() . "/po/add")
            ->assertStatus(404);
    }

    public function testAddToPurchaseOrderWithSupplierProductNotFound(): void
    {
        $orderItem = CustomerOrderItemFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/" . $orderItem->getId() . "/supplier/product/999/po/add")
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('PO item could not be added');
    }

    public function testAddToPurchaseOrderWithUnmatchedSupplierProduct(): void
    {
        $unmatchedSupplierProduct = SupplierProductFactory::new()->recalculatePrice()->create();
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/" . $orderItem->getId() . "/supplier/product/"
                . $unmatchedSupplierProduct->getId() . "/po/add"
            )
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('PO item could not be added');
    }

    public function testAddToPurchaseOrderWithoutAllowEdit(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $orderItem->getCustomerOrder()->getId() . "/process")
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit/status")
            ->fillField(
                'change_purchase_order_item_status[purchaseOrderItemStatus]',
                PurchaseOrderStatus::ACCEPTED->value
            )
            ->click('Update Purchase Order Item')
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit/status")
            ->fillField(
                'change_purchase_order_item_status[purchaseOrderItemStatus]',
                PurchaseOrderStatus::SHIPPED->value
            )
            ->click('Update Purchase Order Item')
            ->get("/order/" . $orderItem->getCustomerOrder()->getId() . "/process")
            ->assertSee('Shipped')
            ->get("/order/item/" . $orderItem->getId() . "/supplier/product/"
                . $purchaseOrderItem->getSupplierProduct()->getId() . "/po/add"
            )
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('PO item could not be added');
    }

    public function testReAddToNewPurchaseOrder(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['quantity' => 2]);
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();
        $newSupplierProduct = SupplierProductFactory::new()->recalculatePrice()->create([
            'product' => $orderItem->getProduct()
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->fillField('edit_purchase_order_item[quantity]', 1)
            ->click('Update Purchase Order Item')
            ->get("/order/" . $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getId())
            ->assertSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()))
            ->assertSee('Qty: 1 of 2')
            ->get("/order/item/" . $purchaseOrderItem->getCustomerOrderItem()->getId()
                . "/supplier/product/" . $newSupplierProduct->getId() . "/po/add"
            )
            ->assertSuccessful()
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()));

        $customerOrder = CustomerOrderFactory::repository()->find($orderItem->getCustomerOrder());
        $this->assertCount(2, $customerOrder->getPurchaseOrders());
    }

    public function testReAddToSamePurchaseOrder(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['quantity' => 2]);
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit")
            ->fillField('edit_purchase_order_item[quantity]', 1)
            ->click('Update Purchase Order Item')
            ->get("/order/" . $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getId())
            ->assertSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()))
            ->assertSee('Qty: 1 of 2')
            ->get("/order/item/" . $purchaseOrderItem->getCustomerOrderItem()->getId()
                . "/supplier/product/" . $purchaseOrderItem->getSupplierProduct()->getId() . "/po/add"
            )
            ->assertSuccessful()
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('PO #' . sprintf('%06d', $purchaseOrderItem->getPurchaseOrder()->getId()))
            ->assertSee('Qty: 2');
    }

    public function testCancelOrderItem(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertNotSee('Cancelled')
            ->get("/order/item/" . $orderItem->getId() . "/cancel")
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('Cancelled')
            ->assertSee('Order item cancelled');
    }

    public function testCancelOrderItemWhenAlreadyCancelled(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create(['stock' => 10]);
        $orderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/item/" . $orderItem->getId() . "/cancel")
            ->get("/order/item/" . $orderItem->getId() . "/cancel")
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('Order item cannot be cancelled');
    }

    public function testCancelOrderItemWithoutAllowCancel(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $orderItem->getCustomerOrder()->getId() . "/process")
            ->get("/purchase/order/item/" . $purchaseOrderItem->getId() . "/edit/status")
            ->fillField(
                'change_purchase_order_item_status[purchaseOrderItemStatus]',
                PurchaseOrderStatus::ACCEPTED->value
            )
            ->click('Update Purchase Order Item')
            ->get("/order/" . $orderItem->getCustomerOrder()->getId() . "/process")
            ->assertSee('Accepted')
            ->get("/order/item/" . $orderItem->getId() . "/cancel")
            ->assertOn('/order/' . $orderItem->getCustomerOrder()->getId())
            ->assertSee('Order item cannot be cancelled');
    }

    public function testCancelOrderItemNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/item/999/cancel')
            ->assertStatus(404);
    }
}