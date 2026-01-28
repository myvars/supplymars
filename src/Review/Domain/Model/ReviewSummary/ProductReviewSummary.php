<?php

namespace App\Review\Domain\Model\ReviewSummary;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::INTEGER)]
    private int $reviewCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2)]
    private string $averageRating = '0.00';

    /** @var array<int, int> */
    #[ORM\Column(type: Types::JSON)]
    private array $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

    #[ORM\Column(type: Types::INTEGER)]
    private int $pendingCount = 0;

    private function __construct()
    {
        $this->initializePublicId();
    }

    public static function create(Product $product): self
    {
        $summary = new self();
        $summary->product = $product;

        return $summary;
    }

    /**
     * @param array<int, int> $ratingDistribution
     */
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ReviewSummaryPublicId
    {
        return ReviewSummaryPublicId::fromString($this->publicIdString());
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getReviewCount(): int
    {
        return $this->reviewCount;
    }

    public function getAverageRating(): string
    {
        return $this->averageRating;
    }

    /** @return array<int, int> */
    public function getRatingDistribution(): array
    {
        return $this->ratingDistribution;
    }

    public function getPendingCount(): int
    {
        return $this->pendingCount;
    }

    public function hasReviews(): bool
    {
        return $this->reviewCount > 0;
    }
}
