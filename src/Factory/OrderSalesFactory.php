<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use App\Entity\OrderSales;
use Zenstruck\Foundry\Object\Instantiator;

/**
 * @extends PersistentObjectFactory<OrderSales>
 */
final class OrderSalesFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return OrderSales::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'dateString' => self::faker()->date(),
            'orderCount' => self::faker()->numberBetween(1, 1000),
            'orderValue' => self::faker()->numberBetween(1, 1000000) / 100,
            'averageOrderValue' => null,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
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
