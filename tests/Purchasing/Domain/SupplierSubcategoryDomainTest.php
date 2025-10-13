<?php

namespace App\Tests\Purchasing\Domain;


use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use PHPUnit\Framework\TestCase;

final class SupplierSubcategoryDomainTest extends TestCase
{
    private function stubSupplier(?int $id = null): Supplier
    {
        $supplier = $this->createStub(Supplier::class);

        if ($id !== null) {
            $supplier->method('getId')->willReturn($id);
        }

        return $supplier;
    }

    private function stubCategory(?int $id = null): SupplierCategory
    {
        $category = $this->createStub(SupplierCategory::class);

        if ($id !== null) {
            $category->method('getId')->willReturn($id);
        }

        return $category;
    }

    public function testCreateTrimsName(): void
    {
        $supplier = $this->stubSupplier();
        $category = $this->stubCategory();

        $sub = SupplierSubcategory::create(
            name: '  Gadgets  ',
            supplier: $supplier,
            supplierCategory: $category,
        );

        self::assertSame('Gadgets', $sub->getName());
    }

    public function testUpdateChangesNameSupplierAndCategory(): void
    {
        $supplierA = $this->stubSupplier();
        $supplierB = $this->stubSupplier(42);
        $categoryA = $this->stubCategory();
        $categoryB = $this->stubCategory(43);

        $subcategory = SupplierSubcategory::create(
            name: 'Old',
            supplier: $supplierA,
            supplierCategory: $categoryA,
        );

        $subcategory->update(
            name: 'New',
            supplier: $supplierB,
            supplierCategory: $categoryB,
        );

        self::assertSame('New', $subcategory->getName());
        self::assertSame($supplierB->getId(), $subcategory->getSupplier()?->getId());
        self::assertSame($categoryB->getId(), $subcategory->getSupplierCategory()?->getId());
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subcategory name cannot be empty');

        $supplier = $this->stubSupplier();
        $category = $this->stubCategory();

        SupplierSubcategory::create(
            name: '',
            supplier: $supplier,
            supplierCategory: $category,
        );
    }
}
