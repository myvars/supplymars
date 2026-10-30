<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Pricing\Domain\Model\VatRate\VatRate;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<VatRate>
 */
final class VatRateFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return VatRate::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(255),
            'rate' => self::faker()->numberBetween(1, 100000) / 100,
        ];
    }

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
