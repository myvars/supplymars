<?php

namespace App\Tests\Integration\Entity;

use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierManufacturerFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSSupplier(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Test Supplier']);

        $errors = $this->validator->validate($supplier);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $supplier = SupplierFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($supplier);
        $this->assertSame('Please enter a supplier name', $violations[0]->getMessage());
    }

    public function testSupplierPersistence(): void
    {
        $supplier = SupplierFactory::createOne([
            'name' => 'Global Supplies',
            'isWarehouse' => false,
            'isActive' => true,
        ]);

        $persistedSupplier = SupplierFactory::repository()->find($supplier->getId());
        $this->assertEquals('Global Supplies', $persistedSupplier->getName());
        $this->assertTrue($persistedSupplier->isActive());
        $this->assertFalse($persistedSupplier->isWarehouse());

        $colourScheme = $persistedSupplier->getId() < 5 ? 'supplier'.$persistedSupplier->getId() : 'supplier1';
        $this->assertSame($colourScheme, $persistedSupplier->getColourScheme());

    }

    public function testAddSupplierCategoryToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier])->_real();

        $this->assertTrue($supplier->getSupplierCategories()->contains($supplierCategory));
        $this->assertSame($supplier, $supplierCategory->getSupplier());
    }

    public function testRemoveSupplierCategoryFromSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierCategory($supplierCategory);

        $this->assertFalse($supplier->getSupplierCategories()->contains($supplierCategory));
        $this->assertNull($supplierCategory->getSupplier());
    }

    public function testReAddSupplierCategoryToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierCategory($supplierCategory);
        $supplier->addSupplierCategory($supplierCategory);

        $this->assertTrue($supplier->getSupplierCategories()->contains($supplierCategory));
        $this->assertSame($supplier, $supplierCategory->getSupplier());
    }

    public function testAddSupplierSubcategoryToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplier' => $supplier])->_real();

        $this->assertTrue($supplier->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($supplier, $supplierSubcategory->getSupplier());
    }

    public function testRemoveSupplierSubcategoryFromSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierSubcategory($supplierSubcategory);

        $this->assertFalse($supplier->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertNull($supplierSubcategory->getSupplier());
    }

    public function testReAddSupplierSubcategoryToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierSubcategory($supplierSubcategory);
        $supplier->addSupplierSubcategory($supplierSubcategory);

        $this->assertTrue($supplier->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($supplier, $supplierSubcategory->getSupplier());
    }

    public function testAddSupplierManufacturerToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier])->_real();

        $this->assertTrue($supplier->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertSame($supplier, $supplierManufacturer->getSupplier());
    }

    public function testRemoveSupplierManufacturerFromSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierManufacturer($supplierManufacturer);

        $this->assertFalse($supplier->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertNull($supplierManufacturer->getSupplier());
    }

    public function testReAddSupplierManufacturerToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierManufacturer($supplierManufacturer);
        $supplier->addSupplierManufacturer($supplierManufacturer);

        $this->assertTrue($supplier->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertSame($supplier, $supplierManufacturer->getSupplier());
    }

    public function testAddSupplierProductToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplier' => $supplier])->_real();

        $this->assertTrue($supplier->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplier, $supplierProduct->getSupplier());
    }

    public function testRemoveSupplierProductFromSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplier->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplier());
    }

    public function testReAddSupplierProductToSupplier()
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplier' => $supplier])->_real();

        $supplier->removeSupplierProduct($supplierProduct);
        $supplier->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplier->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplier, $supplierProduct->getSupplier());
    }
}