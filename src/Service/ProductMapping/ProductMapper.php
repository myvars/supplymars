<?php

namespace App\Service\ProductMapping;

use App\Entity\Manufacturer;
use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductMapper
{
    public const DEFAULT_MARKUP = '0.000';
    public const DEFAULT_PRICE_MODEL = PriceModel::NONE;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createProductFromSupplierProduct(
        SupplierProduct $supplierProduct,
        Manufacturer $manufacturer,
        Subcategory $subcategory
    ): Product
    {
        if ($product = $this->productAlreadyExists($supplierProduct->getName())) {
            $this->mapProductToSupplier($supplierProduct, $product);

            return $product;
        }

        $product = new Product();
        $product->setName($supplierProduct->getName());
        $product->setDefaultMarkup(self::DEFAULT_MARKUP);
        $product->setPriceModel(self::DEFAULT_PRICE_MODEL);
        $product->setCost($supplierProduct->getCost());
        $product->setLeadTimeDays($supplierProduct->getLeadTimeDays());
        $product->setMfrPartNumber($supplierProduct->getMfrPartNumber());
        $product->setWeight($supplierProduct->getWeight());
        $product->setCategory($subcategory->getCategory());
        $product->setSubcategory($subcategory);
        $product->setManufacturer($manufacturer);
        $product->setIsActive(true);

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