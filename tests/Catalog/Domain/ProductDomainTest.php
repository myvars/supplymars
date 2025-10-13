<?php

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\TestCase;

class ProductDomainTest extends TestCase
{
    private function stubUser(): User
    {
        return $this->createStub(User::class);
    }

    private function stubManufacturer(): Manufacturer
    {
        return $this->createStub(Manufacturer::class);
    }

    private function stubVatRate(string $rate = '20.000'): VatRate
    {
        $vat = $this->createStub(VatRate::class);
        $vat->method('getRate')->willReturn($rate);

        return $vat;
    }

    private function stubCategory(?VatRate $vatRate = null): Category
    {
        $cat = $this->createStub(Category::class);
        $cat->method('getVatRate')->willReturn($vatRate ?? $this->stubVatRate('20.000'));
        $cat->method('getDefaultMarkup')->willReturn('3.000');
        $cat->method('getPriceModel')->willReturn(PriceModel::DEFAULT);
        $cat->method('isActive')->willReturn(true);

        return $cat;
    }

    private function stubSubcategory(): Subcategory
    {
        $sub = $this->createStub(Subcategory::class);
        $sub->method('hasDefaultMarkup')->willReturn(true);
        $sub->method('getDefaultMarkup')->willReturn('5.000');
        $sub->method('hasPriceModel')->willReturn(true);
        $sub->method('getPriceModel')->willReturn(PriceModel::DEFAULT);
        $sub->method('isActive')->willReturn(true);

        return $sub;
    }

    private function stubSupplier(bool $active = true): Supplier
    {
        $supplier = $this->createStub(Supplier::class);
        $supplier->method('isActive')->willReturn($active);

        return $supplier;
    }

    private function stubSupplierProduct(
        string $cost,
        int $stock,
        int $leadTimeDays,
        bool $active = true,
        ?Supplier $supplier = null
    ): SupplierProduct {
        $sp = $this->createStub(SupplierProduct::class);

        $sp->method('isActive')->willReturn($active);
        $sp->method('getSupplier')->willReturn($supplier ?? $this->stubSupplier(true));
        $sp->method('getCost')->willReturn($cost);
        $sp->method('getStock')->willReturn($stock);
        $sp->method('getLeadTimeDays')->willReturn($leadTimeDays);
        $sp->method('hasStock')->willReturn($stock > 0);
        $sp->method('hasPositiveCost')->willReturn(((float) $cost) > 0);

        return $sp;
    }

    private function stubCalculator(
        string $pretty = '99.99',
        string $customMarkup = '12.345',
        string $sell = '88.88'
    ): MarkupCalculator {
        $calc = $this->createStub(MarkupCalculator::class);
        $calc->method('calculatePrettyPrice')->willReturn($pretty);
        $calc->method('calculateCustomMarkup')->willReturn($customMarkup);
        $calc->method('calculateSellPrice')->willReturn($sell);

        return $calc;
    }

    public function testCreateTrimsNameAndSetsActive(): void
    {
        $product = Product::create(
            name: '  Widget  ',
            description: 'Desc',
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: $this->stubUser(),
            isActive: true,
        );

        self::assertSame('Widget', $product->getName());
        self::assertTrue($product->isActive());
    }

    public function testUpdateRecalculatesPrice(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: 'Desc',
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: $this->stubUser(),
            isActive: true,
        );

        $calc = $this->stubCalculator(pretty: '123.45', customMarkup: '7.500', sell: '100.00');

        $product->update(
            markupCalculator: $calc,
            name: 'Updated',
            description: 'Updated desc',
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-2',
            owner: $this->stubUser(),
            isActive: true,
        );

        self::assertSame('Updated', $product->getName());
        self::assertSame('MFR-2', $product->getMfrPartNumber());
        self::assertSame('7.500', $product->getMarkup());
        self::assertSame('100.00', $product->getSellPrice());
        self::assertSame('123.45', $product->getSellPriceIncVat());
    }

    public function testChangePricingUpdatesAndRecalculates(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: 'Desc',
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: $this->stubUser(),
            isActive: true,
        );

        $calc = $this->stubCalculator(pretty: '199.99', customMarkup: '12.000', sell: '160.00');

        $product->changePricing(
            markupCalculator: $calc,
            defaultMarkup: '8.000',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        self::assertSame('8.000', $product->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $product->getPriceModel());
        self::assertFalse($product->isActive());
        self::assertSame('12.000', $product->getMarkup());
        self::assertSame('160.00', $product->getSellPrice());
        self::assertSame('199.99', $product->getSellPriceIncVat());
    }

    public function testInvalidNameThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Product name cannot be empty');

        Product::create(
            name: '',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: $this->stubUser(),
            isActive: true,
        );
    }

    public function testNegativeDefaultMarkupThrows(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: 'Desc',
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: $this->stubUser(),
            isActive: true,
        );

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Markup cannot be negative');

        $product->changePricing(
            markupCalculator: $this->stubCalculator(),
            defaultMarkup: '-1.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
    }

    public function testHelperPredicates(): void
    {
        $with = Product::create(
            name: 'A',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'M1',
            owner: $this->stubUser(),
            isActive: true,
        );
        $with->changePricing(
            markupCalculator: $this->stubCalculator(),
            defaultMarkup: '1.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true
        );

        self::assertTrue($with->hasDefaultMarkup());
        self::assertTrue($with->hasOwner());
        self::assertTrue($with->hasPriceModel());

        $without = Product::create(
            name: 'B',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'M2',
            owner: null,
            isActive: false,
        );

        $without->changePricing(
            markupCalculator: $this->stubCalculator(),
            defaultMarkup: '0.000',
            priceModel: PriceModel::NONE,
            isActive: false
        );

        self::assertFalse($without->hasDefaultMarkup());
        self::assertFalse($without->hasOwner());
        self::assertFalse($without->hasPriceModel());
    }

    public function testActivePricingResolvesFromSubcategoryWhenProductHasNone(): void
    {
        $product = Product::create(
            name: 'P',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MP',
            owner: null,
            isActive: true,
        );

        $product->changePricing(
            markupCalculator: $this->stubCalculator(),
            defaultMarkup: '0.000',
            priceModel: PriceModel::NONE,
            isActive: true
        );

        self::assertSame('5.000', $product->getActiveMarkup());
        self::assertSame('SUBCATEGORY', $product->getActiveMarkupTarget());
        self::assertSame(PriceModel::DEFAULT, $product->getActivePriceModel());
        self::assertSame('SUBCATEGORY', $product->getActivePriceModelTarget());
    }

    public function testActivePricingPrefersProductOverSubcategoryAndCategory(): void
    {
        $product = Product::create(
            name: 'P',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MP',
            owner: null,
            isActive: true,
        );

        $product->changePricing(
            markupCalculator: $this->stubCalculator(),
            defaultMarkup: '2.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: true
        );

        self::assertSame('2.500', $product->getActiveMarkup());
        self::assertSame('PRODUCT', $product->getActiveMarkupTarget());
        self::assertSame(PriceModel::PRETTY_99, $product->getActivePriceModel());
        self::assertSame('PRODUCT', $product->getActivePriceModelTarget());
    }

    public function testRecalculateActiveSourceAppliesBestSource(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: null,
            isActive: true
        );

        $calc = $this->stubCalculator(pretty: '222.22', customMarkup: '6.000', sell: '180.00');

        $sp1 = $this->stubSupplierProduct(cost: '10.00', stock: 5, leadTimeDays: 3, active: true);
        $sp2 = $this->stubSupplierProduct(cost: '9.50', stock: 5, leadTimeDays: 5, active: true);

        $product->addSupplierProduct($calc, $sp1);
        $product->addSupplierProduct($calc, $sp2);

        self::assertSame($sp2, $product->getActiveProductSource());
        self::assertSame('9.50', $product->getCost());
        self::assertSame(5, $product->getStock());
        self::assertSame(5, $product->getLeadTimeDays());

        self::assertSame('6.000', $product->getMarkup());
        self::assertSame('180.00', $product->getSellPrice());
        self::assertSame('222.22', $product->getSellPriceIncVat());
    }

    public function testRecalculateActiveSourceRemovesWhenNoValidSources(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: null,
            isActive: true
        );

        $calc = $this->stubCalculator(pretty: '200.00', customMarkup: '5.000', sell: '160.00');

        $sp = $this->stubSupplierProduct(cost: '12.00', stock: 3, leadTimeDays: 4, active: true);
        $product->addSupplierProduct($calc, $sp);
        self::assertTrue($product->hasActiveProductSource());

        $product->removeSupplierProduct($calc, $sp);

        self::assertFalse($product->hasActiveProductSource());
        self::assertSame(0, $product->getStock());

        self::assertSame('5.000', $product->getMarkup());
        self::assertSame('160.00', $product->getSellPrice());
        self::assertSame('200.00', $product->getSellPriceIncVat());
    }

    public function testActiveSupplierProductsRequireBothSidesActive(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: null,
            isActive: true
        );

        $calc = $this->stubCalculator();

        $activeSupplier = $this->stubSupplier(true);
        $inactiveSupplier = $this->stubSupplier(false);

        $activeSp = $this->stubSupplierProduct('10.00', 1, 2, true, $activeSupplier);
        $inactiveSp = $this->stubSupplierProduct('11.00', 1, 2, false, $activeSupplier);
        $inactiveSupplierSp = $this->stubSupplierProduct('12.00', 1, 2, true, $inactiveSupplier);

        $product->addSupplierProduct($calc, $activeSp);
        $product->addSupplierProduct($calc, $inactiveSp);
        $product->addSupplierProduct($calc, $inactiveSupplierSp);

        $list = $product->getActiveSupplierProducts();
        self::assertCount(1, $list);
        self::assertSame($activeSp, $list->first());
    }

    public function testBestSourceMinQuantityAndTieBreakByStock(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: null,
            isActive: true
        );

        $calc = $this->stubCalculator();

        $sp1 = $this->stubSupplierProduct('9.50', 3, 4, true, $this->stubSupplier(true));
        $sp2 = $this->stubSupplierProduct('9.50', 7, 5, true, $this->stubSupplier(true));

        $product->addSupplierProduct($calc, $sp1);
        $product->addSupplierProduct($calc, $sp2);

        self::assertSame($sp2, $product->getBestSourceWithMinQuantity(3));
        self::assertNull($product->getBestSourceWithMinQuantity(0));
    }

    public function testIsValidProductPredicate(): void
    {
        $product = Product::create(
            name: 'Widget',
            description: null,
            category: $this->stubCategory(),
            subcategory: $this->stubSubcategory(),
            manufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            owner: null,
            isActive: true
        );

        $calc = $this->stubCalculator();

        $sp = $this->stubSupplierProduct('10.00', 2, 3, true, $this->stubSupplier(true));
        $product->addSupplierProduct($calc, $sp);

        self::assertTrue($product->isValidProduct());
    }
}
