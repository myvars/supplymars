<?php

namespace App\Tests\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\CreateProductImage;
use App\Catalog\Application\Handler\ProductImage\CreateProductImageHandler;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;

final class CreateProductImageHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateProductImageHandler $handler;

    private ProductRepository $products;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateProductImageHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
    }

    private function validImage(string $name = 'dummy-image.jpg'): UploadedFile
    {
        $path = __DIR__ . '/../../../../Shared/Resources/' . $name;

        return new UploadedFile(
            $path,
            $name,
            'image/jpeg',
            null,
            true
        );
    }

    public function testHandleFailsWhenNoFilesProvided(): void
    {
        $product = ProductFactory::new()->create();

        $command = new CreateProductImage($product->getPublicId(), []);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('No image files provided', $result->message);
    }

    public function testHandleSkipsNonUploadedFileEntries(): void
    {
        $product = ProductFactory::new()->create();

        /** @phpstan-ignore argument.type (intentionally passing invalid array for testing) */
        $command = new CreateProductImage($product->getPublicId(), ['not-a-file', $this->validImage()]);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertStringContainsString('1 image', $result->message);
    }

    public function testHandleAddsImageAndPersists(): void
    {
        $product = ProductFactory::createOne();
        $file = $this->validImage();

        $command = new CreateProductImage($product->getPublicId(), [$file]);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $reloaded = $this->products->getByPublicId($product->getPublicId());
        $productImages = $reloaded->getProductImages();
        self::assertCount(1, $productImages);

        $first = $productImages[0];
        self::assertSame($file->getMimeType(), $first->getImageMimeType());
        self::assertNotSame($file->getClientOriginalName(), $first->getImageName());
    }
}
