<?php

namespace App\Tests\Application\Controller;

use App\Factory\PurchaseOrderItemFactory;
use App\Factory\UserFactory;
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
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/order/" . $order->getId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Customer Order Created');
    }

    public function testShowOrderStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $order = $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder();

        $this->browser()
            ->get("/status/log/order/" . $order->getId())
            ->assertOn('/login');
    }

    public function testShowOrderStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/order/999")
            ->assertStatus(404);
    }

    public function testShowOrderItemStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/order/item/" . $orderItem->getId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Customer Order Item Created');
    }

    public function testShowOrderItemStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $orderItem = $purchaseOrderItem->getCustomerOrderItem();

        $this->browser()
            ->get("/status/log/order/item/" . $orderItem->getId())
            ->assertOn('/login');
    }

    public function testShowOrderItemStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/order/item/999")
            ->assertStatus(404);
    }

    public function testShowPurchaseOrderStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/purchase/order/" . $purchaseOrder->getId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Purchase Order Created');
    }

    public function testShowPurchaseOrderStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();

        $this->browser()
            ->get("/status/log/purchase/order/" . $purchaseOrder->getId())
            ->assertOn('/login');
    }

    public function testShowPurchaseOrderStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/purchase/order/999")
            ->assertStatus(404);
    }

    public function testShowPurchaseOrderItemStatusLog(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/purchase/order/item/" . $purchaseOrderItem->getId())
            ->assertSuccessful()
            ->assertSee('Status Log')
            ->assertSee('Purchase Order Item Created');
    }

    public function testShowPurchaseOrderItemStatusLogSecurity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->browser()
            ->get("/status/log/purchase/order/item/" . $purchaseOrderItem->getId())
            ->assertOn('/login');
    }

    public function testShowPurchaseOrderItemStatusLogNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/status/log/purchase/order/item/999")
            ->assertStatus(404);
    }
}