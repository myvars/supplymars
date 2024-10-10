<?php

namespace App\Form\DataTransformer;

use App\Entity\Manufacturer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToManufacturerTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (Manufacturer).
     *
     *
     * @throws TransformationFailedException if object (Manufacturer) is not found
     */
    public function transform(mixed $value): ?Manufacturer
    {
        if (null === $value) {
            return null;
        }

        $manufacturer = $this->entityManager->getRepository(Manufacturer::class)->find($value);

        if (null === $manufacturer) {
            throw new TransformationFailedException(
                sprintf('A manufacturer with Id "%s" does not exist!', $value)
            );
        }

        return $manufacturer;
    }

    /**
     * Transforms an object (Manufacturer) to a string (id).
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
