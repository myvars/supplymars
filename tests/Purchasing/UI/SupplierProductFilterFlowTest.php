<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class SupplierProductFilterFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testDependentFieldsOfferOnlyTheChosenSuppliersRecords(): void
    {
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier, 'name' => 'Our Category']);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
            'name' => 'Our Subcategory',
        ]);
        // Manufacturers are offered through the supplier products in the chosen category
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
            'supplierSubcategory' => $subcategory,
            'supplierManufacturer' => SupplierManufacturerFactory::createOne(['supplier' => $supplier, 'name' => 'Our Manufacturer']),
        ]);

        $other = SupplierFactory::createOne();
        $otherCategory = SupplierCategoryFactory::createOne(['supplier' => $other, 'name' => 'Other Category']);
        SupplierSubcategoryFactory::createOne([
            'supplier' => $other,
            'supplierCategory' => $otherCategory,
            'name' => 'Other Subcategory',
        ]);
        SupplierManufacturerFactory::createOne(['supplier' => $other, 'name' => 'Other Manufacturer']);

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get(sprintf(
                '/supplier-product/search/filter?supplierId=%d&supplierCategoryId=%d&supplierSubcategoryId=%d',
                $supplier->getId(),
                $category->getId(),
                $subcategory->getId(),
            ))
            ->assertSuccessful();

        self::assertSame(['Any Category', 'Our Category'], $this->options($browser, 'supplierCategory'));
        self::assertSame(['Any Subcategory', 'Our Subcategory'], $this->options($browser, 'supplierSubcategory'));
        self::assertSame(['Any Manufacturer', 'Our Manufacturer'], $this->options($browser, 'supplierManufacturer'));
    }

    /**
     * @return list<string>
     */
    private function options(KernelBrowser $browser, string $field): array
    {
        return $browser->crawler()
            ->filter(sprintf('select[name="supplier_product_filter[%s]"] option', $field))
            ->each(static fn ($option): string => trim($option->text()));
    }
}
