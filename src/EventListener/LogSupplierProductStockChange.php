<?php

namespace App\EventListener;

use App\Entity\SupplierStockChangeLog;
use App\Event\SupplierProductStockWasChangedEvent;
use App\EventListener\DoctrineEvents\SupplierProductPublicIdResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Listens for supplier stock change events and logs them.
 */
#[AsEventListener(event: SupplierProductStockWasChangedEvent::class)]
final readonly class LogSupplierProductStockChange
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private SupplierProductPublicIdResolver $publicIdResolver,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(SupplierProductStockWasChangedEvent $event): void
    {
        $legacyId = $this->publicIdResolver->resolve($event->publicId()); // VO in, ?int out
        if ($legacyId === null) {
            $this->logger->warning('Could not resolve legacy ID for public ID', [
                'publicId' => (string) $event->publicId(),
                'eventClass' => get_class($event),
            ]);

            return;
        }

        $supplierStockChangeLog = SupplierStockChangeLog::create(
            $event->type(),
            $legacyId,
            $event->stockChange(),
            $event->costChange(),
            $event->occurredAt()
        );

        $this->save($supplierStockChangeLog);
    }

    private function save(SupplierStockChangeLog $supplierStockChangeLog): void
    {
        $errors = $this->validator->validate($supplierStockChangeLog);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($supplierStockChangeLog);
        $this->entityManager->flush();
    }
}
