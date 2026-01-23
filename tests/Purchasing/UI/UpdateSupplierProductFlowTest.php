<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Before Edit',
            'productCode' => 'CODE-1',
            'isActive' => true,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

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
            ->get('/supplier-product/' . $publicId . '/edit')
            ->fillField('supplier_product[name]', 'After Edit')
            ->fillField('supplier_product[productCode]', 'NEWCODE-999')
            ->fillField('supplier_product[supplier]', (string) $supplier->getId())
            ->click('supplier_product_auto-update')
            ->fillField('supplier_product[category]', (string) $category->getId())
            ->click('supplier_product_auto-update')
            ->fillField('supplier_product[subcategory]', (string) $subcategory->getId())
            ->fillField('supplier_product[manufacturer]', (string) $manufacturer->getId())
            ->fillField('supplier_product[mfrPartNumber]', 'MFR-UPDATED-001')
            ->fillField('supplier_product[cost]', '123.45')
            ->fillField('supplier_product[stock]', '123')
            ->fillField('supplier_product[leadTimeDays]', '14')
            ->fillField('supplier_product[weight]', '250')
            ->fillField('supplier_product[product]', (string) $product->getId())
            ->uncheckField('supplier_product[isActive]')
            ->click('Update Supplier product')
            ->assertOn('/supplier-product/')
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $supplierProduct = SupplierProductFactory::createOne(['name' => 'To Edit']);
        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/' . $publicId . '/edit')
            ->fillField('supplier_product[name]', '')
            ->click('Update Supplier product')
            ->assertOn('/supplier-product/' . $publicId . '/edit')
            ->assertSee('Please enter a supplier product name');
    }
}
