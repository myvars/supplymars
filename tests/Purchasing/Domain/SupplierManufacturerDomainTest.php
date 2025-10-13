<?php

namespace App\Tests\Purchasing\Domain;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use PHPUnit\Framework\TestCase;

final class SupplierManufacturerDomainTest extends TestCase
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

        $manufacturer = SupplierManufacturer::create(
            name: '  ACME  ',
            supplier: $supplier,
        );

        self::assertSame('ACME', $manufacturer->getName());
    }

    public function testUpdateChangesNameAndSupplier(): void
    {
        $supplierA = $this->stubSupplier();
        $supplierB = $this->stubSupplier(42);

        $manufacturer = SupplierManufacturer::create(
            name: 'Old',
            supplier: $supplierA,
        );

        $manufacturer->update(
            name: 'New',
            supplier: $supplierB,
        );

        self::assertSame('New', $manufacturer->getName());
        self::assertSame($supplierB->getId(), $manufacturer->getSupplier()?->getId());
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manufacturer name cannot be empty');

        $supplier = $this->stubSupplier();

        SupplierManufacturer::create(
            name: '',
            supplier: $supplier,
        );
    }
}
