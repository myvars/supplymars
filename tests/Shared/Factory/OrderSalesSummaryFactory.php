<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<OrderSalesSummary>
 */
final class OrderSalesSummaryFactory extends PersistentObjectFactory
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
        return OrderSalesSummary::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'orderSalesType' => OrderSalesType::create(
                self::faker()->randomElement(SalesDuration::cases()),
            ),
            'dateString' => self::faker()->date(),
            'orderCount' => self::faker()->numberBetween(1, 1000),
            'orderValue' => self::faker()->randomFloat(2),
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
