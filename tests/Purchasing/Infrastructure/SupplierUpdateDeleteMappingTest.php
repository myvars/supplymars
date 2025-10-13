<?php

namespace App\Tests\Purchasing\Infrastructure;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $supplier = Supplier::create(
            name: 'Before',
            isActive: true
        );

        $this->em->persist($supplier);
        $this->em->flush();
        $id = $supplier->getId();

        /** @var Supplier $loaded */
        $loaded = $this->em->getRepository(Supplier::class)->find($id);
        $loaded->update(
            name: 'After',
            isActive: false
        );

        $this->em->flush();
        $this->em->clear();

        /** @var Supplier $reloaded */
        $reloaded = $this->em->getRepository(Supplier::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertFalse($reloaded->isActive());
    }

    public function testDeleteRemovesRow(): void
    {
        $supplier = Supplier::create(
            name: 'To Delete',
            isActive: true
        );

        $this->em->persist($supplier);
        $this->em->flush();
        $id = $supplier->getId();

        $this->em->remove($supplier);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(Supplier::class)->find($id));
    }
}
