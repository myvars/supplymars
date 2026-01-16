<?php

namespace App\Tests\Shared\Factory;

use App\Pricing\Domain\Model\VatRate\VatRate;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

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
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }

    public function withStandardRate(): self
    {
        return $this
            ->with([
                'name' => VatRate::STANDARD_VAT_NAME,
                'rate' => VatRate::STANDARD_VAT_RATE,
            ])
            ->afterInstantiate(function (VatRate $vatRate): void {
                $vatRate->setAsDefaultRate(true);
            });
    }
}
