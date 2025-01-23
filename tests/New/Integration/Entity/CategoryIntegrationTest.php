<?php

namespace App\Tests\New\Integration\Entity;

use App\Enum\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CategoryIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCategory(): void
    {
        $owner = UserFactory::new()->staff()->create();
        $vatRate = VatRateFactory::createOne();

        $category = CategoryFactory::createOne([
            'name' => 'Test Category',
            'defaultMarkup' => '5.000',
            'owner' => $owner,
            'vatRate' => $vatRate,
            'priceModel' => PriceModel::DEFAULT,
        ]);

        $errors = $this->validator->validate($category);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $category = CategoryFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a category name', $violations[0]->getMessage());
    }

    public function testDefaultMarkupIsRequired(): void
    {
        $category = CategoryFactory::new()->withoutPersisting()->create(['defaultMarkup' => '']);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a category markup %', $violations[0]->getMessage());
    }

    public function testDefaultMarkupMustBePositiveOrZero(): void
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '-5.000']);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a positive or zero category markup %', $violations[0]->getMessage());
    }

    public function testOwnerIsRequired(): void
    {
        $category = CategoryFactory::new()->withoutPersisting()->create(['owner' => null]);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a category owner', $violations[0]->getMessage());
    }

    public function testVatRateIsRequired(): void
    {
        $category = CategoryFactory::new()->withoutPersisting()->create(['vatRate' => null]);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a VAT rate', $violations[0]->getMessage());
    }

    public function testPriceModelIsRequired(): void
    {
        $category = CategoryFactory::new()->withoutPersisting()->create(['priceModel' => null]);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a price model', $violations[0]->getMessage());
    }

    public function testPriceModelMustNotBeNone(): void
    {
        $category = CategoryFactory::createOne(['priceModel' => PriceModel::NONE]);

        $violations = $this->validator->validate($category);
        $this->assertSame('A category must have a price model', $violations[0]->getMessage());
    }

    public function testCategoryPersistence(): void
    {
        $owner = UserFactory::new()->staff()->create();
        $vatRate = VatRateFactory::createOne();

        $category = CategoryFactory::createOne([
            'name' => 'Electronics',
            'defaultMarkup' => '10.000',
            'owner' => $owner,
            'vatRate' => $vatRate,
            'priceModel' => PriceModel::DEFAULT,
            'isActive' => true,
        ]);

        $persistedCategory = CategoryFactory::repository()->find($category->getId());
        $this->assertEquals('Electronics', $persistedCategory->getName());
    }

    public function testAddSubcategoryToCategory()
    {
        $category = CategoryFactory::createOne()->_real();
        $subcategory = SubcategoryFactory::createOne(['category' => $category])->_real();

        $this->assertTrue($category->getSubcategories()->contains($subcategory));
        $this->assertSame($category, $subcategory->getCategory());
    }

    public function testRemoveSubcategoryFromCategory()
    {
        $category = CategoryFactory::createOne()->_real();
        $subcategory = SubcategoryFactory::createOne(['category' => $category])->_real();

        $category->removeSubcategory($subcategory);

        $this->assertFalse($category->getSubcategories()->contains($subcategory));
        $this->assertNull($subcategory->getCategory());
    }

    public function testReAddSubcategoryToCategory()
    {
        $category = CategoryFactory::createOne()->_real();
        $subcategory = SubcategoryFactory::createOne(['category' => $category])->_real();

        $category->removeSubcategory($subcategory);
        $category->addSubcategory($subcategory);

        $this->assertTrue($category->getSubcategories()->contains($subcategory));
        $this->assertSame($category, $subcategory->getCategory());
    }

    public function testAddProductToCategory()
    {
        $category = CategoryFactory::createOne()->_real();
        $product = ProductFactory::createOne(['category' => $category])->_real();

        $this->assertTrue($category->getProducts()->contains($product));
        $this->assertSame($category, $product->getCategory());
    }

    public function testRemoveProductFromCategory()
    {
        $category = CategoryFactory::createOne()->_real();
        $product = ProductFactory::createOne(['category' => $category])->_real();

        $category->removeProduct($product);

        $this->assertFalse($category->getProducts()->contains($product));
        $this->assertNull($product->getCategory());
    }

    public function testReAddProductToCategory()
    {
        $category = CategoryFactory::createOne()->_real();
        $product = ProductFactory::createOne(['category' => $category])->_real();

        $category->removeProduct($product);
        $category->addProduct($product);

        $this->assertTrue($category->getProducts()->contains($product));
        $this->assertSame($category, $product->getCategory());
    }
}