<?php

namespace App\Factory;

use App\Entity\CustomerOrder;
use App\Repository\CustomerOrderRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CustomerOrder>
 *
 * @method        CustomerOrder|Proxy                     create(array|callable $attributes = [])
 * @method static CustomerOrder|Proxy                     createOne(array $attributes = [])
 * @method static CustomerOrder|Proxy                     find(object|array|mixed $criteria)
 * @method static CustomerOrder|Proxy                     findOrCreate(array $attributes)
 * @method static CustomerOrder|Proxy                     first(string $sortedField = 'id')
 * @method static CustomerOrder|Proxy                     last(string $sortedField = 'id')
 * @method static CustomerOrder|Proxy                     random(array $attributes = [])
 * @method static CustomerOrder|Proxy                     randomOrCreate(array $attributes = [])
 * @method static CustomerOrderRepository|RepositoryProxy repository()
 * @method static CustomerOrder[]|Proxy[]                 all()
 * @method static CustomerOrder[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static CustomerOrder[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static CustomerOrder[]|Proxy[]                 findBy(array $attributes)
 * @method static CustomerOrder[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CustomerOrder[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class CustomerOrderFactory extends ModelFactory
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
        return [
            'billingAddress' => AddressFactory::new(),
            'customer' => UserFactory::new(),
            'dueDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'shippingAddress' => AddressFactory::new(),
            'shippingMethod' => self::faker()->text(20),
            'status' => self::faker()->text(20),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(CustomerOrder $customerOrder): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CustomerOrder::class;
    }
}
