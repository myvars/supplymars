<?php

namespace App\Audit\Infrastructure\Logging;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Customer\Domain\Model\User\User;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\StatusChange;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class StatusChangeLogWriter
{
    public function __construct(
        private StatusChangeLogRepository $statusChangeLogs,
        private ValidatorInterface $validator,
        private FlusherInterface $flusher,
    ) {
    }

    public function write(
        DomainEventType $type,
        int $entityId,
        StatusChange $statusChange,
        \DateTimeImmutable $occurredAt,
        User $currentUser,
    ): void {
        $statusChangeLog = new StatusChangeLog(
            $type,
            $entityId,
            (string) $statusChange->after()->value,
            $occurredAt,
            $currentUser
        );

        $errors = $this->validator->validate($statusChangeLog);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->statusChangeLogs->add($statusChangeLog);
        $this->flusher->flush();
    }
}
