<?php

namespace App\Tests\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\CreateProductImage;
use App\Catalog\Application\Handler\ProductImage\CreateProductImageHandler;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;

final class CreateProductImageHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateProductImageHandler $handler;

    private ProductRepository $products;

    private FilesystemOperator $uploads;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateProductImageHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
        $this->uploads = self::getContainer()->get('oneup_flysystem.products_fs_filesystem');
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), $this->tempFiles);
        $this->tempFiles = [];

        parent::tearDown();
    }

    public function testHandleFailsWhenNoFilesProvided(): void
    {
        $product = ProductFactory::new()->create();

        $command = new CreateProductImage($product->getPublicId(), []);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('No image files provided', $result->message);
    }

    public function testHandleFailsOnNonUploadedFileEntry(): void
    {
        $product = ProductFactory::new()->create();

        /** @phpstan-ignore argument.type (intentionally passing invalid array for testing) */
        $command = new CreateProductImage($product->getPublicId(), ['not-a-file', $this->validImage()]);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertCount(0, $this->reloadedImages($product->getPublicId()));
    }

    public function testHandleAddsImageAndPersists(): void
    {
        $product = ProductFactory::createOne();
        $file = $this->validImage();

        $command = new CreateProductImage($product->getPublicId(), [$file]);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $productImages = $this->reloadedImages($product->getPublicId());
        self::assertCount(1, $productImages);

        $first = $productImages[0];
        self::assertSame('image/jpeg', $first->getImageMimeType());
        self::assertNotSame($file->getClientOriginalName(), $first->getImageName());
        self::assertStringEndsWith('.jpg', $first->getImageName());

        $this->assertStoredAndDelete($first->getImageName());
    }

    public function testHandleNamesStoredFileFromItsContent(): void
    {
        $product = ProductFactory::createOne();

        $result = ($this->handler)(new CreateProductImage($product->getPublicId(), [$this->validImage('photo.jpeg')]));

        self::assertTrue($result->ok);
        $image = $this->reloadedImages($product->getPublicId())[0];
        self::assertSame('photo.jpeg', $image->getImageOriginalName());
        self::assertStringEndsWith('.jpg', $image->getImageName());

        $this->assertStoredAndDelete($image->getImageName());
    }

    public function testHandleRejectsDisallowedType(): void
    {
        $product = ProductFactory::createOne();
        $svg = $this->tempFile('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>');

        $result = ($this->handler)(new CreateProductImage($product->getPublicId(), [
            new UploadedFile($svg, 'logo.svg', test: true),
        ]));

        self::assertFalse($result->ok);
        self::assertStringContainsString('extension of the file is invalid', $result->message);
        self::assertCount(0, $this->reloadedImages($product->getPublicId()));
    }

    public function testHandleRejectsScriptRenamedAsImage(): void
    {
        $product = ProductFactory::createOne();
        $html = $this->tempFile('<html><body><script>alert(document.domain)</script></body></html>');

        $result = ($this->handler)(new CreateProductImage($product->getPublicId(), [
            new UploadedFile($html, 'photo.jpg', test: true),
        ]));

        self::assertFalse($result->ok);
        self::assertStringContainsString('mime type of the file is invalid', $result->message);
        self::assertCount(0, $this->reloadedImages($product->getPublicId()));
    }

    public function testHandleRejectsOversizeFile(): void
    {
        $product = ProductFactory::createOne();
        // A sparse file one byte over the 2 MiB limit
        $path = $this->tempFile('');
        $handle = fopen($path, 'w');
        self::assertIsResource($handle);
        ftruncate($handle, 2 * 1024 * 1024 + 1);
        fclose($handle);

        $result = ($this->handler)(new CreateProductImage($product->getPublicId(), [
            new UploadedFile($path, 'huge.jpg', test: true),
        ]));

        self::assertFalse($result->ok);
        self::assertStringContainsString('too large', $result->message);
        self::assertCount(0, $this->reloadedImages($product->getPublicId()));
    }

    public function testHandleStoresNothingWhenOneFileIsRejected(): void
    {
        $product = ProductFactory::createOne();
        $html = $this->tempFile('<html><body><script>alert(document.domain)</script></body></html>');

        $result = ($this->handler)(new CreateProductImage($product->getPublicId(), [
            $this->validImage(),
            new UploadedFile($html, 'evil.html', test: true),
        ]));

        self::assertFalse($result->ok);
        self::assertStringContainsString('evil.html', $result->message);
        self::assertCount(0, $this->reloadedImages($product->getPublicId()));
    }

    private function validImage(string $name = 'dummy-image.jpg'): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/../../../../Shared/Resources/dummy-image.jpg', $name, test: true);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        self::assertIsString($path);
        file_put_contents($path, $contents);

        return $this->tempFiles[] = $path;
    }

    /**
     * @return list<ProductImage>
     */
    private function reloadedImages(ProductPublicId $id): array
    {
        return array_values($this->products->getByPublicId($id)->getProductImages()->toArray());
    }

    private function assertStoredAndDelete(string $imageName): void
    {
        $path = self::getContainer()->getParameter('app.product_uploads') . $imageName;

        try {
            self::assertTrue($this->uploads->fileExists($path));
        } finally {
            $this->uploads->delete($path);
        }
    }
}
