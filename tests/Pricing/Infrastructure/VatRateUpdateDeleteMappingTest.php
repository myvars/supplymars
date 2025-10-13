<?php

namespace App\Tests\Pricing\Infrastructure;

use App\Pricing\Domain\Model\VatRate\VatRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class VatRateUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $vat = VatRate::create(
            name: 'Before',
            rate: '5.00'
        );

        $this->em->persist($vat);
        $this->em->flush();
        $id = $vat->getId();

        $loaded = $this->em->getRepository(VatRate::class)->find($id);
        $loaded->update(
            name: 'After',
            rate: '7.50'
        );

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(VatRate::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertSame('7.50', $reloaded->getRate());
    }

    public function testDeleteRemovesRow(): void
    {
        $vat = VatRate::create(
            name: 'To Delete',
            rate: '3.00'
        );

        $this->em->persist($vat);
        $this->em->flush();
        $id = $vat->getId();

        $this->em->remove($vat);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(VatRate::class)->find($id));
    }
}
