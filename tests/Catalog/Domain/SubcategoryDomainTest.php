<?php

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Event\SubcategoryPricingWasChangedEvent;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\TestCase;

final class SubcategoryDomainTest extends TestCase
{
    private function stubCategory(): Category
    {
        return $this->createStub(Category::class);
    }

    private function stubUser(): User
    {
        return $this->createStub(User::class);
    }

    public function testCreateTrimsNameAndSetsActive(): void
    {
        $subcategory = Subcategory::create(
            name: '  Parts  ',
            category: $this->stubCategory(),
            owner: $this->stubUser(),
            defaultMarkup: Subcategory::DEFAULT_MARKUP,
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        self::assertSame('Parts', $subcategory->getName());
        self::assertTrue($subcategory->isActive());
    }

    public function testCreateEmitsPricingEventForChangedFields(): void
    {
        $subcategory = Subcategory::create(
            name: 'Parts',
            category: $this->stubCategory(),
            owner: $this->stubUser(),
            defaultMarkup: Subcategory::DEFAULT_MARKUP,
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        $events = $subcategory->releaseDomainEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(SubcategoryPricingWasChangedEvent::class, $event);
        self::assertFalse($event->isMarkupChanged());
        self::assertFalse($event->isPriceModelChanged());
    }

    public function testChangePricingEmitsEventWhenValuesActuallyChange(): void
    {
        $subcategory = Subcategory::create(
            name: 'Parts',
            category: $this->stubCategory(),
            owner: $this->stubUser(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );
        $subcategory->releaseDomainEvents(); // clear initial

        $subcategory->changePricing(
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $events = $subcategory->releaseDomainEvents();
        self::assertCount(1, $events);
        $event = $events[0];
        self::assertTrue($event->isMarkupChanged());
        self::assertTrue($event->isPriceModelChanged());
    }

    public function testChangePricingNoEventWhenNothingChanges(): void
    {
        $subcategory = Subcategory::create(
            name: 'Parts',
            category: $this->stubCategory(),
            owner: $this->stubUser(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );
        $subcategory->releaseDomainEvents();

        $subcategory->changePricing(
            defaultMarkup: $subcategory->getDefaultMarkup(),
            priceModel: $subcategory->getPriceModel(),
            isActive: $subcategory->isActive(),
        );

        self::assertCount(0, $subcategory->releaseDomainEvents());
    }

    public function testInvalidNameThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Subcategory name cannot be empty');

        Subcategory::create(
            name: '',
            category: $this->stubCategory(),
            owner: $this->stubUser(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );
    }

    public function testNegativeMarkupThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Markup cannot be negative');

        Subcategory::create(
            name: 'Parts',
            category: $this->stubCategory(),
            owner: $this->stubUser(),
            defaultMarkup: '-1.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );
    }

    public function testHelperPredicates(): void
    {
        $category = $this->stubCategory();
        $user = $this->stubUser();

        // With positive markup, owner, and non\-NONE price model
        $withValues = Subcategory::create(
            name: 'A',
            category: $category,
            owner: $user,
            defaultMarkup: '1.000',
            priceModel: PriceModel::PRETTY_99,
            isActive: true,
        );

        self::assertTrue($withValues->hasDefaultMarkup());
        self::assertTrue($withValues->hasOwner());
        self::assertTrue($withValues->hasPriceModel());

        // With zero markup, no owner, and NONE price model
        $withoutValues = Subcategory::create(
            name: 'B',
            category: $category,
            owner: null,
            defaultMarkup: '0.000',
            priceModel: PriceModel::NONE,
            isActive: false,
        );

        self::assertFalse($withoutValues->hasDefaultMarkup());
        self::assertFalse($withoutValues->hasOwner());
        self::assertFalse($withoutValues->hasPriceModel());
    }
}
