<?php

namespace App\Factory;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Supplier>
 *
 * @method        Supplier|Proxy                     create(array|callable $attributes = [])
 * @method static Supplier|Proxy                     createOne(array $attributes = [])
 * @method static Supplier|Proxy                     find(object|array|mixed $criteria)
 * @method static Supplier|Proxy                     findOrCreate(array $attributes)
 * @method static Supplier|Proxy                     first(string $sortedField = 'id')
 * @method static Supplier|Proxy                     last(string $sortedField = 'id')
 * @method static Supplier|Proxy                     random(array $attributes = [])
 * @method static Supplier|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SupplierRepository|RepositoryProxy repository()
 * @method static Supplier[]|Proxy[]                 all()
 * @method static Supplier[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Supplier[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Supplier[]|Proxy[]                 findBy(array $attributes)
 * @method static Supplier[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Supplier[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SupplierFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->company,
            'isActive' => self::faker()->boolean(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Supplier $supplier): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Supplier::class;
    }
}
