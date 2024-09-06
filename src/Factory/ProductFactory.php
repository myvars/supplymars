<?php

namespace App\Factory;

use App\Entity\Product;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Product>
 */
final class ProductFactory extends PersistentProxyObjectFactory
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
        return Product::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->text(100),
            'MfrPartNumber' => self::faker()->regexify('[A-Z]{4}[0-4]{4}'),
            'category' => CategoryFactory::new(),
            'subcategory' => SubcategoryFactory::new(),
            'manufacturer' => ManufacturerFactory::new(),
            'cost' => self::faker()->randomNumber(5) / 100,
            'isActive' => self::faker()->boolean(),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'stock' => self::faker()->randomNumber(4),
            'weight' => self::faker()->randomNumber(4),
            'markup' => self::faker()->randomNumber(4) / 100,
            'owner' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Product $product): void {})
        ;
    }
}
