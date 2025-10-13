<?php

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Category\Category;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Category>
 */
final class CategoryFactory extends PersistentObjectFactory
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
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'owner' => LazyValue::memoize(fn () => UserFactory::new()->asStaff()->create()),
            'vatRate' => LazyValue::memoize(fn () => VatRateFactory::new()->withStandardRate()->create()),
            'defaultMarkup' => Category::DEFAULT_MARKUP,
            'priceModel' => Category::DEFAULT_PRICE_MODEL,
            'isActive' => true,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
