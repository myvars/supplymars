<?php

namespace App\Factory;

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
    protected function defaults(): array|callable
    {
        $supplier = LazyValue::memoize(fn() => SupplierFactory::createOne());

        return [
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'supplier' => $supplier,
            'supplierCategory' => SupplierCategoryFactory::new()->with(['supplier' => $supplier]),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(SupplierSubcategory $supplierSubcategory): void {})
        ;
    }
}
