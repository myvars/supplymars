<?php

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Event\SubcategoryPricingWasChangedEvent;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Pricing\Application\Listener\SubcategoryPricingWasChanged;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class SubcategoryPricingWasChangedListenerTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private SubcategoryRepository $subcategories;

    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->subcategories = self::getContainer()->get(SubcategoryRepository::class);
        $this->calculator = self::getContainer()->get(MarkupCalculator::class);
    }

    public function testRecalculatesWhenSubcategoryMarkupChangesForActiveTarget(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $subcategory = $product->getSubcategory();

        $before = $product->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SubcategoryPricingWasChanged($this->subcategories, $this->calculator, $flusher);

        $subcategory->changePricing(
            defaultMarkup: '7.500',
            priceModel: $subcategory->getPriceModel(),
            isActive: $subcategory->isActive()
        );

        foreach ($subcategory->releaseDomainEvents() as $event) {
            \assert($event instanceof SubcategoryPricingWasChangedEvent);
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloaded */
        $reloaded = $this->em->getRepository(Product::class)->find($product->getId());
        self::assertNotSame($before, $reloaded->getSellPriceIncVat());
    }

    public function testRecalculatesWhenSubcategoryPriceModelChangesForActiveTarget(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $subcategory = $product->getSubcategory();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new SubcategoryPricingWasChanged($this->subcategories, $this->calculator, $flusher);

        $subcategory->changePricing(
            defaultMarkup: $subcategory->getDefaultMarkup(),
            priceModel: PriceModel::PRETTY_99,
            isActive: $subcategory->isActive()
        );

        foreach ($subcategory->releaseDomainEvents() as $event) {
            \assert($event instanceof SubcategoryPricingWasChangedEvent);
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloaded */
        $reloaded = $this->em->getRepository(Product::class)->find($product->getId());
        // Verify PRETTY_99 price model was applied (price ends in .99)
        self::assertStringEndsWith('.99', $reloaded->getSellPriceIncVat());
    }

    public function testDoesNotFlushWhenNoRelevantChanges(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $subcategory = $product->getSubcategory();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new SubcategoryPricingWasChanged($this->subcategories, $this->calculator, $flusher);

        // No effective changes -> no event or event yields no updates
        $subcategory->changePricing(
            defaultMarkup: $subcategory->getDefaultMarkup(),
            priceModel: $subcategory->getPriceModel(),
            isActive: $subcategory->isActive()
        );

        foreach ($subcategory->releaseDomainEvents() as $event) {
            \assert($event instanceof SubcategoryPricingWasChangedEvent);
            $listener($event);
        }
    }
}
