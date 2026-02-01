<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerSalesSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerSalesSummary>
 */
final class CustomerSalesSummaryFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return CustomerSalesSummary::class;
    }

    protected function defaults(): array
    {
        return [
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'dateString' => new \DateTime()->modify('-29 days')->format('Y-m-d'),
            'totalCustomers' => self::faker()->numberBetween(100, 1000),
            'activeCustomers' => self::faker()->numberBetween(10, 100),
            'newCustomers' => self::faker()->numberBetween(1, 20),
            'returningCustomers' => self::faker()->numberBetween(5, 50),
            'totalRevenue' => (string) (self::faker()->numberBetween(10000, 1000000) / 100),
            'averageClv' => (string) (self::faker()->numberBetween(100, 10000) / 100),
            'averageAov' => (string) (self::faker()->numberBetween(100, 5000) / 100),
            'repeatRate' => (string) (self::faker()->numberBetween(0, 10000) / 100),
            'reviewRate' => (string) (self::faker()->numberBetween(0, 5000) / 100),
            'averageOrdersPerCustomer' => (string) (self::faker()->numberBetween(100, 500) / 100),
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
