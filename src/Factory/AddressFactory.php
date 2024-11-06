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
        $cityData = $this->marsCity();

        return [
            'city' => $cityData['name'],
            'country' => 'Mars Colony',
            'county' => 'Red Zone',
            'customer' => UserFactory::new(),
            'postCode' => $cityData['sectorCode'] . '-' . random_int(10, 50),
            'street' => self::faker()->streetAddress(),
            'phoneNumber' => self::faker()->phoneNumber(),
            'email' => self::faker()->email(),
            'fullName' => self::faker()->name(),
            'companyName' => random_int(1,5) === 1 ? self::faker()->company() : null,
        ];
    }

    // Helper function to randomly select a Mars city and its sector code
    private function marsCity(): array
    {
        $marsCities = [
            ['name' => 'Olympia', 'sectorCode' => 'OM'],
            ['name' => 'Vallis', 'sectorCode' => 'VM'],
            ['name' => 'Gale', 'sectorCode' => 'GC'],
            ['name' => 'Elysium', 'sectorCode' => 'EP'],
            ['name' => 'Red Dune', 'sectorCode' => 'RD'],
            ['name' => 'Crimson', 'sectorCode' => 'CP'],
            ['name' => 'Ironhold', 'sectorCode' => 'ID'],
            ['name' => 'Arcadia', 'sectorCode' => 'AP'],
            ['name' => 'Amazonis', 'sectorCode' => 'AS'],
            ['name' => 'Hellas', 'sectorCode' => 'HB'],
            ['name' => 'Isidis', 'sectorCode' => 'IP'],
            ['name' => 'Noctis', 'sectorCode' => 'NL'],
            ['name' => 'Cydonia', 'sectorCode' => 'CY'],
            ['name' => 'Tharsis', 'sectorCode' => 'TH'],
            ['name' => 'Utopia', 'sectorCode' => 'UP'],
        ];

        return $marsCities[array_rand($marsCities)];
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
