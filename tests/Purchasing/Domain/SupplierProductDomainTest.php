<?php

namespace App\Tests\Purchasing\Domain;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductPricingWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStatusWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStockWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Shared\Domain\Event\AbstractDomainEvent;
use PHPUnit\Framework\TestCase;

final class SupplierProductDomainTest extends TestCase
{
    private function stubActiveSupplier(): Supplier
    {
        $supplier = $this->createStub(Supplier::class);
        $supplier->method('isActive')->willReturn(true);

        return $supplier;
    }

    private function stubInactiveSupplier(): Supplier
    {
        $supplier = $this->createStub(Supplier::class);
        $supplier->method('isActive')->willReturn(false);

        return $supplier;
    }

    private function stubCategory(): SupplierCategory
    {
        return $this->createStub(SupplierCategory::class);
    }

    private function stubSubcategory(): SupplierSubcategory
    {
        return $this->createStub(SupplierSubcategory::class);
    }

    private function stubManufacturer(): SupplierManufacturer
    {
        return $this->createStub(SupplierManufacturer::class);
    }

    private function stubProduct(?int $id = null): Product
    {
        $product = $this->createStub(Product::class);

        if ($id !== null) {
            $product->method('getId')->willReturn($id);
        }

        return $product;
    }

    public function testCreateTrimsNameAndSetsActive(): void
    {
        $supplierProduct = SupplierProduct::create(
            name: '  Test Product  ',
            productCode: 'CODE123',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR-1',
            weight: 10,
            supplier: $this->stubActiveSupplier(),
            stock: 5,
            leadTimeDays: 2,
            cost: '12.34',
            product: null,
            isActive: true,
        );

        self::assertSame('Test Product', $supplierProduct->getName());
        self::assertTrue($supplierProduct->isActive());
    }

    public function testCreateEmitsInitialEvents(): void
    {
        $supplierProduct = SupplierProduct::create(
            name: 'New',
            productCode: 'PC',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR',
            weight: 1,
            supplier: $this->stubActiveSupplier(),
            stock: 10,
            leadTimeDays: 3,
            cost: '5.00',
            product: null,
            isActive: true,
        );

        $events = $supplierProduct->releaseDomainEvents();
        self::assertCount(3, $events);
        self::assertNotEmpty(array_filter($events, fn (AbstractDomainEvent $e): bool => $e instanceof SupplierProductPricingWasChangedEvent));
        self::assertNotEmpty(array_filter($events, fn (AbstractDomainEvent $e): bool => $e instanceof SupplierProductStockWasChangedEvent));
    }

    public function testUpdateEmitsEventsWhenValuesChange(): void
    {
        $supplierProduct = SupplierProduct::create(
            name: 'Base',
            productCode: 'PC',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR',
            weight: 1,
            supplier: $this->stubActiveSupplier(),
            stock: 10,
            leadTimeDays: 3,
            cost: '5.00',
            product: $this->stubProduct(1),
            isActive: true,
        );
        $supplierProduct->releaseDomainEvents(); // clear initial

        $supplierProduct->update(
            name: 'Base Updated',
            productCode: 'PC2',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR2',
            weight: 2,
            supplier: $this->stubActiveSupplier(),
            stock: 25,
            leadTimeDays: 5,
            cost: '7.50',
            product: $this->stubProduct(2),
            isActive: false,
        );

        $events = $supplierProduct->releaseDomainEvents();
        self::assertCount(4, $events);
        self::assertNotEmpty(array_filter($events, fn (AbstractDomainEvent $e): bool => $e instanceof SupplierProductPricingWasChangedEvent));
        self::assertNotEmpty(array_filter($events, fn (AbstractDomainEvent $e): bool => $e instanceof SupplierProductStockWasChangedEvent));
        self::assertNotEmpty(array_filter($events, fn (AbstractDomainEvent $e): bool => $e instanceof SupplierProductStatusWasChangedEvent));
    }

    public function testUpdateNoEventsWhenNothingChanges(): void
    {
        $supplier = $this->stubActiveSupplier();
        $category = $this->stubCategory();
        $subcategory = $this->stubSubcategory();
        $manufacturer = $this->stubManufacturer();
        $product = $this->stubProduct(1);

        $supplierProduct = SupplierProduct::create(
            name: 'Same',
            productCode: 'PC',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR',
            weight: 1,
            supplier: $supplier,
            stock: 10,
            leadTimeDays: 3,
            cost: '5.00',
            product: $product,
            isActive: true,
        );
        $supplierProduct->releaseDomainEvents();

        $supplierProduct->update(
            name: 'Same',
            productCode: 'PC',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR',
            weight: 1,
            supplier: $supplier,
            stock: 10,
            leadTimeDays: 3,
            cost: '5.00',
            product: $product,
            isActive: true,
        );

        self::assertCount(0, $supplierProduct->releaseDomainEvents());
    }

    public function testInvalidNameThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Product name cannot be empty');

        SupplierProduct::create(
            name: '',
            productCode: 'PC',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR',
            weight: 1,
            supplier: $this->stubActiveSupplier(),
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            product: null,
            isActive: true,
        );
    }

    public function testNegativeWeightThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Weight cannot be negative');

        SupplierProduct::create(
            name: 'X',
            productCode: 'PC',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR',
            weight: -5,
            supplier: $this->stubActiveSupplier(),
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            product: null,
            isActive: true,
        );
    }

    public function testNegativeLeadTimeThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Lead time days cannot be negative');

        SupplierProduct::create(
            name: 'X',
            productCode: 'PC',
            supplierCategory: $this->stubCategory(),
            supplierSubcategory: $this->stubSubcategory(),
            supplierManufacturer: $this->stubManufacturer(),
            mfrPartNumber: 'MFR',
            weight: 1,
            supplier: $this->stubActiveSupplier(),
            stock: 1,
            leadTimeDays: -1,
            cost: '1.00',
            product: null,
            isActive: true,
        );
    }

    public function testUpdateStockRaisesEventOnlyOnChange(): void
    {
        $supplier = $this->stubActiveSupplier();
        $category = $this->stubCategory();
        $subcategory = $this->stubSubcategory();
        $manufacturer = $this->stubManufacturer();

        $supplierProduct = SupplierProduct::create(
            name: 'Name',
            productCode: 'CODE',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR',
            weight: 100,
            supplier: $supplier,
            stock: 5,
            leadTimeDays: 7,
            cost: '10.00',
            product: null,
            isActive: true
        );

        $supplierProduct->releaseDomainEvents();

        $supplierProduct->updateStock(5);
        self::assertCount(0, $supplierProduct->releaseDomainEvents());

        $supplierProduct->updateStock(6);
        $events = $supplierProduct->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierProductStockWasChangedEvent::class, $events[0]);
    }

    public function testUpdateCostRaisesEventOnlyOnChange(): void
    {
        $supplier = $this->stubActiveSupplier();
        $category = $this->stubCategory();
        $subcategory = $this->stubSubcategory();
        $manufacturer = $this->stubManufacturer();

        $supplierProduct = SupplierProduct::create(
            name: 'Name',
            productCode: 'CODE',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR',
            weight: 100,
            supplier: $supplier,
            stock: 5,
            leadTimeDays: 7,
            cost: '10.00',
            product: null,
            isActive: true
        );

        $supplierProduct->releaseDomainEvents();

        $supplierProduct->updateCost('10.00');
        self::assertCount(0, $supplierProduct->releaseDomainEvents());

        $supplierProduct->updateCost('11.00');
        $events = $supplierProduct->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierProductStockWasChangedEvent::class, $events[0]);
    }

    public function testHelperPredicates(): void
    {
        $activeSupplier = $this->stubActiveSupplier();
        $inactiveSupplier = $this->stubInactiveSupplier();

        $category = $this->stubCategory();
        $subcategory = $this->stubSubcategory();
        $manufacturer = $this->stubManufacturer();
        $product = $this->stubProduct();

        $supplierProductA = SupplierProduct::create(
            name: 'A',
            productCode: 'A1',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR1',
            weight: 10,
            supplier: $activeSupplier,
            stock: 3,
            leadTimeDays: 2,
            cost: '1.00',
            product: $product,
            isActive: true
        );

        self::assertTrue($supplierProductA->hasPositiveCost());
        self::assertTrue($supplierProductA->hasActiveSupplier());
        self::assertTrue($supplierProductA->hasStock());
        self::assertTrue($supplierProductA->isMapped());

        $supplierProductB = SupplierProduct::create(
            name: 'B',
            productCode: 'B1',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR2',
            weight: 10,
            supplier: $inactiveSupplier,
            stock: 0,
            leadTimeDays: 2,
            cost: '0.00',
            product: null,
            isActive: true
        );

        self::assertFalse($supplierProductB->hasPositiveCost());
        self::assertFalse($supplierProductB->hasActiveSupplier());
        self::assertFalse($supplierProductB->hasStock());
        self::assertFalse($supplierProductB->isMapped());
    }
}
