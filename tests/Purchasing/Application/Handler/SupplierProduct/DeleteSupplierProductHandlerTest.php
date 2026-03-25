<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\DeleteSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\DeleteSupplierProductHandler;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\SuperAdminUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class DeleteSupplierProductHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteSupplierProductHandler $handler;

    private SupplierProductRepository $supplierProducts;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteSupplierProductHandler::class);
        $this->supplierProducts = self::getContainer()->get(SupplierProductRepository::class);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testDeletesExistingSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['product' => null]);
        $publicId = $supplierProduct->getPublicId();

        $command = new DeleteSupplierProduct($publicId);

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Supplier product deleted', $result->message);
        self::assertNull($this->supplierProducts->getByPublicId($publicId));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenSupplierProductNotFound(): void
    {
        $missingId = SupplierProductPublicId::new();

        $command = new DeleteSupplierProduct($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not found', $result->message);
    }
}
