<?php

namespace App\Tests\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\CreateManufacturer;
use App\Catalog\Application\Handler\Manufacturer\CreateManufacturerHandler;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateManufacturerHandlerTest extends KernelTestCase
{
    private CreateManufacturerHandler $handler;
    private ManufacturerRepository $manufacturers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateManufacturerHandler::class);
        $this->manufacturers = self::getContainer()->get(ManufacturerRepository::class);
    }

    public function testHandleCreatesManufacturer(): void
    {
        $command = new CreateManufacturer(
            name: 'New Manufacturer',
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $manufacturerId = $result->payload;
        $persisted = $this->manufacturers->get($manufacturerId);
        self::assertSame('New Manufacturer', $persisted->getName());
        self::assertTrue($persisted->isActive());
    }

    public function testHandleFailsOnInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manufacturer name cannot be empty');

        $command = new CreateManufacturer(
            name: '   ',
            isActive: true,
        );

        ($this->handler)($command);
    }
}
