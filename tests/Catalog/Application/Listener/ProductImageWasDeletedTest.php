<?php

namespace App\Tests\Catalog\Application\Listener;

use App\Catalog\Application\Listener\ProductImageWasDeleted;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Model\ProductImage\Event\ProductImageWasDeletedEvent;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

final class ProductImageWasDeletedTest extends TestCase
{
    private function productId(): ProductPublicId
    {
        return $this->createStub(ProductPublicId::class);
    }

    public function testDoesNothingWhenImageNameEmpty(): void
    {
        $uploadHelper = $this->createMock(UploadHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $productId = $this->createStub(ProductPublicId::class);

        $uploadHelper->expects(self::never())->method('deleteFile');
        $cacheManager->expects(self::never())->method('remove');

        $listener = new ProductImageWasDeleted(
            $uploadHelper,
            $cacheManager,
            '/uploads/'
        );
        $listener(new ProductImageWasDeletedEvent($productId, ''));

        self::assertTrue(true);
    }

    public function testDoesNotRemoveCacheWhenDeleteFails(): void
    {
        $uploadHelper = $this->createMock(UploadHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $productId = $this->createStub(ProductPublicId::class);

        $uploadHelper->expects(self::once())
            ->method('deleteFile')
            ->with('/uploads/image.jpg')
            ->willReturn(false);

        $cacheManager->expects(self::never())->method('remove');

        $listener = new ProductImageWasDeleted(
            $uploadHelper,
            $cacheManager,
            '/uploads/'
        );
        $listener(new ProductImageWasDeletedEvent($productId, 'image.jpg'));

        self::assertTrue(true);
    }

    public function testRemovesCacheWhenDeleteSucceeds(): void
    {
        $uploadHelper = $this->createMock(UploadHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $productId = $this->createStub(ProductPublicId::class);

        $uploadHelper->expects(self::once())
            ->method('deleteFile')
            ->with('/uploads/image.jpg')
            ->willReturn(true);

        $cacheManager->expects(self::once())
            ->method('remove')
            ->with('/uploads/image.jpg');

        $listener = new ProductImageWasDeleted($uploadHelper, $cacheManager, '/uploads/');
        $listener(new ProductImageWasDeletedEvent($productId, 'image.jpg'));

        self::assertTrue(true);
    }
}
