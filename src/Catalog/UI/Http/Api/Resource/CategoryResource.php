<?php

namespace App\Catalog\UI\Http\Api\Resource;

use App\Catalog\Domain\Model\Category\Category;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Category', description: 'A category resource')]
final readonly class CategoryResource implements ApiResourceInterface
{
    /**
     * @param array{name: string, rate: string} $vatRate
     * @param array<array<string, mixed>>|null  $subcategories
     */
    public function __construct(
        #[OA\Property(description: 'Category ULID')]
        public string $id,
        #[OA\Property(description: 'Category name')]
        public string $name,
        #[OA\Property(description: 'VAT rate', properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'rate', type: 'string'),
        ], type: 'object')]
        public array $vatRate,
        #[OA\Property(description: 'Number of subcategories')]
        public int $subcategoryCount,
        #[OA\Property(description: 'Whether the category is active')]
        public bool $isActive,
        #[OA\Property(description: 'Creation timestamp (ISO 8601)', nullable: true)]
        public ?string $createdAt,
        #[OA\Property(description: 'Subcategories (included on detail view)', type: 'array', items: new OA\Items(ref: '#/components/schemas/Subcategory'), nullable: true)]
        public ?array $subcategories = null,
    ) {
    }

    public static function fromEntity(Category $category, bool $includeSubcategories = false): self
    {
        $subcategories = null;

        if ($includeSubcategories) {
            $subcategories = [];
            foreach ($category->getSubcategories() as $subcategory) {
                $subcategories[] = SubcategoryResource::fromEntity($subcategory)->toArray();
            }
        }

        return new self(
            id: $category->getPublicId()->value(),
            name: $category->getName(),
            vatRate: [
                'name' => $category->getVatRate()->getName(),
                'rate' => $category->getVatRate()->getRate(),
            ],
            subcategoryCount: $category->getSubcategories()->count(),
            isActive: $category->isActive(),
            createdAt: $category->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            subcategories: $subcategories,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'vatRate' => $this->vatRate,
            'subcategoryCount' => $this->subcategoryCount,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
        ];

        if ($this->subcategories !== null) {
            $data['subcategories'] = $this->subcategories;
        }

        return $data;
    }
}
