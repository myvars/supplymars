<?php

namespace App\Audit\Domain\Model\StatusChange;

use App\Audit\Infrastructure\Persistence\Doctrine\StatusChangeLogDoctrineRepository;
use App\Customer\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatusChangeLogDoctrineRepository::class)]
#[ORM\Index(columns: ['event_type_id', 'event_type', 'status'])]
class StatusChangeLog
{
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        private readonly DomainEventType $eventType,

        #[ORM\Column]
        #[Assert\Positive(message: 'Please enter a positive event type Id')]
        private readonly int $eventTypeId,

        #[ORM\Column(length: 255)]
        #[Assert\NotBlank(message: 'Please enter a status')]
        #[Assert\Length(max: 255, maxMessage: 'Status must be less than {{ limit }} characters')]
        private readonly string $status,

        #[ORM\Column]
        private readonly \DateTimeImmutable $eventTimestamp,

        #[ORM\ManyToOne(inversedBy: 'statusChangeLogs')]
        #[ORM\JoinColumn(nullable: false)]
        private User $user,
    ) {
    }

    public function assignUser(User $user): void
    {
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): DomainEventType
    {
        return $this->eventType;
    }

    public function getEventTypeId(): int
    {
        return $this->eventTypeId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEventTimestamp(): \DateTimeImmutable
    {
        return $this->eventTimestamp;
    }
}
