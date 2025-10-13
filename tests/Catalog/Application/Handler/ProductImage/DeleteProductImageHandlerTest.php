<?php

namespace App\Tests\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\DeleteProductImage;
use App\Catalog\Application\Handler\ProductImage\DeleteProductImageHandler;
use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;
use App\Catalog\Domain\Repository\ProductImageRepository;
use App\Tests\Shared\Factory\ProductImageFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DeleteProductImageHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteProductImageHandler $handler;
    private ProductImageRepository $productImages;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteProductImageHandler::class);
        $this->productImages = self::getContainer()->get(ProductImageRepository::class);
    }

    public function testDeletesExistingProductImage(): void
    {
        $productImage = ProductImageFactory::createOne();
        $publicId = $productImage->getPublicId();

        $command = new DeleteProductImage($publicId);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Product image deleted', $result->message);
        self::assertNull($this->productImages->getByPublicId($publicId));
    }

    public function testFailsWhenProductImageNotFound(): void
    {
        $missingId = ProductImagePublicId::new();

        $command = new DeleteProductImage($missingId);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product image not found', $result->message);
    }
}
