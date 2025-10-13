<?php

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use PHPUnit\Framework\TestCase;

final class ManufacturerDomainTest extends TestCase
{
    public function testCreateTrimsNameAndSetsActive(): void
    {
        $manufacturer = Manufacturer::create(
            name: '  Acme Corp  ',
            isActive: true
        );

        self::assertSame('Acme Corp', $manufacturer->getName());
        self::assertTrue($manufacturer->isActive());
    }

    public function testInvalidNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manufacturer name cannot be empty');

        Manufacturer::create(
            name: '',
            isActive: true
        );
    }
}
