<?php

namespace App\Tests\Application\Controller;

use App\Factory\ProductFactory;
use App\Factory\UserFactory;
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
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images');
    }

    public function testShowProductImagesNotFound() : void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/product/999999/images')
            ->assertStatus(404);
    }

    public function testCreateRemoveImage(): void
    {
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/'
            . static::getContainer()->getParameter('app.product_uploads');
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$dummyImagePath, $dummyImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('Product Image(s) added!')
            ->assertSee('2 Product Images');

        $productImages = $product->getProductImages();
        $this->assertCount(2, $productImages);

        foreach ($productImages as $productImage) {

            $this->assertFileExists($uploadDir . $productImage->getImageName());

            $this->browser()
                ->actingAs(UserFactory::new()->staff()->create())
                ->visit('/product/images/' . $productImage->getId() . '/remove')
                ->followRedirect()
                ->assertSuccessful()
                ->assertSee('Product Images');
        }

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images');
    }


    public function testCreateImageWithInvalidType(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $invalidImagePath = __DIR__ . '/../../Resources/invalid-image.txt';

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$invalidImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('0 Product Images');
    }

    // test for the case when the product does not exist
    public function testCreateImageWithNonExistentProduct(): void
    {
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->request('POST', '/product/999999/images/create', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'imageFile' => $dummyImagePath,
                ],
            ])
            ->assertStatus(404);
    }

    // test for the case when the product image does not exist
    public function testRemoveNonExistentImage(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->request('GET', '/product/images/999999/remove')
            ->assertStatus(404);
    }

    public function testReorderImages(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$dummyImagePath, $dummyImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('Product Image(s) added!')
            ->assertSee('2 Product Images');

        $productImages = $product->getProductImages();
        $this->assertCount(2, $productImages);

        $reverseOrder = [
            0 => $productImages[1]->getId(),
            1 => $productImages[0]->getId(),
        ];

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
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
                ->actingAs(UserFactory::new()->staff()->create())
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
            ->actingAs(UserFactory::new()->staff()->create())
            ->request('POST', '/product/' . $product->getId() . '/images/reorder', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => 'invalid json',
            ])
            ->assertStatus(400);
    }
}