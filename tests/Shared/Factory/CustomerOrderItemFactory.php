<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CustomerOrderItem>
 */
final class CustomerOrderItemFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CustomerOrderItem::class;
    }

    protected function defaults(): array
    {
        return [
            'customerOrder' => LazyValue::memoize(fn (): CustomerOrder => CustomerOrderFactory::createOne()),
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::createOne()),
            'quantity' => 1,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('createFromProduct')
        );
    }
}
