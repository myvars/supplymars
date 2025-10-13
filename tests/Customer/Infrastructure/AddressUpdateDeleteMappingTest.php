<?php

namespace App\Tests\Customer\Infrastructure;

use App\Customer\Domain\Model\Address\Address;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AddressUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateRoundTripPersistsValues(): void
    {
        $customer = UserFactory::new()->create();

        $addr = Address::create(
            fullName: 'Before Name',
            companyName: null,
            street: 'Before St',
            street2: null,
            city: 'Before City',
            county: 'Before County',
            postCode: 'B-10',
            country: 'Mars Colony',
            phoneNumber: null,
            email: 'before@example.com',
            customer: $customer,
            isDefaultShippingAddress: false,
            isDefaultBillingAddress: false
        );

        $this->em->persist($addr);
        $this->em->flush();
        $id = $addr->getId();

        $this->em->clear();

        $reloaded = $this->em->getRepository(Address::class)->find($id);
        self::assertSame('Before Name', $reloaded->getFullName());
        self::assertNull($reloaded->getCompanyName());
        self::assertSame('Before St', $reloaded->getStreet());
        self::assertNull($reloaded->getStreet2());
        self::assertSame('Before City', $reloaded->getCity());
        self::assertSame('Before County', $reloaded->getCounty());
        self::assertSame('B-10', $reloaded->getPostCode());
        self::assertSame('Mars Colony', $reloaded->getCountry());
        self::assertNull($reloaded->getPhoneNumber());
        self::assertSame('before@example.com', $reloaded->getEmail());
        self::assertSame($customer->getId(), $reloaded->getCustomer()?->getId());
        self::assertFalse($reloaded->isDefaultShippingAddress());
        self::assertFalse($reloaded->isDefaultBillingAddress());
    }

    public function testDeleteRemovesRow(): void
    {
        $customer = UserFactory::new()->create();

        $addr = Address::create(
            fullName: 'To Delete',
            companyName: null,
            street: 'Del St',
            street2: null,
            city: 'Del City',
            county: 'Del County',
            postCode: 'D-01',
            country: 'Mars Colony',
            phoneNumber: null,
            email: null,
            customer: $customer,
            isDefaultShippingAddress: false,
            isDefaultBillingAddress: false
        );

        $this->em->persist($addr);
        $this->em->flush();
        $id = $addr->getId();

        $this->em->remove($addr);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(Address::class)->find($id));
    }
}
