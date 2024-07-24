<?php

namespace App\Entity;

use App\Enum\DomainEventType;
use App\Repository\StatusChangeLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatusChangeLogRepository::class)]
class StatusChangeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter an event type')]
    private DomainEventType $eventType;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter an event type Id')]
    #[Assert\Positive(message: 'Please enter a positive event type Id')]
    private int $eventTypeId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a status')]
    #[Assert\Length(max: 255, maxMessage: 'Status must be less than {{ limit }} characters')]
    private string $status;

    #[ORM\ManyToOne(inversedBy: 'statusChangeLogs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a user')]
    private User $user;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter an event timestamp')]
    private \DateTimeImmutable $eventTimestamp;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        DomainEventType $eventType,
        int $eventTypeId,
        string $status,
        User $user,
        \DateTimeImmutable $eventTimestamp
    ) {
        $this->eventType = $eventType;
        $this->eventTypeId = $eventTypeId;
        $this->status = $status;
        $this->user = $user;
        $this->eventTimestamp = $eventTimestamp;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
