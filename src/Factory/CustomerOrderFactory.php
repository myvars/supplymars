<?php

namespace App\Factory;

use App\Entity\CustomerOrder;
use App\Entity\VatRate;
use App\Enum\OrderStatus;
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
        $shippingMethod = LazyValue::memoize(fn() => self::faker()->randomElement(ShippingMethod::cases()));

        return [
            'customer' => UserFactory::new(),
            'shippingAddress' => AddressFactory::new(),
            'billingAddress' => AddressFactory::new(),
            'customerOrderRef' => self::faker()->word(),
            'shippingMethod' => $shippingMethod,
            'status' => OrderStatus::getDefault(),
            'vatRate' => VatRateFactory::new()->standard(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(Instantiator::withConstructor()->allowExtra())
            ->afterInstantiate(function (CustomerOrder $customerOrder, array $attributes): void {
                $customerOrder->setShippingDetailsFromShippingMethod(
                    $customerOrder->getShippingMethod(),
                    $attributes['vatRate']
                );
            });
    }
}

