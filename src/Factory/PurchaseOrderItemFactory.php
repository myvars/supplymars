<?php

namespace App\Factory;

use App\Entity\CustomerOrder;
use App\Entity\Product;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
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
        return [
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::new()->create()),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::new()->create()),
            'customerOrder' => LazyValue::memoize(fn (): CustomerOrder => CustomerOrderFactory::new()->create()),
            'quantity' => 1,
            'supplierProduct' => null,
            'customerOrderItem' => null,
            'purchaseOrder' => null,
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
                $attributes['supplierProduct'] ??= LazyValue::memoize(
                    fn (): SupplierProductFactory => SupplierProductFactory::new()->with([
                        'supplier' => $attributes['supplier'],
                        'product' => $attributes['product'],
                        'stock' => $attributes['quantity'] ?? 1,
                    ])
                );
                $attributes['customerOrderItem'] ??= LazyValue::memoize(
                    fn (): CustomerOrderItemFactory => CustomerOrderItemFactory::new()->with([
                        'customerOrder' => $attributes['customerOrder'],
                        'product' => $attributes['product'],
                        'quantity' => $attributes['quantity'] ?? 1,
                    ])
                );
                $attributes['purchaseOrder'] ??= LazyValue::memoize(
                    fn (): PurchaseOrderFactory => PurchaseOrderFactory::new()->with([
                        'customerOrder' => $attributes['customerOrder'],
                        'supplier' => $attributes['supplier'],
                    ])
                );

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('createFromCustomerOrderItem')
                    ->allowExtra('product', 'supplier', 'customerOrder')
            );
    }
}
