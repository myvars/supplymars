<?php

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Application\Listener\SupplierStatusWasChanged;
use App\Purchasing\Domain\Model\Supplier\Event\SupplierStatusWasChangedEvent;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class SupplierStatusWasChangedListenerTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private SupplierRepository $suppliers;

    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->suppliers = $container->get(SupplierRepository::class);
        $this->calculator = $container->get(MarkupCalculator::class);
    }

    public function testRecalculatesAllMappedProductsWhenSupplierIsActivated(): void
    {
        $supplier = SupplierFactory::CreateOne(['isActive' => false]);

        $productA = ProductFactory::createOne();
        $productB = ProductFactory::CreateOne();

        SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $productA, 'cost' => '10.00']);
        SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $productB, 'cost' => '20.00']);

        $beforeA = $productA->getSellPriceIncVat();
        $beforeB = $productB->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SupplierStatusWasChanged(
            $this->suppliers,
            $this->calculator,
            $flusher
        );

        // Activate supplier -> event raised
        $supplier->update(
            name: $supplier->getName(),
            isActive: true,
        );

        foreach ($supplier->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierStatusWasChangedEvent) {
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

        self::assertNotSame($beforeA, $reloadedA->getSellPriceIncVat());
        self::assertNotSame($beforeB, $reloadedB->getSellPriceIncVat());
    }

    public function testRecalculatesOnlyProductsWithActiveSourceWhenSupplierIsDeactivated(): void
    {
        $product = ProductFactory::createOne();
        $supplier1 = SupplierFactory::createOne();
        $spActiveSource = SupplierProductFactory::createOne([
            'supplier' => $supplier1,
            'product' => $product,
            'cost' => '10.00',
        ]);
        $supplier2 = SupplierFactory::createOne();
        $sp2 = SupplierProductFactory::createOne([
            'supplier' => $supplier2,
            'product' => $product,
            'cost' => '20.00',
        ]);

        $before = $product->getSellPriceIncVat();

        // Sanity: mapped IDs
        self::assertSame($spActiveSource->getId(), $product->getActiveProductSource()?->getId());

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SupplierStatusWasChanged(
            $this->suppliers,
            $this->calculator,
            $flusher
        );

        // Deactivate supplier -> event raised
        $supplier1->update(
            name: $supplier1->getName(),
            isActive: false,
        );

        foreach ($supplier1->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierStatusWasChangedEvent) {
                continue;
            }

            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloaded */
        $reloaded = $this->em->getRepository(Product::class)->find($product->getId());

        self::assertNotSame($before, $reloaded->getSellPriceIncVat());
        self::assertSame($sp2->getId(), $product->getActiveProductSource()?->getId());
    }

    public function testDoesNotFlushWhenSupplierHasOnlyUnmappedProducts(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => false]);

        // Unmapped supplier products (product => null)
        SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => null, 'isActive' => true]);
        SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => null, 'isActive' => false]);

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new SupplierStatusWasChanged(
            $this->suppliers,
            $this->calculator,
            $flusher
        );

        // Toggle status -> event raised
        $supplier->update(
            name: $supplier->getName(),
            isActive: true,
        );

        foreach ($supplier->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierStatusWasChangedEvent) {
                continue;
            }

            $listener($event);
        }
    }

    public function testDoesNotFlushWhenStatusUnchanged(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new SupplierStatusWasChanged(
            $this->suppliers,
            $this->calculator,
            $flusher
        );

        // Same value -> Supplier::setActive early‑returns, no event raised
        $supplier->update(
            name: $supplier->getName(),
            isActive: true,
        );

        foreach ($supplier->releaseDomainEvents() as $event) {
            if (!$event instanceof SupplierStatusWasChangedEvent) {
                continue;
            }

            $listener($event);
        }
    }
}
