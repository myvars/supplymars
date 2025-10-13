<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\RemoveSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\RemoveSupplierProductHandler;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class RemoveSupplierProductHandlerTest extends KernelTestCase
{
    use Factories;

    private RemoveSupplierProductHandler $handler;
    private SupplierProductRepository $supplierProducts;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(RemoveSupplierProductHandler::class);
        $this->supplierProducts = self::getContainer()->get(SupplierProductRepository::class);
    }

    public function testHandleRemovesMappingWhenMapped(): void
    {
        $product = ProductFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => $product,
        ]);

        $command = new RemoveSupplierProduct($supplierProduct->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $reloaded = $this->supplierProducts->getByPublicId($supplierProduct->getPublicId());
        self::assertNull($reloaded->getProduct());
    }

    public function testHandleFailsWhenNotMapped(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
        ]);

        $command = new RemoveSupplierProduct($supplierProduct->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not mapped', $result->message);
    }

    public function testHandleFailsWhenNotFound(): void
    {
        $missingId = SupplierProductPublicId::new();

        $command = new RemoveSupplierProduct($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not found.', $result->message);
    }
}
