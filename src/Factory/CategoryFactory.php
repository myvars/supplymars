<?php

namespace App\Factory;

use App\Entity\Category;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Category>
 */
final class CategoryFactory extends PersistentProxyObjectFactory
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
        return Category::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'defaultMarkup' => Category::DEFAULT_MARKUP,
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'owner' => LazyValue::memoize(fn (): UserFactory => UserFactory::new()->staff()),
            'priceModel' => Category::DEFAULT_PRICE_MODEL,
            'vatRate' => LazyValue::memoize(fn (): VatRateFactory => VatRateFactory::new()->standard()),
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
            // ->afterInstantiate(function(Category $category): void {})
        ;
    }
}
