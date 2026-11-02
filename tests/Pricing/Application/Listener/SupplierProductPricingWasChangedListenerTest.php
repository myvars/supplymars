<?php

declare(strict_types=1);

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierProductPricingWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRecalculatesMappedProductWhenSupplierProductPricingChanges(): void
    {
        // Markup 25%, VAT 20%, no rounding: 10.00 × 1.25 × 1.20 = 15.00
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        $sp = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        self::assertSame('15.00', $product->getSellPriceIncVat());

        $this->updateSupplierProduct($sp, cost: '20.00', product: $product);
        $this->em->flush();

        // 20.00 × 1.25 × 1.20 = 30.00
        $reloaded = $this->reload($product);
        self::assertSame('20.00', $reloaded->getCost());
        self::assertSame('25.00', $reloaded->getSellPrice());
        self::assertSame('30.00', $reloaded->getSellPriceIncVat());
    }

    /**
     * Doctrine orders UPDATEs by id, compared as strings, so both orders must work: the new product
     * must not take the supplier product as its active source while the previous one still holds it.
     */
    #[TestWith([true], 'new product has the lower id')]
    #[TestWith([false], 'new product has the higher id')]
    public function testRecalculatesPreviousAndCurrentMappedProductsWhenRemapped(bool $newProductHasLowerId): void
    {
        if ($newProductHasLowerId) {
            $productB = $this->createProduct();
            $productA = $this->createProduct();
        } else {
            $productA = $this->createProduct();
            $productB = $this->createProduct();
        }

        $sp1 = SupplierProductFactory::createOne(['cost' => '10.00', 'stock' => 5, 'product' => $productA]);
        $sp2 = SupplierProductFactory::createOne(['cost' => '20.00', 'stock' => 5, 'product' => $productA]);
        self::assertSame($sp1->getId(), $productA->getActiveProductSource()?->getId());
        self::assertSame('12.60', $productA->getSellPriceIncVat());

        $this->updateSupplierProduct($sp1, cost: '10.00', product: $productB);
        $this->em->flush();

        $this->em->clear();

        $reloadedA = ProductFactory::repository()->findOrFail($productA->getId());
        $reloadedB = ProductFactory::repository()->findOrFail($productB->getId());

        // A falls back to sp2: 20.00 × 1.05 × 1.20 = 25.20
        self::assertSame($sp2->getId(), $reloadedA->getActiveProductSource()?->getId());
        self::assertSame('25.20', $reloadedA->getSellPriceIncVat());

        // B takes sp1: 10.00 × 1.05 × 1.20 = 12.60
        self::assertSame($sp1->getId(), $reloadedB->getActiveProductSource()?->getId());
        self::assertSame('12.60', $reloadedB->getSellPriceIncVat());
    }

    /**
     * Category markup 5%, VAT 20%, no rounding.
     */
    private function createProduct(): Product
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '5.000', 'priceModel' => PriceModel::DEFAULT]);

        return ProductFactory::createOne(['category' => $category]);
    }

    /**
     * @param numeric-string $cost
     */
    private function updateSupplierProduct(SupplierProduct $sp, string $cost, Product $product): void
    {
        $sp->update(
            name: $sp->getName(),
            productCode: $sp->getProductCode(),
            supplierCategory: $sp->getSupplierCategory(),
            supplierSubcategory: $sp->getSupplierSubcategory(),
            supplierManufacturer: $sp->getSupplierManufacturer(),
            mfrPartNumber: $sp->getMfrPartNumber(),
            weight: $sp->getWeight(),
            supplier: $sp->getSupplier(),
            stock: $sp->getStock(),
            leadTimeDays: $sp->getLeadTimeDays(),
            cost: $cost,
            product: $product,
            isActive: $sp->isActive() ?? true
        );
    }

    private function reload(Product $product): Product
    {
        $this->em->clear();

        return ProductFactory::repository()->findOrFail($product->getId());
    }
}
