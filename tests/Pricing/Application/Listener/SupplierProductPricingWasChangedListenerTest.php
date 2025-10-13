<?php

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Pricing\Application\Listener\SupplierProductPricingWasChanged;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductPricingWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierProductPricingWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private SupplierProductRepository $supplierProducts;
    private ProductRepository $products;
    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->supplierProducts = $container->get(SupplierProductRepository::class);
        $this->products = $container->get(ProductRepository::class);
        $this->calculator = $container->get(MarkupCalculator::class);
    }

    public function testRecalculatesMappedProductWhenSupplierProductPricingChanges(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $before = $product->getSellPriceIncVat();

        /** @var SupplierProduct $sp */
        $sp = $product->getActiveProductSource();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SupplierProductPricingWasChanged(
            $this->supplierProducts,
            $this->products,
            $this->calculator,
            $flusher
        );

        // Trigger SupplierProductPricingWasChangedEvent via public update()
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
            cost: bcadd($sp->getCost(), '1.00', 2), // change cost to trigger event
            product: $sp->getProduct(),
            isActive: $sp->isActive() ?? true
        );

        foreach ($sp->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierProductPricingWasChangedEvent) {
                continue;
            }
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloaded */
        $reloaded = $this->em->getRepository(Product::class)->find($product->getId());
        self::assertNotSame($before, $reloaded->getSellPriceIncVat());
    }

    public function testRecalculatesPreviousAndCurrentMappedProductsWhenRemapped(): void
    {
        $productA = ProductFactory::createOne();
        $productB = ProductFactory::createOne();
        $sp1 = SupplierProductFactory::createOne(['cost' => '10.00', 'product' => $productA]);
        SupplierProductFactory::createOne(['cost' => '20.00', 'product' => $productA]);

        $beforeA = $productA->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SupplierProductPricingWasChanged(
            $this->supplierProducts,
            $this->products,
            $this->calculator,
            $flusher
        );

        // Remap supplier product to productB -> previousMappedProductId will be set
        $sp1->update(
            name: $sp1->getName(),
            productCode: $sp1->getProductCode(),
            supplierCategory: $sp1->getSupplierCategory(),
            supplierSubcategory: $sp1->getSupplierSubcategory(),
            supplierManufacturer: $sp1->getSupplierManufacturer(),
            mfrPartNumber: $sp1->getMfrPartNumber(),
            weight: $sp1->getWeight(),
            supplier: $sp1->getSupplier(),
            stock: $sp1->getStock(),
            leadTimeDays: $sp1->getLeadTimeDays(),
            cost: $sp1->getCost(),
            product: $productB, // map to B
            isActive: $sp1->isActive() ?? true
        );

        foreach ($sp1->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierProductPricingWasChangedEvent) {
                continue;
            }
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloadedA */
        $reloadedA = $this->em->getRepository(Product::class)->find($productA->getId());
        /** @var Product $reloadedB */
        $reloadedB = $this->em->getRepository(Product::class)->find($productB->getId());

        self::assertTrue($reloadedB->hasActiveProductSource());
        self::assertNotSame($beforeA, $reloadedA->getSellPriceIncVat());
    }

    public function testDoesNotFlushWhenNoRelevantChanges(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();

        /** @var SupplierProduct $sp */
        $sp = $product->getActiveProductSource();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new SupplierProductPricingWasChanged(
            $this->supplierProducts,
            $this->products,
            $this->calculator,
            $flusher
        );

        // No change -> no SupplierProductPricingWasChangedEvent raised
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
            cost: $sp->getCost() ?? '0.00',
            product: $sp->getProduct(),
            isActive: $sp->isActive() ?? true
        );

        foreach ($sp->releaseDomainEvents() as $event) {
            $listener($event);
        }
    }
}
