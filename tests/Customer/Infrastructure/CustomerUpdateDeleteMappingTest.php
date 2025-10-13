<?php

namespace App\Tests\Customer\Infrastructure;

use App\Customer\Domain\Model\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CustomerUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $customer = User::create(
            fullName: 'Before Name',
            email: 'before@example.com',
            isStaff: true,
            isVerified: true,
        );
        $customer->setPassword('password123');

        $this->em->persist($customer);
        $this->em->flush();
        $id = $customer->getId();

        $loaded = $this->em->getRepository(User::class)->find($id);

        $loaded->update(
            fullName: 'After Name',
            email: 'after@example.com',
            isStaff: false,
            isVerified: false,
        );

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(User::class)->find($id);
        self::assertSame('After Name', $reloaded->getFullName());
        self::assertSame('after@example.com', $reloaded->getEmail());
        self::assertFalse($reloaded->isVerified());
        self::assertFalse($reloaded->isStaff());
        self::assertFalse($reloaded->isAdmin());
    }

    public function testDeleteRemovesRow(): void
    {
        $customer = User::create(
            fullName: 'To Delete',
            email: 'delete@example.com',
            isStaff: false,
            isVerified: true,
        );
        $customer->setPassword('password123');

        $this->em->persist($customer);
        $this->em->flush();
        $id = $customer->getId();

        $this->em->remove($customer);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(User::class)->find($id));
    }
}
