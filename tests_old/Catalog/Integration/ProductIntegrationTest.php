<?php

namespace App\Tests\Catalog\Integration;

use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\ManufacturerFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\ProductImageFactory;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\UserFactory;
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
        $this->markupCalculator = static::getContainer()->get(MarkupCalculator::class);
    }

    public function testValidProduct(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'category' => CategoryFactory::createOne(),
            'subcategory' => SubcategoryFactory::createOne(),
            'manufacturer' => ManufacturerFactory::createOne(),
            'mfrPartNumber' => 'Test MfrPartNumber',
            'owner' => UserFactory::createOne(),
        ]);

        $errors = $this->validator->validate($product);
        $this->assertCount(0, $errors);
    }

    public function testInvalidProductWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name cannot be empty');

        $product = ProductFactory::createOne(['name' => '']);
    }

    public function testMfrPartNumberIsRequired(): void
    {
        $product = ProductFactory::createOne(['mfrPartNumber' => '']);

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a manufacturer part number', $violations[0]->getMessage());
    }

    public function testChangePricingWithMissingMarkup(): void
    {
        $defaultMarkup = '';

        $product = ProductFactory::new()->withActiveSource()->create();
        $product->changePricing(
            $this->markupCalculator,
            $defaultMarkup,
            $product->getPriceModel(),
            $product->isActive(),
        );

        $violations = $this->validator->validate($product);
        $this->assertSame('Please enter a product markup %', $violations[0]->getMessage());
    }

    public function testInvalidChangePricingWithNegativeMarkup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup cannot be negative');

        $defaultMarkup = '-5.00';

        $product = ProductFactory::new()->withActiveSource()->create();
        $product->changePricing(
            $this->markupCalculator,
            $defaultMarkup,
            $product->getPriceModel(),
            $product->isActive(),
        );
    }

    public function testProductPersistence(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'description' => 'This is a test product',
            'category' => CategoryFactory::createOne(),
            'subcategory' => SubcategoryFactory::createOne(),
            'manufacturer' => ManufacturerFactory::createOne(),
            'mfrPartNumber' => 'Test MfrPartNumber',
            'owner' => UserFactory::createOne(),
            'isActive' => true,
        ]);

        $persistedProduct = ProductFactory::repository()->find($product->getId());
        $this->assertEquals('Test Product', $persistedProduct->getName());
    }

    public function testAddSupplierProduct(): void
    {
        $product = ProductFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['product' => $product]);

        $this->assertCount(1, $product->getSupplierProducts());
        $this->assertTrue($product->getSupplierProducts()->contains($supplierProduct));
    }

    public function testAddProductImage(): void
    {
        $product = ProductFactory::createOne();
        $productImage = ProductImageFactory::createOne(['product' => $product]);

        $this->assertCount(1, $product->getProductImages());
        $this->assertTrue($product->getProductImages()->contains($productImage));
    }

    public function testRemoveProductImage(): void
    {
        $product = ProductFactory::createOne();
        $productImage = ProductImageFactory::createOne(['product' => $product]);

        $product->removeProductImage($productImage);
        $this->assertCount(0, $product->getProductImages());
    }

    public function testHasActiveProductSource(): void
    {
        $product = ProductFactory::createOne();
        SupplierProductFactory::createOne(['product' => $product]);

        $this->assertTrue($product->hasActiveProductSource());
    }

    public function testHasProductImage(): void
    {
        $productImage = ProductImageFactory::createOne();
        $product = ProductFactory::createOne();

        $product->addProductImage($productImage);

        $this->assertTrue($product->hasProductImage());
    }

    public function testGetFirstImage(): void
    {
        $productImage = ProductImageFactory::createOne();
        $product = ProductFactory::createOne();

        $product->addProductImage($productImage);

        $this->assertSame($productImage, $product->getFirstImage());
    }

    public function testGetActiveMarkup(): void
    {
        $defaultMarkup = '0.210';

        $product = ProductFactory::new()->withActiveSource()->create();
        $product->changePricing(
            $this->markupCalculator,
            $defaultMarkup,
            $product->getPriceModel(),
            $product->isActive(),
        );

        $this->assertEquals('0.210', $product->getActiveMarkup());
    }

    public function testGetActiveMarkupTarget(): void
    {
        $defaultMarkup = '0.210';

        $product = ProductFactory::new()->withActiveSource()->create();
        $product->changePricing(
            $this->markupCalculator,
            $defaultMarkup,
            $product->getPriceModel(),
            $product->isActive(),
        );

        $this->assertEquals('PRODUCT', $product->getActiveMarkupTarget());
    }

    public function testGetActivePriceModel(): void
    {
        $priceModel = PriceModel::PRETTY_99;

        $product = ProductFactory::new()->withActiveSource()->create();
        $product->changePricing(
            $this->markupCalculator,
            $product->getDefaultMarkup(),
            $priceModel,
            $product->isActive(),
        );

        $this->assertSame(PriceModel::PRETTY_99, $product->getActivePriceModel());
    }

    public function testGetActivePriceModelTarget(): void
    {
        $priceModel = PriceModel::PRETTY_99;
        $product = ProductFactory::new()->withActiveSource()->create();

        $product->changePricing(
            $this->markupCalculator,
            $product->getDefaultMarkup(),
            $priceModel,
            $product->isActive(),
        );

        $this->assertEquals('PRODUCT', $product->getActivePriceModelTarget());
    }

    public function testIsValidProduct(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne();
        $product = ProductFactory::new([
            'category' => $category,
            'subcategory' => $subcategory,
        ])->withActiveSource()->create();

        $this->assertTrue($product->isValidProduct());
    }

    public function testGetActiveSupplierProducts(): void
    {
        $product = ProductFactory::createOne();
        $activeSupplierProduct = SupplierProductFactory::createOne(['product' => $product]);
        $inactiveSupplierProduct = SupplierProductFactory::createOne(['product' => $product, 'isActive' => false]);

        $activeSupplierProducts = $product->getActiveSupplierProducts();

        $this->assertCount(1, $activeSupplierProducts);
        $this->assertTrue($activeSupplierProducts->contains($activeSupplierProduct));
        $this->assertFalse($activeSupplierProducts->contains($inactiveSupplierProduct));
    }
}
