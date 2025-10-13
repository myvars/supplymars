<?php

namespace App\Tests\Audit\UI\Http;

use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class StatusLogControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testShowOrderStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $order = $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/order/" . $order->getPublicId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Customer Order Created');
    }

    public function testShowOrderStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $order = $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder();

        $this->browser()
            ->get("/status/log/order/" . $order->getPublicId())
            ->assertOn('/login');
    }

    public function testShowOrderStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/order/999")
            ->assertStatus(500);
    }

    public function testShowOrderItemStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/order/item/" . $orderItem->getPublicId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Customer Order Item Created');
    }

    public function testShowOrderItemStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->get("/status/log/order/item/" . $orderItem->getPublicId())
            ->assertOn('/login');
    }

    public function testShowOrderItemStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/order/item/999")
            ->assertStatus(500);
    }

    public function testShowPurchaseOrderStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/purchase/order/" . $purchaseOrder->getPublicId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Purchase Order Created');
    }

    public function testShowPurchaseOrderStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();

        $this->browser()
            ->get("/status/log/purchase/order/" . $purchaseOrder->getPublicId())
            ->assertOn('/login');
    }

    public function testShowPurchaseOrderStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/purchase/order/999")
            ->assertStatus(500);
    }

    public function testShowPurchaseOrderItemStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/purchase/order/item/" . $purchaseOrderItem->getPublicId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Purchase Order Item Created');
    }

    public function testShowPurchaseOrderItemStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->browser()
            ->get("/status/log/purchase/order/item/" . $purchaseOrderItem->getPublicId())
            ->assertOn('/login');
    }

    public function testShowPurchaseOrderItemStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/status/log/purchase/order/item/999")
            ->assertStatus(500);
    }
}
