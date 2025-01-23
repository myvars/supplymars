<?php

namespace App\Factory;

use App\Entity\SupplierProduct;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SupplierProduct>
 */
final class SupplierProductFactory extends PersistentProxyObjectFactory
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
        return SupplierProduct::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $supplier = LazyValue::memoize(fn() => SupplierFactory::new());
        $supplierCategory = LazyValue::memoize(fn() => SupplierCategoryFactory::new()->with(['supplier' => $supplier]));
        $supplierSubcategory = LazyValue::memoize(fn() => SupplierSubcategoryFactory::new()->with(['supplier' => $supplier, 'supplierCategory' => $supplierCategory]));
        $supplierManufacturer = LazyValue::memoize(fn() => SupplierManufacturerFactory::new()->with(['supplier' => $supplier]));

        return [
            'name' => self::faker()->text(50),
            'productCode' => self::faker()->regexify('[A-Z]{2}[0-4]{5}'),
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'supplierSubcategory' => $supplierSubcategory,
            'supplierManufacturer' => $supplierManufacturer,
            'mfrPartNumber' => self::faker()->numerify('PART-####'),
            'weight' => self::faker()->randomNumber(4),
            'stock' => self::faker()->randomNumber(3),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'cost' => self::faker()->randomNumber(5) / 100,
            'product' => ProductFactory::new(),
            'isActive' => self::faker()->boolean(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(SupplierProduct $supplierProduct): void {})
        ;
    }
}
