<?php

namespace App\Factory;

use App\Entity\PriceModel;
use App\Repository\PriceModelRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<PriceModel>
 *
 * @method        PriceModel|Proxy                     create(array|callable $attributes = [])
 * @method static PriceModel|Proxy                     createOne(array $attributes = [])
 * @method static PriceModel|Proxy                     find(object|array|mixed $criteria)
 * @method static PriceModel|Proxy                     findOrCreate(array $attributes)
 * @method static PriceModel|Proxy                     first(string $sortedField = 'id')
 * @method static PriceModel|Proxy                     last(string $sortedField = 'id')
 * @method static PriceModel|Proxy                     random(array $attributes = [])
 * @method static PriceModel|Proxy                     randomOrCreate(array $attributes = [])
 * @method static PriceModelRepository|RepositoryProxy repository()
 * @method static PriceModel[]|Proxy[]                 all()
 * @method static PriceModel[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static PriceModel[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static PriceModel[]|Proxy[]                 findBy(array $attributes)
 * @method static PriceModel[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static PriceModel[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class PriceModelFactory extends ModelFactory
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
            'isActive' => self::faker()->boolean(),
            'modelTag' => strtoupper(self::faker()->text(20)),
            'name' => self::faker()->text(50),
            'description' => self::faker()->text(100),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(PriceModel $priceModel): void {})
        ;
    }

    protected static function getClass(): string
    {
        return PriceModel::class;
    }
}
