<?php

namespace App\Strategy;

use App\Entity\Category;

// Create new strategy for Category entity by decorating the CrudStrategyInterface (example)
class CategoryCrudStrategy implements CrudStrategyInterface
{
    public function __construct(private readonly CrudStrategyInterface $crudStrategy)
    {
    }

    public function create(object $entity): void
    {
        assert($entity instanceof Category);

        $this->crudStrategy->create($entity);
    }

    public function update(object $entity): void
    {
        assert($entity instanceof Category);

        $this->crudStrategy->update($entity);
    }

    public function delete(object $entity): void
    {
        assert($entity instanceof Category);

        $this->crudStrategy->delete($entity);
    }
}