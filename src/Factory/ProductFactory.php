<?php

namespace App\Factory;

use App\Entity\Product;
use Zenstruck\Foundry\LazyValue;
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
        $category = LazyValue::memoize(fn() => CategoryFactory::CreateOne());

        return [
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'description' => ucfirst(implode(' ', self::faker()->words(random_int(5, 10)))),
            'MfrPartNumber' => self::faker()->regexify('[A-Z]{4}[0-4]{4}'),
            'stock' => self::faker()->randomNumber(4),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'weight' => self::faker()->randomNumber(4),
            'defaultMarkup' => Product::DEFAULT_MARKUP,
            'markup' => '0',
            'priceModel' => Product::DEFAULT_PRICE_MODEL,
            'cost' => self::faker()->randomNumber(5) / 100,
            'sellPrice' => '0',
            'sellPriceIncVat' => '0',
            'category' => $category,
            'subcategory' => SubcategoryFactory::new()->with(['category' => $category]),
            'manufacturer' => ManufacturerFactory::new(),
            'owner' => UserFactory::new(),
            'isActive' => self::faker()->boolean(),
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
