<?php

namespace App\Tests\Application\Controller;

use App\Factory\PurchaseOrderFactory;
use App\Factory\SupplierFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class PurchaseOrderControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexPurchaseOrder(): void
    {
        PurchaseOrderFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/purchase/order/')
            ->assertSuccessful()
            ->assertSee('Purchase Order Search')
            ->assertSee('3 results');
    }

    public function testPurchaseOrderSecurity(): void
    {
        $this->browser()
            ->get('/purchase/order/')
            ->assertOn('/login');
    }

    public function testShowPurchaseOrder(): void
    {
        $purchaseOrder = PurchaseOrderFactory::createOne()->_real();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/" . $purchaseOrder->getId())
            ->assertSuccessful()
            ->assertSee($purchaseOrder->getId())
            ->assertSee($purchaseOrder->getSupplier()->getName());
    }

    public function testSearchFilterPurchaseOrder(): void
    {
        PurchaseOrderFactory::createMany(3);
        $supplier = SupplierFactory::createOne(['name' => 'Test Supplier']);
        PurchaseOrderFactory::createOne(['supplier' => $supplier]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/purchase/order/')
            ->assertSuccessful()
            ->assertSee('Purchase Order Search')
            ->assertSee('4 results')
            ->get('/purchase/order/search/filter')
            ->assertSuccessful()
            ->fillField('purchase_order_search_filter[supplierId]', $supplier->getId())
            ->click('Update Filter')
            ->assertOn('/purchase/order/?supplierId=' . $supplier->getId() . '&filter=on')
            ->assertSee('Purchase Order Search')
            ->assertSee('1 result');
    }

    public function testPurchaseOrderNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/purchase/order/999")
            ->assertSee("Purchase order not found!");
    }
}
