<?php

namespace App\Tests\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\DeleteProduct;
use App\Catalog\Application\Handler\Product\DeleteProductHandler;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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

    public function testFailsWhenProductNotFound(): void
    {
        $missingId = ProductPublicId::new();

        $command = new DeleteProduct($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product not found', $result->message);
    }
}
