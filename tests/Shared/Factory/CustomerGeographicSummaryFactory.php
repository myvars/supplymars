<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerGeographicSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerGeographicSummary>
 */
final class CustomerGeographicSummaryFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return CustomerGeographicSummary::class;
    }

    protected function defaults(): array
    {
        return [
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'city' => self::faker()->city(),
            'dateString' => new \DateTime()->modify('-29 days')->format('Y-m-d'),
            'customerCount' => self::faker()->numberBetween(10, 200),
            'orderCount' => self::faker()->numberBetween(20, 500),
            'orderValue' => (string) (self::faker()->numberBetween(10000, 500000) / 100),
            'averageOrderValue' => (string) (self::faker()->numberBetween(100, 5000) / 100),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            );
    }
}
