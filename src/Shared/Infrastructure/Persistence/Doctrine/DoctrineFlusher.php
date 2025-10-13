<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\FlusherInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineFlusher implements FlusherInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function flush(): void
    {
        $this->em->flush();
    }
}
