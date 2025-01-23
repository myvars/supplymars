<?php

namespace App\Tests\New\Integration\Entity;

use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierManufacturerFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierProductIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSupplierProduct(): void
    {
        $supplier = SupplierFactory::createOne();

        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Test Supplier Product',
            'productCode' => '123456',
            'supplier' => $supplier,
            'mfrPartNumber' => '123456',
            'weight' => 1.0,
            'stock' => 1,
            'leadTimeDays' => 1,
            'cost' => 1.0,
        ]);

        $errors = $this->validator->validate($supplierProduct);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a supplier product name', $violations[0]->getMessage());
    }

    public function testProductCodeIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['productCode' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a product code', $violations[0]->getMessage());
    }

    public function testSupplierIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['supplier' => null]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a supplier', $violations[0]->getMessage());
    }

    public function testMfrPartNumberIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['mfrPartNumber' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a manufacturer part number', $violations[0]->getMessage());
    }

    public function testWeightIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['weight' => null]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a weight', $violations[0]->getMessage());
    }

    public function testStockIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['stock' => null]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a stock level', $violations[0]->getMessage());
    }

    public function testLeadTimeDaysIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['leadTimeDays' => null]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a lead time', $violations[0]->getMessage());
    }

    public function testCostIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['cost' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a cost', $violations[0]->getMessage());
    }

    public function testSupplierProductPersistence(): void
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier])->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory
        ])->_real();
        $supplerManufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier])->_real();

        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Office Chair',
            'productCode' => 'OC12345',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'supplierSubcategory' => $supplierSubcategory,
            'supplierManufacturer' => $supplerManufacturer,
            'mfrPartNumber' => 'PART-1234',
            'weight' => 5000,
            'stock' => 100,
            'leadTimeDays' => 10,
            'cost' => '150.00',
            'isActive' => true,
        ]);

        $persistedSupplierProduct = SupplierProductFactory::repository()->find($supplierProduct->getId());
        $this->assertEquals('Office Chair', $persistedSupplierProduct->getName());
        $this->assertEquals('OC12345', $persistedSupplierProduct->getProductCode());
        $this->assertSame($supplier, $persistedSupplierProduct->getSupplier());
        $this->assertSame($supplierCategory, $persistedSupplierProduct->getSupplierCategory());
        $this->assertSame($supplierSubcategory, $persistedSupplierProduct->getSupplierSubcategory());
        $this->assertSame($supplerManufacturer, $persistedSupplierProduct->getSupplierManufacturer());
        $this->assertEquals('PART-1234', $persistedSupplierProduct->getMfrPartNumber());
        $this->assertEquals(5000, $persistedSupplierProduct->getWeight());
        $this->assertEquals(100, $persistedSupplierProduct->getStock());
        $this->assertEquals(10, $persistedSupplierProduct->getLeadTimeDays());
        $this->assertEquals('150.00', $persistedSupplierProduct->getCost());
        $this->assertTrue($persistedSupplierProduct->isActive());
    }

    public function testAddSupplierCategoryToSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }

    public function testRemoveSupplierCategoryFromSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $supplierCategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierCategory());
    }

    public function testReAddSupplierCategoryToSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $supplierCategory->removeSupplierProduct($supplierProduct);
        $supplierCategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }

    public function testAddSupplierSubcategoryToSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testRemoveSupplierSubcategoryFromSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $supplierSubcategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierSubcategory());
    }

    public function testReAddSupplierSubcategoryToSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $supplierSubcategory->removeSupplierProduct($supplierProduct);
        $supplierSubcategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testAddSupplierManufacturerToSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
    }

    public function testRemoveSupplierManufacturerFromSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $supplierManufacturer->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierManufacturer());
    }

    public function testReAddSupplierManufacturerToSupplierProduct()
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplierProducts' => [$supplierProduct]])->_real();

        $supplierManufacturer->removeSupplierProduct($supplierProduct);
        $supplierManufacturer->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
    }
}