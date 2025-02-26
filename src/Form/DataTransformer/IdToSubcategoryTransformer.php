<?php

namespace App\Form\DataTransformer;

use App\Entity\Subcategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToSubcategoryTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (Subcategory).
     *
     * @throws TransformationFailedException if object (Subcategory) is not found
     */
    public function transform(mixed $value): ?Subcategory
    {
        if (null === $value) {
            return null;
        }

        $subcategory = $this->entityManager->getRepository(Subcategory::class)->find($value);

        if (null === $subcategory) {
            throw new TransformationFailedException(sprintf('A subcategory with Id "%s" does not exist!', $value));
        }

        return $subcategory;
    }

    /**
     * Transforms an object (Subcategory) to a string (id).
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
