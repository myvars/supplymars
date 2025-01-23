<?php

namespace App\Factory;

use App\Entity\CustomerOrder;
use App\Enum\OrderStatus;
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
        $shippingMethod = LazyValue::memoize(fn() => self::faker()->randomElement(ShippingMethod::cases()));

        return [
            'customer' => UserFactory::new(),
            'shippingAddress' => AddressFactory::new(),
            'billingAddress' => AddressFactory::new(),
            'customerOrderRef' => self::faker()->word(),
            'shippingMethod' => $shippingMethod,
            'status' => OrderStatus::getDefault(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (CustomerOrder $customerOrder): void {
                $customerOrder->setShippingDetailsFromShippingMethod(
                    $customerOrder->getShippingMethod(),
                    VatRateFactory::new()->standard()->create()
                );
            });
    }
}

