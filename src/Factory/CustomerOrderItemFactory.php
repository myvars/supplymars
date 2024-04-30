<?php

namespace App\Factory;

use App\Entity\CustomerOrderItem;
use App\Repository\CustomerOrderItemRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CustomerOrderItem>
 *
 * @method        CustomerOrderItem|Proxy                     create(array|callable $attributes = [])
 * @method static CustomerOrderItem|Proxy                     createOne(array $attributes = [])
 * @method static CustomerOrderItem|Proxy                     find(object|array|mixed $criteria)
 * @method static CustomerOrderItem|Proxy                     findOrCreate(array $attributes)
 * @method static CustomerOrderItem|Proxy                     first(string $sortedField = 'id')
 * @method static CustomerOrderItem|Proxy                     last(string $sortedField = 'id')
 * @method static CustomerOrderItem|Proxy                     random(array $attributes = [])
 * @method static CustomerOrderItem|Proxy                     randomOrCreate(array $attributes = [])
 * @method static CustomerOrderItemRepository|RepositoryProxy repository()
 * @method static CustomerOrderItem[]|Proxy[]                 all()
 * @method static CustomerOrderItem[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static CustomerOrderItem[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static CustomerOrderItem[]|Proxy[]                 findBy(array $attributes)
 * @method static CustomerOrderItem[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CustomerOrderItem[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class CustomerOrderItemFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
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
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(CustomerOrderItem $customerOrderItem): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CustomerOrderItem::class;
    }
}
