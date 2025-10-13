<?php

namespace App\Catalog\Application\Handler\ProductImage;

use App\Catalog\Application\Command\ProductImage\CreateProductImage;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Repository\ProductImageRepository;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateProductImageHandler
{
    public function __construct(
        private ProductImageRepository $productImages,
        private ProductRepository $products,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
        private UploadHelper $uploadHelper,
        #[Autowire('%app.product_uploads%')]
        private string $appProductUploads,
    ) {
    }

    public function __invoke(CreateProductImage $command): Result
    {
        if ($command->imageFiles === [] ) {
            return Result::fail('No image files provided.');
        }

        $product = $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $position = $this->productImages->getNextPositionForProduct($product);
        $added = 0;

        foreach ($command->imageFiles as $imageFile) {
            if (!$imageFile instanceof UploadedFile) {
                continue;
            }

            $productImage = ProductImage::createFromUploadedFile(
                product: $product,
                uploadedFile: $imageFile,
                position: $position
            );

            $errors = $this->validator->validate($productImage);
            if (count($errors) > 0) {
                continue;
            }

            try {
                $storedName = $this->uploadHelper->uploadFile(
                    $productImage->getImageFile(),
                    $this->appProductUploads
                );
            } catch (\Throwable) {
                // Skip this file if upload failed
                continue;
            }

            $productImage->changeImageName($storedName);
            $product->addProductImage($productImage);

            $this->productImages->add($productImage);
            ++$position;
            ++$added;
        }

        if ($added === 0) {
            return Result::fail('No valid images were added.');
        }

        $this->flusher->flush();

        return Result::ok(sprintf('%d %s added.', $added, $added === 1 ? 'image' : 'images'));
    }
}
