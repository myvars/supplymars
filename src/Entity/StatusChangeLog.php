<?php

namespace App\Entity;

use App\Enum\DomainEventType;
use App\Repository\StatusChangeLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatusChangeLogRepository::class)]
#[ORM\Index(columns: ["event_type_id", "event_type", "status"])]
class StatusChangeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank(message: 'Please enter an event type')]
        private readonly DomainEventType $eventType,
        #[ORM\Column]
        #[Assert\NotBlank(message: 'Please enter an event type Id')]
        #[Assert\Positive(message: 'Please enter a positive event type Id')]
        private readonly int $eventTypeId,
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank(message: 'Please enter a status')]
        #[Assert\Length(max: 255, maxMessage: 'Status must be less than {{ limit }} characters')]
        private readonly string $status,
        #[ORM\ManyToOne(inversedBy: 'statusChangeLogs')]
        #[ORM\JoinColumn(nullable: false)]
        #[Assert\NotNull(message: 'Please enter a user')]
        private User $user,
        #[ORM\Column]
        #[Assert\NotBlank(message: 'Please enter an event timestamp')]
        private readonly \DateTimeImmutable $eventTimestamp
    ) {
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

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getEventTimestamp(): \DateTimeImmutable
    {
        return $this->eventTimestamp;
    }
}
