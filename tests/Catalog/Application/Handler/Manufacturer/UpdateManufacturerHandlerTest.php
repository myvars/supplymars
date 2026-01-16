<?php

namespace App\Tests\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\UpdateManufacturer;
use App\Catalog\Application\Handler\Manufacturer\UpdateManufacturerHandler;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Tests\Shared\Factory\ManufacturerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateManufacturerHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateManufacturerHandler $handler;

    private ManufacturerRepository $manufacturers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateManufacturerHandler::class);
        $this->manufacturers = self::getContainer()->get(ManufacturerRepository::class);
    }

    public function testUpdatesExistingManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Old Name', 'isActive' => false]);
        $command = new UpdateManufacturer(
            id: $manufacturer->getPublicId(),
            name: 'New Name',
            isActive: true
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Manufacturer updated', $result->message);

        $reloaded = $this->manufacturers->getByPublicId($manufacturer->getPublicId());
        self::assertSame('New Name', $reloaded->getName());
        self::assertTrue($reloaded->isActive());
    }

    public function testFailsWhenManufacturerNotFound(): void
    {
        $missingId = ManufacturerPublicId::new();
        $command = new UpdateManufacturer(
            id: $missingId,
            name: 'Irrelevant',
            isActive: true
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Manufacturer not found', $result->message);
    }
}
