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
            'productCode' => self::faker()->regexify('[A-Z]{2}[0-4]{5}'),
            'supplierCategory' => SupplierCategoryFactory::new(),
            'supplierSubcategory' => SupplierSubcategoryFactory::new(),
            'supplierManufacturer' => SupplierManufacturerFactory::new(),
            'mfrPartNumber' => self::faker()->numerify('PART-####'),
            'weight' => self::faker()->randomNumber(4),
            'stock' => self::faker()->randomNumber(4),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'cost' => self::faker()->randomNumber(5) / 100,
            'isActive' => self::faker()->boolean(),
            'supplier' => SupplierFactory::new(),
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
