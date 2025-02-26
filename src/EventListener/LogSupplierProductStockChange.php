<?php

namespace App\EventListener;

use App\Entity\SupplierStockChangeLog;
use App\Event\SupplierProductCostChangedEvent;
use App\Event\SupplierProductStockChangedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class LogSupplierProductStockChange
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    #[AsEventListener]
    public function onSupplierProductStockChange(SupplierProductStockChangedEvent $event): void
    {
        $supplierStockChangeLog = SupplierStockChangeLog::create(
            $event->getDomainEventType(),
            $event->getSupplierProduct(),
            $event->getEventTimestamp()
        );

        $this->save($supplierStockChangeLog);
    }

    #[AsEventListener]
    public function onSupplierProductCostChange(SupplierProductCostChangedEvent $event): void
    {
        $supplierStockChangeLog = SupplierStockChangeLog::create(
            $event->getDomainEventType(),
            $event->getSupplierProduct(),
            $event->getEventTimestamp()
        );

        $this->save($supplierStockChangeLog);
    }

    private function save(SupplierStockChangeLog $supplierStockChangeLog): void
    {
        $errors = $this->validator->validate($supplierStockChangeLog);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            throw new \InvalidArgumentException($errorsString);
        }

        $this->entityManager->persist($supplierStockChangeLog);
        $this->entityManager->flush();
    }
}
