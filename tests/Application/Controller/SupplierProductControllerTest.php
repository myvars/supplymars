<?php

namespace App\Tests\Application\Controller;

use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
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
        $supplier = SupplierFactory::createOne(['name' => 'Test Supplier']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/supplier-product/new')
            ->assertSuccessful()
            ->fillField('supplier_product[name]','Test Supplier Product')
            ->fillField('supplier_product[productCode]','12345')
            ->fillField('supplier_product[supplier]', $supplier->getId())
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
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Supplier Product to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/supplier-product/')
            ->assertNotSee('Supplier Product to be deleted');
    }

    public function testSupplierProductNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/999")
            ->assertSee("Sorry, we can't find that Supplier Product");
    }
}