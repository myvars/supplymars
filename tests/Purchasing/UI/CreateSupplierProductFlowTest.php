<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CreateSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);
        $product = ProductFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/new')
            ->fillField('supplier_product[name]', 'Flow Supplier Product')
            ->fillField('supplier_product[productCode]', 'FLOW123')
            ->fillField('supplier_product[supplier]', (string) $supplier->getId())
            ->click('supplier_product_auto-update')
            ->fillField('supplier_product[category]', (string) $category->getId())
            ->click('supplier_product_auto-update')
            ->fillField('supplier_product[subcategory]', (string) $subcategory->getId())
            ->fillField('supplier_product[manufacturer]', (string) $manufacturer->getId())
            ->fillField('supplier_product[mfrPartNumber]', 'MFR-FLOW')
            ->fillField('supplier_product[weight]', '250')
            ->fillField('supplier_product[stock]', '40')
            ->fillField('supplier_product[leadTimeDays]', '5')
            ->fillField('supplier_product[cost]', '19.99')
            ->fillField('supplier_product[product]', (string) $product->getId())
            ->fillField('supplier_product[isActive]', '1')
            ->click('Create Supplier product')
            ->assertOn('/supplier-product/')
            ->assertSee('Flow Supplier Product');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier-product/new')
            ->click('Create Supplier product')
            ->assertOn('/supplier-product/new')
            ->assertSee('Please enter a supplier product name')
            ->assertSee('Please enter a product code')
            ->assertSee('Please choose a Supplier')
            ->assertSee('Please choose a Category')
            ->assertSee('Please choose a Subcategory')
            ->assertSee('Please choose a Manufacturer')
            ->assertSee('Please enter a manufacturer part number')
            ->assertSee('Please enter a lead time');
    }
}
