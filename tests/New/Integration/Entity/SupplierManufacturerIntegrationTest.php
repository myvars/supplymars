<?php

namespace App\Tests\New\Integration\Entity;

use App\Factory\SupplierFactory;
use App\Factory\SupplierManufacturerFactory;
use App\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierManufacturerIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSupplierManufacturer(): void
    {
        $supplier = SupplierFactory::createOne();

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'name' => 'Test Supplier Manufacturer',
            'supplier' => $supplier,
        ]);

        $errors = $this->validator->validate($supplierManufacturer);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($supplierManufacturer);
        $this->assertSame('Please enter a manufacturer name', $violations[0]->getMessage());
    }

    public function testSupplierIsRequired(): void
    {
        $supplierManufacturer = SupplierManufacturerFactory::new()->withoutPersisting()->create(['supplier' => null]);

        $violations = $this->validator->validate($supplierManufacturer);
        $this->assertSame('Please enter a supplier', $violations[0]->getMessage());
    }

    public function testSupplierManufacturerPersistence(): void
    {
        $supplier = SupplierFactory::createOne()->_real();

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'name' => 'Tech Supplies',
            'supplier' => $supplier,
        ]);

        $persistedSupplierManufacturer = SupplierManufacturerFactory::repository()->find($supplierManufacturer->getId());
        $this->assertEquals('Tech Supplies', $persistedSupplierManufacturer->getName());
        $this->assertSame($supplier, $persistedSupplierManufacturer->getSupplier());
    }

    public function testAddSupplierProductToSupplierManufacturer()
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierManufacturer' => $supplierManufacturer])->_real();

        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
    }

    public function testRemoveSupplierProductFromSupplierManufacturer()
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierManufacturer' => $supplierManufacturer])->_real();

        $supplierManufacturer->removeSupplierProduct($supplierProduct);

        $this->assertFalse($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertNull($supplierProduct->getSupplierManufacturer());
    }

    public function testReAddSupplierProductToSupplierManufacturer()
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['supplierManufacturer' => $supplierManufacturer])->_real();

        $supplierManufacturer->removeSupplierProduct($supplierProduct);
        $supplierManufacturer->addSupplierProduct($supplierProduct);

        $this->assertTrue($supplierManufacturer->getSupplierProducts()->contains($supplierProduct));
        $this->assertSame($supplierManufacturer, $supplierProduct->getSupplierManufacturer());
    }
}