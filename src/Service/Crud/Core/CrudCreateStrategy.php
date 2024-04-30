<?php

namespace App\Service\Crud\Core;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.create.strategy')]
final class CrudCreateStrategy implements CrudCreateStrategyInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(object $entity, ?array $context): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}