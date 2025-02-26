<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\VatRate;
use PHPUnit\Framework\TestCase;

class VatRateTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $vatRate = (new VatRate())
            ->setName('Standard rate')
            ->setRate('20.00')
            ->setIsDefaultVatRate(true);

        $this->assertEquals('Standard rate', $vatRate->getName());
        $this->assertEquals('20.00', $vatRate->getRate());
        $this->assertTrue($vatRate->isDefaultVatRate());
    }

    public function testAddCategory(): void
    {
        $vatRate = new VatRate();
        $category = $this->createMock(Category::class);

        // Test adding a category
        $category->expects($this->once())
            ->method('setVatRate')
            ->with($vatRate);

        $vatRate->addCategory($category);
        $this->assertCount(1, $vatRate->getCategories());
        $this->assertTrue($vatRate->getCategories()->contains($category));
    }

    public function testRemoveCategory(): void
    {
        $vatRate = new VatRate();
        $category = $this->createMock(Category::class);

        // Add the category first to set up the state
        $vatRate->addCategory($category);

        // Test removing a category
        $category->expects($this->once())
            ->method('getVatRate')
            ->willReturn($vatRate);

        $category->expects($this->once())
            ->method('setVatRate')
            ->with(null);

        $vatRate->removeCategory($category);
        $this->assertCount(0, $vatRate->getCategories());
    }
}