<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\DeleteProductImage;
use App\Catalog\Application\Handler\ProductImage\DeleteProductImageHandler;
use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;
use App\Catalog\Domain\Repository\ProductImageRepository;
use App\Tests\Shared\Factory\ProductImageFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteProductImageHandlerTest extends KernelTestCase
{
    private DeleteProductImageHandler $handler;

    private ProductImageRepository $productImages;

    private FilesystemOperator $uploads;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteProductImageHandler::class);
        $this->productImages = self::getContainer()->get(ProductImageRepository::class);

        $uploads = self::getContainer()->get('oneup_flysystem.products_fs_filesystem');
        \assert($uploads instanceof FilesystemOperator);
        $this->uploads = $uploads;
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

    public function testRemovesTheFileFromStorage(): void
    {
        $productImage = ProductImageFactory::createOne();
        $path = self::getContainer()->getParameter('app.product_uploads') . $productImage->getImageName();
        $this->uploads->write($path, 'contents');

        try {
            $result = ($this->handler)(new DeleteProductImage($productImage->getPublicId()));

            self::assertTrue($result->ok);
            self::assertFalse($this->uploads->fileExists($path));
        } finally {
            $this->uploads->delete($path);
        }
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
