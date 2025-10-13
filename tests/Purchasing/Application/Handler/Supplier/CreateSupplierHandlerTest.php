<?php

namespace App\Tests\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\CreateSupplier;
use App\Purchasing\Application\Handler\Supplier\CreateSupplierHandler;
use App\Purchasing\Domain\Repository\SupplierRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreateSupplierHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateSupplierHandler $handler;
    private SupplierRepository $suppliers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateSupplierHandler::class);
        $this->suppliers = self::getContainer()->get(SupplierRepository::class);
    }

    public function testHandleCreatesSupplier(): void
    {
        $command = new CreateSupplier(
            name: 'New Supplier',
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $supplierId = $result->payload;

        $persisted = $this->suppliers->get($supplierId);
        self::assertSame('New Supplier', $persisted->getName());
        self::assertTrue($persisted->isActive());
    }

    public function testHandleThrowsWhenNameEmpty(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Supplier name cannot be empty');

        $command = new CreateSupplier(
            name: '',
            isActive: true,
        );

        ($this->handler)($command);
    }
}
