<?php

namespace App\Tests\Integration\Entity;

use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\SupplierManufacturerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ManufacturerIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test Manufacturer']);

        $errors = $this->validator->validate($manufacturer);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($manufacturer);
        $this->assertSame('Please enter a manufacturer name', $violations[0]->getMessage());
    }

    public function testManufacturerPersistence(): void
    {
        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Test Manufacturer',
            'isActive' => true,
        ])->_real();

        $persistedManufacturer = ManufacturerFactory::repository()->find($manufacturer->getId())->_real();
        $this->assertEquals('Test Manufacturer', $persistedManufacturer->getName());
        $this->assertTrue($persistedManufacturer->isActive());
    }

    public function testAddProductToManufacturer()
    {
        $manufacturer = ManufacturerFactory::createOne()->_real();
        $product = ProductFactory::createOne(['manufacturer' => $manufacturer])->_real();

        $this->assertTrue($manufacturer->getProducts()->contains($product));
        $this->assertSame($manufacturer, $product->getManufacturer());
    }

    public function testRemoveProductFromManufacturer()
    {
        $manufacturer = ManufacturerFactory::createOne()->_real();
        $product = ProductFactory::createOne(['manufacturer' => $manufacturer])->_real();

        $manufacturer->removeProduct($product);

        $this->assertFalse($manufacturer->getProducts()->contains($product));
        $this->assertNull($product->getManufacturer());
    }

    public function testReAddProductToManufacturer()
    {
        $manufacturer = ManufacturerFactory::createOne()->_real();
        $product = ProductFactory::createOne(['manufacturer' => $manufacturer])->_real();

        $manufacturer->removeProduct($product);
        $manufacturer->addProduct($product);

        $this->assertTrue($manufacturer->getProducts()->contains($product));
        $this->assertSame($manufacturer, $product->getManufacturer());
    }

    public function testAddSupplierManufacturerToManufacturer()
    {
        $manufacturer = ManufacturerFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['mappedManufacturer' => $manufacturer])->_real();

        $this->assertTrue($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertSame($manufacturer, $supplierManufacturer->getMappedManufacturer());
    }

    public function testRemoveSupplierManufacturerFromManufacturer()
    {
        $manufacturer = ManufacturerFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['mappedManufacturer' => $manufacturer])->_real();

        $manufacturer->removeSupplierManufacturer($supplierManufacturer);

        $this->assertFalse($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertNull($supplierManufacturer->getMappedManufacturer());
    }

    public function testReAddSupplierManufacturerToManufacturer()
    {
        $manufacturer = ManufacturerFactory::createOne()->_real();
        $supplierManufacturer = SupplierManufacturerFactory::createOne(['mappedManufacturer' => $manufacturer])->_real();

        $manufacturer->removeSupplierManufacturer($supplierManufacturer);
        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        $this->assertTrue($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertSame($manufacturer, $supplierManufacturer->getMappedManufacturer());
    }
}
