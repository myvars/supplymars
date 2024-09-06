<?php

namespace App\Service\Product;

use App\Entity\Category;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubcategoryMapper
{
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
        if (!$supplierSubcategory instanceof SupplierSubcategory) {
            throw new \InvalidArgumentException('Supplier subcategory is missing');
        }

        $subcategory = $this->subcategoryAlreadyExists($supplierSubcategory->getName());
        if ($subcategory instanceof Subcategory) {
            $this->mapSubcategoryToSupplier($supplierSubcategory, $subcategory);

            return $subcategory;
        }

        $subcategory = (new Subcategory())
            ->setName($supplierSubcategory->getName())
            ->setCategory($category)
            ->setIsActive(true);

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
        if ($supplierSubcategory->getMappedSubcategory() instanceof Subcategory) {
            return;
        }

        $subcategory->addSupplierSubcategory($supplierSubcategory);

        $this->entityManager->persist($supplierSubcategory);
        $this->entityManager->flush();
    }
}