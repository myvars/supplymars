<?php

namespace App\Tests\Unit\Enum;

use App\Enum\PriceModel;
use PHPUnit\Framework\TestCase;

class PriceModelTest extends TestCase
{
    /**
     * @dataProvider nameProvider
     */
    public function testName(PriceModel $model, string $expectedName): void
    {
        $this->assertEquals($expectedName, $model->getName());
    }

    public function nameProvider(): array
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

    /**
     * @dataProvider descriptionProvider
     */
    public function testDescription(PriceModel $model, string $expectedDescription): void
    {
        $this->assertEquals($expectedDescription, $model->getDescription());
    }

    public function descriptionProvider(): array
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

    /**
     * @dataProvider prettyPriceProvider
     */
    public function testGetPrettyPrice(PriceModel $model, string $price, string $expected): void
    {
        $this->assertEquals($expected, $model->getPrettyPrice($price));
    }

    public function prettyPriceProvider(): array
    {
        return [
            [PriceModel::NONE, '10.50', '10.50'],
            [PriceModel::DEFAULT, '10.50', '10.50'],
            [PriceModel::PRETTY_00, '10.00', '10.00'],
            [PriceModel::PRETTY_00, '10.01', '11.00'],
            [PriceModel::PRETTY_00, '10.50', '11.00'],
            [PriceModel::PRETTY_10, '10.00', '10.00'],
            [PriceModel::PRETTY_10, '10.01', '10.10'],
            [PriceModel::PRETTY_10, '10.50', '10.50'],
            [PriceModel::PRETTY_49, '10.00', '10.49'],
            [PriceModel::PRETTY_49, '10.01', '10.49'],
            [PriceModel::PRETTY_49, '10.49', '10.49'],
            [PriceModel::PRETTY_49, '10.50', '10.99'],
            [PriceModel::PRETTY_49, '10.99', '10.99'],
            [PriceModel::PRETTY_95, '10.01', '10.95'],
            [PriceModel::PRETTY_95, '10.95', '10.95'],
            [PriceModel::PRETTY_95, '10.96', '11.95'],
            [PriceModel::PRETTY_99, '10.01', '10.99'],
            [PriceModel::PRETTY_99, '10.99', '10.99'],
        ];
    }

    public function testInvalidPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PriceModel::NONE->getPrettyPrice('0');
    }
}