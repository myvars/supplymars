<?php

namespace App\Factory;

use App\Entity\Address;
use App\Enum\MarsCity;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Address>
 */
final class AddressFactory extends PersistentProxyObjectFactory
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
        return Address::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        $cityData = MarsCity::random();

        return [
            'city' => $cityData->value,
            'country' => 'Mars Colony',
            'county' => 'Red Zone',
            'customer' => LazyValue::memoize(fn (): UserFactory => UserFactory::new()),
            'postCode' => $cityData->sectorCode().'-'.random_int(10, 50),
            'street' => self::faker()->streetAddress(),
            'phoneNumber' => self::faker()->phoneNumber(),
            'email' => self::faker()->email(),
            'fullName' => self::faker()->name(),
            'companyName' => 1 === random_int(1, 5) ? self::faker()->company() : null,
            'street2' => 1 === random_int(1, 5) ? self::faker()->streetName() : null,
            'isDefaultShippingAddress' => false,
            'isDefaultBillingAddress' => false,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Address $address): void {})
        ;
    }
}
