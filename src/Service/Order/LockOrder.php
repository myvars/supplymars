<?php

namespace App\Service\Order;

use App\Entity\CustomerOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class LockOrder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function toggleStatus(CustomerOrder $customerOrder): void
    {
        $customerOrder->setOrderLock(
            $customerOrder->getOrderLock() === null ? $this->security->getUser() : null
        );

        $this->entityManager->flush();
    }
}