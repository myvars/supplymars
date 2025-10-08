<?php

namespace App\Service\Crud\Common;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CrudCreateAction implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $this->create($crudOptions->getEntity());
    }

    private function create(object $entity): void
    {
        $errors = $this->validator->validate($entity);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
