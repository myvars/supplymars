<?php

namespace App\EventListener;


use App\Entity\ProductImage;
use App\Service\UploadHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: ProductImage::class)]
class ProductImageRemover
{
    public function __construct(
        private readonly UploadHelper $uploadHelper,
        private readonly CacheManager $cacheManager,
        #[Autowire('%app.product_uploads%')]
        private readonly string $appProductUploads
    )
    {
    }

    public function preRemove(ProductImage $productImage, PreRemoveEventArgs $eventArgs): void
    {
        if ($productImage->getImageName() === null) {
            return;
        }

        $path = $this->appProductUploads.$productImage->getImageName();

        if ($this->uploadHelper->deleteFile($path)) {
            $this->cacheManager->remove($path);
        }
    }
}