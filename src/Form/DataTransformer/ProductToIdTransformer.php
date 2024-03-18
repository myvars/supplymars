<?php

namespace App\Form\DataTransformer;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class ProductToIdTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms an object (product) to a string (id).
     *
     * @param Product|null $value
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        return $value->getId();
    }

    /**
     * Transforms a string (value) to an object (Product).
     *
     * @param string $value
     *
     * @throws TransformationFailedException if object (Product) is not found
     */
    public function reverseTransform(mixed $value): ?Product
    {
        if (!$value) {
            return null;
        }

        $product = $this->entityManager->getRepository(Product::class)->find($value);

        if (null === $product) {
            throw new TransformationFailedException(sprintf('A product with Id "%s" does not exist!', $value));
        }

        return $product;
    }
}
