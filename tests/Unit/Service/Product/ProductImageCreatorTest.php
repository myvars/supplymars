<?php

namespace App\Tests\Unit\Service\Product;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Repository\ProductImageRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ProductImageCreator;
use App\Service\Utility\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductImageCreatorTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    private MockObject $uploadHelper;

    private ProductImageCreator $productImageCreator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->uploadHelper = $this->createMock(UploadHelper::class);
        $this->productImageCreator = new ProductImageCreator($this->entityManager, $this->validator, $this->uploadHelper, 'uploads/products');
    }

    public function testHandleWithNonProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of Product');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->productImageCreator->handle($crudOptions);
    }

    public function testHandleWithoutImageFile(): void
    {
        $product = $this->createMock(Product::class);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($product);
        $crudOptions->method('getCrudActionContext')->willReturn(['imageFiles' => []]);

        $this->entityManager->expects($this->never())->method('flush');

        $this->productImageCreator->handle($crudOptions);
    }

    public function testHandleSuccessfully(): void
    {
        $product = $this->createMock(Product::class);
        $imageFile = $this->createMock(UploadedFile::class);
        $imageFile->method('getSize')->willReturn(1024);
        $imageFile->method('getMimeType')->willReturn('image/jpeg');
        $imageFile->method('getClientOriginalName')->willReturn('image.jpg');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($product);
        $crudOptions->method('getCrudActionContext')->willReturn(['imageFiles' => [$imageFile]]);

        $productImage = $this->createMock(ProductImage::class);
        $productImage->method('getImageFile')->willReturn($imageFile);

        $this->entityManager->method('getRepository')->willReturnMap([
            [ProductImage::class, $this->createMock(ProductImageRepository::class)]
        ]);
        $this->entityManager->getRepository(ProductImage::class)->method('getNextPositionForProduct')->willReturn(1);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->uploadHelper->method('uploadFile')->willReturn('uploaded_image.jpg');

        $this->productImageCreator->handle($crudOptions);
    }
}