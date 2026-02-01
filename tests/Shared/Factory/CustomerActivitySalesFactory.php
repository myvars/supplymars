<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Model\SalesType\CustomerActivitySales;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerActivitySales>
 */
final class CustomerActivitySalesFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return CustomerActivitySales::class;
    }

    protected function defaults(): array
    {
        return [
            'dateString' => self::faker()->date(),
            'totalCustomers' => self::faker()->numberBetween(100, 1000),
            'activeCustomers' => self::faker()->numberBetween(10, 100),
            'newCustomers' => self::faker()->numberBetween(1, 20),
            'returningCustomers' => self::faker()->numberBetween(5, 50),
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
