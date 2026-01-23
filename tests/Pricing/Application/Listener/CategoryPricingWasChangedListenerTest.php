<?php

namespace App\Tests\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Category\Event\CategoryPricingWasChangedEvent;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Pricing\Application\Listener\CategoryPricingWasChanged;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryPricingWasChangedListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private CategoryRepository $categories;

    private MarkupCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->categories = self::getContainer()->get(CategoryRepository::class);
        $this->calculator = self::getContainer()->get(MarkupCalculator::class);
    }

    public function testRecalculatesActiveProductsWhenVatRateChanges(): void
    {
        $category = CategoryFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create(['category' => $category]);

        $before = $product->getSellPriceIncVat();

        $newVat = VatRateFactory::new()->with(['rate' => '25.00'])->create();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new CategoryPricingWasChanged($this->categories, $this->calculator, $flusher);

        $category->changePricing(
            vatRate: $newVat,
            defaultMarkup: $category->getDefaultMarkup(),
            priceModel: $category->getPriceModel(),
            isActive: $category->isActive()
        );

        foreach ($category->releaseDomainEvents() as $event) {
            \assert($event instanceof CategoryPricingWasChangedEvent);
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
        $category = CategoryFactory::createOne();
        ProductFactory::new()->withActiveSource()->create(['category' => $category]);

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $listener = new CategoryPricingWasChanged($this->categories, $this->calculator, $flusher);

        // Same values cause no event to be raised, or event produces no updates
        $category->changePricing(
            vatRate: $category->getVatRate(),
            defaultMarkup: $category->getDefaultMarkup(),
            priceModel: $category->getPriceModel(),
            isActive: $category->isActive()
        );

        foreach ($category->releaseDomainEvents() as $event) {
            \assert($event instanceof CategoryPricingWasChangedEvent);
            $listener($event);
        }
    }

    public function testRecalculatesWhenCategoryMarkupAndPriceModelAreActiveTargets(): void
    {
        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->withActiveSource()->create(['category' => $category]);

        $before = $product->getSellPriceIncVat();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $listener = new CategoryPricingWasChanged($this->categories, $this->calculator, $flusher);

        $category->changePricing(
            vatRate: $category->getVatRate(),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: $category->isActive()
        );

        foreach ($category->releaseDomainEvents() as $event) {
            \assert($event instanceof CategoryPricingWasChangedEvent);
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        /** @var Product $reloaded */
        $reloaded = $this->em->getRepository(Product::class)->find($product->getId());
        self::assertNotSame($before, $reloaded->getSellPriceIncVat());
    }
}
