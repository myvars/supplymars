<?php

namespace App\Service\OrderProcessing;

use App\Entity\StatusChangeLog;
use App\Event\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StatusChangeLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function fromStatusChangeEvent(DomainEvent $event, int $eventTypeId, string $status): void
    {
        $statusChangeLog = new StatusChangeLog(
            $event->getDomainEventType(),
            $eventTypeId,
            $status,
            $event->getUser(),
            $event->getEventTimestamp()
        );

        $this->save($statusChangeLog);
    }

    private function save(StatusChangeLog $statusChangeLog): void
    {
        $errors = $this->validator->validate($statusChangeLog);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            throw new \InvalidArgumentException($errorsString);
        }

        $this->entityManager->persist($statusChangeLog);
        $this->entityManager->flush();
    }
}
