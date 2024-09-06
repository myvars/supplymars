<?php

namespace App\Factory;

use App\Entity\CustomerOrderItem;
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
        $product = ProductFactory::new()->create();

        return [
            'customerOrder' => CustomerOrderFactory::new(),
            'product' => ProductFactory::new(),
            'quantity' => self::faker()->randomNumber(1),
            'price' => $product->getSellPrice(),
            'priceIncVat' => $product->getSellPriceIncVat(),
            'weight' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(CustomerOrderItem $customerOrderItem): void {})
        ;
    }
}
