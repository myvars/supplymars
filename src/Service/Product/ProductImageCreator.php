<?php

namespace App\Service\Product;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductImageCreator implements CrudActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly UploadHelper $uploadHelper,
        #[Autowire('%app.product_uploads%')]
        private readonly string $appProductUploads,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $product = $crudOptions->getEntity();
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Entity must be an instance of Product');
        }

        $imageFiles = $crudOptions->getCrudActionContext()['imageFiles'] ?? [];
        $this->createProductImagesFromArray($product, $imageFiles);
    }

    public function createProductImagesFromArray(Product $product, array $imageFiles=[]): void
    {
        if (count($imageFiles) === 0) {
            return;
        }

        $nextPosition = $this->getNextPosition($product);
        foreach ($imageFiles as $imageFile) {
            $productImage = (new ProductImage())
                ->setProduct($product)
                ->setImageFile($imageFile)
                ->setPosition($nextPosition);

            $errors = $this->validator->validate($productImage);
            if (count($errors) > 0) {
                continue;
            }

            $this->createProductImage($productImage);
            ++$nextPosition;
        }
    }

    private function createProductImage(ProductImage $productImage): void
    {
        $productImage->setImageName(
            $this->uploadHelper->uploadFile($productImage->getImageFile(), $this->appProductUploads)
        );

        $this->entityManager->persist($productImage);
        $this->entityManager->flush();
    }

    private function getNextPosition(Product $product): int
    {
        return $this->entityManager->getRepository(ProductImage::class)->getNextPositionForProduct($product);
    }
}