<?php

namespace App\Form\DataTransformer;

use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToSupplierTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (Supplier).
     *
     * @throws TransformationFailedException if object (Supplier) is not found
     */
    public function transform(mixed $value): ?Supplier
    {
        if (null === $value) {
            return null;
        }

        $supplier = $this->entityManager->getRepository(Supplier::class)->find($value);

        if (null === $supplier) {
            throw new TransformationFailedException(sprintf('A supplier with Id "%s" does not exist!', $value));
        }

        return $supplier;
    }

    /**
     * Transforms an object (Supplier) to a string (id).
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
