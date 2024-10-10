<?php

namespace App\Form\DataTransformer;

use App\Entity\SupplierCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToSupplierCategoryTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (SupplierCategory).
     *
     *
     * @throws TransformationFailedException if object (SupplierCategory) is not found
     */
    public function transform(mixed $value): ?SupplierCategory
    {
        if (null === $value) {
            return null;
        }

        $supplierCategory = $this->entityManager->getRepository(SupplierCategory::class)->find($value);

        if (null === $supplierCategory) {
            throw new TransformationFailedException(
                sprintf('A supplier category with Id "%s" does not exist!', $value)
            );
        }

        return $supplierCategory;
    }

    /**
     * Transforms an object (SupplierCategory) to a string (id).
     *
     * @param mixed|null $value
     */
    public function reverseTransform(mixed $value): ?int
    {
        if (!$value) {
            return null;
        }

        return $value->getId();
    }
}
