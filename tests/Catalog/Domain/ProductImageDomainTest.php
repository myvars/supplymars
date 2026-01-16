<?php

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductImageDomainTest extends TestCase
{
    private function uploadedFile(string $name = 'test.png'): UploadedFile
    {
        $path = sys_get_temp_dir() . '/' . $name;
        file_put_contents($path, random_bytes(10));

        return new UploadedFile(
            path: $path,
            originalName: $name,
            mimeType: 'image/png',
            error: null,
            test: true,
        );
    }

    private function stubProduct(): Product
    {
        return $this->createStub(Product::class);
    }

    public function testCreateFromUploadedFileInitializesFields(): void
    {
        $file = $this->uploadedFile();
        $product = $this->stubProduct();

        $productImage = ProductImage::createFromUploadedFile(
            product: $product,
            uploadedFile: $file,
            position: 1,
        );

        self::assertSame(1, $productImage->getPosition());
        self::assertSame($file->getClientOriginalName(), $productImage->getImageOriginalName());
        self::assertSame($file->getMimeType(), $productImage->getImageMimeType());
        self::assertGreaterThan(0, $productImage->getImageSize());
        self::assertNotEmpty($productImage->getPublicId()->value());
    }

    public function testChangeImageNameUpdatesName(): void
    {
        $file = $this->uploadedFile();
        $product = $this->stubProduct();

        $productImage = ProductImage::createFromUploadedFile(
            product: $product,
            uploadedFile: $file,
            position: 2,
        );
        $productImage->changeImageName('stored-name.png');
        self::assertSame('stored-name.png', $productImage->getImageName());
    }

    public function testChangePositionUpdates(): void
    {
        $file = $this->uploadedFile();
        $product = $this->stubProduct();

        $productImage = ProductImage::createFromUploadedFile(
            product: $product,
            uploadedFile: $file,
            position: 1,
        );
        $productImage->changePosition(5);
        self::assertSame(5, $productImage->getPosition());
    }

    public function testChangePositionThrowsWhenInvalid(): void
    {
        $file = $this->uploadedFile();
        $product = $this->stubProduct();

        $productImage = ProductImage::createFromUploadedFile(
            product: $product,
            uploadedFile: $file,
            position: 1,
        );

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Position must be greater than 0');
        $productImage->changePosition(0);
    }
}
