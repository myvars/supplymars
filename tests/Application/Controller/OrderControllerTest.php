<?php

namespace App\Tests\Application\Controller;

use App\Enum\ShippingMethod;
use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class OrderControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexOrder(): void
    {
        CustomerOrderFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/')
            ->assertSuccessful()
            ->assertSee('Order Search')
            ->assertSee('3 results');
    }

    public function testOrderSecurity(): void
    {
        $this->browser()
            ->get('/order/')
            ->assertOn('/login');
    }

    public function testFilterOrder(): void
    {
        CustomerOrderFactory::createMany(3);
        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/')
            ->assertSuccessful()
            ->assertSee('Order Search')
            ->assertSee('4 results')
            ->get('/order/search/filter')
            ->assertSuccessful()
            ->fillField('order_search_filter[customerId]', $customer->getId())
            ->click('Update Filter')
            ->assertOn('/order/?customerId=' . $customer->getId() . '&filter=on')
            ->assertSee('Order Search')
            ->assertSee('1 result');
    }

    public function testShowOrder(): void
    {
        $order = CustomerOrderFactory::createOne(['customerOrderRef' => 'TEST12345']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $order->getId())
            ->assertSuccessful()
            ->assertSee('TEST12345');
    }

    public function testNewOrder(): void
    {
        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);
        $user = UserFactory::new()->staff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/order/new')
            ->assertSuccessful()
            ->fillField('create_order[customerId]', $customer->getId())
            ->fillField('create_order[shippingMethod]', ShippingMethod::NEXT_DAY->value)
            ->fillField('create_order[customerOrderRef]', 'TEST12345')
            ->click('Create Order')
            ->assertOn('/order/')
            ->assertSee('TEST12345');
    }

    public function testNewOrderValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/new')
            ->assertSuccessful()
            ->click('Create Order')
            ->assertOn('/order/new')
            ->assertSee('Please enter a customer Id')
            ->assertSee('Please enter a shipping method');
    }

    public function testNewOrderWithInvalidCustomer(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/order/new')
            ->assertSuccessful()
            ->fillField('create_order[customerId]', 999)
            ->fillField('create_order[shippingMethod]', ShippingMethod::NEXT_DAY->value)
            ->click('Create Order')
            ->assertOn('/order/new')
            ->assertSee('Customer with Id "999" not found.');
    }

    public function testOrderNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/999")
            ->assertSee("Order not found!");
    }

    public function testCancelOrderConfirmation(): void
    {
        $order = CustomerOrderFactory::createOne(['customerOrderRef' => 'TEST12345']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $order->getId() . "/cancel/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to cancel this Order');
    }

    public function testCancelOrderNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/999/cancel/confirm")
            ->assertStatus(404);
    }

    public function testCancelOrder(): void
    {
        $order = CustomerOrderFactory::createOne(['customerOrderRef' => 'TEST12345']);
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $order->getId() . "/cancel/confirm")
            ->assertSuccessful()
            ->click('Cancel')
            ->assertOn("/order/" . $order->getId())
            ->assertSee('Order cancelled')
            ->assertSee('Cancelled');
    }

    public function testCancelOrderWithInvalidStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $order = $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $order->getId() . "/cancel/confirm")
            ->assertSuccessful()
            ->click('Cancel')
            ->assertOn("/order/" . $order->getId())
            ->assertSee('Order cannot be cancelled');
    }

    public function testProcessOrder(): void
    {
        $supplierProduct = SupplierProductFactory::new()->recalculatePrice()->create();
        $order = CustomerOrderFactory::createOne(['customerOrderRef' => 'TEST12345']);
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $supplierProduct->getProduct()
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $order->getId())
            ->assertSuccessful()
            ->assertSee('Pending')
            ->get("/order/" . $order->getId() . "/process")
            ->assertOn("/order/" . $order->getId())
            ->assertSee('PO #')
            ->assertSee('Processing');
    }

    public function testProcessOrderNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/999/process")
            ->assertStatus(404);
    }

    public function testLockOrder(): void
    {
        $order = CustomerOrderFactory::createOne(['customerOrderRef' => 'TEST12345']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/" . $order->getId())
            ->assertSuccessful()
            ->assertNotSee("Locked by Staff Member")
            ->get("/order/" . $order->getId() . "/lock/toggle")
            ->assertSee('Locked by Staff Member');
    }

    public function testLockOrderNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/order/999/lock/toggle")
            ->assertStatus(404);
    }
}