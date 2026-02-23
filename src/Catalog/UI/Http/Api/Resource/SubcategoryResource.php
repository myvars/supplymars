<?php

namespace App\Catalog\UI\Http\Api\Resource;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Subcategory', description: 'A subcategory resource')]
final readonly class SubcategoryResource implements ApiResourceInterface
{
    /**
     * @param array{id: string, name: string} $category
     */
    public function __construct(
        #[OA\Property(description: 'Subcategory ULID')]
        public string $id,
        #[OA\Property(description: 'Subcategory name')]
        public string $name,
        #[OA\Property(description: 'Parent category', properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object')]
        public array $category,
        #[OA\Property(description: 'Whether the subcategory is active')]
        public bool $isActive,
        #[OA\Property(description: 'Creation timestamp (ISO 8601)', nullable: true)]
        public ?string $createdAt,
    ) {
    }

    public static function fromEntity(Subcategory $subcategory): self
    {
        return new self(
            id: $subcategory->getPublicId()->value(),
            name: $subcategory->getName(),
            category: [
                'id' => $subcategory->getCategory()->getPublicId()->value(),
                'name' => $subcategory->getCategory()->getName(),
            ],
            isActive: $subcategory->isActive(),
            createdAt: $subcategory->getCreatedAt()?->format(\DateTimeInterface::ATOM),
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
            'category' => $this->category,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
        ];
    }
}
