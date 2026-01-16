<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CreateProductImageFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private function imagePath(): string
    {
        return __DIR__ . '/../../Shared/Resources/dummy-image.jpg';
    }

    private function invalidPath(): string
    {
        return __DIR__ . '/../../Shared/Resources/invalid-image.txt';
    }

    private function uploaded(string $clientName, string $path, string $mime): UploadedFile
    {
        return new UploadedFile(
            $path,
            $clientName,
            $mime,
            null,
            true
        );
    }

    public function testSuccessfulMultiImageUpload(): void
    {
        $product = ProductFactory::createOne();
        $user = UserFactory::new()->asStaff()->create();

        $file1 = $this->uploaded('one.jpg', $this->imagePath(), 'image/jpeg');
        $file2 = $this->uploaded('two.jpg', $this->imagePath(), 'image/jpeg');

        $this->browser()
            ->actingAs($user)
            ->get('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$file1->getPathname(), $file2->getPathname()])
            ->click('Upload')
            ->followRedirects()
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('2 images added')
            ->assertSee('2 Product Images');
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
            ->assertSee('0 Product Images');
    }

    public function testUploadWithoutFile(): void
    {
        $product = ProductFactory::createOne();
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->post('/product/' . $product->getPublicId()->value() . '/images/create')
            ->followRedirects()
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('No image files provided');
    }
}
