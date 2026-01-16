<?php

namespace App\Tests\Pricing\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\VatRateFactory;
use Zenstruck\Foundry\Test\Factories;

class VatRateIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidVatRate(): void
    {
        $vatRate = VatRateFactory::createOne([
            'name' => 'Standard rate',
            'rate' => '20.00',
        ]);

        $errors = $this->validator->validate($vatRate);
        $this->assertCount(0, $errors);
    }

    public function testInvalidVatRateWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate name cannot be empty');

        VatRateFactory::createOne(['name' => '']);
    }

    public function testRateIsRequired(): void
    {
        $vatRate = VatRateFactory::new()->withoutPersisting()->create(['rate' => '']);

        $errors = $this->validator->validate($vatRate);
        $this->assertSame('Please enter a VAT rate %', $errors[0]->getMessage());
    }

    public function testInvalidVatRateWithNegativeRate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate cannot be negative');

        VatRateFactory::createOne(['rate' => '-5.00']);
    }

    public function testVatRatePersistence(): void
    {
        $vatRate = VatRateFactory::createOne([
            'name' => 'Standard rate',
            'rate' => '20.00',
        ]);

        $persistedVatRate = VatRateFactory::repository()->find($vatRate->getId());
        $this->assertEquals('Standard rate', $persistedVatRate->getName());
    }

    public function testAddCategoryToVatRate(): void
    {
        $vatRate = VatRateFactory::createOne();
        $category = CategoryFactory::createOne(['vatRate' => $vatRate]);

        $this->assertTrue($vatRate->getCategories()->contains($category));
        $this->assertSame($vatRate, $category->getVatRate());
    }

    public function testRemoveCategoryFromVatRate(): void
    {
        $vatRate = VatRateFactory::createOne();
        $category = CategoryFactory::createOne(['vatRate' => $vatRate]);

        $vatRate->removeCategory($category);

        $this->assertFalse($vatRate->getCategories()->contains($category));
        $this->assertNull($category->getVatRate());
    }

    public function testReAddCategoryToVatRate(): void
    {
        $vatRate = VatRateFactory::createOne();
        $category = CategoryFactory::createOne(['vatRate' => $vatRate]);

        $vatRate->removeCategory($category);
        $vatRate->addCategory($category);

        $this->assertTrue($vatRate->getCategories()->contains($category));
        $this->assertSame($vatRate, $category->getVatRate());
    }
}
