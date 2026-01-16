<?php

namespace App\Catalog\Application\Listener;

use App\Catalog\Domain\Model\ProductImage\Event\ProductImageWasDeletedEvent;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ProductImageWasDeletedEvent::class)]
final readonly class ProductImageWasDeleted
{
    public function __construct(
        private UploadHelper $uploadHelper,
        private CacheManager $cacheManager,
        #[Autowire('%app.product_uploads%')]
        private string $uploadsDir,
    ) {
    }

    public function __invoke(ProductImageWasDeletedEvent $event): void
    {
        if ($event->getImageName() === '') {
            return;
        }

        $path = $this->uploadsDir . $event->getImageName();
        if ($this->uploadHelper->deleteFile($path)) {
            $this->cacheManager->remove($path);
        }
    }
}
