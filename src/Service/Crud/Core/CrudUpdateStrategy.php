<?php

namespace App\Service\Crud\Core;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.update.strategy')]
final class CrudUpdateStrategy implements CrudUpdateStrategyInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function update(object $entity, ?array $context): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}