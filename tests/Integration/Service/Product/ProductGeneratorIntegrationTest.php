<?php

namespace App\Tests\Integration\Service\Product;

use App\Entity\Product;
use App\Factory\SupplierProductFactory;
use App\Factory\VatRateFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ProductGenerator;
use App\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductGeneratorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductGenerator $productGenerator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->productGenerator = static::getContainer()->get(ProductGenerator::class);
        VatRateFactory::new()->standard()->create();
        StaffUserStory::load();
    }

    public function testHandleWithValidSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['product' => null])->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($supplierProduct);

        $this->productGenerator->handle($crudOptions);

        $product = $supplierProduct->getProduct();
        $this->assertInstanceOf(Product::class, $product);
    }

    public function testHandleWithExistingProduct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product already exists');

        $supplierProduct = SupplierProductFactory::createOne()->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($supplierProduct);

        $this->productGenerator->handle($crudOptions);
    }
}