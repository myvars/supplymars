<?php

namespace App\Factory;

use App\Entity\Address;
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
    protected function defaults(): array|callable
    {
        return [
            'city' => self::faker()->city(),
            'country' => 'United Kingdom',
            'county' => 'South Yorkshire',
            'customer' => UserFactory::new(),
            'postCode' => self::faker()->postcode(),
            'street' => self::faker()->streetAddress(),
            'phoneNumber' => self::faker()->phoneNumber(),
            'email' => self::faker()->email(),
            'fullName' => self::faker()->name(),
            'companyName' => random_int(1,5) === 1 ? self::faker()->company() : null,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Address $address): void {})
        ;
    }
}
