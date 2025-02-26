<?php

namespace App\Service\Order;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class LockOrder implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $customerOrder = $crudOptions->getEntity();
        if (!$customerOrder instanceof CustomerOrder) {
            throw new \InvalidArgumentException('Entity must be an instance of CustomerOrder');
        }

        $this->toggleStatus($customerOrder);
    }

    public function toggleStatus(CustomerOrder $customerOrder): void
    {
        $customerOrder->lockOrder(
            $customerOrder->getOrderLock() instanceof User ? null : $this->security->getUser()
        );

        $this->entityManager->flush();
    }
}
