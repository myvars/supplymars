<?php

namespace App\Factory;

use App\Entity\PurchaseOrder;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<PurchaseOrder>
 */
final class PurchaseOrderFactory extends PersistentProxyObjectFactory
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
        return PurchaseOrder::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $supplier = LazyValue::memoize(fn() => SupplierFactory::new());
        $customerOrder = LazyValue::memoize(fn() => CustomerOrderFactory::new());

        return [
            'customerOrder' => $customerOrder,
            'supplier' => $supplier,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes) {
            return PurchaseOrder::createFromOrder(
                $attributes['customerOrder'],
                $attributes['supplier']
            );
        });
    }
}
