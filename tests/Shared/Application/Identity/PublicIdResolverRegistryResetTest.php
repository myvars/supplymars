<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Identity;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Shared\Application\Identity\PublicIdResolverRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry outlives a single request in a Messenger worker (and in FrankenPHP worker mode),
 * so its cache must be emptied by the container's services resetter between messages and requests.
 */
final class PublicIdResolverRegistryResetTest extends KernelTestCase
{
    private PublicIdResolverRegistry $registry;

    private EntityManagerInterface $em;

    private ResetInterface $servicesResetter;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->registry = $container->get(PublicIdResolverRegistry::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->servicesResetter = $container->get('services_resetter');
    }

    public function testMissIsForgottenAfterReset(): void
    {
        $manufacturer = Manufacturer::create('Olympus Mons Tools', true);

        self::assertNull($this->registry->resolve($manufacturer->getPublicId()));

        $this->em->persist($manufacturer);
        $this->em->flush();

        // Still cached as missing until the reset
        self::assertNull($this->registry->resolve($manufacturer->getPublicId()));

        $this->servicesResetter->reset();

        self::assertSame($manufacturer->getId(), $this->registry->resolve($manufacturer->getPublicId()));
    }

    public function testHitIsForgottenAfterReset(): void
    {
        $manufacturer = Manufacturer::create('Valles Marineris Supply', true);
        $this->em->persist($manufacturer);
        $this->em->flush();

        $publicId = $manufacturer->getPublicId();

        self::assertSame($manufacturer->getId(), $this->registry->resolve($publicId));

        $this->em->remove($manufacturer);
        $this->em->flush();

        $this->servicesResetter->reset();

        self::assertNull($this->registry->resolve($publicId));
    }
}
