<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductSalesSummary>
 */
final class ProductSalesSummaryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ProductSalesSummary::class;
    }

    protected function defaults(): array
    {
        return [
            'productSalesType' => ProductSalesType::create(
                self::faker()->randomElement(SalesType::cases()),
                self::faker()->randomElement(SalesDuration::cases()),
            ),
            'salesId' => self::faker()->numberBetween(1, 1000),
            'dateString' => self::faker()->date(),
            'salesQty' => self::faker()->numberBetween(1, 1000),
            'salesCost' => self::faker()->randomFloat(2),
            'salesValue' => self::faker()->randomFloat(2),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
