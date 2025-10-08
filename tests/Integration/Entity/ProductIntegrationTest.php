<?php

namespace App\Tests\Integration\Entity;

use App\Enum\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\ProductImageFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProduct(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'mfrPartNumber' => 'Test MfrPartNumber',
            'stock' => 100,
            'leadTimeDays' => 7,
            'weight' => 500,
            'defaultMarkup' => '0.21',
            'markup' => '0.21',
            'cost' => '100.00',
            'sellPrice' => '150.00',
            'sellPriceIncVat' => '180.00',
            'category' => CategoryFactory::createOne(),
            'subcategory' => SubcategoryFactory::createOne(),
            'manufacturer' => ManufacturerFactory::createOne(),
            'owner' => UserFactory::createOne(),
            'priceModel' => PriceModel::PRETTY_99,
        ]);

        $errors = $this->validator->validate($product);
        $this->assertCount(0, $errors);
    }

    public function testNameIsRequired(): void
    {
        $product = ProductFactory::createOne(['name' => '']);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a product name', $violations[0]->getMessage());
    }

    public function testMfrPartNumberIsRequired(): void
    {
        $product = ProductFactory::createOne(['mfrPartNumber' => '']);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a manufacturer part number', $violations[0]->getMessage());
    }

    public function testInvalidStockLessThanZero(): void
    {
        $product = ProductFactory::createOne(['stock' => -1]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a stock level', $violations[0]->getMessage());
    }

    public function testInvalidStockGreaterThanMax(): void
    {
        $product = ProductFactory::createOne(['stock' => 10001]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a stock level', $violations[0]->getMessage());
    }

    public function testLeadTimeDaysIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['leadTimeDays' => null]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a lead time(days)', $violations[0]->getMessage());
    }

    public function testInvalidLeadTimeLessThanZero(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['leadTimeDays' => -1]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a lead time (0 to 1000)', $violations[0]->getMessage());
    }

    public function testInvalidLeadTimeGreaterThanMax(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['leadTimeDays' => 1001]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a lead time (0 to 1000)', $violations[0]->getMessage());
    }

    public function testWeightIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['weight' => null]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a product weight(grams)', $violations[0]->getMessage());
    }

    public function testInvalidWeightLessThanZero(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['weight' => -1]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a product weight (0 to 100000)', $violations[0]->getMessage());
    }

    public function testInvalidWeightGreaterThanMax(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['weight' => 100001]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a product weight (0 to 100000)', $violations[0]->getMessage());
    }

    public function testDefaultMarkupIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['defaultMarkup' => '']);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a product markup %', $violations[0]->getMessage());
    }

    public function testInvalidDefaultMarkupLessThanZero(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['defaultMarkup' => -1]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a positive or zero product markup %', $violations[0]->getMessage());
    }

    public function testCostIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['cost' => '']);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a cost', $violations[0]->getMessage());
    }

    public function testInvalidCostLessThanZero(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['cost' => -1]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a positive or zero cost', $violations[0]->getMessage());
    }

    public function testCategoryIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['category' => null]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a category', $violations[0]->getMessage());
    }

    public function testManufacturerIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['manufacturer' => null]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a manufacturer', $violations[0]->getMessage());
    }

    public function testPriceModelIsRequired(): void
    {
        $product = ProductFactory::new()->withoutPersisting()->create(['priceModel' => null]);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a price model', $violations[0]->getMessage());
    }

    public function testProductPersistence(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'mfrPartNumber' => 'Test MfrPartNumber',
            'stock' => 100,
            'leadTimeDays' => 7,
            'weight' => 500,
            'defaultMarkup' => '0.21',
            'markup' => '0.21',
            'cost' => '100.00',
            'sellPrice' => '150.00',
            'sellPriceIncVat' => '180.00',
            'category' => CategoryFactory::createOne(),
            'subcategory' => SubcategoryFactory::createOne(),
            'manufacturer' => ManufacturerFactory::createOne(),
            'owner' => UserFactory::createOne(),
            'priceModel' => PriceModel::PRETTY_99,
            'isActive' => true,
        ])->_real();

        $persistedProduct = ProductFactory::repository()->find($product->getId())->_real();
        $this->assertEquals('Test Product', $persistedProduct->getName());
    }

    public function testAddSupplierProduct(): void
    {
        $product = ProductFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne(['product' => $product])->_real();

        $this->assertCount(1, $product->getSupplierProducts());
        $this->assertTrue($product->getSupplierProducts()->contains($supplierProduct));
    }

    public function testAddProductImage(): void
    {
        $product = ProductFactory::createOne()->_real();
        $productImage = ProductImageFactory::createOne(['product' => $product])->_real();

        $this->assertCount(1, $product->getProductImages());
        $this->assertTrue($product->getProductImages()->contains($productImage));
    }

    public function testRemoveProductImage(): void
    {
        $product = ProductFactory::createOne()->_real();
        $productImage = ProductImageFactory::createOne(['product' => $product])->_real();

        $product->removeProductImage($productImage);
        $this->assertCount(0, $product->getProductImages());
    }

    public function testHasActiveProductSource(): void
    {
        $product = ProductFactory::createOne();
        SupplierProductFactory::createOne(['product' => $product])->_real();

        $this->assertTrue($product->hasActiveProductSource());
    }

    public function testHasProductImage(): void
    {
        $productImage = ProductImageFactory::createOne();
        $product = ProductFactory::createOne()->_real();

        $product->addProductImage($productImage);

        $this->assertTrue($product->hasProductImage());
    }

    public function testGetFirstImage(): void
    {
        $productImage = ProductImageFactory::createOne();
        $product = ProductFactory::createOne()->_real();

        $product->addProductImage($productImage);

        $this->assertSame($productImage, $product->getFirstImage());
    }

    public function testGetActiveMarkup(): void
    {
        $product = ProductFactory::createOne(['defaultMarkup' => '0.210']);
        $this->assertEquals('0.210', $product->getActiveMarkup());
    }

    public function testGetActiveMarkupTarget(): void
    {
        $product = ProductFactory::createOne(['defaultMarkup' => '0.210']);
        $this->assertEquals('PRODUCT', $product->getActiveMarkupTarget());
    }

    public function testGetActivePriceModel(): void
    {
        $product = ProductFactory::createOne(['priceModel' => PriceModel::PRETTY_99]);
        $this->assertSame(PriceModel::PRETTY_99, $product->getActivePriceModel());
    }

    public function testGetActivePriceModelTarget(): void
    {
        $product = ProductFactory::createOne(['priceModel' => PriceModel::PRETTY_99]);
        $this->assertEquals('PRODUCT', $product->getActivePriceModelTarget());
    }

    public function testIsValidProduct(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne();
        $product = ProductFactory::createOne([
            'isActive' => true,
            'category' => $category,
            'subcategory' => $subcategory,
        ]);
        SupplierProductFactory::new()->recalculatePrice()->create([
            'product' => $product,
            'isActive' => true,
            'stock' => 100,
        ]);

        $this->assertTrue($product->isValidProduct());
    }

    public function testGetActiveSupplierProducts(): void
    {
        $product = ProductFactory::createOne();
        $activeSupplierProduct = SupplierProductFactory::createOne(['product' => $product])->_real();
        $inactiveSupplierProduct = SupplierProductFactory::createOne(['product' => $product, 'isActive' => false])->_real();

        $activeSupplierProducts = $product->getActiveSupplierProducts();

        $this->assertCount(1, $activeSupplierProducts);
        $this->assertTrue($activeSupplierProducts->contains($activeSupplierProduct));
        $this->assertFalse($activeSupplierProducts->contains($inactiveSupplierProduct));
    }

    public function testGetDefaultOwner(): void
    {
        $categoryOwner = UserFactory::createOne()->_real();
        $subcategoryOwner = UserFactory::createOne()->_real();
        $productOwner = UserFactory::createOne()->_real();

        $category = CategoryFactory::createOne(['owner' => $categoryOwner]);
        $subcategory = SubcategoryFactory::createOne(['category' => $category, 'owner' => $subcategoryOwner]);
        $product = ProductFactory::createOne(['category' => $category, 'subcategory' => $subcategory, 'owner' => $productOwner])->_real();

        $this->assertSame($productOwner, $product->getDefaultOwner());

        $product->setOwner(null);
        $this->assertSame($subcategoryOwner, $product->getDefaultOwner());

        $subcategory->setOwner(null);
        $this->assertSame($categoryOwner, $product->getDefaultOwner());
    }
}
