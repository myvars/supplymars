<?php

namespace App\Factory;

use App\Entity\SupplierProduct;
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
        return [
            'name' => self::faker()->text(50),
            'supplier' => SupplierFactory::new(),
            'supplierCategory' => SupplierCategoryFactory::new(),
            'supplierSubcategory' => SupplierSubcategoryFactory::new(),
            'supplierManufacturer' => SupplierManufacturerFactory::new(),
            'productCode' => self::faker()->regexify('[A-Z]{2}[0-4]{5}'),
            'mfrPartNumber' => self::faker()->numerify('PART-####'),
            'weight' => self::faker()->randomNumber(4),
            'stock' => self::faker()->randomNumber(3),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'cost' => self::faker()->randomNumber(5) / 100,
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
