<?php

namespace App\Service\OrderProcessing;

use App\Entity\StatusChangeLog;
use App\Entity\User;
use App\Event\StatusWasChangedEventInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class StatusChangedLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function fromStatusWasChangedEvent(
        StatusWasChangedEventInterface $event,
        User $currentUser,
        int $legacyId
    ): void {
        $statusChangeLog = new StatusChangeLog(
            $event->type(),
            $legacyId,
            $event->statusChange()->after()->value,
            $event->occurredAt(),
            $currentUser
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
