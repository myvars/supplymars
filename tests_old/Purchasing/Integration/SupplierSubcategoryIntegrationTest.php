<?php

namespace App\Tests\Purchasing\Integration;

use App\Shared\Application\FlusherInterface;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\SupplierCategoryFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\SupplierSubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierSubcategoryIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        $this->flusher = static::getContainer()->get(FlusherInterface::class);
    }

    public function testValidSupplierSubcategory(): void
    {
        $supplier = SupplierFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier]);

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'name' => 'Test Supplier Subcategory',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
        ]);

        $errors = $this->validator->validate($supplierSubcategory);
        $this->assertCount(0, $errors);
    }

    public function testInvalidSupplierSubcategoryWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subcategory name cannot be empty');

        SupplierSubcategoryFactory::createOne(['name' => '']);
    }

    public function testSupplierSubcategoryAssignMappedSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory']);
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $supplierSubcategory->assignMappedSubcategory($subcategory);

        $errors = $this->validator->validate($supplierSubcategory);
        $this->assertCount(0, $errors);
    }

    public function testSupplierSubcategoryPersistence(): void
    {
        $supplier = SupplierFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $mappedSubcategory = SubcategoryFactory::createOne();

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'name' => 'Office Furniture',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
        ]);
        $supplierSubcategory->assignMappedSubcategory($mappedSubcategory);
        $this->flusher->flush();

        $persisted = SupplierSubcategoryFactory::repository()->find($supplierSubcategory->getId());
        $this->assertEquals('Office Furniture', $persisted->getName());
        $this->assertSame($supplier, $persisted->getSupplier());
        $this->assertSame($supplierCategory, $persisted->getSupplierCategory());
        $this->assertSame($mappedSubcategory, $persisted->getMappedSubcategory());
    }

    public function testAddSupplierProductToSupplierSubcategory(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['supplierSubcategory' => $supplierSubcategory]);

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testRemoveSupplierProductFromSupplierSubcategory(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['supplierSubcategory' => $supplierSubcategory]);

        $supplierSubcategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierSubcategory());
    }

    public function testReAddSupplierProductToSupplierSubcategory(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['supplierSubcategory' => $supplierSubcategory]);

        $supplierSubcategory->removeSupplierProduct($supplierProduct);
        $supplierSubcategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testAddSupplierSubcategoryToSupplierCategory(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierCategory' => $supplierCategory]);

        $this->assertTrue($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($supplierCategory, $supplierSubcategory->getSupplierCategory());
    }

    public function testRemoveSupplierSubcategoryFromSupplierCategory(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierCategory' => $supplierCategory]);

        $supplierCategory->removeSupplierSubcategory($supplierSubcategory);

        $this->assertFalse($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertNull($supplierSubcategory->getSupplierCategory());
    }

    public function testReAddSupplierSubcategoryToSupplierCategory(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierCategory' => $supplierCategory]);

        $supplierCategory->removeSupplierSubcategory($supplierSubcategory);
        $supplierCategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertTrue($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($supplierCategory, $supplierSubcategory->getSupplierCategory());
    }
}
