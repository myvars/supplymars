<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use App\Entity\Category;
use App\Entity\Product;
use Zenstruck\Foundry\LazyValue;

/**
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
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
    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'description' => ucfirst(implode(' ', self::faker()->words(random_int(5, 10)))),
            'MfrPartNumber' => self::faker()->regexify('[A-Z]{4}[0-4]{4}'),
            'stock' => self::faker()->randomNumber(4),
            'leadTimeDays' => self::faker()->numberBetween(1, 99),
            'weight' => self::faker()->numberBetween(1, 10000),
            'defaultMarkup' => Product::DEFAULT_MARKUP,
            'markup' => '0',
            'priceModel' => Product::DEFAULT_PRICE_MODEL,
            'cost' => self::faker()->numberBetween(1, 100000) / 100,
            'sellPrice' => '0',
            'sellPriceIncVat' => '0',
            'category' => LazyValue::memoize(fn (): Category => CategoryFactory::new()->create()),
            'subcategory' => null,
            'manufacturer' => LazyValue::memoize(fn (): ManufacturerFactory => ManufacturerFactory::new()),
            'owner' => LazyValue::memoize(fn (): UserFactory => UserFactory::new()),
            'isActive' => true,
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
                if (null !== $attributes['category']) {
                    $attributes['subcategory'] ??= LazyValue::memoize(
                        fn (): SubcategoryFactory => SubcategoryFactory::new()->with(['category' => $attributes['category']])
                    );
                }

                return $attributes;
            });
    }
}
