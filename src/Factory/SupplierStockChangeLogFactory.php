<?php

namespace App\Factory;

use App\Entity\SupplierStockChangeLog;
use App\Enum\DomainEventType;
use App\ValueObject\CostChange;
use App\ValueObject\StockChange;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SupplierStockChangeLog>
 */
final class SupplierStockChangeLogFactory extends PersistentProxyObjectFactory
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
    protected function defaults(): array|callable
    {
        return [
            'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'supplierProduct' => LazyValue::memoize(fn (): SupplierProductFactory => SupplierProductFactory::new()),
            'supplierProductId' => null,
            'stockChange' => null,
            'costChange' => null,
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
                $attributes['supplierProductId'] ??= $attributes['supplierProduct']->getId();
                $attributes['stockChange'] ??= StockChange::from(
                    $attributes['supplierProduct']->getStock(),
                    $attributes['supplierProduct']->getStock() + 1
                );
                $attributes['costChange'] ??= CostChange::from(
                    $attributes['supplierProduct']->getCost(),
                    bcadd($attributes['supplierProduct']->getCost(), '1.00', 2)
                );

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
                    ->allowExtra('supplierProduct')
            );
    }
}
