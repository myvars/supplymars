<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain\Metric;

use App\Reporting\Domain\Metric\SalesType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SalesTypeTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = SalesType::cases();

        self::assertCount(6, $cases);
        self::assertContains(SalesType::PRODUCT, $cases);
        self::assertContains(SalesType::CATEGORY, $cases);
        self::assertContains(SalesType::SUBCATEGORY, $cases);
        self::assertContains(SalesType::MANUFACTURER, $cases);
        self::assertContains(SalesType::SUPPLIER, $cases);
        self::assertContains(SalesType::ALL, $cases);
    }

    public function testCaseValuesAreCorrect(): void
    {
        self::assertSame('product', SalesType::PRODUCT->value);
        self::assertSame('category', SalesType::CATEGORY->value);
        self::assertSame('subcategory', SalesType::SUBCATEGORY->value);
        self::assertSame('manufacturer', SalesType::MANUFACTURER->value);
        self::assertSame('supplier', SalesType::SUPPLIER->value);
        self::assertSame('all', SalesType::ALL->value);
    }

    public function testFromReturnsCorrectCase(): void
    {
        self::assertSame(SalesType::PRODUCT, SalesType::from('product'));
        self::assertSame(SalesType::CATEGORY, SalesType::from('category'));
        self::assertSame(SalesType::SUBCATEGORY, SalesType::from('subcategory'));
        self::assertSame(SalesType::MANUFACTURER, SalesType::from('manufacturer'));
        self::assertSame(SalesType::SUPPLIER, SalesType::from('supplier'));
        self::assertSame(SalesType::ALL, SalesType::from('all'));
    }

    #[DataProvider('invalidValueProvider')]
    public function testTryFromReturnsNullForInvalidValue(string $value): void
    {
        $result = SalesType::tryFrom($value);

        self::assertNull($result);
    }

    /**
     * @return iterable<array{string}>
     */
    public static function invalidValueProvider(): iterable
    {
        yield ['invalid'];
        yield [''];
        yield ['PRODUCT'];
    }
}
