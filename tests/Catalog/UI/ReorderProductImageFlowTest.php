<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ReorderProductImageFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private function imagePath(): string
    {
        return __DIR__ . '/../../Shared/Resources/dummy-image.jpg';
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

    public function testSuccessfulReorderViaFlow(): void
    {
        $product = ProductFactory::createOne();
        $user = UserFactory::new()->asStaff()->create();

        $file1 = $this->uploaded('one.jpg', $this->imagePath(), 'image/jpeg');
        $file2 = $this->uploaded('two.jpg', $this->imagePath(), 'image/jpeg');

        // upload two images via the flow
        $this->browser()
            ->actingAs($user)
            ->get('/product_image/'.$product->getPublicId()->value().'/images')
            ->assertSuccessful()
            ->attachFile('imageFile[]', [$file1->getPathname(), $file2->getPathname()])
            ->click('Upload')
            ->followRedirects()
            ->assertSee('2 images added')
            ->assertSee('2 Product Images');


        $product = ProductFactory::repository()->find($product->getId());
        $productImages = $product->getProductImages();
        self::assertCount(2, $productImages);

        $reverseOrder = [
            0 => $productImages[1]->getId(),
            1 => $productImages[0]->getId(),
        ];

        $this->browser()
            ->actingAs($user)
            ->request('POST', '/product/' . $product->getPublicId()->value() . '/images/reorder', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($reverseOrder),
            ])
            ->assertSuccessful();

        $productAfter = ProductFactory::repository()->find($product->getId());
        $reordered = $productAfter->getProductImages();

        self::assertEquals($productImages[1]->getId(), $reordered[0]->getId());
        self::assertEquals($productImages[0]->getId(), $reordered[1]->getId());
    }
}
