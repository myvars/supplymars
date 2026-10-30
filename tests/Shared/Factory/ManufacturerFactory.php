<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Manufacturer>
 */
final class ManufacturerFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Manufacturer::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
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
