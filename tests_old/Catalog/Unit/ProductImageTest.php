<?php

namespace App\Tests\Catalog\Unit;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductImageTest extends TestCase
{
    public function testCreateFromUploadedFile(): void
    {
        $product = $this->createMock(Product::class);
        $uploadedFile = $this->createMock(UploadedFile::class);

        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->method('getClientOriginalName')->willReturn('test.jpg');

        $productImage = ProductImage::createFromUploadedFile($product, $uploadedFile, 1);

        $this->assertSame($product, $productImage->getProduct());
        $this->assertEquals(1, $productImage->getPosition());
        $this->assertEquals(1024, $productImage->getImageSize());
        $this->assertEquals('image/jpeg', $productImage->getImageMimeType());
        $this->assertEquals('test.jpg', $productImage->getImageOriginalName());
        $this->assertSame($uploadedFile, $productImage->getImageFile());
    }

    public function testUpdateImageName(): void
    {
        $product = $this->createMock(Product::class);
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->method('getClientOriginalName')->willReturn('test.jpg');

        $productImage = ProductImage::createFromUploadedFile($product, $uploadedFile, 1);
        $productImage->changeImageName('new_image_name');

        $this->assertEquals('new_image_name', $productImage->getImageName());
    }

    public function testUpdatePosition(): void
    {
        $product = $this->createMock(Product::class);
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->method('getClientOriginalName')->willReturn('test.jpg');

        $productImage = ProductImage::createFromUploadedFile($product, $uploadedFile, 1);
        $productImage->changePosition(2);

        $this->assertEquals(2, $productImage->getPosition());
    }
}
