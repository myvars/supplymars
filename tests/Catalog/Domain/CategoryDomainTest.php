<?php

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Category\Event\CategoryPricingWasChangedEvent;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\TestCase;

class CategoryDomainTest extends TestCase
{
    private function stubUser(): User
    {
        return $this->createStub(User::class);
    }

    private function stubVatRate(string $rate = '20.000'): VatRate
    {
        $vatRate = $this->createStub(VatRate::class);
        $vatRate->method('getRate')->willReturn($rate);

        return $vatRate;
    }

    public function testCreateTrimsNameAndSetsActive(): void
    {
        $category = Category::create(
            name: '  Electronics  ',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate(),
            defaultMarkup: Category::DEFAULT_MARKUP,
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        self::assertSame('Electronics', $category->getName());
        self::assertTrue($category->isActive());
    }

    public function testCreateEmitsPricingEventForChangedFields(): void
    {
        $category = Category::create(
            name: 'Electronics',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate(),
            defaultMarkup: Category::DEFAULT_MARKUP,
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        $events = $category->releaseDomainEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(CategoryPricingWasChangedEvent::class, $event);
        self::assertTrue($event->isVatRateChanged());
        self::assertFalse($event->isMarkupChanged());
        self::assertFalse($event->isPriceModelChanged());
    }

    public function testChangePricingEmitsEventWhenValuesActuallyChange(): void
    {
        $category = Category::create(
            name: 'Electronics',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate('20.000'),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
        $category->releaseDomainEvents(); // clear initial

        $category->changePricing(
            vatRate: $this->stubVatRate('10.000'),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $events = $category->releaseDomainEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(CategoryPricingWasChangedEvent::class, $event);
        self::assertTrue($event->isVatRateChanged());
        self::assertTrue($event->isMarkupChanged());
        self::assertTrue($event->isPriceModelChanged());
    }

    public function testChangePricingNoEventWhenNothingChanges(): void
    {
        $category = Category::create(
            name: 'Electronics',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate('20.000'),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
        $category->releaseDomainEvents();

        $category->changePricing(
            vatRate: $category->getVatRate(),
            defaultMarkup: $category->getDefaultMarkup(),
            priceModel: $category->getPriceModel(),
            isActive: $category->isActive(),
        );

        self::assertCount(0, $category->releaseDomainEvents());
    }

    public function testInvalidNameThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Category name cannot be empty');

        Category::create(
            name: '',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
    }

    public function testNegativeMarkupThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Markup cannot be negative');

        Category::create(
            name: 'Electronics',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate(),
            defaultMarkup: '-1.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
    }

    public function testPriceModelNoneThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('A category must have a price model');

        Category::create(
            name: 'Electronics',
            owner: $this->stubUser(),
            vatRate: $this->stubVatRate(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );
    }
}
