<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<PurchaseOrderItem>
 */
final class PurchaseOrderItemFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return PurchaseOrderItem::class;
    }

    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::createOne()),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::createOne()),
            'customerOrder' => LazyValue::memoize(fn (): CustomerOrder => CustomerOrderFactory::createOne()),
            'quantity' => 1,
            'supplierProduct' => null,
            'customerOrderItem' => null,
            'purchaseOrder' => null,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                $attributes['supplierProduct'] ??= LazyValue::memoize(
                    fn (): SupplierProduct => SupplierProductFactory::createOne([
                        'supplier' => $attributes['supplier'],
                        'product' => $attributes['product'],
                        'stock' => $attributes['quantity'] ?? 1,
                    ])
                );
                $attributes['customerOrderItem'] ??= LazyValue::memoize(
                    fn (): CustomerOrderItem => CustomerOrderItemFactory::createOne([
                        'customerOrder' => $attributes['customerOrder'],
                        'product' => $attributes['product'],
                        'quantity' => $attributes['quantity'] ?? 1,
                    ])
                );
                $attributes['purchaseOrder'] ??= LazyValue::memoize(
                    fn (): PurchaseOrder => PurchaseOrderFactory::createOne([
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

    /** Walks the item through each status to delivered, which stamps deliveredAt with now. */
    public function delivered(): self
    {
        return $this->afterInstantiate(function (PurchaseOrderItem $item): void {
            foreach ([
                PurchaseOrderStatus::PROCESSING,
                PurchaseOrderStatus::ACCEPTED,
                PurchaseOrderStatus::SHIPPED,
                PurchaseOrderStatus::DELIVERED,
            ] as $status) {
                $item->updateItemStatus($status);
            }
        });
    }
}
