<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

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
            ->click('Update Supplier Product')
            ->assertOn('/supplier-product/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testRemappingTheActiveSourceToALowerIdProductMovesIt(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        // Doctrine orders UPDATEs by id compared as strings: give the new product the id that sorts
        // first, so its UPDATE would run before the previous product's
        $newProduct = ProductFactory::createOne();
        $previousProduct = ProductFactory::createOne();
        if (strcmp((string) $newProduct->getId(), (string) $previousProduct->getId()) > 0) {
            [$newProduct, $previousProduct] = [$previousProduct, $newProduct];
        }

        $supplierProduct = SupplierProductFactory::createOne(['cost' => '10.00', 'product' => $previousProduct]);
        $nextBest = SupplierProductFactory::createOne(['cost' => '20.00', 'product' => $previousProduct]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/' . $publicId . '/edit')
            ->fillField('supplier_product[product]', (string) $newProduct->getId())
            ->click('Update Supplier Product')
            ->assertOn('/supplier-product/' . $publicId);

        $newProduct = ProductFactory::repository()->find($newProduct->getId());
        $previousProduct = ProductFactory::repository()->find($previousProduct->getId());

        self::assertSame($supplierProduct->getId(), $newProduct?->getActiveProductSource()?->getId());
        self::assertSame($nextBest->getId(), $previousProduct?->getActiveProductSource()?->getId());
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
            ->click('Update Supplier Product')
            ->assertOn('/supplier-product/' . $publicId . '/edit')
            ->assertSee('Please enter a supplier product name');
    }

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesASupplierProductIdPostedInTheForm(string $postedId): void
    {
        $other = SupplierProductFactory::createOne(['name' => 'Other']);
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/supplier-product/' . $supplierProduct->getPublicId()->value() . '/edit',
            'Update Supplier Product',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['name' => 'Renamed'],
        );

        SupplierProductFactory::assert()->exists(['id' => $other->getId(), 'name' => 'Other']);
        SupplierProductFactory::assert()->exists(['id' => $supplierProduct->getId(), 'name' => 'Before Edit']);
    }
}
