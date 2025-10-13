<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductImageDoctrineRepository;
use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ProductImageCreator;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductImageCreatorTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private MockObject $uploadHelper;

    private ProductImageCreator $productImageCreator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->uploadHelper = $this->createMock(UploadHelper::class);
        $this->productImageCreator = new ProductImageCreator($this->em, $this->validator, $this->uploadHelper, 'uploads/products');
    }

    public function testHandleWithNonProductEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of Product');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->productImageCreator)($context);
    }

    public function testHandleWithoutImageFile(): void
    {
        $product = $this->createMock(Product::class);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($product);
        $context->method('getCrudHandlerContext')->willReturn(['imageFiles' => []]);

        $this->em->expects($this->never())->method('flush');

        ($this->productImageCreator)($context);
    }

    public function testHandleSuccessfully(): void
    {
        $product = $this->createMock(Product::class);
        $imageFile = $this->createMock(UploadedFile::class);
        $imageFile->method('getSize')->willReturn(1024);
        $imageFile->method('getMimeType')->willReturn('image/jpeg');
        $imageFile->method('getClientOriginalName')->willReturn('image.jpg');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($product);
        $context->method('getCrudHandlerContext')->willReturn(['imageFiles' => [$imageFile]]);

        $productImage = $this->createMock(ProductImage::class);
        $productImage->method('getImageFile')->willReturn($imageFile);

        $this->em->method('getRepository')->willReturnMap([
            [ProductImage::class, $this->createMock(ProductImageDoctrineRepository::class)]
        ]);
        $this->em->getRepository(ProductImage::class)->method('getNextPositionForProduct')->willReturn(1);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->uploadHelper->method('uploadFile')->willReturn('uploaded_image.jpg');

        ($this->productImageCreator)($context);
    }
}
