<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\MapSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\MapSupplierProductHandler;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class MapSupplierProductHandlerTest extends KernelTestCase
{
    use Factories;

    private MapSupplierProductHandler $handler;
    private SupplierProductRepository $supplierProducts;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(MapSupplierProductHandler::class);
        $this->supplierProducts = self::getContainer()->get(SupplierProductRepository::class);
        VatRateFactory::new()->withStandardRate()->create();
    }

    #[WithStory(StaffUserStory::class)]
    public function testHandleMapsWhenUnmapped(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
        ]);

        $command = new MapSupplierProduct($supplierProduct->getPublicId());

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertStringContainsString('Supplier product mapped', $result->message);

        $reloaded = $this->supplierProducts->getByPublicId($supplierProduct->getPublicId());
        self::assertNotNull($reloaded->getProduct());
    }

    public function testHandleFailsWhenAlreadyMapped(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => ProductFactory::createOne(),
        ]);

        $command = new MapSupplierProduct($supplierProduct->getPublicId());

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product already mapped', $result->message);
    }

    public function testHandleFailsWhenNotFound(): void
    {
        $missingId = SupplierProductPublicId::new();

        $command = new MapSupplierProduct($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not found', $result->message);
    }
}
