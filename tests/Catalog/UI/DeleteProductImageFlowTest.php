<?php

namespace App\Tests\Catalog\UI;

use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductImageFactory;
use App\Tests\Shared\Factory\UserFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteProductImageFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaFlow(): void
    {
        $productImage = ProductImageFactory::createOne();
        $publicId = $productImage->getPublicId()->value();
        $product = $productImage->getProduct();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/images/' . $publicId . '/remove')
            ->followRedirect()
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('Product image deleted')
            ->assertSee('0 Product Images');
    }

    public function testDeleteRemovesTheRecordAndTheStoredFile(): void
    {
        $product = ProductFactory::createOne();
        $productId = $product->getPublicId()->value();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product_image/' . $productId . '/images')
            ->attachFile('imageFile[]', [__DIR__ . '/../../Shared/Resources/dummy-image.jpg'])
            ->click('Upload')
            ->followRedirects()
            ->assertSee('1 image added.');

        $productImage = ProductImageFactory::repository()->findOneBy(['product' => $product->getId()]);
        self::assertNotNull($productImage);
        $path = self::getContainer()->getParameter('app.product_uploads') . $productImage->getImageName();
        self::assertTrue($this->uploads()->fileExists($path));

        try {
            $browser
                ->get('/product/images/' . $productImage->getPublicId()->value() . '/remove')
                ->assertOn('/product_image/' . $productId . '/images')
                ->assertSee('Product image deleted')
                ->assertSee('0 Product Images');

            ProductImageFactory::assert()->empty();
            self::assertFalse($this->uploads()->fileExists($path));
        } finally {
            if ($this->uploads()->fileExists($path)) {
                $this->uploads()->delete($path);
            }
        }
    }

    public function testDeleteNonExistentImageReturns404(): void
    {
        $nonExistentId = ProductImagePublicId::new()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/images/' . $nonExistentId . '/remove')
            ->assertStatus(404);
    }

    private function uploads(): FilesystemOperator
    {
        $uploads = self::getContainer()->get('oneup_flysystem.products_fs_filesystem');
        \assert($uploads instanceof FilesystemOperator);

        return $uploads;
    }
}
