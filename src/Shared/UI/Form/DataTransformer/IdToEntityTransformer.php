<?php

namespace App\Shared\UI\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final readonly class IdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @param class-string<object>                                $entityClass
     * @param \Closure(EntityManagerInterface, int): ?object|null $finder
     */
    public function __construct(
        private EntityManagerInterface $em,
        private string $entityClass,
        private ?\Closure $finder = null,
    ) {
    }

    public function transform(mixed $value): ?object
    {
        if ($value === null) {
            return null;
        }

        $entity = $this->finder
            ? ($this->finder)($this->em, (int) $value)
            : $this->em->getRepository($this->entityClass)->find((int) $value);

        if (!$entity) {
            throw new TransformationFailedException(sprintf('%s with id "%s" not found.', $this->entityClass, $value));
        }

        return $entity;
    }

    public function reverseTransform(mixed $value): ?int
    {
        if (!$value) {
            return null;
        }

        if (!\is_object($value) || !method_exists($value, 'getId')) {
            throw new TransformationFailedException('Expected an entity with getId().');
        }

        return (int) $value->getId();
    }
}
