<?php

namespace App\Tests\Integration\Service\Product;

use App\Service\Product\ActiveSourceCalculator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Zenstruck\Foundry\Test\Factories;

class ActiveSourceCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ActiveSourceCalculator $activeSourceCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->activeSourceCalculator = static::getContainer()->get(ActiveSourceCalculator::class);
    }

    public function testRecalculateActiveSource(): void
    {
        $product = ProductFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $product->addSupplierProduct($supplierProduct);

        $this->activeSourceCalculator->recalculateActiveSource($product);

        $this->assertSame($supplierProduct, $product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithNoSupplierProducts(): void
    {
        $product = ProductFactory::createOne();

        $this->activeSourceCalculator->recalculateActiveSource($product);

        $this->assertNull($product->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithInactiveSupplier(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct->getSupplier()->setIsActive(false);

        $this->assertNotNull($supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertNull($supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithInactiveSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct->setIsActive(false);

        $this->assertNotNull($supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertNull($supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithNoSupplierStock(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct->changeStock(0);

        $this->assertNotNull($supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertNull($supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithNoSupplierCost(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct->setCost(0);

        $this->assertNotNull($supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertNull($supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithLowerCost(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'product' => null,
            'cost' => '50.00',
            'stock' => 10,
        ]);
        $supplierProduct->getProduct()->addSupplierProduct($supplierProduct2);

        $this->assertSame($supplierProduct, $supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertSame($supplierProduct2, $supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithSameCostAndGreaterStock(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 5,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'product' => null,
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct->getProduct()->addSupplierProduct($supplierProduct2);

        $this->assertSame($supplierProduct, $supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertSame($supplierProduct2, $supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceWithSameCostAndLessStock(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'product' => null,
            'cost' => '100.00',
            'stock' => 5,
        ]);
        $supplierProduct->getProduct()->addSupplierProduct($supplierProduct2);

        $this->assertSame($supplierProduct, $supplierProduct->getProduct()->getActiveProductSource());

        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->assertSame($supplierProduct, $supplierProduct->getProduct()->getActiveProductSource());
    }

    public function testRecalculateActiveSourceFromArray(): void
    {
        $supplierProduct1 = SupplierProductFactory::createOne([
            'cost' => '100.00',
            'stock' => 10,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'cost' => '200.00',
            'stock' => 20,
        ]);

        $this->activeSourceCalculator->recalculateActiveSourceFromArray([
            $supplierProduct1->getProduct(),
            $supplierProduct2->getProduct(),
        ]);

        $this->assertNotNull($supplierProduct1->getProduct()->getActiveProductSource());
        $this->assertNotNull($supplierProduct2->getProduct()->getActiveProductSource());
    }

    public function testToggleStatus(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();

        $this->activeSourceCalculator->toggleStatus($supplierProduct);

        $this->assertFalse($supplierProduct->isActive());
    }
}
