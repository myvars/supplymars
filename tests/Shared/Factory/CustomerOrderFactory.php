<?php

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Shared\Domain\ValueObject\ShippingMethod;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerOrder>
 */
final class CustomerOrderFactory extends PersistentObjectFactory
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
    protected function defaults(): array
    {
        return [
            'customer' => LazyValue::memoize(fn () => UserFactory::createOne()),
            'billingAddress' => null,
            'shippingMethod' => LazyValue::memoize(fn () => self::faker()->randomElement(ShippingMethod::cases())),
            'vatRate' => LazyValue::memoize(fn () => VatRateFactory::new()->withStandardRate()->create()),
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
                        fn () => AddressFactory::createOne([
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
