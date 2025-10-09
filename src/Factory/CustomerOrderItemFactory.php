<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use App\Entity\CustomerOrderItem;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;

/**
 * @extends PersistentObjectFactory<CustomerOrderItem>
 */
final class CustomerOrderItemFactory extends PersistentObjectFactory
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
    protected function defaults(): array
    {
        return [
            'customerOrder' => LazyValue::memoize(fn (): CustomerOrderFactory => CustomerOrderFactory::new()),
            'product' => LazyValue::memoize(fn (): ProductFactory => ProductFactory::new()),
            'quantity' => 1,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('createFromProduct')
        );
    }
}
