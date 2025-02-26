<?php

namespace App\Service\PurchaseOrder;

use App\Entity\CustomerOrder;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreatePurchaseOrder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function fromOrder(CustomerOrder $customerOrder, Supplier $supplier): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::createFromOrder($customerOrder, $supplier);

        $errors = $this->validator->validate($purchaseOrder);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($purchaseOrder);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($purchaseOrder);

        return $purchaseOrder;
    }
}
