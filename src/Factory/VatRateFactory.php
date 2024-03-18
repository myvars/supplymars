<?php

namespace App\Factory;

use App\Entity\VatRate;
use App\Repository\VatRateRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<VatRate>
 *
 * @method        VatRate|Proxy                     create(array|callable $attributes = [])
 * @method static VatRate|Proxy                     createOne(array $attributes = [])
 * @method static VatRate|Proxy                     find(object|array|mixed $criteria)
 * @method static VatRate|Proxy                     findOrCreate(array $attributes)
 * @method static VatRate|Proxy                     first(string $sortedField = 'id')
 * @method static VatRate|Proxy                     last(string $sortedField = 'id')
 * @method static VatRate|Proxy                     random(array $attributes = [])
 * @method static VatRate|Proxy                     randomOrCreate(array $attributes = [])
 * @method static VatRateRepository|RepositoryProxy repository()
 * @method static VatRate[]|Proxy[]                 all()
 * @method static VatRate[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static VatRate[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static VatRate[]|Proxy[]                 findBy(array $attributes)
 * @method static VatRate[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static VatRate[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class VatRateFactory extends ModelFactory
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
            'name' => self::faker()->text(255),
            'rate' => self::faker()->randomNumber(5) / 100,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(VatRate $vatRate): void {})
        ;
    }

    protected static function getClass(): string
    {
        return VatRate::class;
    }
}
