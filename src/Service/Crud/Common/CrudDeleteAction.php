<?php

namespace App\Service\Crud\Common;

use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CrudDeleteAction implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $this->delete($crudOptions->getEntity());
    }

    private function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($entity);
    }
}
