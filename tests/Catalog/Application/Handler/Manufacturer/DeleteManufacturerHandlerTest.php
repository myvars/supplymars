<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\DeleteManufacturer;
use App\Catalog\Application\Handler\Manufacturer\DeleteManufacturerHandler;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\save;

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

    public function testFailsWhenManufacturerHasProducts(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        ProductFactory::createOne(['manufacturer' => $manufacturer]);

        $command = new DeleteManufacturer($manufacturer->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Has products', $result->message);
        self::assertNotNull($this->manufacturers->getByPublicId($manufacturer->getPublicId()));
    }

    public function testFailsWhenManufacturerHasSupplierMappings(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $manufacturer->addSupplierManufacturer(SupplierManufacturerFactory::createOne());
        save($manufacturer);

        $result = ($this->handler)(new DeleteManufacturer($manufacturer->getPublicId()));

        self::assertFalse($result->ok);
        self::assertStringContainsString('supplier mappings', $result->message);
        self::assertNotNull($this->manufacturers->getByPublicId($manufacturer->getPublicId()));
    }
}
