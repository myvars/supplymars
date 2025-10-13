<?php

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriceModelDomainTest extends TestCase
{
    #[DataProvider('nameProvider')]
    public function testName(PriceModel $model, string $expectedName): void
    {
        $this->assertSame($expectedName, $model->getName());
    }

    public static function nameProvider(): array
    {
        return [
            [PriceModel::NONE, 'None'],
            [PriceModel::DEFAULT, 'Default (Cost+)'],
            [PriceModel::PRETTY_00, 'Pretty 00'],
            [PriceModel::PRETTY_10, 'Pretty 10'],
            [PriceModel::PRETTY_49, 'Pretty 49'],
            [PriceModel::PRETTY_95, 'Pretty 95'],
            [PriceModel::PRETTY_99, 'Pretty 99'],
        ];
    }

    #[DataProvider('descriptionProvider')]
    public function testDescription(PriceModel $model, string $expectedDescription): void
    {
        $this->assertSame($expectedDescription, $model->getDescription());
    }

    public static function descriptionProvider(): array
    {
        return [
            [PriceModel::NONE, 'No Price Model'],
            [PriceModel::DEFAULT, 'The default Cost+ price model'],
            [PriceModel::PRETTY_00, 'A pretty price model with .00 rounding'],
            [PriceModel::PRETTY_10, 'A pretty price model with .10 rounding'],
            [PriceModel::PRETTY_49, 'A pretty price model with .49 or .99 rounding'],
            [PriceModel::PRETTY_95, 'A pretty price model with .95 rounding'],
            [PriceModel::PRETTY_99, 'A pretty price model with .99 rounding'],
        ];
    }

    #[DataProvider('prettyPriceProvider')]
    public function testGetPrettyPrice(PriceModel $model, string $price, string $expected): void
    {
        $this->assertSame($expected, $model->getPrettyPrice($price));
    }

    public static function prettyPriceProvider(): array
    {
        return [
            // Identity models
            [PriceModel::NONE, '10.50', '10.50'],
            [PriceModel::DEFAULT, '10.50', '10.50'],
            [PriceModel::NONE, '0.01', '0.01'],
            [PriceModel::DEFAULT, '1234.56', '1234.56'],

            // PRETTY_00: round up to next whole if any cents
            [PriceModel::PRETTY_00, '10.00', '10.00'],
            [PriceModel::PRETTY_00, '10.01', '11.00'],
            [PriceModel::PRETTY_00, '10.50', '11.00'],
            [PriceModel::PRETTY_00, '10.99', '11.00'],

            // PRETTY_10: round up to next tenth if any remainder beyond 1 dp
            [PriceModel::PRETTY_10, '10.00', '10.00'],
            [PriceModel::PRETTY_10, '10.01', '10.10'],
            [PriceModel::PRETTY_10, '10.09', '10.10'],
            [PriceModel::PRETTY_10, '10.10', '10.10'],
            [PriceModel::PRETTY_10, '10.11', '10.20'],
            [PriceModel::PRETTY_10, '10.50', '10.50'],

            // PRETTY_49: < .50 -> .49, >= .50 -> .99
            [PriceModel::PRETTY_49, '10.00', '10.49'],
            [PriceModel::PRETTY_49, '10.01', '10.49'],
            [PriceModel::PRETTY_49, '10.49', '10.49'],
            [PriceModel::PRETTY_49, '10.50', '10.99'],
            [PriceModel::PRETTY_49, '10.99', '10.99'],
            [PriceModel::PRETTY_49, '9.50',  '9.99'],

            // PRETTY_95: <= .95 -> .95, > .95 -> +1.95
            [PriceModel::PRETTY_95, '10.00', '10.95'],
            [PriceModel::PRETTY_95, '10.01', '10.95'],
            [PriceModel::PRETTY_95, '10.95', '10.95'],
            [PriceModel::PRETTY_95, '10.96', '11.95'],

            // PRETTY_99: always integer + .99 (non-decreasing)
            [PriceModel::PRETTY_99, '0.01', '0.99'],
            [PriceModel::PRETTY_99, '10.00', '10.99'],
            [PriceModel::PRETTY_99, '10.01', '10.99'],
            [PriceModel::PRETTY_99, '10.99', '10.99'],
        ];
    }

    public function testInvalidPriceZeroVariants(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PriceModel::NONE->getPrettyPrice('0.00');
    }

    public function testInvalidPriceNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PriceModel::DEFAULT->getPrettyPrice('-1.00');
    }
}
