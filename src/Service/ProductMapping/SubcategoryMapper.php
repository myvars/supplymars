<?php

namespace App\Service\ProductMapping;

use App\Entity\Category;
use App\Entity\PriceModel;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubcategoryMapper
{
    public const DEFAULT_MARKUP = '0.000';
    public const DEFAULT_PRICE_MODEL = PriceModel::NONE;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createSubcategoryFromSupplierProduct(
        SupplierProduct $supplierProduct,
        Category $category
    ): Subcategory
    {
        $supplierSubcategory = $supplierProduct->getSupplierSubcategory();
        if (!$supplierSubcategory) {
            throw new \InvalidArgumentException('Supplier subcategory is missing');
        }

        if ($subcategory = $this->subcategoryAlreadyExists($supplierSubcategory->getName())) {
            $this->mapSubcategoryToSupplier($supplierSubcategory, $subcategory);

            return $subcategory;
        }

        $subcategory = new Subcategory();
        $subcategory->setName($supplierSubcategory->getName());
        $subcategory->setCategory($category);
        $subcategory->setDefaultMarkup(self::DEFAULT_MARKUP);
        $subcategory->setPriceModel(self::DEFAULT_PRICE_MODEL);
        $subcategory->setIsActive(true);

        $errors = $this->validator->validate($subcategory);

        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $this->entityManager->persist($subcategory);
        $this->entityManager->flush();

        $this->mapSubcategoryToSupplier($supplierSubcategory, $subcategory);

        return $subcategory;
    }

    private function subcategoryAlreadyExists(string $name): ?Subcategory
    {
        return $this->entityManager->getRepository(Subcategory::class)->findOneBy(['name' => $name]);
    }

    private function mapSubcategoryToSupplier(
        SupplierSubcategory $supplierSubcategory,
        Subcategory $subcategory
    ): void {
        if ($supplierSubcategory->getMappedSubcategory()) {
            return;
        }

        $subcategory->addSupplierSubcategory($supplierSubcategory);
        $this->entityManager->persist($supplierSubcategory);
        $this->entityManager->flush();
    }
}