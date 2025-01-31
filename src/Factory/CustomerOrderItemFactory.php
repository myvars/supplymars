<?php

namespace App\Factory;

use App\Entity\CustomerOrderItem;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<CustomerOrderItem>
 */
final class CustomerOrderItemFactory extends PersistentProxyObjectFactory
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
        return CustomerOrderItem::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'customerOrder' => CustomerOrderFactory::new(),
            'product' => ProductFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(Instantiator::withConstructor()->allowExtra())
            ->afterInstantiate(function(CustomerOrderItem $customerOrderItem, array $attributes): void {
                $product = $attributes['product'] ?? ProductFactory::new()->create()->_real();
                $quantity = $attributes['quantity'] ?? self::faker()->randomNumber(1);
                $customerOrderItem->createFromProduct($product, $quantity);
            });
    }
}
