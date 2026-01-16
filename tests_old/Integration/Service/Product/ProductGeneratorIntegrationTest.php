<?php

namespace App\Tests\Integration\Service\Product;

use App\Catalog\Domain\Model\Product\Product;
use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ProductGenerator;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\VatRateFactory;
use Zenstruck\Foundry\Test\Factories;

class ProductGeneratorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductGenerator $productGenerator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->productGenerator = static::getContainer()->get(ProductGenerator::class);
        VatRateFactory::new()->withStandardRate()->create();
        StaffUserStory::load();
    }

    public function testHandleWithValidSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['product' => null]);

        $context = new CrudContext();
        $context->setEntity($supplierProduct);

        ($this->productGenerator)($context);

        $product = $supplierProduct->getProduct();
        $this->assertInstanceOf(Product::class, $product);
    }

    public function testHandleWithExistingProduct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product already exists');

        $supplierProduct = SupplierProductFactory::createOne();

        $context = new CrudContext();
        $context->setEntity($supplierProduct);

        ($this->productGenerator)($context);
    }
}
