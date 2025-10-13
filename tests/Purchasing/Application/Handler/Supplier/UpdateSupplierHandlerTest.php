<?php

namespace App\Tests\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\UpdateSupplier;
use App\Purchasing\Application\Handler\Supplier\UpdateSupplierHandler;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Tests\Shared\Factory\SupplierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSupplierHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateSupplierHandler $handler;
    private SupplierRepository $suppliers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateSupplierHandler::class);
        $this->suppliers = self::getContainer()->get(SupplierRepository::class);
    }

    public function testHandleUpdatesSupplier(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Before', 'isActive' => true]);
        $publicId = $supplier->getPublicId();

        $command = new UpdateSupplier(
            id: $publicId,
            name: 'Updated Supplier',
            isActive: false
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $persisted = $this->suppliers->getByPublicId($publicId);
        self::assertSame('Updated Supplier', $persisted->getName());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenSupplierNotFound(): void
    {
        $missingId = SupplierPublicId::new();

        $command = new UpdateSupplier(
            id: $missingId,
            name: 'X',
            isActive: true
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier not found', $result->message);
    }

    public function testHandleThrowsWhenNameEmpty(): void
    {
        $supplier = SupplierFactory::createOne();
        $publicId = $supplier->getPublicId();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Supplier name cannot be empty');

        $command = new UpdateSupplier(
            id: $publicId,
            name: '',
            isActive: true
        );

        ($this->handler)($command);
    }
}
