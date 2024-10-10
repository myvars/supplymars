<?php

namespace App\Form\DataTransformer;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToCategoryTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (Category).
     *
     *
     * @throws TransformationFailedException if object (Category) is not found
     */
    public function transform(mixed $value): ?Category
    {
        if (null === $value) {
            return null;
        }

        $category = $this->entityManager->getRepository(Category::class)->find($value);

        if (null === $category) {
            throw new TransformationFailedException(sprintf('A category with Id "%s" does not exist!', $value));
        }

        return $category;
    }

    /**
     * Transforms an object (Category) to a string (id).
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
