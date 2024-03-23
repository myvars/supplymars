<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;
use App\Entity\VatRate;

class VatRateTest extends TestCase
{

    public function testGetSetName(): void
    {
        $vatRate = new VatRate();
        $vatRate->setName('Test VatRate');

        $this->assertEquals('Test VatRate', $vatRate->getName());
    }

    public function testGetSetRate(): void
    {
        $vatRate = new VatRate();
        $vatRate->setRate(0.21);

        $this->assertEquals(0.21, $vatRate->getRate());
    }

    public function testAddRemoveCategory(): void
    {
        $category = $this->createMock(Category::class);
        $vatRate = new VatRate();
        $vatRate->addCategory($category);

        $this->assertEquals($category, $vatRate->getCategories()->first());

        $vatRate->removeCategory($category);

        $this->assertEmpty($vatRate->getCategories());
    }
}