<?php

namespace App\Form\DataTransformer;

use App\Entity\SupplierManufacturer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToSupplierManufacturerTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (SupplierManufacturer).
     *
     *
     * @throws TransformationFailedException if object (SupplierManufacturer) is not found
     */
    public function transform(mixed $value): ?SupplierManufacturer
    {
        if (null === $value) {
            return null;
        }

        $supplierManufacturer = $this->entityManager->getRepository(SupplierManufacturer::class)->find($value);

        if (null === $supplierManufacturer) {
            throw new TransformationFailedException(
                sprintf('A supplier manufacturer with Id "%s" does not exist!', $value)
            );
        }

        return $supplierManufacturer;
    }

    /**
     * Transforms an object (SupplierManufacturer) to a string (id).
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
