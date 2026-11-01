<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\Application\Service;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Infrastructure\Security\DefaultUserAuthenticator;
use App\Purchasing\Application\Service\SupplierProductMappingService;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class SupplierProductMappingServiceTest extends KernelTestCase
{
    private SupplierProductMappingService $service;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SupplierProductMappingService::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        VatRateFactory::new()->withStandardRate()->create();
    }

    #[WithStory(StaffUserStory::class)]
    public function testMapsSupplierProductToNewCatalogEntities(): void
    {
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
        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Test Mapped Product',
            'productCode' => 'TMP-001',
            'supplierCategory' => $supplierCategory,
            'supplierSubcategory' => $supplierSubcategory,
            'supplierManufacturer' => $supplierManufacturer,
            'mfrPartNumber' => 'MFR-12345',
            'weight' => 500,
            'supplier' => $supplier,
            'stock' => 100,
            'leadTimeDays' => 7,
            'cost' => '25.99',
            'product' => null,
            'isActive' => true,
        ]);

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

        // And that the mappings were saved, not just set in memory
        $this->em->clear();
        $saved = $this->em->find(SupplierProduct::class, $supplierProduct->getId());
        self::assertSame($product->getId(), $saved?->getProduct()?->getId());
        self::assertSame($manufacturer->getId(), $saved->getSupplierManufacturer()?->getMappedManufacturer()?->getId());
        self::assertSame($subcategory->getId(), $saved->getSupplierSubcategory()?->getMappedSubcategory()?->getId());
    }

    public function testRefusesADefaultOwnerWhoIsNotStaff(): void
    {
        // No one is signed in, so a new category falls back to the default staff account
        UserFactory::createOne(['email' => self::getContainer()->get(DefaultUserAuthenticator::class)->getDefaultEmail()]);

        $supplierProduct = SupplierProductFactory::createOne(['product' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Default owner not found');

        $this->service->map($supplierProduct);
    }

    public function testMapReusesExistingEntitiesAndDoesNotDuplicate(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sm = $sp->getSupplierManufacturer();
        $sc = $sp->getSupplierCategory();
        $ss = $sp->getSupplierSubcategory();

        $existingManufacturer = ManufacturerFactory::createOne(['name' => $sm->getName()]);
        $existingCategory = CategoryFactory::createOne(['name' => $sc->getName()]);
        $existingSubcategory = SubcategoryFactory::createOne([
            'name' => $ss->getName(),
            'category' => $existingCategory,
        ]);
        $existingProduct = ProductFactory::createOne([
            'name' => $sp->getName(),
            'category' => $existingCategory,
            'subcategory' => $existingSubcategory,
            'manufacturer' => $existingManufacturer,
        ]);

        // map and ensure reuse
        $product = $this->service->map($sp);
        $this->em->flush();

        self::assertSame($existingProduct->getId(), $product->getId());
        self::assertSame($existingProduct, $sp->getProduct());

        ProductFactory::assert()->count(1, ['name' => $sp->getName()]);
        ManufacturerFactory::assert()->count(1, ['name' => $sm->getName()]);
        CategoryFactory::assert()->count(1, ['name' => $sc->getName()]);
        SubcategoryFactory::assert()->count(1, ['name' => $ss->getName()]);
    }

    public function testMapThrowsWhenSupplierManufacturerMissing(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sp->assignSupplierManufacturer(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Supplier manufacturer is missing');
        $this->service->map($sp);
    }

    public function testMapThrowsWhenSupplierCategoryMissing(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sp->assignSupplierCategory(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Supplier category is missing');
        $this->service->map($sp);
    }

    public function testMapThrowsWhenSupplierSubcategoryMissing(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sp->assignSupplierSubcategory(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Supplier subcategory is missing');
        $this->service->map($sp);
    }

    public function testMapUsesDefaultOwnerWhenNoAuthenticatedUser(): void
    {
        // Create the default owner that the service looks for when no authenticated user
        $defaultEmail = self::getContainer()->get(DefaultUserAuthenticator::class)->getDefaultEmail();
        UserFactory::new(['email' => $defaultEmail])->asStaff()->create();

        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sc = $sp->getSupplierCategory();

        $product = $this->service->map($sp);
        $this->em->flush();

        $category = $product->getCategory();
        self::assertSame($sc->getName(), $category->getName());
        self::assertSame($defaultEmail, $category->getOwner()->getEmail());
    }

    #[WithStory(StaffUserStory::class)]
    public function testMapEstablishesBidirectionalRelationships(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sm = $sp->getSupplierManufacturer();
        $ss = $sp->getSupplierSubcategory();

        $product = $this->service->map($sp);
        $this->em->flush();

        $manufacturer = $product->getManufacturer();
        $subcategory = $product->getSubcategory();

        // Verify bidirectional relationship: manufacturer -> supplierManufacturers
        self::assertTrue(
            $manufacturer->getSupplierManufacturers()->contains($sm),
            'Manufacturer should contain the mapped SupplierManufacturer'
        );

        // Verify bidirectional relationship: subcategory -> supplierSubcategories
        self::assertTrue(
            $subcategory->getSupplierSubcategories()->contains($ss),
            'Subcategory should contain the mapped SupplierSubcategory'
        );

        // Verify bidirectional relationship: product -> supplierProducts
        self::assertTrue(
            $product->getSupplierProducts()->contains($sp),
            'Product should contain the mapped SupplierProduct'
        );
    }
}
