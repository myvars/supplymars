<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Metric\CustomerSegment;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\CustomerSegmentSummary;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerSegmentSummary>
 */
final class CustomerSegmentSummaryFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return CustomerSegmentSummary::class;
    }

    protected function defaults(): array
    {
        return [
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'segment' => self::faker()->randomElement(CustomerSegment::cases()),
            'dateString' => new \DateTime()->modify('-29 days')->format('Y-m-d'),
            'customerCount' => self::faker()->numberBetween(10, 200),
            'orderCount' => self::faker()->numberBetween(20, 500),
            'orderValue' => (string) (self::faker()->numberBetween(10000, 500000) / 100),
            'averageOrderValue' => (string) (self::faker()->numberBetween(100, 5000) / 100),
            'averageItemsPerOrder' => (string) (self::faker()->numberBetween(100, 500) / 100),
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
