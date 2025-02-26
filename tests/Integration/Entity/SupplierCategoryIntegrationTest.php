<?php

namespace App\Tests\Integration\Entity;

use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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

    public function testNameIsRequired(): void
    {
        $supplierCategory = SupplierCategoryFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($supplierCategory);
        $this->assertSame('Please enter a category name', $violations[0]->getMessage());
    }

    public function testSupplierIsRequired(): void
    {
        $supplierCategory = SupplierCategoryFactory::new()->withoutPersisting()->create(['supplier' => null]);

        $violations = $this->validator->validate($supplierCategory);
        $this->assertSame('Please enter a supplier', $violations[0]->getMessage());
    }

    public function testSupplierCategoryPersistence(): void
    {
        $supplier = SupplierFactory::createOne()->_real();

        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Office Supplies',
            'supplier' => $supplier,
        ]);

        $persistedSupplierCategory = SupplierCategoryFactory::repository()->find($supplierCategory->getId());
        $this->assertEquals('Office Supplies', $persistedSupplierCategory->getName());
        $this->assertSame($supplier, $persistedSupplierCategory->getSupplier());
    }

    public function testAddSupplierSubcategoryToSupplierCategory()
    {
        $supplierCategory = SupplierCategoryFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierCategory' => $supplierCategory])->_real();

        $this->assertTrue($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($supplierCategory, $supplierSubcategory->getSupplierCategory());
    }

    public function testRemoveSupplierSubcategoryFromSupplierCategory()
    {
        $supplierCategory = SupplierCategoryFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierCategory' => $supplierCategory])->_real();

        $supplierCategory->removeSupplierSubcategory($supplierSubcategory);

        $this->assertFalse($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertNull($supplierSubcategory->getSupplierCategory());
    }

    public function testReAddSupplierSubcategoryToSupplierCategory()
    {
        $supplierCategory = SupplierCategoryFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierCategory' => $supplierCategory])->_real();

        $supplierCategory->removeSupplierSubcategory($supplierSubcategory);
        $supplierCategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertTrue($supplierCategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($supplierCategory, $supplierSubcategory->getSupplierCategory());
    }

    public function testAddSupplierProductToSupplierCategory()
    {
        $supplierCategory = SupplierCategoryFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierCategory' => $supplierCategory])->_real();

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }

    public function testRemoveSupplierProductFromSupplierCategory()
    {
        $supplierCategory = SupplierCategoryFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierCategory' => $supplierCategory])->_real();

        $supplierCategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierCategory());
    }

    public function testReAddSupplierProductToSupplierCategory()
    {
        $supplierCategory = SupplierCategoryFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierCategory' => $supplierCategory])->_real();

        $supplierCategory->removeSupplierProduct($supplierProduct);
        $supplierCategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }
}