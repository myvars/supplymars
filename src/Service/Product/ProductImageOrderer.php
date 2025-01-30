<?php

namespace App\Service\Product;

use App\Entity\Product;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use Doctrine\ORM\EntityManagerInterface;

final class ProductImageOrderer implements CrudActionInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $product = $crudOptions->getEntity();
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Entity must be an instance of Product');
        }

        $orderedIds = $crudOptions->getCrudActionContext()['orderedIds'] ?? [];
        $this->createProductImagesFromArray($product, $orderedIds);
    }

    public function createProductImagesFromArray(Product $product, array $orderedIds=[]): void
    {
        $orderedIds = array_flip($orderedIds);
        foreach ($product->getProductImages() as $productImage) {
            $newPosition = (int) $orderedIds[$productImage->getId()] + 1;
            $productImage->setPosition($newPosition);
        }

        $this->entityManager->flush();
    }
}