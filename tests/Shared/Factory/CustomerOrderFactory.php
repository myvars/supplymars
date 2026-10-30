<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\ShippingMethod;
use App\Pricing\Domain\Model\VatRate\VatRate;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerOrder>
 */
final class CustomerOrderFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CustomerOrder::class;
    }

    protected function defaults(): array
    {
        return [
            'customer' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
            'billingAddress' => null,
            'shippingMethod' => self::faker()->randomElement(ShippingMethod::cases()),
            'vatRate' => LazyValue::memoize(fn (): VatRate => VatRateFactory::new()->withStandardRate()->create()),
            'customerOrderRef' => self::faker()->word(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if (null !== $attributes['customer']) {
                    $attributes['billingAddress'] ??= LazyValue::memoize(
                        fn (): Address => AddressFactory::createOne([
                            'customer' => $attributes['customer'],
                            'isDefaultBillingAddress' => true,
                            'isDefaultShippingAddress' => true,
                        ])
                    );
                }

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('createFromCustomer')
                    ->allowExtra('billingAddress')
            );
    }
}
