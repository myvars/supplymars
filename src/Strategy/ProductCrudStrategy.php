<?php

namespace App\Strategy;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

// Create a new strategy for Product entity (example)
class ProductCrudStrategy implements CrudStrategyInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(object $entity): void
    {
        assert($entity instanceof Product);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function update(object $entity): void
    {
        assert($entity instanceof Product);

        $this->entityManager->flush();
    }

    public function delete(object $entity): void
    {
        assert($entity instanceof Product);

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}