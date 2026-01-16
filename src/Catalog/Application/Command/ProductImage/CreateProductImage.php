<?php

namespace App\Catalog\Application\Command\ProductImage;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class CreateProductImage
{
    /**
     * @param UploadedFile[] $imageFiles
     */
    public function __construct(
        public ProductPublicId $id,
        public array $imageFiles = [],
    ) {
    }
}
