<?php

namespace App\Tests\Catalog\UI\Http;

use App\Catalog\Domain\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductImageControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private readonly string $dummyImagePath;

    private readonly string $invalidImagePath;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->dummyImagePath =  __DIR__ . '/../../../Shared/Resources/dummy-image.jpg';
        $this->invalidImagePath = __DIR__ . '/../../../Shared/Resources/invalid-image.txt';
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testShowProductImages(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images');
    }

    public function testShowProductImagesNotFound() : void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/999/images')
            ->assertStatus(404);
    }

    public function testCreateRemoveImage(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);
        $this->em->refresh($product);
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/'
            . static::getContainer()->getParameter('app.product_uploads');
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$this->dummyImagePath, $this->dummyImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('Product Image(s) added!')
            ->assertSee('2 Product Images');

        $productImages = $product->getProductImages();
        $this->assertCount(2, $productImages);

        foreach ($productImages as $productImage) {

            $this->assertFileExists($uploadDir . $productImage->getImageName());

            $this->browser()
                ->actingAs($user)
                ->visit('/product/images/' . $productImage->getId() . '/remove')
                ->followRedirect()
                ->assertSuccessful()
                ->assertSee('Product Images');
        }

        $this->browser()
            ->actingAs($user)
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('0 Product Images');
    }


    public function testCreateImageWithInvalidType(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$this->invalidImagePath])
            ->click('Upload')
            ->assertSuccessful()
            ->assertSee('0 Product Images');
    }

    public function testCreateImageWithNonExistentProduct(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->request('POST', '/product/999999/images/create', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'imageFile' => $this->dummyImagePath,
                ],
            ])
            ->assertStatus(404);
    }

    public function testRemoveNonExistentImage(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->request('GET', '/product/images/999/remove')
            ->assertStatus(404);
    }

    public function testReorderImages(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);
        $this->em->refresh($product);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->visit('/product/' . $product->getId() . '/images')
            ->assertSuccessful()
            ->assertSee('Product Images')
            ->assertSee('0 Product Images')
            ->attachFile('imageFile[]', [$this->dummyImagePath, $this->dummyImagePath])
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
            ->actingAs($user)
            ->request('POST', '/product/' . $product->getId() . '/images/reorder', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($reverseOrder),
            ])
            ->assertSuccessful();

        // check the new position order is correct
        $product = $this->em->getRepository(Product::class)->find($product->getId());
        $reorderedProductImages = $product->getProductImages();
        $this->assertEquals($reorderedProductImages[0]->getId(), $productImages[1]->getId());
        $this->assertEquals($reorderedProductImages[1]->getId(), $productImages[0]->getId());

        foreach ($productImages as $productImage) {
            $this->browser()
                ->actingAs($user)
                ->visit('/product/images/' . $productImage->getId() . '/remove')
                ->followRedirect()
                ->assertSuccessful()
                ->assertSee('Product Images');
        }
    }

    public function testReorderImagesWithInvalidBody(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->request('POST', '/product/' . $product->getId() . '/images/reorder', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => 'invalid json',
            ])
            ->assertStatus(400);
    }
}
