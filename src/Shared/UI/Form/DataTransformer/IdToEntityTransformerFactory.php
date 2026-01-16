<?php

namespace App\Shared\UI\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;

final readonly class IdToEntityTransformerFactory
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param class-string<object>                               $entityClass
     * @param \Closure(EntityManagerInterface, int):?object|null $finder
     */
    public function for(string $entityClass, ?\Closure $finder = null): IdToEntityTransformer
    {
        return new IdToEntityTransformer($this->em, $entityClass, $finder);
    }
}
