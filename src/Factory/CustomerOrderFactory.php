<?php

namespace App\Factory;

use App\Entity\CustomerOrder;
use App\Enum\ShippingMethod;
use Zenstruck\Foundry\LazyValue;
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
        $customer = LazyValue::memoize(fn() => UserFactory::new()->create());
        $billingAddress = LazyValue::memoize(fn() => AddressFactory::new([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true
        ])->create()->_real());
        $shippingMethod = LazyValue::memoize(fn() => self::faker()->randomElement(ShippingMethod::cases()));
        $vatRate = LazyValue::memoize(fn() => VatRateFactory::new()->standard());

        return [
            'customer' => $customer,
            'billingAddress' => $billingAddress,
            'shippingMethod' => $shippingMethod,
            'vatRate' => $vatRate,
            'customerOrderRef' => self::faker()->word(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes) {
            return CustomerOrder::createFromCustomer(
                $attributes['customer'],
                $attributes['shippingMethod'],
                $attributes['vatRate'],
                $attributes['customerOrderRef']
            );
        });
    }
}

