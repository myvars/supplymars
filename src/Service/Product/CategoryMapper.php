<?php

namespace App\Service\Product;

use App\Entity\Category;
use App\Entity\SupplierProduct;
use App\Entity\User;
use App\Entity\VatRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryMapper
{
    public const DEFAULT_VAT_RATE = 'Standard rate';
    public const DEFAULT_OWNER = 'adam@admin.com';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly Security $security
    ) {
    }

    public function createCategoryFromSupplierProduct(SupplierProduct $supplierProduct): Category
    {
        $supplierCategory = $supplierProduct->getSupplierCategory();

        if (!$supplierCategory) {
            throw new \InvalidArgumentException('Supplier category is missing');
        }

        if ($category = $this->categoryAlreadyExists($supplierCategory->getName())) {
            return $category;
        }

        $category = (new Category())
            ->setName($supplierCategory->getName())
            ->setOwner($this->security->getUser() ?? $this->getOwner())
            ->setVatRate($this->getDefaultVatRate())
            ->setIsActive(true);

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function categoryAlreadyExists(string $name): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $name]);
    }

    private function getOwner(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::DEFAULT_OWNER]);
    }

    private function getDefaultVatRate(): VatRate
    {
        return $this->entityManager->getRepository(VatRate::class)->findOneBy(['name' => self::DEFAULT_VAT_RATE]);
    }
}