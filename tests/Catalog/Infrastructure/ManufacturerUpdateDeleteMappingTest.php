<?php

namespace App\Tests\Catalog\Infrastructure;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ManufacturerUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $manufacturer = Manufacturer::create(
            name: 'Before',
            isActive: true,
        );

        $this->em->persist($manufacturer);
        $this->em->flush();

        $id = $manufacturer->getId();

        /** @var Manufacturer $loaded */
        $loaded = $this->em->getRepository(Manufacturer::class)->find($id);

        $loaded->update(
            name: 'After',
            isActive: false,
        );

        $this->em->flush();
        $this->em->clear();

        /** @var Manufacturer $reloaded */
        $reloaded = $this->em->getRepository(Manufacturer::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertFalse($reloaded->isActive());
    }

    public function testDeleteRemovesRow(): void
    {
        $manufacturer = Manufacturer::create(
            name: 'To Delete',
            isActive: true,
        );

        $this->em->persist($manufacturer);
        $this->em->flush();

        $id = $manufacturer->getId();

        $this->em->remove($manufacturer);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(Manufacturer::class)->find($id));
    }
}
