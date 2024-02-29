<?php

namespace App\Factory;

use App\Entity\SupplierSubcategory;
use App\Repository\SupplierSubcategoryRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<SupplierSubcategory>
 *
 * @method        SupplierSubcategory|Proxy                     create(array|callable $attributes = [])
 * @method static SupplierSubcategory|Proxy                     createOne(array $attributes = [])
 * @method static SupplierSubcategory|Proxy                     find(object|array|mixed $criteria)
 * @method static SupplierSubcategory|Proxy                     findOrCreate(array $attributes)
 * @method static SupplierSubcategory|Proxy                     first(string $sortedField = 'id')
 * @method static SupplierSubcategory|Proxy                     last(string $sortedField = 'id')
 * @method static SupplierSubcategory|Proxy                     random(array $attributes = [])
 * @method static SupplierSubcategory|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SupplierSubcategoryRepository|RepositoryProxy repository()
 * @method static SupplierSubcategory[]|Proxy[]                 all()
 * @method static SupplierSubcategory[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static SupplierSubcategory[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static SupplierSubcategory[]|Proxy[]                 findBy(array $attributes)
 * @method static SupplierSubcategory[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SupplierSubcategory[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SupplierSubcategoryFactory extends ModelFactory
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
            'supplierCategory' => SupplierCategoryFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(SupplierSubcategory $supplierSubcategory): void {})
        ;
    }

    protected static function getClass(): string
    {
        return SupplierSubcategory::class;
    }
}
