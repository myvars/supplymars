<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<PurchaseOrder>
 */
final class PurchaseOrderFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return PurchaseOrder::class;
    }

    protected function defaults(): array
    {
        return [
            'customerOrder' => LazyValue::memoize(fn (): CustomerOrder => CustomerOrderFactory::createOne()),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::createOne()),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('createFromOrder')
        );
    }
}
