<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductSales>
 */
final class ProductSalesFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ProductSales::class;
    }

    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::createOne()),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::createOne()),
            'dateString' => self::faker()->date(),
            'salesQty' => self::faker()->numberBetween(1, 1000),
            'salesCost' => self::faker()->numberBetween(1, 1000000) / 100,
            'salesValue' => self::faker()->numberBetween(1, 1000000) / 100,
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
