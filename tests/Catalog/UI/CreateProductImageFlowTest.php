<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductImageFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class CreateProductImageFlowTest extends WebTestCase
{
    use HasBrowser;

    private function imagePath(): string
    {
        return __DIR__ . '/../../Shared/Resources/dummy-image.jpg';
    }

    private function invalidPath(): string
    {
        return __DIR__ . '/../../Shared/Resources/invalid-image.txt';
    }

    public function testSuccessfulMultiImageUpload(): void
    {
        $product = ProductFactory::createOne();
        $user = UserFactory::new()->asStaff()->create();

        $browser = $this->browser()
            ->actingAs($user)
            ->get('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$this->imagePath(), $this->imagePath()]);

        try {
            $browser
                ->click('Upload')
                ->followRedirects()
                ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
                ->assertSee('2 images added')
                ->assertSee('2 Product Images');
        } finally {
            $this->deleteStoredImages($product);
        }
    }

    public function testUploadInvalidType(): void
    {
        $product = ProductFactory::createOne();
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$this->invalidPath()])
            ->click('Upload')
            ->followRedirects()
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('Only JPG, PNG, GIF and WebP images are allowed')
            ->assertSee('0 Product Images');
    }

    public function testUploadWithoutFile(): void
    {
        $product = ProductFactory::createOne();

        $browser = $this->uploadBrowser($product, $token);
        $this->upload($browser, $product, $token)
            ->followRedirects()
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('No image files provided');
    }

    public function testDropzoneUploadStoresImageNamedFromItsContent(): void
    {
        $product = ProductFactory::createOne();

        $browser = $this->uploadBrowser($product, $token);

        try {
            $this->upload($browser, $product, $token, [new UploadedFile($this->imagePath(), 'photo.jpeg', test: true)], xhr: true)
                ->assertStatus(200)
                ->assertJson();

            $image = ProductFactory::repository()->find($product->getId())->getProductImages()->first();
            self::assertNotFalse($image);
            self::assertStringEndsWith('.jpg', $image->getImageName());
        } finally {
            $this->deleteStoredImages($product);
        }
    }

    public function testDropzoneUploadReportsRejectedFileAsError(): void
    {
        $product = ProductFactory::createOne();

        // A JPEG named .png passes Dropzone's own check, so only the server can refuse it
        $browser = $this->uploadBrowser($product, $token);
        $this->upload($browser, $product, $token, [new UploadedFile($this->imagePath(), 'photo.png', test: true)], xhr: true)
            ->assertStatus(422)
            ->assertJsonMatches('error', 'photo.png: Only JPG, PNG, GIF and WebP images are allowed.');

        self::assertCount(0, ProductFactory::repository()->find($product->getId())->getProductImages());
    }

    public function testUploadRequiresCsrfToken(): void
    {
        $product = ProductFactory::createOne();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $this->upload($browser, $product, null, [new UploadedFile($this->imagePath(), 'photo.jpg', test: true)], xhr: true)
            ->assertStatus(403);

        self::assertCount(0, ProductFactory::repository()->find($product->getId())->getProductImages());
    }

    public function testUploadRejectsReorderToken(): void
    {
        // The reorder control, and so its token, is only rendered when there is an image to reorder
        $product = ProductFactory::createOne();
        ProductImageFactory::createOne(['product' => $product]);

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product_image/' . $product->getPublicId()->value() . '/images');
        $reorderToken = (string) $browser->crawler()
            ->filter('[data-controller="sortable"]')->attr('data-sortable-csrf-token-value');
        self::assertNotSame('', $reorderToken);

        $this->upload($browser, $product, $reorderToken, [new UploadedFile($this->imagePath(), 'photo.jpg', test: true)], xhr: true)
            ->assertStatus(403);

        self::assertCount(1, ProductFactory::repository()->find($product->getId())->getProductImages());
    }

    /**
     * Loads the images page and reads the upload token the form carries.
     *
     * @param-out string $token
     */
    private function uploadBrowser(Product $product, ?string &$token): KernelBrowser
    {
        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSuccessful();

        $token = (string) $browser->crawler()->filter('form[data-controller="dropzone"] input[name="_token"]')->attr('value');
        self::assertNotSame('', $token);

        return $browser;
    }

    /**
     * Posts the files the way Dropzone does: as `imageFile[]`, with the form's `_token` field.
     *
     * @param list<UploadedFile> $files
     */
    private function upload(KernelBrowser $browser, Product $product, ?string $token, array $files = [], bool $xhr = false): KernelBrowser
    {
        return $browser->post('/product/' . $product->getPublicId()->value() . '/images/create', [
            'body' => $token === null ? [] : ['_token' => $token],
            'files' => ['imageFile' => $files],
            'ajax' => $xhr,
        ]);
    }

    private function deleteStoredImages(Product $product): void
    {
        $uploadHelper = self::getContainer()->get(UploadHelper::class);
        $reloaded = ProductFactory::repository()->find($product->getId());
        foreach ($reloaded->getProductImages() as $image) {
            $uploadHelper->deleteFile(self::getContainer()->getParameter('app.product_uploads') . $image->getImageName());
        }
    }
}
