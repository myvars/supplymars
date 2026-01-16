<?php

namespace App\Tests\Purchasing\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\SupplierCategoryFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierManufacturerFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\SupplierSubcategoryFactory;
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
            'mfrPartNumber' => '123456',
            'weight' => 1,
            'supplier' => $supplier,
            'stock' => 1,
            'leadTimeDays' => 1,
            'cost' => '1.00',
        ]);

        $errors = $this->validator->validate($supplierProduct);
        $this->assertCount(0, $errors);
    }

    public function testInvalidSupplierProductWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name cannot be empty');

        $supplierProduct = SupplierProductFactory::createOne(['name' => '']);
    }

    public function testProductCodeIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['productCode' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a product code', $violations[0]->getMessage());
    }

    public function testMfrPartNumberIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['mfrPartNumber' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a manufacturer part number', $violations[0]->getMessage());
    }

    public function testInvalidSupplierProductWithNegativeWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight cannot be negative');

        SupplierProductFactory::new()->withoutPersisting()->create(['weight' => -1]);
    }

    public function testInvalidWeightGreaterThanMax(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['weight' => 100001]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a product weight(grams)', $violations[0]->getMessage());
    }

    public function testInvalidStockLessThanZero(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['stock' => -1]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a stock level', $violations[0]->getMessage());
    }

    public function testInvalidStockGreaterThanMax(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['stock' => 10001]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a stock level', $violations[0]->getMessage());
    }

    public function testInvalidLeadTimeLessThanZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lead time days cannot be negative');

        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['leadTimeDays' => -1]);
    }

    public function testInvalidLeadTimeGreaterThanMax(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['leadTimeDays' => 1001]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a lead time(days)', $violations[0]->getMessage());
    }

    public function testCostIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['cost' => '']);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a cost', $violations[0]->getMessage());
    }

    public function testInvalidCostLessThanZero(): void
    {
        $supplierProduct = SupplierProductFactory::new()->withoutPersisting()->create(['cost' => -1]);

        $violations = $this->validator->validate($supplierProduct);
        $this->assertSame('Please enter a positive or zero cost', $violations[0]->getMessage());
    }

    public function testSupplierProductPersistence(): void
    {
        $supplier = SupplierFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
        ]);
        $supplerManufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Office Chair',
            'productCode' => 'OC12345',
            'supplierCategory' => $supplierCategory,
            'supplierSubcategory' => $supplierSubcategory,
            'supplierManufacturer' => $supplerManufacturer,
            'mfrPartNumber' => 'PART-1234',
            'weight' => 5000,
            'supplier' => $supplier,
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

    public function testAddSupplierCategoryToSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }

    public function testRemoveSupplierCategoryFromSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $supplierCategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierCategory());
    }

    public function testReAddSupplierCategoryToSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $supplierCategory->removeSupplierProduct($supplierProduct);
        $supplierCategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierCategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierCategory, $supplierProduct->getSupplierCategory());
    }

    public function testAddSupplierSubcategoryToSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testRemoveSupplierSubcategoryFromSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $supplierSubcategory->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierSubcategory());
    }

    public function testReAddSupplierSubcategoryToSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $supplierSubcategory->removeSupplierProduct($supplierProduct);
        $supplierSubcategory->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierSubcategory->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierSubcategory, $supplierProduct->getSupplierSubcategory());
    }

    public function testAddSupplierManufacturerToSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
    }

    public function testRemoveSupplierManufacturerFromSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $supplierManufacturer->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierManufacturer());
    }

    public function testReAddSupplierManufacturerToSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['supplierProducts' => [$supplierProduct]]);

        $supplierManufacturer->removeSupplierProduct($supplierProduct);
        $supplierManufacturer->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
    }
}
