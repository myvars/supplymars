<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Model\SalesType\CustomerSales;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerSales>
 */
final class CustomerSalesFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return CustomerSales::class;
    }

    protected function defaults(): array
    {
        return [
            'customerId' => self::faker()->numberBetween(1, 1000),
            'dateString' => self::faker()->date(),
            'orderCount' => self::faker()->numberBetween(1, 10),
            'orderValue' => (string) (self::faker()->numberBetween(100, 100000) / 100),
            'itemCount' => self::faker()->numberBetween(1, 50),
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
