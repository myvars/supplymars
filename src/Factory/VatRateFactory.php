<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use App\Entity\VatRate;

/**
 * @extends PersistentObjectFactory<VatRate>
 */
final class VatRateFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return VatRate::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(255),
            'rate' => self::faker()->numberBetween(1, 100000) / 100,
            'isDefaultVatRate' => false,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(VatRate $vatRate): void {})
        ;
    }

    public function standard(): self
    {
        return $this->with(['name' => 'Standard rate', 'rate' => '20.00', 'isDefaultVatRate' => true]);
    }
}
