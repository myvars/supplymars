<?php

namespace App\Tests\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\ReorderProductImage;
use App\Catalog\Application\Handler\ProductImage\ReorderProductImageHandler;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductImageFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class ReorderProductImageHandlerTest extends KernelTestCase
{
    use Factories;

    private ReorderProductImageHandler $handler;

    private ProductRepository $products;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(ReorderProductImageHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
    }

    public function testHandleFailsWhenNoOrderProvided(): void
    {
        $product = ProductFactory::createOne();

        $command = new ReorderProductImage($product->getPublicId(), []);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('No image ordering found', $result->message);
    }

    public function testHandleReordersImagesSuccessfully(): void
    {
        $product = ProductFactory::createOne();
        ProductImageFactory::createMany(2, ['product' => $product]);

        $reloaded = $this->products->getByPublicId($product->getPublicId());
        $productImages = $reloaded->getProductImages();
        self::assertCount(2, $productImages);

        /** @var array<int, int> $newOrder */
        $newOrder = [
            $productImages[1]->getId() => 0,
            $productImages[0]->getId() => 1,
        ];

        $command = new ReorderProductImage($product->getPublicId(), $newOrder);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $reloadedAfter = $this->products->getByPublicId($product->getPublicId());
        $reorderedImages = $reloadedAfter->getProductImages();

        self::assertEquals($productImages[1]->getId(), $reorderedImages[0]->getId());
        self::assertEquals($productImages[0]->getId(), $reorderedImages[1]->getId());
    }
}
