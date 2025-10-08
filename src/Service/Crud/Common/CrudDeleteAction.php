<?php

namespace App\Service\Crud\Common;

use Doctrine\ORM\EntityManagerInterface;

final readonly class CrudDeleteAction implements CrudActionInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $this->delete($crudOptions->getEntity());
    }

    private function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
