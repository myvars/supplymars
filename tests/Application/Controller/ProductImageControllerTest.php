<?php

namespace App\Tests\Application\Controller;

use App\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductImageControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testShowProductImages(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();

        $this->browser()
            ->get('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 results');
    }

    public function testShowProductImagesNotFound() : void
    {
        $this->browser()
            ->get('/product/999999/images')
            ->assertSee("Sorry, we can't find that Product");
    }

    public function testCreateRemoveImage(): void
    {
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/'
            . static::getContainer()->getParameter('app.product_uploads');
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

        $this->browser()
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 results')
            ->attachFile('imageFile[]', [$dummyImagePath, $dummyImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('New Product Image added!')
            ->assertSee('2 results');

        $productImages = $product->getProductImages();
        $this->assertCount(2, $productImages);

        foreach ($productImages as $productImage) {

            $this->assertFileExists($uploadDir . $productImage->getImageName());

            $this->browser()
                ->visit('/product/images/' . $productImage->getId() . '/remove')
                ->followRedirect()
                ->assertSuccessful()
                ->assertSee('Product Images');
        }

        $this->browser()
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 results');
    }


    public function testCreateImageWithInvalidType(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $invalidImagePath = __DIR__ . '/../../Resources/invalid-image.txt';

        $this->browser()
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 results')
            ->attachFile('imageFile[]', [$invalidImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('Image could not be uploaded! Please check the file type and try again.')
            ->assertSee('0 results');
    }

    // test for the case when the product does not exist
    public function testCreateImageWithNonExistentProduct(): void
    {
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

        $this->browser()
            ->request('POST', '/product/999999/images/create', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'imageFile' => $dummyImagePath,
                ],
            ])
            ->assertStatus(500);
    }

    // test for the case when the product image does not exist
    public function testRemoveNonExistentImage(): void
    {
        $this->browser()
            ->request('GET', '/product/images/999999/remove')
            ->assertStatus(500);
    }

    public function testReorderImages(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

        $this->browser()
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 results')
            ->attachFile('imageFile[]', [$dummyImagePath, $dummyImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('New Product Image added!')
            ->assertSee('2 results');

        $productImages = $product->getProductImages();
        $this->assertCount(2, $productImages);

        $reverseOrder = [
            0 => $productImages[1]->getId(),
            1 => $productImages[0]->getId(),
        ];

        $this->browser()
            ->request('POST', '/product/' . $product->getId() . '/images/reorder', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($reverseOrder),
            ])
            ->assertSuccessful();

        // check the new position order is correct
        $product = ProductFactory::repository()->find($product->getId());
        $reorderedProductImages = $product->getProductImages();
        $this->assertEquals($reorderedProductImages[0]->getId(), $productImages[1]->getId());
        $this->assertEquals($reorderedProductImages[1]->getId(), $productImages[0]->getId());

        foreach ($productImages as $productImage) {
            $this->browser()
                ->visit('/product/images/' . $productImage->getId() . '/remove')
                ->followRedirect()
                ->assertSuccessful()
                ->assertSee('Product Images');
        }
    }

    // test for the case when the request body is not a valid JSON
    public function testReorderImagesWithInvalidBody(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();

        $this->browser()
            ->request('POST', '/product/' . $product->getId() . '/images/reorder', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => 'invalid json',
            ])
            ->assertStatus(400);
    }
}