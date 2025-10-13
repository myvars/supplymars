<?php

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<PurchaseOrderItem>
 */
final class PurchaseOrderItemFactory extends PersistentObjectFactory
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
    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn () => ProductFactory::createOne()),
            'supplier' => LazyValue::memoize(fn () => SupplierFactory::createOne()),
            'customerOrder' => LazyValue::memoize(fn () => CustomerOrderFactory::createOne()),
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
                    fn () => SupplierProductFactory::createOne([
                        'supplier' => $attributes['supplier'],
                        'product' => $attributes['product'],
                        'stock' => $attributes['quantity'] ?? 1,
                    ])
                );
                $attributes['customerOrderItem'] ??= LazyValue::memoize(
                    fn () => CustomerOrderItemFactory::createOne([
                        'customerOrder' => $attributes['customerOrder'],
                        'product' => $attributes['product'],
                        'quantity' => $attributes['quantity'] ?? 1,
                    ])
                );
                $attributes['purchaseOrder'] ??= LazyValue::memoize(
                    fn () => PurchaseOrderFactory::createOne([
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
