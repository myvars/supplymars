<?php

namespace App\Factory;

use App\Entity\PriceModel;
use App\Entity\Subcategory;
use App\Repository\SubcategoryRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Subcategory>
 *
 * @method        Subcategory|Proxy                     create(array|callable $attributes = [])
 * @method static Subcategory|Proxy                     createOne(array $attributes = [])
 * @method static Subcategory|Proxy                     find(object|array|mixed $criteria)
 * @method static Subcategory|Proxy                     findOrCreate(array $attributes)
 * @method static Subcategory|Proxy                     first(string $sortedField = 'id')
 * @method static Subcategory|Proxy                     last(string $sortedField = 'id')
 * @method static Subcategory|Proxy                     random(array $attributes = [])
 * @method static Subcategory|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SubcategoryRepository|RepositoryProxy repository()
 * @method static Subcategory[]|Proxy[]                 all()
 * @method static Subcategory[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Subcategory[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Subcategory[]|Proxy[]                 findBy(array $attributes)
 * @method static Subcategory[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Subcategory[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SubcategoryFactory extends ModelFactory
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
            'isActive' => self::faker()->boolean(),
            'defaultMarkup' => self::faker()->randomNumber(4) / 100,
            'category' => CategoryFactory::new(),
            'owner' => UserFactory::new(),
            'priceModel' => PriceModel::NONE,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Subcategory $subcategory): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Subcategory::class;
    }
}
