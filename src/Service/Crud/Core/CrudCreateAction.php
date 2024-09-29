<?php

namespace App\Service\Crud\Core;

use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.create.action')]
final readonly class CrudCreateAction implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(object $entity, ?array $context): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($entity);
    }
}