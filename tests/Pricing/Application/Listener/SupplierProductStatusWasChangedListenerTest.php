<?php

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Application\Listener\SupplierProductStatusWasChanged;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStatusWasChangedEvent;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierProductStatusWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private SupplierProductRepository $supplierProducts;

    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->supplierProducts = $container->get(SupplierProductRepository::class);
        $this->calculator = $container->get(MarkupCalculator::class);
    }

    public function testRecalculatesMappedProductWhenStatusChanges(): void
    {
        $product = ProductFactory::createOne();
        // Two supplier products mapped to the same product, different costs
        $spLow = SupplierProductFactory::createOne(['cost' => '10.00', 'product' => $product, 'isActive' => false]);
        SupplierProductFactory::createOne(['cost' => '20.00', 'product' => $product, 'isActive' => true]);

        $before = $product->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SupplierProductStatusWasChanged(
            $this->supplierProducts,
            $this->calculator,
            $flusher
        );

        // Activate the lower-cost supplier product -> event raised
        $spLow->setActive(true);

        foreach ($spLow->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierProductStatusWasChangedEvent) {
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

    public function testDoesNotFlushWhenUnmappedSupplierProductStatusChanges(): void
    {
        // Unmapped supplier product (product => null)
        $sp = SupplierProductFactory::createOne(['product' => null, 'isActive' => false]);

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new SupplierProductStatusWasChanged(
            $this->supplierProducts,
            $this->calculator,
            $flusher
        );

        // Toggle status; setActive will not raise event when product is null
        $sp->setActive(true);

        foreach ($sp->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierProductStatusWasChangedEvent) {
                continue;
            }

            $listener($event);
        }
    }

    public function testDoesNotFlushWhenStatusUnchanged(): void
    {
        $product = ProductFactory::createOne();
        $sp = SupplierProductFactory::createOne(['product' => $product, 'isActive' => true]);

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new SupplierProductStatusWasChanged(
            $this->supplierProducts,
            $this->calculator,
            $flusher
        );

        // Same value -> SupplierProduct::setActive does nothing, no event raised
        $sp->setActive(true);

        foreach ($sp->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierProductStatusWasChangedEvent) {
                continue;
            }

            $listener($event);
        }
    }
}
