<?php

namespace App\Tests\Catalog\Integration;

use App\Shared\Domain\ValueObject\PriceModel;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\UserFactory;
use tests\Shared\Factory\VatRateFactory;
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
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::createOne();

        $category = CategoryFactory::createOne([
            'name' => 'Test Category',
            'owner' => $owner,
            'vatRate' => $vatRate,
            'defaultMarkup' => '5.000',
            'priceModel' => PriceModel::DEFAULT,
        ]);

        $errors = $this->validator->validate($category);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCategoryWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name cannot be empty');

        CategoryFactory::createOne(['name' => '']);
    }

    public function testDefaultMarkupIsRequired(): void
    {
        $category = CategoryFactory::new()->withoutPersisting()->create(['defaultMarkup' => '']);

        $violations = $this->validator->validate($category);
        $this->assertSame('Please enter a category markup %', $violations[0]->getMessage());
    }

    public function testInvalidCategoryWithNegativeMarkup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup cannot be negative');

        CategoryFactory::createOne(['defaultMarkup' => '-5.000']);
    }

    public function testInvalidCategoryWithPriceModelSetToNone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A category must have a price model');

        $category = CategoryFactory::createOne(['priceModel' => PriceModel::NONE]);
    }

    public function testCategoryPersistence(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::createOne();

        $category = CategoryFactory::createOne([
            'name' => 'Electronics',
            'owner' => $owner,
            'vatRate' => $vatRate,
            'defaultMarkup' => '10.000',
            'priceModel' => PriceModel::DEFAULT,
            'isActive' => true,
        ]);

        $persistedCategory = CategoryFactory::repository()->find($category->getId());
        $this->assertEquals('Electronics', $persistedCategory->getName());
    }

    public function testAddSubcategoryToCategory(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);

        $this->assertTrue($category->getSubcategories()->contains($subcategory));
        $this->assertSame($category, $subcategory->getCategory());
    }

    public function testRemoveSubcategoryFromCategory(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);

        $category->removeSubcategory($subcategory);

        $this->assertFalse($category->getSubcategories()->contains($subcategory));
        $this->assertNull($subcategory->getCategory());
    }

    public function testReAddSubcategoryToCategory(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);

        $category->removeSubcategory($subcategory);
        $category->addSubcategory($subcategory);

        $this->assertTrue($category->getSubcategories()->contains($subcategory));
        $this->assertSame($category, $subcategory->getCategory());
    }

    public function testAddProductToCategory(): void
    {
        $category = CategoryFactory::createOne();
        $product = ProductFactory::createOne(['category' => $category]);

        $this->assertTrue($category->getProducts()->contains($product));
        $this->assertSame($category, $product->getCategory());
    }

    public function testRemoveProductFromCategory(): void
    {
        $category = CategoryFactory::createOne();
        $product = ProductFactory::createOne(['category' => $category]);

        $category->removeProduct($product);

        $this->assertFalse($category->getProducts()->contains($product));
        $this->assertNull($product->getCategory());
    }

    public function testReAddProductToCategory(): void
    {
        $category = CategoryFactory::createOne();
        $product = ProductFactory::createOne(['category' => $category]);

        $category->removeProduct($product);
        $category->addProduct($product);

        $this->assertTrue($category->getProducts()->contains($product));
        $this->assertSame($category, $product->getCategory());
    }
}
