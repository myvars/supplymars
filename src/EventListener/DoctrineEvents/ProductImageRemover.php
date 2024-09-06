<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Service\UploadHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: ProductImage::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: ProductImage::class)]
class ProductImageRemover
{
    /** @var Product[] */
    private array $changedProducts = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadHelper $uploadHelper,
        private readonly CacheManager $cacheManager,
        #[Autowire('%app.product_uploads%')]
        private readonly string $appProductUploads
    ) {
    }

    public function preRemove(ProductImage $productImage, PreRemoveEventArgs $eventArgs): void
    {
        if (null === $productImage->getImageName()) {
            return;
        }

        $path = $this->appProductUploads.$productImage->getImageName();

        if ($this->uploadHelper->deleteFile($path)) {
            $this->cacheManager->remove($path);
            $product = $productImage->getProduct();
            $this->setChangedProduct($product);
        }
    }

    public function postRemove(): void
    {
        if ($this->changedProducts === []) {
            return;
        }

        foreach ($this->changedProducts as $product) {
            $this->reorderProductImages($product);
        }

        $this->entityManager->flush();

        unset($this->changedProducts);
    }

    public function setChangedProduct(Product $product): void
    {
        $this->changedProducts[$product->getId()] = $product;
    }

    public function reorderProductImages(Product $product): void
    {
        foreach ($product->getProductImages() as $index => $productImage) {
            $productImage->setPosition($index + 1);
        }
    }
}
