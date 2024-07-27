<?php

namespace App\Service\Product;

use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductMapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createProductFromSupplierProduct(
        SupplierProduct $supplierProduct,
        Manufacturer $manufacturer,
        Subcategory $subcategory
    ): Product {
        if ($product = $this->productAlreadyExists($supplierProduct->getName())) {
            $this->mapProductToSupplier($supplierProduct, $product);

            return $product;
        }

        $product = (new Product())
            ->setName($supplierProduct->getName())
            ->setCost($supplierProduct->getCost())
            ->setLeadTimeDays($supplierProduct->getLeadTimeDays())
            ->setMfrPartNumber($supplierProduct->getMfrPartNumber())
            ->setWeight($supplierProduct->getWeight())
            ->setCategory($subcategory->getCategory())
            ->setSubcategory($subcategory)
            ->setManufacturer($manufacturer)
            ->setIsActive(true);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->mapProductToSupplier($supplierProduct, $product);

        return $product;
    }

    private function productAlreadyExists(string $name): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $name]);
    }

    private function mapProductToSupplier(
        SupplierProduct $supplierProduct,
        Product $product
    ): void {
        if ($supplierProduct->getProduct()) {
            return;
        }

        $product->addSupplierProduct($supplierProduct);

        $this->entityManager->persist($supplierProduct);
        $this->entityManager->flush();
    }
}