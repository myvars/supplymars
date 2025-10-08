<?php

namespace App\Tests\Integration\Service\SupplierProduct;

use App\Factory\ProductFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\SupplierProduct\RemoveMappedProduct;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class RemoveMappedProductIntegrationTest extends KernelTestCase
{
    use Factories;

    private RemoveMappedProduct $removeMappedProduct;

    protected function setUp(): void
    {
        self::bootKernel();
        $activeSourceCalculator = static::getContainer()->get(ActiveSourceCalculator::class);
        $this->removeMappedProduct = new RemoveMappedProduct($activeSourceCalculator);
    }

    public function testHandleWithValidSupplierProduct(): void
    {
        $product = ProductFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne([
            'stock' => 100,
            'product' => $product
        ])->_real();

        $this->assertSame($supplierProduct, $product->getActiveProductSource());

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($supplierProduct);

        $this->removeMappedProduct->handle($crudOptions);

        $this->assertNull($supplierProduct->getProduct());
        $this->assertNull($product->getActiveProductSource());
    }

    public function testHandleWhenActiveSourceRemoved(): void
    {
        $product = ProductFactory::createOne()->_real();
        $supplierProduct1 = SupplierProductFactory::createOne([
            'stock' => 100,
            'cost' => '5.00',
            'product' => $product
        ])->_real();
        $supplierProduct2 = SupplierProductFactory::createOne([
            'stock' => 100,
            'cost' => '10.00',
            'product' => $product
        ])->_real();

        $this->assertSame($supplierProduct1, $product->getActiveProductSource());

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($supplierProduct1);

        $this->removeMappedProduct->handle($crudOptions);

        $this->assertNull($supplierProduct1->getProduct());
        $this->assertSame($supplierProduct2, $product->getActiveProductSource());
    }
}
