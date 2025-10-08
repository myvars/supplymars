<?php

namespace App\Factory;

use App\Entity\Supplier;
use App\Entity\SupplierSubcategory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SupplierSubcategory>
 */
final class SupplierSubcategoryFactory extends PersistentProxyObjectFactory
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
        return SupplierSubcategory::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::new()->create()),
            'supplierCategory' => null,
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
                if (null !== $attributes['supplier']) {
                    $attributes['supplierCategory'] ??= LazyValue::memoize(
                        fn (): SupplierCategoryFactory => SupplierCategoryFactory::new()->with(['supplier' => $attributes['supplier']])
                    );
                }

                return $attributes;
            });
    }
}
