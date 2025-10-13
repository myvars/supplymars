<?php

namespace App\Tests\Catalog\Integration;

use App\Shared\Domain\ValueObject\PriceModel;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\SupplierSubcategoryFactory;
use tests\Shared\Factory\UserFactory;
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
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createOne();

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Test Subcategory',
            'category' => $category,
            'owner' => $owner,
            'defaultMarkup' => '0.000',
            'priceModel' => PriceModel::NONE,
        ]);

        $errors = $this->validator->validate($subcategory);
        $this->assertCount(0, $errors);
    }

    public function testInvalidSubcategoryWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subcategory name cannot be empty');

        SubcategoryFactory::createOne(['name' => '']);
    }

    public function testDefaultMarkupIsRequired(): void
    {
        $subcategory = SubcategoryFactory::new()->withoutPersisting()->create(['defaultMarkup' => '']);

        $violations = $this->validator->validate($subcategory);
        $this->assertSame('Please enter a subcategory markup %', $violations[0]->getMessage());
    }

    public function testInvalidSubcategoryWithNegativeMarkup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup cannot be negative');

        SubcategoryFactory::createOne(['defaultMarkup' => '-5.000']);
    }

    public function testSubcategoryPersistence(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createOne();

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Smartphones',
            'category' => $category,
            'owner' => $owner,
            'defaultMarkup' => '5.000',
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
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne();
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertTrue($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($subcategory, $supplierSubcategory->getMappedSubcategory());
    }

    public function testRemoveSupplierSubcategoryFromSubcategory(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne();
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $subcategory->removeSupplierSubcategory($supplierSubcategory);

        $this->assertFalse($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertNull($supplierSubcategory->getMappedSubcategory());
    }

    public function testReAddSupplierSubcategoryToSubcategory(): void
    {
        $supplierSubcategory = SupplierSubcategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne();
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $subcategory->removeSupplierSubcategory($supplierSubcategory);
        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $this->assertTrue($subcategory->getSupplierSubcategories()->contains($supplierSubcategory));
        $this->assertSame($subcategory, $supplierSubcategory->getMappedSubcategory());
    }
}
