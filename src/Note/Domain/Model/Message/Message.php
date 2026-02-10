<?php

namespace App\Note\Domain\Model\Message;

use App\Customer\Domain\Model\User\User;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Infrastructure\Persistence\Doctrine\MessageDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageDoctrineRepository::class)]
class Message
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private Ticket $ticket;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $author = null;

    #[ORM\Column(length: 255)]
    private AuthorType $authorType;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Please enter a message')]
    private ?string $body = null;

    #[ORM\Column(length: 255)]
    private MessageVisibility $visibility;

    final public function __construct()
    {
        $this->initializePublicId();
    }

    public static function create(
        Ticket $ticket,
        ?User $author,
        AuthorType $authorType,
        string $body,
        MessageVisibility $visibility,
    ): self {
        $self = new self();
        $self->ticket = $ticket;
        $self->author = $author;
        $self->authorType = $authorType;
        $self->setBody($body);
        $self->visibility = $visibility;

        return $self;
    }

    public static function system(Ticket $ticket, string $body): self
    {
        return self::create(
            ticket: $ticket,
            author: null,
            authorType: AuthorType::SYSTEM,
            body: $body,
            visibility: MessageVisibility::PUBLIC,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): MessagePublicId
    {
        return MessagePublicId::fromString($this->publicIdString());
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getAuthorType(): AuthorType
    {
        return $this->authorType;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getVisibility(): MessageVisibility
    {
        return $this->visibility;
    }

    public function isInternal(): bool
    {
        return $this->visibility->isInternal();
    }

    public function isSystem(): bool
    {
        return $this->authorType === AuthorType::SYSTEM;
    }

    private function setBody(string $body): void
    {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $this->body = $body;
    }
}
