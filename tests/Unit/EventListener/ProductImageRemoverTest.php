<?php

namespace App\Tests\Unit\EventListener;


use App\Entity\Product;
use App\Entity\ProductImage;
use App\EventListener\DoctrineEvents\ProductImageRemover;
use App\Service\UploadHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

class ProductImageRemoverTest extends TestCase
{
    public function testPostRemoveReordersProductImages(): void
    {
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $uploadHelperMock = $this->createMock(UploadHelper::class);
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $appProductUploads = 'uploads/products';

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getProductImages')
            ->willReturn(new ArrayCollection([
                (new ProductImage())->setPosition(2),
                (new ProductImage())->setPosition(1),
            ]));

        $entityManagerMock->expects($this->once())
            ->method('flush');

        $listener = new ProductImageRemover($entityManagerMock, $uploadHelperMock, $cacheManagerMock, $appProductUploads);
        $listener->setChangedProduct($product);

        $listener->postRemove();
    }
}