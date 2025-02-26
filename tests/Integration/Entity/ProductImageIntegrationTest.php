<?php

namespace App\Tests\Integration\Entity;

use App\Factory\ProductFactory;
use App\Factory\ProductImageFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductImageIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProductImage(): void
    {
        $product = ProductFactory::createOne();
        $productImage = ProductImageFactory::createOne([
            'product' => $product,
            'position' => 1,
            'uploadedFile' => new UploadedFile(
                __DIR__ . '/../../Resources/dummy-image.jpg',
                'test.jpg',
                'image/jpeg',
                null,
                true
            ),
        ]);

        $errors = $this->validator->validate($productImage);
        $this->assertCount(0, $errors);
    }

    public function testInvalidProductImageType(): void
    {
        $product = ProductFactory::createOne();
        $productImage = ProductImageFactory::createOne([
            'product' => $product,
            'position' => 1,
            'uploadedFile' => new UploadedFile(
                __DIR__ . '/../../Resources/invalid-image.txt',
                'invalid-image.txt',
                'text/plain',
                null,
                true
            ),
        ]);

        $violations = $this->validator->validate($productImage);
        $this->assertCount(2, $violations);
        $this->assertEquals('This file is not a valid image.', $violations[0]->getMessage());
        $this->assertEquals('Please upload a valid file type', $violations[1]->getMessage());
    }

    public function testImageNameIsRequired(): void
    {
        $productImage = ProductImageFactory::new()->withoutPersisting()->create(['imageName' => '']);

        $violations = $this->validator->validate($productImage);
        $this->assertSame('Please enter an image name', $violations[0]->getMessage());
    }

    public function testInvalidPosition(): void
    {
        $productImage = ProductImageFactory::new()->withoutPersisting()->create(['position' => -1]);

        $violations = $this->validator->validate($productImage);
        $this->assertSame('Position must be greater than 0', $violations[0]->getMessage());
    }

    public function testProductImagePersistence(): void
    {
        $product = ProductFactory::createOne()->_real();
        $productImage = ProductImageFactory::createOne(['product' => $product]);

        $persistedProductImage = ProductImageFactory::repository()->find($productImage->getId());
        $this->assertSame($product, $persistedProductImage->getProduct());
    }
}