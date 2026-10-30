<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SupplierManufacturer>
 */
final class SupplierManufacturerFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return SupplierManufacturer::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
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
