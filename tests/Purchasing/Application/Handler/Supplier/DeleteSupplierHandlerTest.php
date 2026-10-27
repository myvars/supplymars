<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\DeleteSupplier;
use App\Purchasing\Application\Handler\Supplier\DeleteSupplierHandler;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Story\StaffUserStory;
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

    #[WithStory(StaffUserStory::class)]
    public function testRefusesStaffUser(): void
    {
        $supplier = SupplierFactory::createOne();

        $result = ($this->handler)(new DeleteSupplier($supplier->getPublicId()));

        self::assertFalse($result->ok);
        self::assertSame('Deleting is disabled for this user.', $result->message);
        self::assertNotNull($this->suppliers->getByPublicId($supplier->getPublicId()));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierHasSupplierProducts(): void
    {
        $supplier = SupplierFactory::createOne();
        SupplierProductFactory::createOne(['supplier' => $supplier]);

        $this->assertRefusedAsInUse($supplier);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierHasPurchaseOrders(): void
    {
        $supplier = SupplierFactory::createOne();
        PurchaseOrderFactory::createOne(['supplier' => $supplier]);

        $this->assertRefusedAsInUse($supplier);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierHasCategoryMappings(): void
    {
        $supplier = SupplierFactory::createOne();
        SupplierCategoryFactory::createOne(['supplier' => $supplier]);

        $this->assertRefusedAsInUse($supplier);
    }

    private function assertRefusedAsInUse(Supplier $supplier): void
    {
        $result = ($this->handler)(new DeleteSupplier($supplier->getPublicId()));

        self::assertFalse($result->ok);
        self::assertStringContainsString('deactivate it instead', $result->message);
        self::assertNotNull($this->suppliers->getByPublicId($supplier->getPublicId()));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierHasSubcategoryMappings(): void
    {
        $supplier = SupplierFactory::createOne();
        SupplierSubcategoryFactory::createOne(['supplier' => $supplier]);

        $this->assertRefusedAsInUse($supplier);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierHasManufacturerMappings(): void
    {
        $supplier = SupplierFactory::createOne();
        SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $this->assertRefusedAsInUse($supplier);
    }
}
