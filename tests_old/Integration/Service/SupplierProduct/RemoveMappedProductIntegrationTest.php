<?php

namespace App\Tests\Integration\Service\SupplierProduct;

use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\SupplierProduct\RemoveMappedProduct;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SupplierProductFactory;
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
        $product = ProductFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'stock' => 100,
            'product' => $product,
        ]);

        $this->assertSame($supplierProduct, $product->getActiveProductSource());

        $context = new CrudContext();
        $context->setEntity($supplierProduct);

        ($this->removeMappedProduct)($context);

        $this->assertNull($supplierProduct->getProduct());
        $this->assertNull($product->getActiveProductSource());
    }

    public function testHandleWhenActiveSourceRemoved(): void
    {
        $product = ProductFactory::createOne();
        $supplierProduct1 = SupplierProductFactory::createOne([
            'stock' => 100,
            'cost' => '5.00',
            'product' => $product,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'stock' => 100,
            'cost' => '10.00',
            'product' => $product,
        ]);

        $this->assertSame($supplierProduct1, $product->getActiveProductSource());

        $context = new CrudContext();
        $context->setEntity($supplierProduct1);

        ($this->removeMappedProduct)($context);

        $this->assertNull($supplierProduct1->getProduct());
        $this->assertSame($supplierProduct2, $product->getActiveProductSource());
    }
}
