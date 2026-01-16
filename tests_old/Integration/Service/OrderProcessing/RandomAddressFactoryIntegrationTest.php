<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Infrastructure\Factory\RandomAddressFactory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;

class RandomAddressFactoryIntegrationTest extends KernelTestCase
{
    use Factories;

    private RandomAddressFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();
        $faker = static::getContainer()->get(Generator::class);
        $this->factory = new RandomAddressFactory($faker);
    }

    public function testCreateReturnsValidAddress(): void
    {
        $user = UserFactory::createOne();

        $address = $this->factory->create($user);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame($user, $address->getCustomer());
        $this->assertNotEmpty($address->getCity());
        $this->assertSame('Mars Colony', $address->getCountry());
        $this->assertSame('Red Zone', $address->getCounty());
        $this->assertNotEmpty($address->getPostCode());
        $this->assertNotEmpty($address->getStreet());
        $this->assertNotEmpty($address->getPhoneNumber());
        $this->assertSame($user->getEmail(), $address->getEmail());
        $this->assertSame($user->getFullName(), $address->getFullName());
    }
}
