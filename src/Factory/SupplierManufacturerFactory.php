<?php

namespace App\Factory;

use App\Entity\SupplierManufacturer;
use App\Repository\SupplierManufacturerRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<SupplierManufacturer>
 *
 * @method        SupplierManufacturer|Proxy                     create(array|callable $attributes = [])
 * @method static SupplierManufacturer|Proxy                     createOne(array $attributes = [])
 * @method static SupplierManufacturer|Proxy                     find(object|array|mixed $criteria)
 * @method static SupplierManufacturer|Proxy                     findOrCreate(array $attributes)
 * @method static SupplierManufacturer|Proxy                     first(string $sortedField = 'id')
 * @method static SupplierManufacturer|Proxy                     last(string $sortedField = 'id')
 * @method static SupplierManufacturer|Proxy                     random(array $attributes = [])
 * @method static SupplierManufacturer|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SupplierManufacturerRepository|RepositoryProxy repository()
 * @method static SupplierManufacturer[]|Proxy[]                 all()
 * @method static SupplierManufacturer[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static SupplierManufacturer[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static SupplierManufacturer[]|Proxy[]                 findBy(array $attributes)
 * @method static SupplierManufacturer[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SupplierManufacturer[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SupplierManufacturerFactory extends ModelFactory
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
            'supplier' => SupplierFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(SupplierManufacturer $supplierManufacturer): void {})
        ;
    }

    protected static function getClass(): string
    {
        return SupplierManufacturer::class;
    }
}
