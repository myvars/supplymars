<?php

namespace App\Tests\Integration\Entity;

use App\Enum\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\SupplierSubcategoryFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSubcategory(): void
    {
        $owner = UserFactory::new()->staff()->create();
        $category = CategoryFactory::createOne();

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Test Subcategory',
            'defaultMarkup' => '0.000',
            'owner' => $owner,
            'category' => $category,
            'priceModel' => PriceModel::NONE,
        ]);

        $errors = $this->validator->validate($subcategory);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($subcategory);
        $this->assertSame('Please enter a Subcategory name', $violations[0]->getMessage());
    }

    public function testDefaultMarkupIsRequired(): void
    {
        $subcategory = SubcategoryFactory::new()->withoutPersisting()->create(['defaultMarkup' => '']);

        $violations = $this->validator->validate($subcategory);
        $this->assertSame('Please enter a subcategory markup %', $violations[0]->getMessage());
    }

    public function testDefaultMarkupMustBePositiveOrZero(): void
    {
        $subcategory = SubcategoryFactory::createOne(['defaultMarkup' => '-5.000']);

        $violations = $this->validator->validate($subcategory);
        $this->assertSame('Please enter a positive or zero subcategory markup %', $violations[0]->getMessage());
    }

    public function testCategoryIsRequired(): void
    {
        $subcategory = SubcategoryFactory::new()->withoutPersisting()->create(['category' => null]);

        $violations = $this->validator->validate($subcategory);
        $this->assertSame('Please enter a category', $violations[0]->getMessage());
    }

    public function testPriceModelIsRequired(): void
    {
        $subcategory = SubcategoryFactory::new()->withoutPersisting()->create(['priceModel' => null]);

        $violations = $this->validator->validate($subcategory);
        $this->assertSame('Please enter a price model', $violations[0]->getMessage());
    }

    public function testSubcategoryPersistence(): void
    {
        $owner = UserFactory::new()->staff()->create();
        $category = CategoryFactory::createOne();

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Smartphones',
            'defaultMarkup' => '5.000',
            'owner' => $owner,
            'category' => $category,
            'priceModel' => PriceModel::NONE,
            'isActive' => true,
        ]);

        $persistedSubcategory = SubcategoryFactory::repository()->find($subcategory->getId());
        $this->assertEquals('Smartphones', $persistedSubcategory->getName());
    }

    public function testAddProductToSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $product = ProductFactory::createOne(['subcategory' => $subcategory]);

        $this->assertTrue($subcategory->getProducts()->contains($product));
        $this->assertSame($subcategory, $product->getSubcategory());
    }

    public function testRemoveProductFromSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $product = ProductFactory::createOne(['subcategory' => $subcategory]);

        $subcategory->removeProduct($product);

        $this->assertFalse($subcategory->getProducts()->contains($product));
        $this->assertNull($product->getSubcategory());
    }

    public function testReAddProductToSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $product = ProductFactory::createOne(['subcategory' => $subcategory]);

        $subcategory->removeProduct($product);
        $subcategory->addProduct($product);

        $this->assertTrue($subcategory->getProducts()->contains($product));
        $this->assertSame($subcategory, $product->getSubcategory());
    }

    public function testAddSupplierSubcategoryToSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['mappedSubcategory' => $subcategory]);

        $this->assertTrue($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($subcategory, $supplierSubcategory->getMappedSubcategory());
    }

    public function testRemoveSupplierSubcategoryFromSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['mappedSubcategory' => $subcategory]);

        $subcategory->removeSupplierSubcategory($supplierSubcategory);

        $this->assertFalse($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertNull($supplierSubcategory->getMappedSubcategory());
    }

    public function testReAddSupplierSubcategoryToSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $supplierSubcategory = SupplierSubcategoryFactory::createOne(['mappedSubcategory' => $subcategory]);

        $subcategory->removeSupplierSubcategory($supplierSubcategory);
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertTrue($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($subcategory, $supplierSubcategory->getMappedSubcategory());
    }
}
