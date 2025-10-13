<?php

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\Address\MarsCity;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Address>
 */
final class AddressFactory extends PersistentObjectFactory
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
            'fullName' => self::faker()->name(),
            'companyName' => 1 === random_int(1, 5) ? self::faker()->company() : null,
            'street' => self::faker()->streetAddress(),
            'street2' => 1 === random_int(1, 5) ? self::faker()->streetName() : null,
            'city' => $cityData->value,
            'county' => 'Red Zone',
            'postCode' => $cityData->sectorCode().'-'.random_int(10, 50),
            'country' => 'Mars Colony',
            'phoneNumber' => self::faker()->phoneNumber(),
            'email' => self::faker()->email(),
            'customer' => LazyValue::memoize(fn () => UserFactory::createOne()),
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
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
