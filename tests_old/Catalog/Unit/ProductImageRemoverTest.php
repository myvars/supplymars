<?php

namespace App\Tests\Catalog\Unit;


use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Infrastructure\Persistence\Doctrine\EventListener\ProductImageRemover;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

class ProductImageRemoverTest extends TestCase
{
    public function testPostRemoveReordersProductImages(): void
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $uploadHelperMock = $this->createMock(UploadHelper::class);
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $appProductUploads = 'uploads/products';

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getProductImages')
            ->willReturn(new ArrayCollection([
                $this->createMock(ProductImage::class),
                $this->createMock(ProductImage::class),
            ]));

        $emMock->expects($this->once())
            ->method('flush');

        $listener = new ProductImageRemover($emMock, $uploadHelperMock, $cacheManagerMock, $appProductUploads);
        $listener->setChangedProduct($product);

        $listener->postRemove();
    }
}
