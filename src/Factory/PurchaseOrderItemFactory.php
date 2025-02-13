<?php

namespace App\Factory;

use App\Entity\PurchaseOrderItem;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<PurchaseOrderItem>
 */
final class PurchaseOrderItemFactory extends PersistentProxyObjectFactory
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
        return PurchaseOrderItem::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $product = LazyValue::memoize(fn() => ProductFactory::new());
        $supplier = LazyValue::memoize(fn() => SupplierFactory::new());
        $supplierProduct = LazyValue::memoize(fn() => SupplierProductFactory::new()->with(['supplier' => $supplier, 'product' => $product]));
        $customerOrder = LazyValue::memoize(fn() => CustomerOrderFactory::new());
        $customerOrderItem = LazyValue::memoize(fn() => CustomerOrderItemFactory::new()->with(['customerOrder' => $customerOrder, 'product' => $product]));
        $purchaseOrder = LazyValue::memoize(fn() => PurchaseOrderFactory::new()->with(['customerOrder' => $customerOrder, 'supplier' => $supplier]));

        return [
            'customerOrderItem' => $customerOrderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'quantity' => 1,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes) {
            return PurchaseOrderItem::createFromCustomerOrderItem(
                $attributes['customerOrderItem'],
                $attributes['purchaseOrder'],
                $attributes['supplierProduct'],
                $attributes['quantity']
            );
        });
    }
}
