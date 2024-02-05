<?php

namespace App\Factory;

use App\Entity\Manufacturer;
use App\Repository\ManufacturerRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Manufacturer>
 *
 * @method        Manufacturer|Proxy                     create(array|callable $attributes = [])
 * @method static Manufacturer|Proxy                     createOne(array $attributes = [])
 * @method static Manufacturer|Proxy                     find(object|array|mixed $criteria)
 * @method static Manufacturer|Proxy                     findOrCreate(array $attributes)
 * @method static Manufacturer|Proxy                     first(string $sortedField = 'id')
 * @method static Manufacturer|Proxy                     last(string $sortedField = 'id')
 * @method static Manufacturer|Proxy                     random(array $attributes = [])
 * @method static Manufacturer|Proxy                     randomOrCreate(array $attributes = [])
 * @method static ManufacturerRepository|RepositoryProxy repository()
 * @method static Manufacturer[]|Proxy[]                 all()
 * @method static Manufacturer[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Manufacturer[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Manufacturer[]|Proxy[]                 findBy(array $attributes)
 * @method static Manufacturer[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Manufacturer[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class ManufacturerFactory extends ModelFactory
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
            'name' => self::faker()->company(),
            'isActive' => self::faker()->boolean(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Manufacturer $manufacturer): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Manufacturer::class;
    }
}
