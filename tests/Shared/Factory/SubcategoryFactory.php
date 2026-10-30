<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Subcategory>
 */
final class SubcategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Subcategory::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', (array) self::faker()->words(self::faker()->numberBetween(1, 3)))),
            'category' => LazyValue::memoize(fn (): Category => CategoryFactory::createOne()),
            'owner' => LazyValue::memoize(fn (): User => UserFactory::new()->asStaff()->create()),
            'defaultMarkup' => Subcategory::DEFAULT_MARKUP,
            'priceModel' => Subcategory::DEFAULT_PRICE_MODEL,
            'isActive' => true,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
