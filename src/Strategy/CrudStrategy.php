<?php

namespace App\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias]
final class CrudStrategy implements CrudStrategyInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(object $entity): void
    {
        // Custom logic for creating an entity
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function update(object $entity): void
    {
        // Custom logic for updating an entity
        $this->entityManager->flush();
    }

    public function delete(object $entity): void
    {
        // Custom logic for deleting an entity
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}