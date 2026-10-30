<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Supplier>
 */
final class SupplierFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Supplier::class;
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

    public function asWarehouse(): self
    {
        return $this
            ->with([
                'name' => Supplier::DEFAULT_WAREHOUSE_NAME,
            ])
            ->afterInstantiate(function (Supplier $supplier): void {
                $supplier->setAsWarehouse(true);
            });
    }
}
