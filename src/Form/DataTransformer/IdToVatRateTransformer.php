<?php

namespace App\Form\DataTransformer;

use App\Entity\VatRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToVatRateTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (VatRate).
     *
     *
     * @throws TransformationFailedException if object (VatRate) is not found
     */
    public function transform(mixed $value): ?VatRate
    {
        if (null === $value) {
            return null;
        }

        $vatRate = $this->entityManager->getRepository(VatRate::class)->find($value);

        if (null === $vatRate) {
            throw new TransformationFailedException(sprintf('A Vat rate with Id "%s" does not exist!', $value));
        }

        return $vatRate;
    }

    /**
     * Transforms an object (VatRate) to a string (id).
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
