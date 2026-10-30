<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SupplierCategory>
 */
final class SupplierCategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return SupplierCategory::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', (array) self::faker()->words(self::faker()->numberBetween(1, 3)))),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::createOne()),
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
