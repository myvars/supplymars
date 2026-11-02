<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierProductStatusWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRecalculatesMappedProductWhenStatusChanges(): void
    {
        $product = $this->createProduct();
        // Two supplier products mapped to the same product; only the dearer one is active
        $spLow = SupplierProductFactory::createOne(['cost' => '10.00', 'stock' => 5, 'product' => $product, 'isActive' => false]);
        SupplierProductFactory::createOne(['cost' => '20.00', 'stock' => 5, 'product' => $product, 'isActive' => true]);
        // 20.00 × 1.05 × 1.20 = 25.20
        self::assertSame('25.20', $product->getSellPriceIncVat());

        $spLow->setActive(true);
        $this->em->flush();

        // The cheaper source takes over: 10.00 × 1.05 × 1.20 = 12.60
        $reloaded = $this->reload($product);
        self::assertSame($spLow->getId(), $reloaded->getActiveProductSource()?->getId());
        self::assertSame('10.00', $reloaded->getCost());
        self::assertSame('12.60', $reloaded->getSellPriceIncVat());
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
