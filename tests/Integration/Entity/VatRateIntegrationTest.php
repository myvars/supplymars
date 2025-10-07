<?php

namespace App\Tests\Integration\Entity;

use App\Factory\CategoryFactory;
use App\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class VatRateIntegrationTest  extends KernelTestCase
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
            'rate' => '20.00'
        ]);

        $errors = $this->validator->validate($vatRate);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => '']);

        $errors = $this->validator->validate($vatRate);
        $this->assertSame('Please enter a VAT rate name', $errors[0]->getMessage());
    }

    public function testRateIsRequired(): void
    {
        $vatRate = VatRateFactory::new()->withoutPersisting()->create(['rate' => '']);

        $errors = $this->validator->validate($vatRate);
        $this->assertSame('Please enter a VAT rate %', $errors[0]->getMessage());
    }

    public function testRateMustBePositiveOrZero(): void
    {
        $vatRate = VatRateFactory::createOne(['rate' => '-5.00']);

        $errors = $this->validator->validate($vatRate);
        $this->assertSame('Please enter a positive or zero VAT rate', $errors[0]->getMessage());
    }

    public function testVatRatePersistence(): void
    {
        $vatRate = VatRateFactory::createOne([
            'name' => 'Standard rate',
            'rate' => '20.00',
            'isDefaultVatRate' => true
        ]);

        $persistedVatRate = VatRateFactory::repository()->find($vatRate->getId())->_real();
        $this->assertEquals('Standard rate', $persistedVatRate->getName());
    }

    public function testAddCategoryToVatRate()
    {
        $vatRate = VatRateFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['vatRate' => $vatRate])->_real();

        $this->assertTrue($vatRate->getCategories()->contains($category));
        $this->assertSame($vatRate, $category->getVatRate());
    }

    public function testRemoveCategoryFromVatRate()
    {
        $vatRate = VatRateFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['vatRate' => $vatRate])->_real();

        $vatRate->removeCategory($category);

        $this->assertFalse($vatRate->getCategories()->contains($category));
        $this->assertNull($category->getVatRate());
    }

    public function testReAddCategoryToVatRate()
    {
        $vatRate = VatRateFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['vatRate' => $vatRate])->_real();

        $vatRate->removeCategory($category);
        $vatRate->addCategory($category);

        $this->assertTrue($vatRate->getCategories()->contains($category));
        $this->assertSame($vatRate, $category->getVatRate());
    }
}
