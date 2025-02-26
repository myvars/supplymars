<?php

namespace App\Tests\Integration\Service\SupplierProduct;

use App\Factory\ProductFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\SupplierProduct\ChangeMappedProductStatus;
use App\Service\Utility\DomainEventDispatcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ChangeMappedProductStatusIntegrationTest extends KernelTestCase
{
    use Factories;

    private ChangeMappedProductStatus $changeMappedProductStatus;

    protected function setUp(): void
    {
        self::bootKernel();
        $activeSourceCalculator = static::getContainer()->get(ActiveSourceCalculator::class);
        $domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        $this->changeMappedProductStatus = new ChangeMappedProductStatus(
            $activeSourceCalculator,
            $domainEventDispatcher
        );
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

        $this->changeMappedProductStatus->handle($crudOptions);

        $this->assertFalse($supplierProduct->isActive());
        $this->assertNull($product->getActiveProductSource());
    }

    public function testHandleWithActiveSourceStatusChange(): void
    {
        $product = ProductFactory::createOne(['isActive' => true])->_real();
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

        $this->changeMappedProductStatus->handle($crudOptions);

        $this->assertFalse($supplierProduct1->isActive());
        $this->assertSame($supplierProduct2, $product->getActiveProductSource());
    }
}