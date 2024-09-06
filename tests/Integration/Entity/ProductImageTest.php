<?php

namespace App\Tests\Integration\Entity;

use App\Entity\ProductImage;
use App\Factory\ProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductImageTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteProductImage(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';
        $dummyImage = new UploadedFile($dummyImagePath, 'dummy-image.jpg', 'image/jpeg', null, true);

        $productImage = new ProductImage();
        $productImage
            ->setProduct($product)
            ->setImageName('dummy-image.jpg')
            ->setPosition(1)
            ->setImageFile($dummyImage);

        $this->entityManager->persist($productImage);
        $this->entityManager->flush();

        $this->assertNotNull($productImage->getId());

        $productImage->setImageName('updated-image.jpg');
        $this->entityManager->flush();

        $this->assertEquals('updated-image.jpg', $productImage->getImageName());

        $this->entityManager->remove($productImage);
        $this->entityManager->flush();

        $this->assertNull($productImage->getId());
    }

    public function testProductImageValidation(): void
    {
        $productImage = new ProductImage();

        $result = $this->validator->validate($productImage);
        $this->assertCount(2, $result);
        $this->assertEquals('Please enter a product', $result[0]->getMessage());
        $this->assertEquals('Please upload an image', $result[1]->getMessage());
    }

    public function testSetImageFile(): void
    {
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';
        $dummyImage = new UploadedFile($dummyImagePath, 'dummy-image.jpg', 'image/jpeg', null, true);

        $productImage = new ProductImage();
        $productImage->setImageFile($dummyImage);

        $this->assertNotNull($productImage->getImageFile());
        $this->assertEquals('dummy-image.jpg', $productImage->getImageOriginalName());
        $this->assertEquals('image/jpeg', $productImage->getImageMimeType());
        $this->assertEquals(35769, $productImage->getImageSize());
    }

    public function testSetImageFileInvalid(): void
    {
        $product= ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $invalidImagePath = __DIR__ . '/../../Resources/invalid-image.txt';
        $invalidImage = new UploadedFile($invalidImagePath, 'invalid-image.txt', 'text/plain', null, true);

        $productImage = new ProductImage();
        $productImage->setProduct($product);
        $productImage->setImageFile($invalidImage);

        $result = $this->validator->validate($productImage);
        $this->assertCount(2, $result);
        $this->assertEquals('This file is not a valid image.', $result[0]->getMessage());
        $this->assertEquals('Please upload a valid file type', $result[1]->getMessage());
    }
}