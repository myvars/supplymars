<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Model\SalesType\OrderSales;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<OrderSales>
 */
final class OrderSalesFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return OrderSales::class;
    }

    protected function defaults(): array
    {
        return [
            'dateString' => self::faker()->date(),
            'orderCount' => self::faker()->numberBetween(1, 1000),
            'orderValue' => self::faker()->numberBetween(1, 1000000) / 100,
            'averageOrderValue' => null,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if ($attributes['orderValue'] > 0) {
                    $attributes['averageOrderValue'] ??= $attributes['orderValue'] / $attributes['orderCount'];
                } else {
                    $attributes['averageOrderValue'] = 0;
                }

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            );
    }
}
