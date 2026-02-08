<?php

namespace App\Tests\Purchasing\Application\Service;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Purchasing\Application\Service\SupplierProductMappingService;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class SupplierProductMappingServiceTest extends KernelTestCase
{
    use Factories;

    private SupplierProductMappingService $service;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SupplierProductMappingService::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testMapsSupplierProductToNewCatalogEntities(): void
    {
        // Ensure default VAT rate exists
        VatRateFactory::new()->withStandardRate()->create();

        $supplier = SupplierFactory::createOne();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Test Category',
        ]);

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'name' => 'Test Subcategory',
        ]);

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Test Manufacturer',
        ]);

        // Create unmapped SupplierProduct using factory with no product mapping
        $supplierProduct = SupplierProduct::create(
            name: 'Test Mapped Product',
            productCode: 'TMP-001',
            supplierCategory: $supplierCategory,
            supplierSubcategory: $supplierSubcategory,
            supplierManufacturer: $supplierManufacturer,
            mfrPartNumber: 'MFR-12345',
            weight: 500,
            supplier: $supplier,
            stock: 100,
            leadTimeDays: 7,
            cost: '25.99',
            product: null,
            isActive: true,
        );

        $this->em->persist($supplierProduct);
        $this->em->flush();

        // Map the supplier product
        $product = $this->service->map($supplierProduct);

        $this->em->flush();

        // Verify product was created
        self::assertInstanceOf(Product::class, $product);
        self::assertSame('Test Mapped Product', $product->getName());
        self::assertSame('MFR-12345', $product->getMfrPartNumber());

        // Verify manufacturer was created
        $manufacturer = $product->getManufacturer();
        self::assertInstanceOf(Manufacturer::class, $manufacturer);
        self::assertSame('Test Manufacturer', $manufacturer->getName());

        // Verify category was created
        $category = $product->getCategory();
        self::assertInstanceOf(Category::class, $category);
        self::assertSame('Test Category', $category->getName());

        // Verify subcategory was created
        $subcategory = $product->getSubcategory();
        self::assertInstanceOf(Subcategory::class, $subcategory);
        self::assertSame('Test Subcategory', $subcategory->getName());
        self::assertSame($category, $subcategory->getCategory());

        // Verify supplier product is now mapped
        self::assertSame($product, $supplierProduct->getProduct());
    }

    #[WithStory(StaffUserStory::class)]
    public function testReusesExistingManufacturerByName(): void
    {
        VatRateFactory::new()->withStandardRate()->create();

        // Create existing manufacturer
        $existingManufacturer = ManufacturerFactory::createOne(['name' => 'Existing Manufacturer']);

        $supplier = SupplierFactory::createOne();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Cat A',
        ]);

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'name' => 'Sub A',
        ]);

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Existing Manufacturer', // Same name as existing
        ]);

        $supplierProduct = SupplierProduct::create(
            name: 'Product With Existing Mfr',
            productCode: 'PWE-001',
            supplierCategory: $supplierCategory,
            supplierSubcategory: $supplierSubcategory,
            supplierManufacturer: $supplierManufacturer,
            mfrPartNumber: 'MFR-999',
            weight: 200,
            supplier: $supplier,
            stock: 50,
            leadTimeDays: 3,
            cost: '15.00',
            product: null,
            isActive: true,
        );

        $this->em->persist($supplierProduct);
        $this->em->flush();

        $product = $this->service->map($supplierProduct);

        $this->em->flush();

        // Should reuse existing manufacturer, not create new one
        self::assertSame($existingManufacturer->getId(), $product->getManufacturer()->getId());
    }

    #[WithStory(StaffUserStory::class)]
    public function testReusesExistingCategoryByName(): void
    {
        VatRateFactory::new()->withStandardRate()->create();

        // Create existing category
        $existingCategory = CategoryFactory::createOne(['name' => 'Existing Category']);

        $supplier = SupplierFactory::createOne();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Existing Category', // Same name
        ]);

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'name' => 'New Sub',
        ]);

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Some Mfr',
        ]);

        $supplierProduct = SupplierProduct::create(
            name: 'Product With Existing Cat',
            productCode: 'PWC-001',
            supplierCategory: $supplierCategory,
            supplierSubcategory: $supplierSubcategory,
            supplierManufacturer: $supplierManufacturer,
            mfrPartNumber: 'MFR-888',
            weight: 300,
            supplier: $supplier,
            stock: 25,
            leadTimeDays: 5,
            cost: '50.00',
            product: null,
            isActive: true,
        );

        $this->em->persist($supplierProduct);
        $this->em->flush();

        $product = $this->service->map($supplierProduct);

        $this->em->flush();

        // Should reuse existing category
        self::assertSame($existingCategory->getId(), $product->getCategory()->getId());
    }

    #[WithStory(StaffUserStory::class)]
    public function testReusesExistingProductByName(): void
    {
        VatRateFactory::new()->withStandardRate()->create();

        $existingCategory = CategoryFactory::createOne(['name' => 'Prod Cat']);
        $existingSubcategory = SubcategoryFactory::createOne([
            'name' => 'Prod Sub',
            'category' => $existingCategory,
        ]);
        $existingManufacturer = ManufacturerFactory::createOne(['name' => 'Prod Mfr']);

        // Create existing product
        $existingProduct = Product::create(
            name: 'Existing Product',
            description: 'Already exists',
            category: $existingCategory,
            subcategory: $existingSubcategory,
            manufacturer: $existingManufacturer,
            mfrPartNumber: 'ORIG-001',
            owner: null,
            isActive: true,
        );
        $this->em->persist($existingProduct);
        $this->em->flush();

        $supplier = SupplierFactory::createOne();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Prod Cat',
        ]);

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'name' => 'Prod Sub',
        ]);

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'supplier' => $supplier,
            'name' => 'Prod Mfr',
        ]);

        $supplierProduct = SupplierProduct::create(
            name: 'Existing Product', // Same name as existing
            productCode: 'SP-001',
            supplierCategory: $supplierCategory,
            supplierSubcategory: $supplierSubcategory,
            supplierManufacturer: $supplierManufacturer,
            mfrPartNumber: 'MFR-NEW',
            weight: 100,
            supplier: $supplier,
            stock: 75,
            leadTimeDays: 2,
            cost: '10.00',
            product: null,
            isActive: true,
        );

        $this->em->persist($supplierProduct);
        $this->em->flush();

        $product = $this->service->map($supplierProduct);

        $this->em->flush();

        // Should reuse existing product
        self::assertSame($existingProduct->getId(), $product->getId());
        // Supplier product should now be linked
        self::assertSame($product, $supplierProduct->getProduct());
    }
}
