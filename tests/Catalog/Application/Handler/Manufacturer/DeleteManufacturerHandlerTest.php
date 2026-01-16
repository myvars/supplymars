<?php

namespace App\Tests\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\DeleteManufacturer;
use App\Catalog\Application\Handler\Manufacturer\DeleteManufacturerHandler;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Tests\Shared\Factory\ManufacturerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DeleteManufacturerHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteManufacturerHandler $handler;

    private ManufacturerRepository $manufacturers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteManufacturerHandler::class);
        $this->manufacturers = self::getContainer()->get(ManufacturerRepository::class);
    }

    public function testDeletesExistingManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $publicId = $manufacturer->getPublicId();

        $command = new DeleteManufacturer($manufacturer->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Manufacturer deleted', $result->message);
        self::assertNull($this->manufacturers->getByPublicId($publicId));
    }

    public function testReturnsFailWhenManufacturerNotFound(): void
    {
        $missingId = ManufacturerPublicId::new();

        $command = new DeleteManufacturer($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Manufacturer not found', $result->message);
    }
}
