<?php

namespace App\Service\Crud\Common;

use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CrudCreateAction implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $this->create($crudOptions->getEntity());
    }

    private function create(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($entity);
    }
}