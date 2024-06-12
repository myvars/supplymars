<?php

namespace App\Service\Crud\Core;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.delete.action')]
final class CrudDeleteAction implements CrudActionInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function handle(object $entity, ?array $context): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}