<?php

namespace App\Service\Crud\Core;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.delete.strategy')]
final class CrudDeleteStrategy implements CrudDeleteStrategyInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function delete(object $entity, ?array $context): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}