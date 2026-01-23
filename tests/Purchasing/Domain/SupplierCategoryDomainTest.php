<?php

namespace App\Tests\Purchasing\Domain;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use PHPUnit\Framework\TestCase;

final class SupplierCategoryDomainTest extends TestCase
{
    private function stubSupplier(?int $id = null): Supplier
    {
        $supplier = $this->createStub(Supplier::class);

        if ($id !== null) {
            $supplier->method('getId')->willReturn($id);
        }

        return $supplier;
    }

    public function testCreateTrimsName(): void
    {
        $supplier = $this->stubSupplier();

        $category = SupplierCategory::create(
            name: '  Widgets  ',
            supplier: $supplier,
        );

        self::assertSame('Widgets', $category->getName());
    }

    public function testUpdateChangesNameAndSupplier(): void
    {
        $supplierA = $this->stubSupplier();
        $supplierB = $this->stubSupplier(42);

        $category = SupplierCategory::create(
            name: 'Old',
            supplier: $supplierA,
        );

        $category->update(
            name: 'New',
            supplier: $supplierB,
        );

        self::assertSame('New', $category->getName());
        self::assertSame($supplierB->getId(), $category->getSupplier()->getId());
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name cannot be empty');

        $supplier = $this->stubSupplier();

        SupplierCategory::create(
            name: '',
            supplier: $supplier,
        );
    }
}
