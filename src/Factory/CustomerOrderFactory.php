<?php

namespace App\Factory;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Enum\ShippingMethod;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<CustomerOrder>
 */
final class CustomerOrderFactory extends PersistentProxyObjectFactory
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
        return CustomerOrder::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'customer' => LazyValue::memoize(fn (): User => UserFactory::new()->create()),
            'billingAddress' => null,
            'shippingMethod' => LazyValue::memoize(fn () => self::faker()->randomElement(ShippingMethod::cases())),
            'vatRate' => LazyValue::memoize(fn (): VatRateFactory => VatRateFactory::new()->standard()),
            'customerOrderRef' => self::faker()->word(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if (null !== $attributes['customer']) {
                    $attributes['billingAddress'] ??= LazyValue::memoize(
                        fn (): AddressFactory => AddressFactory::new()->with([
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
