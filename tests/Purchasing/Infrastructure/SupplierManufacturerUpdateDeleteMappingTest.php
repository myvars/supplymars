<?php

namespace App\Tests\Purchasing\Infrastructure;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Tests\Shared\Factory\SupplierFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class SupplierManufacturerUpdateDeleteMappingTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $supplierA = SupplierFactory::createOne();
        $supplierB = SupplierFactory::createOne();

        $manufacturer = SupplierManufacturer::create(
            name: 'Before',
            supplier: $supplierA,
        );

        $this->em->persist($manufacturer);
        $this->em->flush();

        $id = $manufacturer->getId();

        /** @var SupplierManufacturer $loaded */
        $loaded = $this->em->getRepository(SupplierManufacturer::class)->find($id);
        $loaded->update(
            name: 'After',
            supplier: $supplierB,
        );

        $this->em->flush();
        $this->em->clear();

        /** @var SupplierManufacturer $reloaded */
        $reloaded = $this->em->getRepository(SupplierManufacturer::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertSame($supplierB->getId(), $reloaded->getSupplier()->getId());
    }

    public function testDeleteRemovesRow(): void
    {
        $supplier = SupplierFactory::createOne();

        $manufacturer = SupplierManufacturer::create(
            name: 'To Delete',
            supplier: $supplier,
        );
        $this->em->persist($manufacturer);
        $this->em->flush();

        $id = $manufacturer->getId();

        $this->em->remove($manufacturer);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(SupplierManufacturer::class)->find($id));
    }
}
