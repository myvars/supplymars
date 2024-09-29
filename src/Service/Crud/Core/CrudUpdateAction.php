<?php

namespace App\Service\Crud\Core;

use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.update.action')]
final readonly class CrudUpdateAction implements CrudActionInterface
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