<?php

namespace App\Service\Crud\Core;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.create.action')]
final class CrudCreateAction implements CrudActionInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function handle(object $entity, ?array $context): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}