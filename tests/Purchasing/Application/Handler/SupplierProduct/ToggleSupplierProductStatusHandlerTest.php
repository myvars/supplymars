<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\ToggleSupplierProductStatus;
use App\Purchasing\Application\Handler\SupplierProduct\ToggleSupplierProductStatusHandler;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class ToggleSupplierProductStatusHandlerTest extends KernelTestCase
{
    use Factories;

    private ToggleSupplierProductStatusHandler $handler;
    private SupplierProductRepository $supplierProducts;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(ToggleSupplierProductStatusHandler::class);
        $this->supplierProducts = self::getContainer()->get(SupplierProductRepository::class);
    }

    public function testHandleTogglesStatus(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'isActive' => true,
            'product' => ProductFactory::createOne(),
        ]);

        $command = new ToggleSupplierProductStatus($supplierProduct->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $reloaded = $this->supplierProducts->getByPublicId($supplierProduct->getPublicId());
        self::assertFalse($reloaded->isActive());
    }

    public function testHandleFailsWhenNotFound(): void
    {
        $missingId = SupplierProductPublicId::new();

        $command = new ToggleSupplierProductStatus($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not found', $result->message);
    }
}
