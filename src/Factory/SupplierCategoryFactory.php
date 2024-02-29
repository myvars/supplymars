<?php

namespace App\Factory;

use App\Entity\SupplierCategory;
use App\Repository\SupplierCategoryRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<SupplierCategory>
 *
 * @method        SupplierCategory|Proxy                     create(array|callable $attributes = [])
 * @method static SupplierCategory|Proxy                     createOne(array $attributes = [])
 * @method static SupplierCategory|Proxy                     find(object|array|mixed $criteria)
 * @method static SupplierCategory|Proxy                     findOrCreate(array $attributes)
 * @method static SupplierCategory|Proxy                     first(string $sortedField = 'id')
 * @method static SupplierCategory|Proxy                     last(string $sortedField = 'id')
 * @method static SupplierCategory|Proxy                     random(array $attributes = [])
 * @method static SupplierCategory|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SupplierCategoryRepository|RepositoryProxy repository()
 * @method static SupplierCategory[]|Proxy[]                 all()
 * @method static SupplierCategory[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static SupplierCategory[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static SupplierCategory[]|Proxy[]                 findBy(array $attributes)
 * @method static SupplierCategory[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SupplierCategory[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SupplierCategoryFactory extends ModelFactory
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
            'name' => ucfirst(implode(' ', self::faker()->words(rand(1, 3)))),
            'supplier' => SupplierFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(SupplierCategory $supplierCategory): void {})
        ;
    }

    protected static function getClass(): string
    {
        return SupplierCategory::class;
    }
}
