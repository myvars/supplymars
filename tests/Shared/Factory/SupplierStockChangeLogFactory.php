<?php

namespace App\Tests\Shared\Factory;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SupplierStockChangeLog>
 */
final class SupplierStockChangeLogFactory extends PersistentObjectFactory
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
        return SupplierStockChangeLog::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'supplierProductId' => 1,
            'stockChange' => StockChange::from(0, 0),
            'costChange' => CostChange::from('0.00', '0.00'),
            'occurredAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
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
                $attributes['stockChange'] ??= StockChange::from(
                    $attributes['supplierProduct']->getStock(),
                    $attributes['supplierProduct']->getStock() + 1
                );
                $attributes['costChange'] ??= CostChange::from(
                    $attributes['supplierProduct']->getCost() ?? '0.00',
                    bcadd($attributes['supplierProduct']->getCost() ?? '0.00', '1.00', 2)
                );

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
                    ->allowExtra('supplierProduct')
            );
    }
}
