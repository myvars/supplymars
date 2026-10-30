<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\Address\MarsCity;
use App\Customer\Domain\Model\User\User;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Address>
 */
final class AddressFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Address::class;
    }

    protected function defaults(): array
    {
        $cityData = MarsCity::random();

        return [
            'fullName' => self::faker()->name(),
            'companyName' => self::faker()->boolean(20) ? self::faker()->company() : null,
            'street' => self::faker()->streetAddress(),
            'street2' => self::faker()->boolean(20) ? self::faker()->streetName() : null,
            'city' => $cityData->value,
            'county' => 'Red Zone',
            'postCode' => $cityData->sectorCode() . '-' . self::faker()->numberBetween(10, 50),
            'country' => 'Mars Colony',
            'phoneNumber' => self::faker()->phoneNumber(),
            'email' => self::faker()->email(),
            'customer' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
            'isDefaultShippingAddress' => false,
            'isDefaultBillingAddress' => false,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
