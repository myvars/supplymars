<?php

namespace App\Catalog\UI\Http\Api\Resource;

use App\Catalog\Domain\Model\Product\Product;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Product', description: 'A product resource')]
final readonly class ProductResource implements ApiResourceInterface
{
    /**
     * @param array{id: string, name: string}                                                          $category
     * @param array{id: string, name: string}                                                          $subcategory
     * @param array{id: string, name: string}                                                          $manufacturer
     * @param array<array{id: string, url: string, position: int}>                                     $images
     * @param array{averageRating: string, reviewCount: int, ratingDistribution: array<int, int>}|null $reviewSummary
     */
    public function __construct(
        #[OA\Property(description: 'Product ULID')]
        public string $id,
        #[OA\Property(description: 'Product name')]
        public string $name,
        #[OA\Property(description: 'Manufacturer part number')]
        public string $mfrPartNumber,
        #[OA\Property(description: 'Product description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Sell price including VAT')]
        public string $price,
        #[OA\Property(description: 'Sell price excluding VAT')]
        public string $priceExVat,
        #[OA\Property(description: 'Current stock level')]
        public int $stock,
        #[OA\Property(description: 'Whether the product is in stock')]
        public bool $inStock,
        #[OA\Property(description: 'Product weight', nullable: true)]
        public ?int $weight,
        #[OA\Property(description: 'Lead time in days', nullable: true)]
        public ?int $leadTimeDays,
        #[OA\Property(description: 'Category', properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object')]
        public array $category,
        #[OA\Property(description: 'Subcategory', properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object')]
        public array $subcategory,
        #[OA\Property(description: 'Manufacturer', properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object')]
        public array $manufacturer,
        #[OA\Property(description: 'Product images', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'url', type: 'string'),
            new OA\Property(property: 'position', type: 'integer'),
        ], type: 'object'))]
        public array $images,
        #[OA\Property(description: 'Review summary', nullable: true)]
        public ?array $reviewSummary,
        #[OA\Property(description: 'Creation timestamp (ISO 8601)', nullable: true)]
        public ?string $createdAt,
    ) {
    }

    public static function fromEntity(Product $product, ?ProductReviewSummary $reviewSummary = null): self
    {
        $images = [];
        foreach ($product->getProductImages() as $image) {
            $images[] = [
                'id' => $image->getPublicId()->value(),
                'url' => '/uploads/products/' . $image->getImageName(),
                'position' => $image->getPosition(),
            ];
        }

        $reviewData = null;
        if ($reviewSummary instanceof ProductReviewSummary) {
            $reviewData = [
                'averageRating' => $reviewSummary->getAverageRating(),
                'reviewCount' => $reviewSummary->getReviewCount(),
                'ratingDistribution' => $reviewSummary->getRatingDistribution(),
            ];
        }

        return new self(
            id: $product->getPublicId()->value(),
            name: $product->getName(),
            mfrPartNumber: $product->getMfrPartNumber(),
            description: $product->getDescription(),
            price: $product->getSellPriceIncVat(),
            priceExVat: $product->getSellPrice(),
            stock: $product->getStock(),
            inStock: $product->getStock() > 0,
            weight: $product->getWeight(),
            leadTimeDays: $product->getLeadTimeDays(),
            category: [
                'id' => $product->getCategory()->getPublicId()->value(),
                'name' => $product->getCategory()->getName(),
            ],
            subcategory: [
                'id' => $product->getSubcategory()->getPublicId()->value(),
                'name' => $product->getSubcategory()->getName(),
            ],
            manufacturer: [
                'id' => $product->getManufacturer()->getPublicId()->value(),
                'name' => $product->getManufacturer()->getName(),
            ],
            images: $images,
            reviewSummary: $reviewData,
            createdAt: $product->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mfrPartNumber' => $this->mfrPartNumber,
            'description' => $this->description,
            'price' => $this->price,
            'priceExVat' => $this->priceExVat,
            'stock' => $this->stock,
            'inStock' => $this->inStock,
            'weight' => $this->weight,
            'leadTimeDays' => $this->leadTimeDays,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'manufacturer' => $this->manufacturer,
            'images' => $this->images,
            'reviewSummary' => $this->reviewSummary,
            'createdAt' => $this->createdAt,
        ];
    }
}
