<?php

namespace App\Form\DataTransformer;

use App\Entity\SupplierSubcategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToSupplierSubcategoryTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (SupplierSubcategory).
     *
     * @throws TransformationFailedException if object (SupplierSubcategory) is not found
     */
    public function transform(mixed $value): ?SupplierSubcategory
    {
        if (null === $value) {
            return null;
        }

        $supplierSubcategory = $this->entityManager->getRepository(SupplierSubcategory::class)->find($value);

        if (null === $supplierSubcategory) {
            throw new TransformationFailedException(sprintf('A supplier subcategory with Id "%s" does not exist!', $value));
        }

        return $supplierSubcategory;
    }

    /**
     * Transforms an object (SupplierSubcategory) to a string (id).
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
