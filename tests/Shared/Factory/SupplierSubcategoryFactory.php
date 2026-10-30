<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SupplierSubcategory>
 */
final class SupplierSubcategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return SupplierSubcategory::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', (array) self::faker()->words(self::faker()->numberBetween(1, 3)))),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::createOne()),
            'supplierCategory' => null,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if (null !== $attributes['supplier']) {
                    $attributes['supplierCategory'] ??= LazyValue::memoize(
                        fn (): SupplierCategory => SupplierCategoryFactory::createOne(['supplier' => $attributes['supplier']])
                    );
                }

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            );
    }
}
