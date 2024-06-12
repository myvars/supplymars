<?php

namespace App\Service\PurchaseOrder;

use App\Entity\CustomerOrder;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreatePurchaseOrder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function fromOrder(CustomerOrder $customerOrder, Supplier $supplier, bool $flush = true): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::createFromOrder($customerOrder, $supplier);

        $errors = $this->validator->validate($purchaseOrder);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $this->entityManager->persist($purchaseOrder);
        if ($flush) {
            $this->entityManager->flush();
        }

        return $purchaseOrder;
    }
}