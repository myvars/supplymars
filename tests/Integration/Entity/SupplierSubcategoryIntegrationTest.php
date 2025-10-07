<?php

namespace App\Tests\Integration\Entity;

use App\Factory\SubcategoryFactory;
use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
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

    public function testNameIsRequired(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($supplierSubcategory);
        $this->assertSame('Please enter a Subcategory name', $violations[0]->getMessage());
    }

    public function testSupplierIsRequired(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::new()->withoutPersisting()->create(['supplier' => null]);

        $violations = $this->validator->validate($supplierSubcategory);
        $this->assertSame('Please enter a supplier', $violations[0]->getMessage());
    }

    public function testSupplierSubcategoryPersistence(): void
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier])->_real();
        $mappedSubcategory = SubcategoryFactory::createOne()->_real();

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'name' => 'Office Furniture',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'mappedSubcategory' => $mappedSubcategory,
        ]);

        $persistedSupplierSubcategory = SupplierSubcategoryFactory::repository()->find($supplierSubcategory->getId())->_real();
        $this->assertEquals('Office Furniture', $persistedSupplierSubcategory->getName());
        $this->assertSame($supplier, $persistedSupplierSubcategory->getSupplier());
        $this->assertSame($supplierCategory, $persistedSupplierSubcategory->getSupplierCategory());
        $this->assertSame($mappedSubcategory, $persistedSupplierSubcategory->getMappedSubcategory());
    }

    public function testAddSupplierProductToSupplierSubcategory()
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierSubcategory' => $supplierSubcategory])->_real();

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testRemoveSupplierProductFromSupplierSubcategory()
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierSubcategory' => $supplierSubcategory])->_real();

        $supplierSubcategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierSubcategory());
    }

    public function testReAddSupplierProductToSupplierSubcategory()
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierSubcategory' => $supplierSubcategory])->_real();

        $supplierSubcategory->removeSupplierProduct($supplierProduct);
        $supplierSubcategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
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
}
