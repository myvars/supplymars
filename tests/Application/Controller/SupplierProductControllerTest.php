<?php

namespace App\Tests\Application\Controller;

use App\Factory\SupplierFactory;
use App\Factory\SupplierManufacturerFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class SupplierProductControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexSupplierProduct(): void
    {
        SupplierProductFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/supplier-product/')
            ->assertSuccessful()
            ->assertSee('Supplier Product Search')
            ->assertSee('3 results');
    }

    public function testSupplierProductSecurity(): void
    {
        $this->browser()
            ->get('/supplier-product/')
            ->assertOn('/login');
    }

    public function testFilterCategory(): void
    {
        SupplierProductFactory::createMany(3);
        SupplierProductFactory::createOne(['productCode' => 'TEST0001']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/supplier-product/')
            ->assertSuccessful()
            ->assertSee('Supplier Product Search')
            ->assertSee('4 results')
            ->get('/supplier-product/search/filter')
            ->assertSuccessful()
            ->fillField('supplier_product_search_filter[productCode]', 'TEST0001')
            ->click('Update Filter')
            ->assertOn('/supplier-product/?productCode=TEST0001&filter=on')
            ->assertSee('Supplier Product Search')
            ->assertSee('1 result');
    }

    public function testShowSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be shown']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId())
            ->assertSuccessful()
            ->assertSee('Supplier Product to be shown');
    }

    public function testNewSupplierProduct(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Test Supplier'])->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplier' => $supplier])->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/supplier-product/new')
            ->assertSuccessful()
            ->fillField('supplier_product[name]','Test Supplier Product')
            ->fillField('supplier_product[productCode]','12345')
            ->fillField('supplier_product[supplier]', $supplier->getId())
            ->click('supplier_product_auto-update')
            ->fillField('supplier_product[supplierCategory]', $supplierSubcategory->getSupplierCategory()->getId())
            ->click('supplier_product_auto-update')
            ->fillField('supplier_product[supplierSubcategory]', $supplierSubcategory->getId())
            ->fillField('supplier_product[supplierManufacturer]', $supplierManufacturer->getId())
            ->fillField('supplier_product[cost]','500')
            ->fillField('supplier_product[stock]','1')
            ->fillField('supplier_product[leadTimeDays]','7')
            ->fillField('supplier_product[weight]','100')
            ->fillField('supplier_product[mfrPartNumber]','12345')
            ->click('Create Supplier Product')
            ->assertOn('/supplier-product/')
            ->assertSee('Test Supplier Product');
    }

    public function testNewSupplierProductValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/supplier-product/new')
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('supplier_product[stock]','-1')
            ->fillField('supplier_product[leadTimeDays]','-1')
            ->fillField('supplier_product[weight]','-1')
            ->click('Create Supplier Product')
            ->assertOn('/supplier-product/new')
            ->assertSee('Please enter a supplier product name')
            ->assertSee('Please enter a product code')
            ->assertSee('Please enter a supplier')
            ->assertSee('Please enter a manufacturer part number')
            ->assertSee('Please enter a cost')
            ->assertSee('Please enter a stock level')
            ->assertSee('Please enter a lead time(days)')
            ->assertSee('Please enter a product weight(grams)')
            ->assertSee('Please enter a manufacturer part number');
    }

    public function testEditSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('supplier_product[name]','Edited Supplier Product')
            ->click('Update Supplier Product')
            ->assertOn('/supplier-product/')
            ->assertSee('Edited Supplier Product');
    }

    public function testEditSupplierProductValidation(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('supplier_product[name]','')
            ->fillField('supplier_product[productCode]','')
            ->fillField('supplier_product[supplier]', '')
            ->fillField('supplier_product[cost]','')
            ->fillField('supplier_product[stock]','-1')
            ->fillField('supplier_product[leadTimeDays]','-1')
            ->fillField('supplier_product[weight]','-1')
            ->fillField('supplier_product[mfrPartNumber]','')
            ->click('Update Supplier Product')
            ->assertOn("/supplier-product/" . $supplierProduct->getId() . "/edit")
            ->assertSee('Please enter a supplier product name')
            ->assertSee('Please enter a product code')
            ->assertSee('Please enter a supplier')
            ->assertSee('Please enter a cost')
            ->assertSee('Please enter a stock level')
            ->assertSee('Please enter a lead time(days)')
            ->assertSee('Please enter a product weight(grams)')
            ->assertSee('Please enter a manufacturer part number');
    }

    public function testDeleteSupplierProductConfirmation(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Supplier Product');
    }

    public function testDeleteSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
            'name' => 'Supplier Product to be deleted'
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/supplier-product/')
            ->assertNotSee('Supplier Product to be deleted');
    }

    public function testRemoveSupplierProductConfirmation(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be removed']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/remove/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to remove this Supplier Product');
    }

    public function testRemoveSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be removed']);
        $productId = $supplierProduct->getProduct()->getId();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $productId . "/stock")
            ->assertSuccessful()
            ->assertSee('Supplier Product to be removed')
            ->get("/supplier-product/" . $supplierProduct->getId() . "/remove/confirm")
            ->assertSuccessful()
            ->click('Remove')
            ->assertOn("/product/" . $productId . "/stock")
            ->assertSee('Supplier product removed')
            ->assertNotSee('Supplier Product to be removed');
    }

    public function testRemoveSupplierProductNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/999/remove/confirm")
            ->assertStatus(404);
    }

    public function testToggleSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be toggled']);
        $productId = $supplierProduct->getProduct()->getId();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $productId . "/stock")
            ->assertSuccessful()
            ->assertSee('Supplier Product to be toggled')
            ->assertSee('Active')
            ->get("/supplier-product/" . $supplierProduct->getId() . "/status/toggle")
            ->assertOn("/product/" . $productId . "/stock")
            ->assertSee('Supplier product status updated')
            ->assertSee('InActive')
            ->assertSee('Incomplete Product');
    }

    public function testToggleSupplierProductNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/999/status/toggle")
            ->assertStatus(404);
    }

    public function testMapSupplierProduct(): void
    {
        VatRateFactory::new()->standard()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Supplier Product to be mapped',
            'product' => null
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId())
            ->assertSuccessful()
            ->assertSee('Supplier Product to be mapped')
            ->assertSee('Map Product')
            ->click('Map Product')
            ->assertSee('Supplier product mapped')
            ->get("/supplier-product/" . $supplierProduct->getId())
            ->assertOn("/supplier-product/" . $supplierProduct->getId())
            ->assertNotSee('Map Product');
    }

    public function testSupplierProductNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/999")
            ->assertSee("Supplier product not found!");
    }
}