<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\DeleteProduct;
use App\Catalog\Application\Handler\Product\DeleteProductHandler;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductImageFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use App\Tests\Shared\Story\SuperAdminUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class DeleteProductHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteProductHandler $handler;

    private ProductRepository $products;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteProductHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testDeletesExistingProduct(): void
    {
        $product = ProductFactory::createOne();
        $publicId = $product->getPublicId();

        $command = new DeleteProduct($publicId);

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Product deleted', $result->message);
        self::assertNull($this->products->getByPublicId($publicId));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenProductNotFound(): void
    {
        $missingId = ProductPublicId::new();

        $command = new DeleteProduct($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product not found', $result->message);
    }

    #[WithStory(StaffUserStory::class)]
    public function testRefusesStaffUser(): void
    {
        $product = ProductFactory::createOne();

        $result = ($this->handler)(new DeleteProduct($product->getPublicId()));

        self::assertFalse($result->ok);
        self::assertSame('Deleting is disabled for this user.', $result->message);
        self::assertNotNull($this->products->getByPublicId($product->getPublicId()));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenProductHasImages(): void
    {
        $product = ProductFactory::createOne();
        ProductImageFactory::createOne(['product' => $product]);

        $this->assertRefusedAsInUse($product);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenProductHasSupplierProducts(): void
    {
        $product = ProductFactory::createOne();
        SupplierProductFactory::createOne(['product' => $product]);

        $this->assertRefusedAsInUse($product);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenProductIsOnAnOrder(): void
    {
        $product = ProductFactory::createOne();
        CustomerOrderItemFactory::createOne(['product' => $product]);

        $this->assertRefusedAsInUse($product);
    }

    private function assertRefusedAsInUse(Product $product): void
    {
        $result = ($this->handler)(new DeleteProduct($product->getPublicId()));

        self::assertFalse($result->ok);
        self::assertStringContainsString('deactivate it instead', $result->message);
        self::assertNotNull($this->products->getByPublicId($product->getPublicId()));
    }
}
