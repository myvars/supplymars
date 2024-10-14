<?php

namespace App\Form\DataTransformer;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

readonly class IdToManagerTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Transforms a string (id) to an object (User).
     *
     *
     * @throws TransformationFailedException if object (User) is not found
     */
    public function transform(mixed $value): ?User
    {
        if (null === $value) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $value, 'isStaff' => true]);

        if (null === $user) {
            throw new TransformationFailedException(sprintf('A category with Id "%s" does not exist!', $value));
        }

        return $user;
    }

    /**
     * Transforms an object (User) to a string (id).
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
