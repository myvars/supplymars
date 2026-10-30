<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductImageFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteProductImageFlowTest extends WebTestCase
{
    use HasBrowser;
    use ActionFormToken;

    public function testDeleteViaFlow(): void
    {
        $productImage = ProductImageFactory::createOne();
        $publicId = $productImage->getPublicId()->value();
        $product = $productImage->getProduct();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product_image/' . $product->getPublicId()->value() . '/images')
            ->click('form[action="/product/images/' . $publicId . '/remove"] button')
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('Product image deleted')
            ->assertSee('0 Product Images');
    }

    public function testDeleteRemovesTheRecordAndTheStoredFile(): void
    {
        $product = ProductFactory::createOne();
        $productId = $product->getPublicId()->value();

        $uploadsDir = self::getContainer()->getParameter('app.product_uploads');
        $storedBefore = $this->storedFiles($uploadsDir);

        try {
            $browser = $this->browser()
                ->actingAs(UserFactory::new()->asStaff()->create())
                ->get('/product_image/' . $productId . '/images')
                ->attachFile('imageFile[]', [__DIR__ . '/../../Shared/Resources/dummy-image.jpg'])
                ->click('Upload')
                ->followRedirects()
                ->assertSee('1 image added.');

            $productImage = ProductImageFactory::repository()->findOneBy(['product' => $product->getId()]);
            self::assertNotNull($productImage);
            $path = $uploadsDir . $productImage->getImageName();
            self::assertTrue($this->uploads()->fileExists($path));

            $browser
                ->click('form[action="/product/images/' . $productImage->getPublicId()->value() . '/remove"] button')
                ->assertOn('/product_image/' . $productId . '/images')
                ->assertSee('Product image deleted')
                ->assertSee('0 Product Images');

            ProductImageFactory::assert()->empty();
            self::assertFalse($this->uploads()->fileExists($path));
        } finally {
            foreach (array_diff($this->storedFiles($uploadsDir), $storedBefore) as $leftover) {
                $this->uploads()->delete($leftover);
            }
        }
    }

    public function testDeleteNonExistentImageReturns404(): void
    {
        $nonExistentId = ProductImagePublicId::new()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->post('/product/images/' . $nonExistentId . '/remove')
            ->assertStatus(404);
    }

    public function testRemoveIsNotReachableWithGet(): void
    {
        $publicId = ProductImageFactory::createOne()->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/images/' . $publicId . '/remove')
            ->assertStatus(405);

        ProductImageFactory::assert()->count(1);
    }

    public function testRemoveRejectsAMissingTokenOrAnotherImagesToken(): void
    {
        $product = ProductFactory::createOne();
        $other = ProductImageFactory::createOne(['product' => $product])->getPublicId()->value();
        $productImage = ProductImageFactory::createOne(['product' => $product]);
        $imagesUrl = '/product_image/' . $product->getPublicId()->value() . '/images';
        $url = '/product/images/' . $productImage->getPublicId()->value() . '/remove';

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $imagesUrl, '/product/images/' . $other . '/remove');

        $browser
            ->post($url)
            ->assertOn($imagesUrl)
            ->assertSee('Invalid CSRF token.')
            ->post($url, ['body' => ['_token' => $otherToken]])
            ->assertOn($imagesUrl)
            ->assertSee('Invalid CSRF token.');

        ProductImageFactory::assert()->count(2);
    }

    /**
     * @return array<string>
     */
    private function storedFiles(string $directory): array
    {
        return $this->uploads()->listContents($directory)->map(fn ($item): string => $item->path())->toArray();
    }

    private function uploads(): FilesystemOperator
    {
        $uploads = self::getContainer()->get('oneup_flysystem.products_fs_filesystem');
        \assert($uploads instanceof FilesystemOperator);

        return $uploads;
    }
}
