<?php

namespace App\Shared\UI\Form\DataTransformer;

use Closure;
use Doctrine\ORM\EntityManagerInterface;

final readonly class IdToEntityTransformerFactory
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param class-string<object> $entityClass
     * @param null|Closure(\Doctrine\ORM\EntityManagerInterface, int): ?object $finder
     */
    public function for(string $entityClass, ?Closure $finder = null): IdToEntityTransformer
    {
        return new IdToEntityTransformer($this->em, $entityClass, $finder);
    }
}
