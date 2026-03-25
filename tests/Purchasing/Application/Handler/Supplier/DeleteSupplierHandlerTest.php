<?php

namespace App\Tests\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\DeleteSupplier;
use App\Purchasing\Application\Handler\Supplier\DeleteSupplierHandler;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Story\SuperAdminUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class DeleteSupplierHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteSupplierHandler $handler;

    private SupplierRepository $suppliers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteSupplierHandler::class);
        $this->suppliers = self::getContainer()->get(SupplierRepository::class);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testDeletesExistingSupplier(): void
    {
        $supplier = SupplierFactory::createOne();
        $publicId = $supplier->getPublicId();

        $command = new DeleteSupplier($publicId);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Supplier deleted', $result->message);
        self::assertNull($this->suppliers->getByPublicId($publicId));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierNotFound(): void
    {
        $missingId = SupplierPublicId::new();

        $command = new DeleteSupplier($missingId);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier not found', $result->message);
    }
}
