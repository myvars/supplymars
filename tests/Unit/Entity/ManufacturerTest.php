<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use App\Entity\Manufacturer;

class ManufacturerTest extends TestCase
{
    public function testGetSetName(): void
    {
        $manufacturer = new Manufacturer();
        $manufacturer->setName('Test Manufacturer');

        $this->assertEquals('Test Manufacturer', $manufacturer->getName());
    }

    public function testGetSetIsActive(): void
    {
        $manufacturer = new Manufacturer();
        $manufacturer->setIsActive(true);

        $this->assertTrue($manufacturer->isActive());
    }

    public function testAddRemoveProduct(): void
    {
        $product = $this->createMock(Product::class);
        $manufacturer = new Manufacturer();
        $manufacturer->addProduct($product);

        $this->assertEquals($product, $manufacturer->getProducts()->first());

        $manufacturer->removeProduct($product);

        $this->assertEmpty($manufacturer->getProducts());
    }
}