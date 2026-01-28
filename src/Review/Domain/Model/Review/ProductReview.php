<?php

namespace App\Review\Domain\Model\Review;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Review\Domain\Model\Review\Event\ReviewRatingWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewWasCreatedEvent;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'product_review')]
#[ORM\UniqueConstraint(name: 'unique_customer_product_review', columns: ['customer_id', 'product_id'])]
class ProductReview implements DomainEventProviderInterface
{
    use HasPublicUlid;
    use DomainEventProviderTrait;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CustomerOrder $customerOrder;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $rating;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(type: Types::STRING, enumType: ReviewStatus::class)]
    private ReviewStatus $status = ReviewStatus::PENDING;

    #[ORM\Column(type: Types::STRING, nullable: true, enumType: RejectionReason::class)]
    private ?RejectionReason $rejectionReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationNotes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $moderatedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    private function __construct()
    {
        $this->initializePublicId();
    }

    public static function create(
        User $customer,
        Product $product,
        CustomerOrder $customerOrder,
        int $rating,
        ?string $title,
        ?string $body,
    ): self {
        $review = new self();
        $review->customer = $customer;
        $review->product = $product;
        $review->customerOrder = $customerOrder;
        $review->setRating($rating);
        $review->title = $title;
        $review->body = $body;

        $review->raiseDomainEvent(
            new ReviewWasCreatedEvent($review->getPublicId())
        );

        return $review;
    }

    public function update(int $rating, ?string $title, ?string $body): void
    {
        $previousRating = $this->rating;

        $this->setRating($rating);
        $this->title = $title;
        $this->body = $body;

        if ($this->isPublished() && $previousRating !== $this->rating) {
            $this->raiseDomainEvent(
                new ReviewRatingWasChangedEvent($this->getPublicId())
            );
        }
    }

    public function approve(User $moderator): void
    {
        $this->changeStatus(ReviewStatus::PUBLISHED, $moderator);
        $this->publishedAt = new \DateTimeImmutable();
    }

    public function reject(User $moderator, RejectionReason $reason, ?string $notes = null): void
    {
        $this->changeStatus(ReviewStatus::REJECTED, $moderator);
        $this->rejectionReason = $reason;
        $this->moderationNotes = $notes;
    }

    public function hide(User $moderator): void
    {
        $this->changeStatus(ReviewStatus::HIDDEN, $moderator);
    }

    public function republish(User $moderator): void
    {
        $this->changeStatus(ReviewStatus::PUBLISHED, $moderator);
        $this->publishedAt ??= new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ReviewPublicId
    {
        return ReviewPublicId::fromString($this->publicIdString());
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getCustomerOrder(): CustomerOrder
    {
        return $this->customerOrder;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getStatus(): ReviewStatus
    {
        return $this->status;
    }

    public function getRejectionReason(): ?RejectionReason
    {
        return $this->rejectionReason;
    }

    public function getModerationNotes(): ?string
    {
        return $this->moderationNotes;
    }

    public function getModeratedBy(): ?User
    {
        return $this->moderatedBy;
    }

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function isPublished(): bool
    {
        return $this->status === ReviewStatus::PUBLISHED;
    }

    public function isPending(): bool
    {
        return $this->status === ReviewStatus::PENDING;
    }

    public function getName(): string
    {
        return $this->title ?? 'Review #' . $this->id;
    }

    private function setRating(int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $this->rating = $rating;
    }

    private function changeStatus(ReviewStatus $newStatus, User $moderator): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(sprintf('Cannot transition review from %s to %s.', $this->status->value, $newStatus->value));
        }

        $statusChange = StatusChange::from($this->status, $newStatus);
        $this->status = $newStatus;
        $this->moderatedBy = $moderator;
        $this->moderatedAt = new \DateTimeImmutable();

        if ($statusChange->hasChanged()) {
            $this->raiseDomainEvent(
                new ReviewStatusWasChangedEvent($this->getPublicId(), $statusChange)
            );
        }
    }
}
