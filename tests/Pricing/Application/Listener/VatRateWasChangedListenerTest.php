<?php

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Application\Listener\VatRateWasChanged;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class VatRateWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private VatRateRepository $vatRates;

    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->vatRates = self::getContainer()->get(VatRateRepository::class);
        $this->calculator = self::getContainer()->get(MarkupCalculator::class);
    }

    public function testRecalculatesActiveProductsWhenVatRateChanges(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $vatRate = $product->getCategory()->getVatRate();

        $before = $product->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new VatRateWasChanged($this->vatRates, $this->calculator, $flusher);

        // Trigger event by changing the rate
        $vatRate->update(
            name: $vatRate->getName(),
            rate: '25.00'
        );

        foreach ($vatRate->releaseDomainEvents() as $event) {
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloaded */
        $reloaded = $this->em->getRepository(Product::class)->find($product->getId());
        self::assertNotSame($before, $reloaded->getSellPriceIncVat());
    }

    public function testDoesNotFlushWhenNoRelevantChanges(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $vatRate = $product->getCategory()->getVatRate();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new VatRateWasChanged($this->vatRates, $this->calculator, $flusher);

        // Same rate -> no event or event not produced
        $vatRate->update(
            name: $vatRate->getName(),
            rate: $vatRate->getRate()
        );

        foreach ($vatRate->releaseDomainEvents() as $event) {
            $listener($event);
        }
    }

    public function testRecalculatesAcrossMultipleCategories(): void
    {
        $vatRate = VatRateFactory::new()->withStandardRate()->create();

        $categoryA = CategoryFactory::createOne(['vatRate' => $vatRate]);
        $categoryB = CategoryFactory::createOne(['vatRate' => $vatRate]);

        $productA = ProductFactory::new()->withActiveSource()->create(['category' => $categoryA]);
        $beforeA = $productA->getSellPriceIncVat();

        $productB = ProductFactory::new()->withActiveSource()->create(['category' => $categoryB]);
        $beforeB = $productB->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new VatRateWasChanged($this->vatRates, $this->calculator, $flusher);

        $vatRate->update($vatRate->getName(), '15.00');

        foreach ($vatRate->releaseDomainEvents() as $event) {
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
}
