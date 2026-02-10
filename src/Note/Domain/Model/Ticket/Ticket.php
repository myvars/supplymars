<?php

namespace App\Note\Domain\Model\Ticket;

use App\Customer\Domain\Model\User\User;
use App\Note\Domain\Model\Message\AuthorType;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Message\MessageVisibility;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Infrastructure\Persistence\Doctrine\TicketDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketDoctrineRepository::class)]
#[ORM\Index(name: 'idx_ticket_pool_status_snooze', columns: ['pool_id', 'status', 'snoozed_until'])]
#[ORM\Index(name: 'idx_ticket_last_message', columns: ['last_message_at'])]
class Ticket
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a subject')]
    private ?string $subject = null;

    #[ORM\Column(length: 255)]
    private TicketStatus $status;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Pool $pool;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $snoozedUntil = null;

    #[ORM\Column]
    private int $messageCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'ticket', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    final public function __construct()
    {
        $this->initializePublicId();
        $this->status = TicketStatus::getDefault();
        $this->messages = new ArrayCollection();
    }

    public static function create(Pool $pool, User $customer, string $subject, string $body): self
    {
        $self = new self();
        $self->pool = $pool;
        $self->customer = $customer;
        $self->setSubject($subject);

        $firstMessage = Message::create(
            ticket: $self,
            author: $customer,
            authorType: AuthorType::CUSTOMER,
            body: $body,
            visibility: MessageVisibility::PUBLIC,
        );

        $self->messages->add($firstMessage);
        $self->messageCount = 1;
        $self->lastMessageAt = new \DateTimeImmutable();

        return $self;
    }

    public function addMessage(Message $message): void
    {
        $this->messages->add($message);
        ++$this->messageCount;
        $this->lastMessageAt = new \DateTimeImmutable();
    }

    public function removeMessage(Message $message): void
    {
        $this->messages->removeElement($message);
        $this->messageCount = max(0, $this->messageCount - 1);

        $latest = null;
        foreach ($this->messages as $m) {
            $createdAt = $m->getCreatedAt();
            if ($createdAt !== null && ($latest === null || $createdAt > $latest)) {
                $latest = $createdAt;
            }
        }

        $this->lastMessageAt = $latest !== null
            ? \DateTimeImmutable::createFromMutable($latest)
            : null;
    }

    public function close(): void
    {
        if (!$this->status->allowClose()) {
            throw new \LogicException('Ticket is already closed.');
        }

        $this->status = TicketStatus::CLOSED;
    }

    public function reopen(): void
    {
        if (!$this->status->allowReopen()) {
            throw new \LogicException('Ticket is not closed.');
        }

        $this->status = TicketStatus::OPEN;
    }

    public function reassignPool(Pool $newPool): void
    {
        $this->pool = $newPool;
    }

    public function snooze(\DateTimeImmutable $until): void
    {
        if ($until <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Snooze date must be in the future.');
        }

        $this->snoozedUntil = $until;
    }

    public function unsnooze(): void
    {
        $this->snoozedUntil = null;
    }

    public function isSnoozed(): bool
    {
        return $this->snoozedUntil instanceof \DateTimeImmutable && $this->snoozedUntil > new \DateTimeImmutable();
    }

    public function transitionStatusForReply(AuthorType $authorType): void
    {
        if ($this->status->isClosed()) {
            return;
        }

        $newStatus = match ($authorType) {
            AuthorType::STAFF => TicketStatus::REPLIED,
            AuthorType::CUSTOMER => TicketStatus::OPEN,
            AuthorType::SYSTEM => $this->status,
        };

        if ($newStatus !== $this->status) {
            $this->status = $newStatus;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): TicketPublicId
    {
        return TicketPublicId::fromString($this->publicIdString());
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function getPool(): Pool
    {
        return $this->pool;
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function getSnoozedUntil(): ?\DateTimeImmutable
    {
        return $this->snoozedUntil;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function getLastMessageAt(): ?\DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    private function setSubject(string $subject): void
    {
        $subject = trim($subject);
        if ($subject === '') {
            throw new \InvalidArgumentException('Ticket subject cannot be empty');
        }

        $this->subject = $subject;
    }
}
