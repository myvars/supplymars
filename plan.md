# Product Reviews System - Implementation Plan

## Table of Contents

1. [Design Decisions & Rationale](#1-design-decisions--rationale)
2. [Bounded Context & File Structure](#2-bounded-context--file-structure)
3. [Domain Model](#3-domain-model)
4. [Domain Events & Summary Projection](#4-domain-events--summary-projection)
5. [Moderation State Machine](#5-moderation-state-machine)
6. [Cross-context Integration](#6-cross-context-integration)
7. [Application Layer (Commands, Handlers, Search)](#7-application-layer)
8. [UI Layer (Controllers, Forms, Mappers, Validators)](#8-ui-layer)
9. [Templates & UI Components](#9-templates--ui-components)
10. [Menu & Navigation Changes](#10-menu--navigation-changes)
11. [Console Command (Review Generator)](#11-console-command)
12. [Configuration Changes](#12-configuration-changes)
13. [Tests](#13-tests)
14. [Suggested Improvements & Edge Cases](#14-suggested-improvements--edge-cases)
15. [Implementation Order](#15-implementation-order)

---

## 1. Design Decisions & Rationale

### New Bounded Context: `Review`

Reviews span Products (Catalog), Customers (Customer), and Orders (Order). Creating a dedicated `Review` bounded context is the cleanest approach:

- **Separation of concerns**: Review logic (moderation, summaries, generation) doesn't belong in Catalog, Order, or Customer.
- **Consistent architecture**: Every other domain concept has its own context. Reviews cross multiple existing boundaries.
- **Future flexibility**: A Review context can be independently scaled, extracted, or evolved.
- **Clean dependency direction**: Review depends on (references) Catalog/Customer/Order entities, never the reverse.

Cross-context Doctrine ManyToOne relationships are standard in this codebase (e.g., `CustomerOrderItem → Product`, `CustomerOrder → User`). Review will follow the same pattern.

### Rich Entity Model

Following the existing codebase philosophy of "rich entities" (entities with behavior, not anemic):

- `ProductReview` is an aggregate root with `DomainEventProviderTrait`
- Status transitions are enforced by the entity itself via `canTransitionTo()`
- Review creation uses a static factory method `create()`
- Moderation actions are explicit domain methods on the entity

### Summary as Projection Entity

`ProductReviewSummary` is a separate entity (not embedded in Product) updated via domain event listener. This:

- Avoids polluting the Product entity in the Catalog context
- Enables fast reads without scanning review tables
- Uses the existing event infrastructure (postFlush listener → sync event → listener recalculates)
- Is recalculated from scratch (not incremented) to avoid race conditions

---

## 2. Bounded Context & File Structure

```
src/Review/
├── Application/
│   ├── Command/
│   │   ├── CreateReview.php                    # Create review (admin tool)
│   │   ├── UpdateReview.php                    # Edit review content
│   │   ├── DeleteReview.php                    # Delete review
│   │   ├── ApproveReview.php                   # PENDING → PUBLISHED
│   │   ├── RejectReview.php                    # PENDING → REJECTED (with reason)
│   │   ├── HideReview.php                      # PUBLISHED → HIDDEN
│   │   ├── RepublishReview.php                 # HIDDEN → PUBLISHED
│   │   └── ReviewFilter.php                    # Filter command (extends FilterCommand)
│   ├── Handler/
│   │   ├── CreateReviewHandler.php
│   │   ├── UpdateReviewHandler.php
│   │   ├── DeleteReviewHandler.php
│   │   ├── ApproveReviewHandler.php
│   │   ├── RejectReviewHandler.php
│   │   ├── HideReviewHandler.php
│   │   ├── RepublishReviewHandler.php
│   │   └── ReviewFilterHandler.php
│   ├── Listener/
│   │   └── ReviewSummaryUpdater.php            # Recalculates summary on status change
│   ├── Search/
│   │   └── ReviewSearchCriteria.php            # Filters: status, productId, customerId, rating
│   └── Service/
│       └── ReviewGenerator.php                 # Generates reviews for eligible purchases
├── Domain/
│   ├── Model/
│   │   ├── Review/
│   │   │   ├── ProductReview.php               # Aggregate root
│   │   │   ├── ReviewPublicId.php              # Typed ULID value object
│   │   │   ├── ReviewStatus.php                # Enum: PENDING, PUBLISHED, REJECTED, HIDDEN
│   │   │   ├── RejectionReason.php             # Enum: SPAM, INAPPROPRIATE, OFF_TOPIC, etc.
│   │   │   └── Event/
│   │   │       ├── ReviewWasCreatedEvent.php
│   │   │       └── ReviewStatusWasChangedEvent.php
│   │   └── ReviewSummary/
│   │       ├── ProductReviewSummary.php         # Projection entity
│   │       └── ReviewSummaryPublicId.php        # Typed ULID
│   └── Repository/
│       ├── ReviewRepository.php                 # Interface
│       └── ReviewSummaryRepository.php          # Interface
├── Infrastructure/
│   └── Persistence/
│       └── Doctrine/
│           ├── ReviewDoctrineRepository.php
│           └── ReviewSummaryDoctrineRepository.php
└── UI/
    ├── Http/
    │   ├── Controller/
    │   │   └── ReviewController.php             # Full CRUD + moderation actions
    │   ├── Form/
    │   │   ├── Model/
    │   │   │   ├── ReviewForm.php               # Create/edit form DTO
    │   │   │   └── RejectReviewForm.php         # Reject form DTO (reason + notes)
    │   │   ├── Type/
    │   │   │   ├── ReviewType.php               # Create/edit form type
    │   │   │   ├── RejectReviewType.php         # Reject form type
    │   │   │   └── ReviewFilterType.php         # Filter form type
    │   │   └── Mapper/
    │   │       ├── CreateReviewMapper.php
    │   │       ├── UpdateReviewMapper.php
    │   │       ├── RejectReviewMapper.php
    │   │       └── ReviewFilterMapper.php
    │   └── Validation/
    │       ├── ValidReviewEligibility.php        # Constraint attribute
    │       └── ValidReviewEligibilityValidator.php  # Checks order/customer/product eligibility
    └── Console/
        └── GenerateReviewsCommand.php           # app:generate-reviews
```

Templates:
```
templates/review/
├── index.html.twig                              # Search/list page
├── show.html.twig                               # Review detail page
├── _review_card.html.twig                       # Review list item card
├── _review_detail_card.html.twig                # Full review detail card
├── _review_summary_card.html.twig               # Summary card (avg, histogram, counts)
├── _review_preview_card.html.twig               # Compact review card for product page

templates/catalog/product/
├── reviews.html.twig                            # Product Reviews tab page (new)
├── _navigation.html.twig                        # Modified: add Reviews tab

templates/shared/form_flow/
├── search_filter.html.twig                      # Existing (reused)
```

Tests:
```
tests/Review/
├── Domain/
│   ├── ReviewStatusTransitionTest.php
│   └── ProductReviewDomainTest.php
├── Application/
│   └── Handler/
│       ├── CreateReviewHandlerTest.php
│       ├── ApproveReviewHandlerTest.php
│       ├── RejectReviewHandlerTest.php
│       └── HideReviewHandlerTest.php
└── UI/
    ├── CreateReviewFlowTest.php
    ├── UpdateReviewFlowTest.php
    ├── ApproveReviewFlowTest.php
    ├── RejectReviewFlowTest.php
    ├── HideReviewFlowTest.php
    ├── RepublishReviewFlowTest.php
    ├── DeleteReviewFlowTest.php
    └── ReviewFilterFlowTest.php

tests/Shared/Factory/
├── ProductReviewFactory.php
└── ProductReviewSummaryFactory.php
```

---

## 3. Domain Model

### 3.1 ProductReview Entity

**File**: `src/Review/Domain/Model/Review/ProductReview.php`

```php
namespace App\Review\Domain\Model\Review;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewWasCreatedEvent;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
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

    #[ORM\ManyToOne(targetEntity: CustomerOrderItem::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CustomerOrderItem $customerOrderItem;

    #[ORM\Column(type: 'smallint')]
    private int $rating;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', enumType: ReviewStatus::class)]
    private ReviewStatus $status;

    #[ORM\Column(type: 'string', nullable: true, enumType: RejectionReason::class)]
    private ?RejectionReason $rejectionReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $moderationNotes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $moderatedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    public function __construct()
    {
        $this->initializePublicId();
        $this->status = ReviewStatus::PENDING;
    }

    public static function create(
        User $customer,
        Product $product,
        CustomerOrder $customerOrder,
        CustomerOrderItem $customerOrderItem,
        int $rating,
        ?string $title,
        ?string $body,
        ?string $displayName,
    ): self {
        $review = new self();
        $review->customer = $customer;
        $review->product = $product;
        $review->customerOrder = $customerOrder;
        $review->customerOrderItem = $customerOrderItem;
        $review->setRating($rating);
        $review->title = $title;
        $review->body = $body;
        $review->displayName = $displayName ?? $customer->getFullName();

        $review->raiseDomainEvent(new ReviewWasCreatedEvent($review->getPublicId()));

        return $review;
    }

    public function update(int $rating, ?string $title, ?string $body): void
    {
        $this->setRating($rating);
        $this->title = $title;
        $this->body = $body;
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

    // -- Getters --

    public function getId(): ?int { return $this->id; }
    public function getPublicId(): ReviewPublicId { return ReviewPublicId::fromString($this->publicIdString()); }
    public function getCustomer(): User { return $this->customer; }
    public function getProduct(): Product { return $this->product; }
    public function getCustomerOrder(): CustomerOrder { return $this->customerOrder; }
    public function getCustomerOrderItem(): CustomerOrderItem { return $this->customerOrderItem; }
    public function getRating(): int { return $this->rating; }
    public function getTitle(): ?string { return $this->title; }
    public function getBody(): ?string { return $this->body; }
    public function getDisplayName(): ?string { return $this->displayName; }
    public function getStatus(): ReviewStatus { return $this->status; }
    public function getRejectionReason(): ?RejectionReason { return $this->rejectionReason; }
    public function getModerationNotes(): ?string { return $this->moderationNotes; }
    public function getModeratedBy(): ?User { return $this->moderatedBy; }
    public function getModeratedAt(): ?\DateTimeImmutable { return $this->moderatedAt; }
    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }

    public function isPublished(): bool { return $this->status === ReviewStatus::PUBLISHED; }
    public function isPending(): bool { return $this->status === ReviewStatus::PENDING; }

    // -- Private --

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
            throw new \LogicException(
                sprintf('Cannot transition review from %s to %s.', $this->status->value, $newStatus->value)
            );
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
```

### 3.2 ReviewStatus Enum

**File**: `src/Review/Domain/Model/Review/ReviewStatus.php`

```php
namespace App\Review\Domain\Model\Review;

enum ReviewStatus: string
{
    case PENDING = 'PENDING';
    case PUBLISHED = 'PUBLISHED';
    case REJECTED = 'REJECTED';
    case HIDDEN = 'HIDDEN';

    public static function getDefault(): self
    {
        return self::PENDING;
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::PENDING => match ($to) {
                self::PUBLISHED, self::REJECTED => true,
                default => false,
            },
            self::PUBLISHED => match ($to) {
                self::HIDDEN => true,
                default => false,
            },
            self::HIDDEN => match ($to) {
                self::PUBLISHED => true,
                default => false,
            },
            self::REJECTED => false,
        };
    }

    public function allowEdit(): bool
    {
        return self::PENDING === $this || self::PUBLISHED === $this;
    }

    public function getLevel(): int
    {
        return match ($this) {
            self::PENDING => 1,
            self::PUBLISHED => 2,
            self::HIDDEN => 3,
            self::REJECTED => 4,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'text-yellow-500',
            self::PUBLISHED => 'text-green-500',
            self::REJECTED => 'text-red-500',
            self::HIDDEN => 'text-gray-500',
        };
    }
}
```

### 3.3 RejectionReason Enum

**File**: `src/Review/Domain/Model/Review/RejectionReason.php`

```php
namespace App\Review\Domain\Model\Review;

enum RejectionReason: string
{
    case SPAM = 'SPAM';
    case INAPPROPRIATE = 'INAPPROPRIATE';
    case OFF_TOPIC = 'OFF_TOPIC';
    case DUPLICATE = 'DUPLICATE';
    case MISLEADING = 'MISLEADING';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SPAM => 'Spam',
            self::INAPPROPRIATE => 'Inappropriate content',
            self::OFF_TOPIC => 'Off topic',
            self::DUPLICATE => 'Duplicate review',
            self::MISLEADING => 'Misleading content',
            self::OTHER => 'Other',
        };
    }
}
```

### 3.4 ReviewPublicId

**File**: `src/Review/Domain/Model/Review/ReviewPublicId.php`

```php
namespace App\Review\Domain\Model\Review;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class ReviewPublicId extends AbstractUlidId
{
}
```

### 3.5 ProductReviewSummary Entity

**File**: `src/Review/Domain/Model/ReviewSummary/ProductReviewSummary.php`

```php
namespace App\Review\Domain\Model\ReviewSummary;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'product_review_summary')]
#[ORM\UniqueConstraint(name: 'unique_product_summary', columns: ['product_id'])]
class ProductReviewSummary
{
    use HasPublicUlid;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $reviewCount = 0;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2)]
    private string $averageRating = '0.00';

    /** @var array<int, int> Rating distribution: {1: count, 2: count, ...} */
    #[ORM\Column(type: 'json')]
    private array $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

    #[ORM\Column(type: 'integer')]
    private int $pendingCount = 0;

    public function __construct()
    {
        $this->initializePublicId();
    }

    public static function create(Product $product): self
    {
        $summary = new self();
        $summary->product = $product;

        return $summary;
    }

    public function recalculate(
        int $reviewCount,
        string $averageRating,
        array $ratingDistribution,
        int $pendingCount,
    ): void {
        $this->reviewCount = $reviewCount;
        $this->averageRating = $averageRating;
        $this->ratingDistribution = $ratingDistribution;
        $this->pendingCount = $pendingCount;
    }

    // -- Getters --

    public function getId(): ?int { return $this->id; }
    public function getPublicId(): ReviewSummaryPublicId { return ReviewSummaryPublicId::fromString($this->publicIdString()); }
    public function getProduct(): Product { return $this->product; }
    public function getReviewCount(): int { return $this->reviewCount; }
    public function getAverageRating(): string { return $this->averageRating; }
    public function getRatingDistribution(): array { return $this->ratingDistribution; }
    public function getPendingCount(): int { return $this->pendingCount; }
    public function hasReviews(): bool { return $this->reviewCount > 0; }
}
```

### 3.6 ReviewSummaryPublicId

**File**: `src/Review/Domain/Model/ReviewSummary/ReviewSummaryPublicId.php`

```php
namespace App\Review\Domain\Model\ReviewSummary;

use App\Shared\Domain\ValueObject\AbstractUlidId;

final readonly class ReviewSummaryPublicId extends AbstractUlidId
{
}
```

---

## 4. Domain Events & Summary Projection

### 4.1 Events

**ReviewWasCreatedEvent** (`src/Review/Domain/Model/Review/Event/ReviewWasCreatedEvent.php`):

```php
namespace App\Review\Domain\Model\Review\Event;

use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class ReviewWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ReviewPublicId $id,
    ) {
        parent::__construct(DomainEventType::REVIEW_CREATED);
    }

    public function getId(): ReviewPublicId { return $this->id; }
}
```

**ReviewStatusWasChangedEvent** (`src/Review/Domain/Model/Review/Event/ReviewStatusWasChangedEvent.php`):

```php
namespace App\Review\Domain\Model\Review\Event;

use App\Review\Domain\Model\Review\ReviewPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Domain\ValueObject\AbstractUlidId;
use App\Shared\Domain\ValueObject\StatusChange;

final class ReviewStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly ReviewPublicId $id,
        private readonly StatusChange $statusChange,
    ) {
        parent::__construct(DomainEventType::REVIEW_STATUS_CHANGED);
    }

    public function getId(): AbstractUlidId { return $this->id; }
    public function getStatusChange(): StatusChange { return $this->statusChange; }
}
```

Both events require adding new cases to `DomainEventType`:

```php
// Add to src/Shared/Domain/Event/DomainEventType.php
case REVIEW_CREATED = 'review.created';
case REVIEW_STATUS_CHANGED = 'review.status.changed';
```

### 4.2 Summary Projection Listener

**File**: `src/Review/Application/Listener/ReviewSummaryUpdater.php`

Listens for both `ReviewWasCreatedEvent` and `ReviewStatusWasChangedEvent`. On either event:

1. Load the review to get its product
2. Query all published reviews for that product (COUNT, AVG, distribution)
3. Query pending count for that product
4. Upsert the `ProductReviewSummary` (get-or-create by product, then recalculate)
5. Flush

```php
namespace App\Review\Application\Listener;

use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Review\Domain\Model\Review\Event\ReviewWasCreatedEvent;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Review\Domain\Repository\ReviewRepository;
use App\Review\Domain\Repository\ReviewSummaryRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// Listener for ReviewWasCreatedEvent
// Listener for ReviewStatusWasChangedEvent
// Both call recalculateSummary(ReviewPublicId)
```

The listener recalculates the entire summary from scratch (not increment/decrement) to avoid race conditions. This is acceptable since review volumes are modest.

### 4.3 Audit Integration

`ReviewStatusWasChangedEvent` implements `StatusWasChangedEventInterface`, which means the existing `StatusChangeLogger` in the Audit context will automatically pick it up and log moderation actions to `StatusChangeLog`. No additional work needed.

---

## 5. Moderation State Machine

```
     PENDING
       │
       ├─────────► PUBLISHED ─────────► HIDDEN
       │                ◄──────────────── │
       │                (republish)
       │
       └─────────► REJECTED (terminal)
```

**Transitions enforced by `ReviewStatus::canTransitionTo()`:**

| From | To | Action | Form Required |
|------|----|--------|---------------|
| PENDING | PUBLISHED | Approve | No (CommandFlow) |
| PENDING | REJECTED | Reject | Yes (reason + notes) |
| PUBLISHED | HIDDEN | Hide | No (CommandFlow) |
| HIDDEN | PUBLISHED | Republish | No (CommandFlow) |

**Terminal states**: REJECTED (no outbound transitions)

**Edit rules**: PENDING and PUBLISHED reviews can be edited (title, body, rating). REJECTED and HIDDEN cannot be content-edited.

---

## 6. Cross-context Integration

### Entity References (Doctrine ManyToOne)

| Review Field | Target Entity | Context |
|---|---|---|
| `customer` | `User` | Customer |
| `product` | `Product` | Catalog |
| `customerOrder` | `CustomerOrder` | Order |
| `customerOrderItem` | `CustomerOrderItem` | Order |
| `moderatedBy` | `User` | Customer |

### Eligibility Check (Validation)

A review is eligible if:

1. The specified `orderId` (CustomerOrder) exists
2. The order belongs to the specified customer
3. The order contains a delivered item for the specified product
4. No existing review exists for this customer + product combination

This is enforced by `ValidReviewEligibilityValidator` (custom Symfony constraint) applied to the `ReviewForm` DTO at the class level. The validator has access to `OrderRepository`, `OrderItemRepository` (or direct entity manager queries), and `ReviewRepository`.

### Product Show Page Integration

The product detail page (`catalog/product/reviews.html.twig`) fetches:
- `ProductReviewSummary` for the product (from `ReviewSummaryRepository`)
- Last 5 published reviews (from `ReviewRepository::findLatestPublishedForProduct()`)

This is handled by a new `ProductController::reviews()` action that renders the reviews tab.

---

## 7. Application Layer

### 7.1 Commands

All commands are `final readonly` DTOs:

| Command | Properties |
|---|---|
| `CreateReview` | `customerId: int, productId: int, orderId: int, rating: int, ?title, ?body, ?displayName` |
| `UpdateReview` | `id: ReviewPublicId, rating: int, ?title, ?body` |
| `DeleteReview` | `id: ReviewPublicId` |
| `ApproveReview` | `id: ReviewPublicId` |
| `RejectReview` | `id: ReviewPublicId, reason: RejectionReason, ?notes: string` |
| `HideReview` | `id: ReviewPublicId` |
| `RepublishReview` | `id: ReviewPublicId` |
| `ReviewFilter` | extends `FilterCommand` + `?status, ?productId, ?customerId, ?rating` |

### 7.2 Handlers

Each handler follows the existing pattern:

1. Inject repository(ies), `FlusherInterface`, `ValidatorInterface`
2. Validate command
3. Load entity, perform domain operation
4. Validate entity
5. Flush and return `Result`

**CreateReviewHandler**:
- Loads Customer (by ID), Product (by ID), CustomerOrder (by ID)
- Finds the delivered order item for the product within the order
- Creates `ProductReview::create(...)`
- Validates, persists, flushes
- Returns `Result::ok('Review created', $review->getPublicId())`

**ApproveReviewHandler**:
- Loads review by public ID
- Gets current authenticated user as moderator (injected via `Security`)
- Calls `$review->approve($moderator)`
- Flushes (triggers event → summary update via listener)
- Returns `Result::ok('Review approved')`

**RejectReviewHandler**:
- Loads review by public ID
- Gets moderator
- Calls `$review->reject($moderator, $reason, $notes)`
- Flushes
- Returns `Result::ok('Review rejected')`

**HideReviewHandler** / **RepublishReviewHandler**: Same pattern with `$review->hide()` / `$review->republish()`.

**ReviewFilterHandler**: Same pattern as `ProductFilterHandler` — builds query params and returns `Result::ok(redirect: new RedirectTarget(...))`.

### 7.3 SearchCriteria

**ReviewSearchCriteria** extends `SearchCriteria`:

```php
namespace App\Review\Application\Search;

use App\Review\Domain\Model\Review\ReviewStatus;
use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class ReviewSearchCriteria extends SearchCriteria
{
    protected const int LIMIT_DEFAULT = 10;
    protected const string SORT_DEFAULT = 'id';
    protected const string SORT_DIRECTION_DEFAULT = 'DESC';
    protected const array SORT_OPTIONS = ['id', 'rating', 'status', 'createdAt'];

    public ?string $status = null;

    #[Assert\Range(min: 1, max: 1000000)]
    public ?int $productId = null;

    #[Assert\Range(min: 1, max: 1000000)]
    public ?int $customerId = null;

    #[Assert\Range(min: 1, max: 5)]
    public ?int $rating = null;
}
```

---

## 8. UI Layer

### 8.1 ReviewController

**File**: `src/Review/UI/Http/Controller/ReviewController.php`

```php
#[IsGranted('ROLE_ADMIN')]
class ReviewController extends AbstractController
{
    public const string MODEL = 'review/review';

    // GET /review/                         → index (SearchFlow)
    // GET|POST /review/search/filter       → searchFilter (FormFlow)
    // GET|POST /review/new                 → new (FormFlow)
    // GET|POST /review/{id}/edit           → edit (FormFlow)
    // GET /review/{id}/delete/confirm      → deleteConfirm (DeleteFlow)
    // POST /review/{id}/delete             → delete (DeleteFlow)
    // GET /review/{id}                     → show
    // GET /review/{id}/approve             → approve (CommandFlow)
    // GET|POST /review/{id}/reject         → reject (FormFlow - needs reason)
    // GET /review/{id}/hide                → hide (CommandFlow)
    // GET /review/{id}/republish           → republish (CommandFlow)
}
```

Route names follow existing convention:
- `app_review_review_index`
- `app_review_review_new`
- `app_review_review_edit`
- `app_review_review_show`
- `app_review_review_delete_confirm`
- `app_review_review_delete`
- `app_review_review_search_filter`
- `app_review_review_approve`
- `app_review_review_reject`
- `app_review_review_hide`
- `app_review_review_republish`

**Approve** uses `CommandFlow::process()` (no form needed):
```php
#[Route(path: '/review/{id}/approve', name: 'app_review_review_approve', methods: ['GET'])]
public function approve(
    Request $request,
    #[ValueResolver('public_id')] ProductReview $review,
    ApproveReviewHandler $handler,
    CommandFlow $flow,
): Response {
    return $flow->process(
        request: $request,
        command: new ApproveReview($review->getPublicId()),
        handler: $handler,
        context: FlowContext::forSuccess('app_review_review_show', ['id' => $review->getPublicId()->value()]),
    );
}
```

**Reject** uses `FormFlow::form()` (needs rejection reason):
```php
#[Route(path: '/review/{id}/reject', name: 'app_review_review_reject', methods: ['GET', 'POST'])]
public function reject(
    Request $request,
    #[ValueResolver('public_id')] ProductReview $review,
    RejectReviewMapper $mapper,
    RejectReviewHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: RejectReviewType::class,
        data: RejectReviewForm::fromEntity($review),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forUpdate('review/reject review')
            ->successRoute('app_review_review_show', ['id' => $review->getPublicId()->value()]),
    );
}
```

### 8.2 Product Reviews Tab (added to ProductController)

**New action** on `ProductController`:

```php
#[Route(path: '/product/{id}/reviews', name: 'app_catalog_product_reviews', methods: ['GET'])]
public function reviews(
    #[ValueResolver('public_id')] Product $product,
    ReviewRepository $reviewRepository,
    ReviewSummaryRepository $summaryRepository,
): Response {
    return $this->render('catalog/product/reviews.html.twig', [
        'result' => $product,
        'summary' => $summaryRepository->findByProduct($product),
        'reviews' => $reviewRepository->findLatestPublishedForProduct($product, 5),
    ]);
}
```

### 8.3 Form DTOs

**ReviewForm** (`src/Review/UI/Http/Form/Model/ReviewForm.php`):

```php
namespace App\Review\UI\Http\Form\Model;

use App\Review\UI\Http\Validation\ValidReviewEligibility;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidReviewEligibility]
final class ReviewForm
{
    public ?string $id = null;

    #[Assert\NotBlank]
    public ?int $customerId = null;

    #[Assert\NotBlank]
    public ?int $productId = null;

    #[Assert\NotBlank]
    public ?int $orderId = null;

    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    public ?int $rating = null;

    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 2000)]
    public ?string $body = null;

    #[Assert\Length(max: 255)]
    public ?string $displayName = null;

    public static function fromEntity(ProductReview $review): self
    {
        $form = new self();
        $form->id = $review->getPublicId()->value();
        $form->customerId = $review->getCustomer()->getId();
        $form->productId = $review->getProduct()->getId();
        $form->orderId = $review->getCustomerOrder()->getId();
        $form->rating = $review->getRating();
        $form->title = $review->getTitle();
        $form->body = $review->getBody();
        $form->displayName = $review->getDisplayName();

        return $form;
    }
}
```

**RejectReviewForm** (`src/Review/UI/Http/Form/Model/RejectReviewForm.php`):

```php
namespace App\Review\UI\Http\Form\Model;

use App\Review\Domain\Model\Review\RejectionReason;
use Symfony\Component\Validator\Constraints as Assert;

final class RejectReviewForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please select a rejection reason')]
    public ?RejectionReason $reason = null;

    public ?string $notes = null;

    public static function fromEntity(ProductReview $review): self
    {
        $form = new self();
        $form->id = $review->getPublicId()->value();
        $form->reason = $review->getRejectionReason();
        $form->notes = $review->getModerationNotes();

        return $form;
    }
}
```

### 8.4 Form Types

**ReviewType**: Fields for customerId (integer input), productId (integer input), orderId (integer input), rating (choice 1-5), title (text), body (textarea), displayName (text, optional).

**ReviewFilterType**: Fields for status (EnumType choice), productId, customerId, rating (choice 1-5).

**RejectReviewType**: Fields for reason (EnumType choice of RejectionReason), notes (textarea optional).

### 8.5 Mappers

**CreateReviewMapper**: `ReviewForm → CreateReview` command
**UpdateReviewMapper**: `ReviewForm → UpdateReview` command
**RejectReviewMapper**: `RejectReviewForm → RejectReview` command
**ReviewFilterMapper**: `ReviewSearchCriteria → ReviewFilter` command

### 8.6 Eligibility Validator

**ValidReviewEligibility** (constraint attribute):

```php
#[Attribute(Attribute::TARGET_CLASS)]
final class ValidReviewEligibility extends Constraint
{
    public string $orderNotFoundMessage = 'Order not found.';
    public string $orderNotOwnedMessage = 'This order does not belong to the selected customer.';
    public string $noDeliveredItemMessage = 'No delivered item found for this product in this order.';
    public string $duplicateReviewMessage = 'A review already exists for this customer and product.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
```

**ValidReviewEligibilityValidator**: Injects `OrderRepository`, `ReviewRepository`. On validate:

1. If `$form->id` is set (edit mode), skip eligibility check (already validated on creation)
2. Load order by `$form->orderId` — if not found, add violation
3. Check order's customer ID matches `$form->customerId`
4. Find a delivered order item in that order for `$form->productId`
5. Check no existing review for this customer + product (via `ReviewRepository::findByCustomerAndProduct()`)

---

## 9. Templates & UI Components

### 9.1 Review Index Page (`templates/review/index.html.twig`)

Standard `<twig:Search>` component with:

**Sort columns**: ID, Rating, Status, Created

**List item card** (`_review_card.html.twig`): Shows:
- Product name (link to product)
- Customer name
- Rating (star display or numeric)
- Title (line-clamp-1)
- Status badge (colored text using `ReviewStatus::getColor()`)
- Created date (ago filter)
- Quick action icons:
  - If PENDING: Approve (green check), Reject (red X) links
  - If PUBLISHED: Hide (eye-off) link
  - If HIDDEN: Republish (eye) link
- Edit link opens modal
- Show link navigates to detail

**Filter form** (`search_filter.html.twig`): Status dropdown, productId, customerId, rating dropdown.

### 9.2 Review Show Page (`templates/review/show.html.twig`)

Displays a full detail card:
- Product info with link
- Customer info with link
- Order reference with link
- Rating display
- Title and body text
- Status badge
- Moderation info (moderatedBy, moderatedAt, reason, notes) if moderated
- Published date
- Created/Updated timestamps
- Action buttons: Edit, Approve/Reject/Hide/Republish (based on current status)
- Delete button (with confirm)

### 9.3 Review Summary Card (`templates/review/_review_summary_card.html.twig`)

Used on the product reviews tab:
- Average rating (large number display)
- Total published review count
- Pending moderation count (with warning color)
- Rating histogram (simple bar display showing distribution 1-5)
- Link to "View all reviews" (filtered to this product)
- Link to "Create review" (with productId pre-filled)

### 9.4 Product Reviews Tab (`templates/catalog/product/reviews.html.twig`)

```twig
{% extends 'base.html.twig' %}
{% block title %}Product Reviews{% endblock %}
{% block body %}
    {{ include('catalog/product/_navigation.html.twig', {product: result}) }}
    <div class="m-3 space-y-3">
        {{ include('review/_review_summary_card.html.twig', {summary: summary}) }}
        {% for review in reviews %}
            {{ include('review/_review_preview_card.html.twig', {review: review}) }}
        {% else %}
            <p class="text-sm text-gray-500">No published reviews yet.</p>
        {% endfor %}
        <a href="{{ path('app_review_review_index', {productId: result.id}) }}"
           class="text-blue-600 hover:text-blue-800 text-sm"
           data-turbo-frame="body">
            View all reviews for this product
        </a>
    </div>
{% endblock %}
```

### 9.5 Review Preview Card (`templates/review/_review_preview_card.html.twig`)

Compact card for product page listing:
- Display name
- Rating (stars or number)
- Title
- Body (line-clamp-3)
- Published date
- "Verified Purchase" badge

---

## 10. Menu & Navigation Changes

### 10.1 Sidebar Menu (`templates/_menu.html.twig`)

Add a new **"Reviews"** dropdown menu between "Customers" and "Suppliers":

```html
<li>
    <button type="button" class="flex w-full items-center rounded-sm p-2 text-base font-normal text-gray-900 transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" aria-controls="dropdown-reviews" data-collapse-toggle="dropdown-reviews">
        <twig:ux:icon name="flowbite:star-solid" class="h-5 w-5 text-gray-400"/>
        <span class="ml-3 flex-1 whitespace-nowrap text-left">Reviews</span>
        <twig:ux:icon name="mingcute:down-line" class="h-5 w-5"/>
    </button>
    <ul id="dropdown-reviews" class="hidden py-2 space-y-2">
        <li>
            <a href="{{ path('app_review_review_index') }}"
               class="flex w-full items-center rounded-sm p-2 pl-11 ..."
               data-turbo-frame="body"
               data-action="basic-drawer#close"
            >Search</a>
        </li>
        <li>
            <a href="{{ path('app_review_review_index', {status: 'PENDING'}) }}"
               class="flex w-full items-center rounded-sm p-2 pl-11 ..."
               data-turbo-frame="body"
               data-action="basic-drawer#close"
            >Moderation Queue</a>
        </li>
    </ul>
</li>
```

### 10.2 Product Navigation Tabs (`templates/catalog/product/_navigation.html.twig`)

Add a **"Reviews"** tab after the existing "Product Sales" tab:

```html
<li class="me-2">
    <a href="{{ path('app_catalog_product_reviews', { id: result.publicId.value }) }}"
       class="inline-block p-3 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 {{ app.request.attributes.get('_route') == 'app_catalog_product_reviews' ? 'active dark:text-gray-300 dark:border-gray-300' : '' }}">
        <span class="hidden md:inline">Product </span>Reviews
    </a>
</li>
```

---

## 11. Console Command

### 11.1 GenerateReviewsCommand

**File**: `src/Review/UI/Console/GenerateReviewsCommand.php`

```php
#[AsCommand(
    name: 'app:generate-reviews',
    description: 'Generate product reviews for eligible delivered purchases',
)]
readonly class GenerateReviewsCommand
{
    // Constructor injects:
    // - ReviewGenerator service
    // - DefaultUserAuthenticator
    // - FlusherInterface

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Max reviews to generate')]
        int $count = 20,
        #[Argument(description: 'Optional product ID to target')]
        ?int $productId = null,
    ): int {
        // 1. Authenticate default user
        // 2. Call ReviewGenerator::generate($count, $productId)
        // 3. Flush
        // 4. Report results
    }
}
```

### 11.2 ReviewGenerator Service

**File**: `src/Review/Application/Service/ReviewGenerator.php`

Responsibilities:
1. Find eligible order items: delivered, customer has no existing review for that product
2. For each eligible item (up to `$count`):
   - Generate a random rating (weighted: 4-5 more common than 1-2)
   - Generate a faker title and body
   - Create `ProductReview::create(...)` in PENDING status
3. Return the number of reviews created

```php
namespace App\Review\Application\Service;

class ReviewGenerator
{
    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly OrderItemRepository $orderItems,
        private readonly FlusherInterface $flusher,
    ) {}

    /**
     * @return int Number of reviews generated
     */
    public function generate(int $maxCount, ?int $productId = null): int
    {
        $eligibleItems = $this->findEligibleOrderItems($maxCount, $productId);

        $created = 0;
        foreach ($eligibleItems as $orderItem) {
            $review = ProductReview::create(
                customer: $orderItem->getCustomerOrder()->getCustomer(),
                product: $orderItem->getProduct(),
                customerOrder: $orderItem->getCustomerOrder(),
                customerOrderItem: $orderItem,
                rating: $this->generateRating(),
                title: $this->generateTitle(),
                body: $this->generateBody(),
                displayName: null, // Uses customer's fullName
            );

            $this->reviews->add($review);
            ++$created;
        }

        return $created;
    }

    private function findEligibleOrderItems(int $limit, ?int $productId): array
    {
        // Query: delivered order items where no review exists
        // for that customer+product combination
        // Optionally filtered by productId
        // Limited to $limit results
    }
}
```

The `findEligibleOrderItems` query will be implemented as a custom repository method on `ReviewDoctrineRepository` (or a dedicated query, possibly a join between `customer_order_item` and `product_review` to find unreviewed items).

### 11.3 Cron Integration

Would be added to the prod crontab:
```
# Every 2 hours - generate reviews for delivered orders
0 */2 * * * app:generate-reviews 10
```

---

## 12. Configuration Changes

### 12.1 Doctrine Mapping (`config/packages/doctrine.yaml`)

Add the Review bounded context mapping:

```yaml
Review:
    type: attribute
    is_bundle: false
    dir: '%kernel.project_dir%/src/Review/Domain/Model'
    prefix: 'App\Review\Domain\Model'
    alias: Review
```

### 12.2 DomainEventType Enum

Add to `src/Shared/Domain/Event/DomainEventType.php`:

```php
case REVIEW_CREATED = 'review.created';
case REVIEW_STATUS_CHANGED = 'review.status.changed';
```

### 12.3 StatusIcon Component

Add review status types to `StatusIcon.php` if desired (pending, published, rejected, hidden).

---

## 13. Tests

### 13.1 Test Factories

**ProductReviewFactory** (`tests/Shared/Factory/ProductReviewFactory.php`):

```php
final class ProductReviewFactory extends PersistentObjectFactory
{
    public static function class(): string { return ProductReview::class; }

    protected function defaults(): array
    {
        return [
            'customer' => LazyValue::memoize(fn () => UserFactory::createOne()),
            'product' => LazyValue::memoize(fn () => ProductFactory::createOne()),
            'customerOrder' => null,    // Set in beforeInstantiate
            'customerOrderItem' => null, // Set in beforeInstantiate
            'rating' => self::faker()->numberBetween(1, 5),
            'title' => self::faker()->sentence(4),
            'body' => self::faker()->paragraph(2),
            'displayName' => self::faker()->name(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                // Auto-create a delivered order + order item if not provided
                if ($attributes['customerOrder'] === null) {
                    $order = CustomerOrderFactory::createOne([
                        'customer' => $attributes['customer'],
                    ]);
                    $orderItem = CustomerOrderItemFactory::createOne([
                        'customerOrder' => $order,
                        'product' => $attributes['product'],
                    ]);
                    $attributes['customerOrder'] = $order;
                    $attributes['customerOrderItem'] = $orderItem;
                }
                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            );
    }

    public function published(): self
    {
        return $this->afterInstantiate(function (ProductReview $review): void {
            // Transition to published (requires a moderator user)
            $moderator = UserFactory::new()->asStaff()->create();
            $review->approve($moderator);
        });
    }
}
```

**ProductReviewSummaryFactory** (`tests/Shared/Factory/ProductReviewSummaryFactory.php`):

```php
final class ProductReviewSummaryFactory extends PersistentObjectFactory
{
    public static function class(): string { return ProductReviewSummary::class; }

    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn () => ProductFactory::createOne()),
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create'));
    }
}
```

### 13.2 Domain Tests

**ReviewStatusTransitionTest** (`tests/Review/Domain/ReviewStatusTransitionTest.php`):
- Test all valid transitions (PENDING→PUBLISHED, PENDING→REJECTED, PUBLISHED→HIDDEN, HIDDEN→PUBLISHED)
- Test all invalid transitions (REJECTED→anything, PUBLISHED→REJECTED, PENDING→HIDDEN, etc.)
- Test `allowEdit()` for each status
- Test `getLevel()` ordering

**ProductReviewDomainTest** (`tests/Review/Domain/ProductReviewDomainTest.php`):
- Test `create()` sets correct defaults (PENDING status, displayName from customer)
- Test `update()` modifies rating/title/body
- Test `approve()` changes status to PUBLISHED, sets publishedAt, moderatedBy, moderatedAt
- Test `reject()` changes status to REJECTED, sets rejectionReason, moderationNotes
- Test `hide()` changes status to HIDDEN
- Test `republish()` changes status back to PUBLISHED
- Test invalid rating (0, 6, -1) throws exception
- Test double-approve throws LogicException (PUBLISHED→PUBLISHED is not a valid transition)
- Test reject-then-approve throws LogicException (REJECTED is terminal)

### 13.3 Handler Tests (KernelTestCase)

**CreateReviewHandlerTest**:
- Test successful creation with valid customer/product/order
- Test failure when order not found
- Test failure when order doesn't belong to customer
- Test failure when no delivered item for product in order
- Test failure when duplicate review exists

**ApproveReviewHandlerTest**:
- Test successful approval of pending review
- Test failure when review not found
- Test failure when review is not in PENDING status

**RejectReviewHandlerTest**:
- Test successful rejection with reason
- Test failure when review is not in PENDING status

**HideReviewHandlerTest**:
- Test successful hiding of published review
- Test failure when review is not PUBLISHED

### 13.4 Flow Tests (WebTestCase)

All flow tests use `HasBrowser` + `Factories` traits and authenticate via `UserFactory::new()->asStaff()->create()`.

**CreateReviewFlowTest**:
```php
public function testSuccessfulCreationViaForm(): void
{
    // Create customer, product, delivered order+item
    // Navigate to /review/new
    // Fill form fields (customerId, productId, orderId, rating, title, body)
    // Submit
    // Assert redirect to /review/
    // Assert review visible in list
}

public function testValidationErrorsOnEmptySubmission(): void
{
    // Navigate to /review/new
    // Submit empty form
    // Assert still on /review/new
    // Assert validation errors visible
}

public function testEligibilityValidationRejectsDuplicate(): void
{
    // Create existing review for customer+product
    // Try to create another
    // Assert error message about duplicate
}
```

**UpdateReviewFlowTest**:
```php
public function testSuccessfulEditViaForm(): void
{
    // Create review
    // Navigate to /review/{id}/edit
    // Change rating/title/body
    // Submit
    // Assert changes persisted
}
```

**ApproveReviewFlowTest**:
```php
public function testApproveChangesStatusToPublished(): void
{
    // Create pending review
    // GET /review/{id}/approve
    // Assert redirected to show page
    // Assert status is PUBLISHED
}
```

**RejectReviewFlowTest**:
```php
public function testRejectWithReasonViaForm(): void
{
    // Create pending review
    // Navigate to /review/{id}/reject
    // Select rejection reason, add notes
    // Submit
    // Assert status is REJECTED
    // Assert reason and notes saved
}
```

**HideReviewFlowTest**:
```php
public function testHidePublishedReview(): void
{
    // Create published review
    // GET /review/{id}/hide
    // Assert status is HIDDEN
}
```

**RepublishReviewFlowTest**:
```php
public function testRepublishHiddenReview(): void
{
    // Create hidden review (create pending, approve, then hide)
    // GET /review/{id}/republish
    // Assert status is PUBLISHED
}
```

**DeleteReviewFlowTest**:
```php
public function testDeleteReviewWithConfirmation(): void
{
    // Create review
    // Navigate to /review/{id}/delete/confirm
    // Submit delete
    // Assert redirected to index
    // Assert review no longer exists
}
```

**ReviewFilterFlowTest**:
```php
public function testFilterFormRedirectsWithParams(): void
{
    // Navigate to /review/search/filter
    // Fill status, productId, rating
    // Submit
    // Assert URL contains correct query parameters
}
```

### 13.5 Summary Projection Tests

**ReviewSummaryUpdaterTest** (KernelTestCase):
- Create a review, approve it → verify summary has count=1, averageRating=X
- Approve second review → verify summary updated (count=2, average recalculated)
- Hide a published review → verify summary decremented
- Verify rating distribution is correct
- Verify pending count reflects pending reviews

---

## 14. Suggested Improvements & Edge Cases

### Improvements Over Base Spec

1. **Rating distribution histogram**: The summary stores per-rating counts (1-5), enabling a histogram display on the product page. Richer than just average + count.

2. **Pending count on summary**: Shows moderators how many reviews are awaiting action, directly on the product page. Useful for workflow visibility.

3. **Display name defaulting**: Auto-populates from `User::getFullName()` on creation but allows override. Supports future pseudonymization.

4. **Separate reject form**: Rather than a single modal with radio buttons, a proper form with the rejection reason enum + optional notes provides a better audit trail.

5. **Republish action**: Allows hidden reviews to be restored without creating a new review. Clean round-trip: PUBLISHED → HIDDEN → PUBLISHED.

6. **`StatusWasChangedEventInterface` integration**: Moderation actions automatically appear in the Audit context's status change log. Zero additional code needed.

7. **Idempotent summary recalculation**: Summary is always recalculated from scratch (query all published reviews) rather than incrementing/decrementing. Prevents drift from race conditions or bugs.

### Edge Cases Handled

1. **Duplicate prevention**: Unique constraint on `(customer_id, product_id)` at the database level + validator check at the form level. Belt and suspenders.

2. **Orphaned summaries**: Once created, a `ProductReviewSummary` persists even if all reviews are removed. It simply shows count=0. Avoids null checks everywhere.

3. **Order item deletion after review**: If an order item is later cancelled/refunded, the review remains (it's historical evidence). The review entity references the order item but doesn't enforce a live status constraint.

4. **Product deactivation**: Inactive products keep their reviews. The review index can still show them. No new reviews can be created (the eligibility validator checks for delivered items, not product active status, but practically a deactivated product won't have new deliveries).

5. **Multiple orders for same product**: The unique constraint is on customer+product, not customer+order. A customer who bought the same product in multiple orders can only leave one review. The form's orderId selects which order provides the "verified purchase" evidence.

6. **Edit resets**: Editing a published review does NOT send it back to pending (since this is an admin tool, not customer-facing). This is a deliberate decision for the simulation context. In a customer-facing system, you'd want re-moderation.

---

## 15. Implementation Order

The implementation should proceed in this order to ensure each step builds on the previous:

### Phase 1: Foundation
1. Add `Review` mapping to `config/packages/doctrine.yaml`
2. Add `REVIEW_CREATED` and `REVIEW_STATUS_CHANGED` to `DomainEventType` enum
3. Create domain model: `ReviewStatus`, `RejectionReason`, `ReviewPublicId`, `ReviewSummaryPublicId`
4. Create entities: `ProductReview`, `ProductReviewSummary`
5. Create repository interfaces: `ReviewRepository`, `ReviewSummaryRepository`
6. Create Doctrine repository implementations

### Phase 2: Application Layer
7. Create commands: `CreateReview`, `UpdateReview`, `DeleteReview`, `ApproveReview`, `RejectReview`, `HideReview`, `RepublishReview`, `ReviewFilter`
8. Create handlers for all commands
9. Create `ReviewSearchCriteria`
10. Create `ReviewSummaryUpdater` listener
11. Create `ReviewGenerator` service

### Phase 3: UI Layer — Forms & Validation
12. Create form DTOs: `ReviewForm`, `RejectReviewForm`
13. Create form types: `ReviewType`, `RejectReviewType`, `ReviewFilterType`
14. Create mappers: `CreateReviewMapper`, `UpdateReviewMapper`, `RejectReviewMapper`, `ReviewFilterMapper`
15. Create eligibility validator: `ValidReviewEligibility` + `ValidReviewEligibilityValidator`

### Phase 4: UI Layer — Controllers & Templates
16. Create `ReviewController` with all actions
17. Create templates: `index.html.twig`, `show.html.twig`, `_review_card.html.twig`, `_review_detail_card.html.twig`, `_review_summary_card.html.twig`, `_review_preview_card.html.twig`
18. Create product reviews tab: `catalog/product/reviews.html.twig`
19. Add `reviews()` action to `ProductController`
20. Modify `_navigation.html.twig` to add Reviews tab
21. Modify `_menu.html.twig` to add Reviews sidebar section

### Phase 5: Console Command
22. Create `GenerateReviewsCommand`

### Phase 6: Tests
23. Create test factories: `ProductReviewFactory`, `ProductReviewSummaryFactory`
24. Create domain tests
25. Create handler tests
26. Create flow tests
27. Create summary projection tests

### Phase 7: Database
28. Generate and run Doctrine migration
