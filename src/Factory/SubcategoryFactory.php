<?php

namespace App\Factory;

use App\Entity\Subcategory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Subcategory>
 */
final class SubcategoryFactory extends PersistentProxyObjectFactory
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
        return Subcategory::class;
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
            'defaultMarkup' => Subcategory::DEFAULT_MARKUP,
            'owner' => LazyValue::memoize(fn (): UserFactory => UserFactory::new()),
            'priceModel' => Subcategory::DEFAULT_PRICE_MODEL,
            'category' => LazyValue::memoize(fn (): CategoryFactory => CategoryFactory::new()),
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
            // ->afterInstantiate(function(Subcategory $subcategory): void {})
        ;
    }
}
