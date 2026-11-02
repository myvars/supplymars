<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierStatusWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRecalculatesAllMappedProductsWhenSupplierIsActivated(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => false]);

        $productA = $this->createProduct();
        $productB = $this->createProduct();

        $spA = SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $productA, 'cost' => '10.00', 'stock' => 5]);
        $spB = SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $productB, 'cost' => '20.00', 'stock' => 5]);
        self::assertNull($productA->getActiveProductSource());
        self::assertNull($productB->getActiveProductSource());

        $supplier->update(name: $supplier->getName(), isActive: true);
        $this->em->flush();

        $this->em->clear();

        $reloadedA = ProductFactory::repository()->findOrFail($productA->getId());
        $reloadedB = ProductFactory::repository()->findOrFail($productB->getId());

        // 10.00 × 1.05 × 1.20 = 12.60
        self::assertSame($spA->getId(), $reloadedA->getActiveProductSource()?->getId());
        self::assertSame('12.60', $reloadedA->getSellPriceIncVat());

        // 20.00 × 1.05 × 1.20 = 25.20
        self::assertSame($spB->getId(), $reloadedB->getActiveProductSource()?->getId());
        self::assertSame('25.20', $reloadedB->getSellPriceIncVat());
    }

    public function testRecalculatesOnlyProductsWithActiveSourceWhenSupplierIsDeactivated(): void
    {
        $product = $this->createProduct();
        $supplier1 = SupplierFactory::createOne();
        $spActiveSource = SupplierProductFactory::createOne([
            'supplier' => $supplier1,
            'product' => $product,
            'cost' => '10.00',
            'stock' => 5,
        ]);
        $sp2 = SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::createOne(),
            'product' => $product,
            'cost' => '20.00',
            'stock' => 5,
        ]);
        self::assertSame($spActiveSource->getId(), $product->getActiveProductSource()?->getId());
        self::assertSame('12.60', $product->getSellPriceIncVat());

        $supplier1->update(name: $supplier1->getName(), isActive: false);
        $this->em->flush();

        // The other supplier's source takes over: 20.00 × 1.05 × 1.20 = 25.20
        $reloaded = $this->reload($product);
        self::assertSame($sp2->getId(), $reloaded->getActiveProductSource()?->getId());
        self::assertSame('25.20', $reloaded->getSellPriceIncVat());
    }

    /**
     * Category markup 5%, VAT 20%, no rounding.
     */
    private function createProduct(): Product
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '5.000', 'priceModel' => PriceModel::DEFAULT]);

        return ProductFactory::createOne(['category' => $category]);
    }

    private function reload(Product $product): Product
    {
        $this->em->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
