<?php

namespace App\Tests\Catalog\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\ManufacturerFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SupplierManufacturerFactory;
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

    public function testInvalidManufacturerWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manufacturer name cannot be empty');

        ManufacturerFactory::createOne(['name' => '']);
    }

    public function testManufacturerPersistence(): void
    {
        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Test Manufacturer',
            'isActive' => true,
        ]);

        $persistedManufacturer = ManufacturerFactory::repository()->find($manufacturer->getId());
        $this->assertEquals('Test Manufacturer', $persistedManufacturer->getName());
        $this->assertTrue($persistedManufacturer->isActive());
    }

    public function testAddProductToManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $product = ProductFactory::createOne(['manufacturer' => $manufacturer]);

        $this->assertTrue($manufacturer->getProducts()->contains($product));
        $this->assertSame($manufacturer, $product->getManufacturer());
    }

    public function testRemoveProductFromManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $product = ProductFactory::createOne(['manufacturer' => $manufacturer]);

        $manufacturer->removeProduct($product);

        $this->assertFalse($manufacturer->getProducts()->contains($product));
        $this->assertNull($product->getManufacturer());
    }

    public function testReAddProductToManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $product = ProductFactory::createOne(['manufacturer' => $manufacturer]);

        $manufacturer->removeProduct($product);
        $manufacturer->addProduct($product);

        $this->assertTrue($manufacturer->getProducts()->contains($product));
        $this->assertSame($manufacturer, $product->getManufacturer());
    }

    public function testAddSupplierManufacturerToManufacturer(): void
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne();
        $manufacturer = ManufacturerFactory::createOne();
        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        $this->assertTrue($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertSame($manufacturer, $supplierManufacturer->getMappedManufacturer());
    }

    public function testRemoveSupplierManufacturerFromManufacturer(): void
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne();
        $manufacturer = ManufacturerFactory::createOne();
        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        $manufacturer->removeSupplierManufacturer($supplierManufacturer);

        $this->assertFalse($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertNull($supplierManufacturer->getMappedManufacturer());
    }

    public function testReAddSupplierManufacturerToManufacturer(): void
    {
        $supplierManufacturer = SupplierManufacturerFactory::createOne();
        $manufacturer = ManufacturerFactory::createOne();
        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        $manufacturer->removeSupplierManufacturer($supplierManufacturer);
        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        $this->assertTrue($manufacturer->getSupplierManufacturers()->contains($supplierManufacturer));
        $this->assertSame($manufacturer, $supplierManufacturer->getMappedManufacturer());
    }
}
