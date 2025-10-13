<?php

namespace App\Tests\Shared\Factory;

use App\Reporting\Domain\Model\SalesType\ProductSales;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductSales>
 */
final class ProductSalesFactory extends PersistentObjectFactory
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
        return ProductSales::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn () => ProductFactory::createOne()),
            'supplier' => LazyValue::memoize(fn () => SupplierFactory::createOne()),
            'dateString' => self::faker()->date(),
            'salesQty' => self::faker()->numberBetween(1, 1000),
            'salesCost' => self::faker()->numberBetween(1, 1000000) / 100,
            'salesValue' => self::faker()->numberBetween(1, 1000000) / 100,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
