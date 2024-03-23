<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\ProductImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductImageTest extends TestCase
{
    public function testGetSetProduct(): void
    {
        $product = $this->createMock(Product::class);

        $productImage = new ProductImage();
        $productImage->setProduct($product);

        $this->assertEquals($product, $productImage->getProduct());
    }

    public function testGetSetPosition(): void
    {
        $position = 1;
        $productImage = new ProductImage();
        $productImage->setPosition($position);

        $this->assertEquals(1, $productImage->getPosition());
    }

    public function testGetSetImageName(): void
    {
        $imageName = 'test-image.jpg';
        $productImage = new ProductImage();
        $productImage->setImageName($imageName);

        $this->assertEquals('test-image.jpg', $productImage->getImageName());
    }

    public function testGetSetImageFile(): void
    {
        $imageFile = $this->createMock(UploadedFile::class);
        $imageFile->method('getSize')->willReturn(1024);
        $imageFile->method('getMimeType')->willReturn('image/jpeg');
        $imageFile->method('getClientOriginalName')->willReturn('test-image.jpg');

        $productImage = new ProductImage();
        $productImage->setImageFile($imageFile);

        $this->assertEquals(1024, $productImage->getImageSize());
        $this->assertEquals('image/jpeg', $productImage->getImageMimeType());
        $this->assertEquals('test-image.jpg', $productImage->getImageOriginalName());
    }
}