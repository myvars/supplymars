<?php

namespace App\Tests\Shared\Factory;

use App\Note\Domain\Model\Pool\Pool;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Pool>
 */
final class PoolFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Pool::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', (array) self::faker()->words(2))) . ' Queries',
            'description' => self::faker()->sentence(),
            'isActive' => true,
            'isCustomerVisible' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->with(['isActive' => false]);
    }

    public function internal(): self
    {
        return $this->with(['isCustomerVisible' => false]);
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
