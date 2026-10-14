<?php

declare(strict_types=1);

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
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateProductImageHandler
{
    /**
     * Only types a browser never runs as script. The stored files are served publicly, so never add
     * svg, html or xml here.
     */
    public const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * PHP's default upload_max_filesize. Raising it means raising that setting and nginx's
     * client_max_body_size too, or the request never reaches this check, and the size messages below.
     */
    public const string MAX_SIZE = '2Mi';

    public function __construct(
        private ProductImageRepository $productImages,
        private ProductRepository $products,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
        private UploadHelper $uploadHelper,
        #[Autowire(param: 'app.product_uploads')]
        private string $appProductUploads,
    ) {
    }

    public function __invoke(CreateProductImage $command): Result
    {
        if ($command->imageFiles === []) {
            return Result::fail('No image files provided.');
        }

        $product = $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $constraint = new Image(
            maxSize: self::MAX_SIZE,
            filenameMaxLength: 255,
            extensions: self::ALLOWED_EXTENSIONS,
            maxSizeMessage: 'The image is larger than 2 MB.',
            uploadIniSizeErrorMessage: 'The image is larger than 2 MB.',
            mimeTypesMessage: 'Only JPG, PNG, GIF and WebP images are allowed.',
            extensionsMessage: 'Only JPG, PNG, GIF and WebP images are allowed.',
        );

        // Check every file before storing any, so a rejected file never leaves half a batch behind
        foreach ($command->imageFiles as $imageFile) {
            if (!$imageFile instanceof UploadedFile) {
                return Result::fail('No valid images were added.');
            }

            $errors = $this->validator->validate($imageFile, $constraint);
            if (count($errors) > 0) {
                return Result::fail(sprintf('%s: %s', $imageFile->getClientOriginalName(), $errors[0]->getMessage()));
            }
        }

        $position = $this->productImages->getNextPositionForProduct($product);
        $added = 0;

        foreach ($command->imageFiles as $imageFile) {
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
