<?php

namespace App\Tests\Purchasing\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\SupplierCategoryFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\SupplierSubcategoryFactory;
use Zenstruck\Foundry\Test\Factories;

class SupplierCategoryIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSupplierCategory(): void
    {
        $supplier = SupplierFactory::createOne();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Test Supplier Category',
            'supplier' => $supplier,
        ]);

        $errors = $this->validator->validate($supplierCategory);
        $this->assertCount(0, $errors);
    }

    public function testInvalidSupplierCategoryWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name cannot be empty');

        $supplierCategory = SupplierCategoryFactory::createOne(['name' => '']);
    }

    public function testSupplierCategoryPersistence(): void
    {
        $supplier = SupplierFactory::createOne();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Office Supplies',
            'supplier' => $supplier,
        ]);

        $persistedSupplierCategory = SupplierCategoryFactory::repository()->find($supplierCategory->getId());
        $this->assertEquals('Office Supplies', $persistedSupplierCategory->getName());
        $this->assertSame($supplier, $persistedSupplierCategory->getSupplier());
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

    public function testAddSupplierProductToSupplierCategory(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['supplierCategory' => $supplierCategory]);

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }

    public function testRemoveSupplierProductFromSupplierCategory(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['supplierCategory' => $supplierCategory]);

        $supplierCategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierCategory());
    }

    public function testReAddSupplierProductToSupplierCategory(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['supplierCategory' => $supplierCategory]);

        $supplierCategory->removeSupplierProduct($supplierProduct);
        $supplierCategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }
}
