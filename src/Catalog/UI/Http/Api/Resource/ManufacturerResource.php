<?php

namespace App\Catalog\UI\Http\Api\Resource;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Manufacturer', description: 'A manufacturer resource')]
final readonly class ManufacturerResource implements ApiResourceInterface
{
    public function __construct(
        #[OA\Property(description: 'Manufacturer ULID')]
        public string $id,
        #[OA\Property(description: 'Manufacturer name')]
        public string $name,
        #[OA\Property(description: 'Whether the manufacturer is active')]
        public bool $isActive,
        #[OA\Property(description: 'Creation timestamp (ISO 8601)', nullable: true)]
        public ?string $createdAt,
    ) {
    }

    public static function fromEntity(Manufacturer $manufacturer): self
    {
        return new self(
            id: $manufacturer->getPublicId()->value(),
            name: $manufacturer->getName(),
            isActive: $manufacturer->isActive(),
            createdAt: $manufacturer->getCreatedAt()?->format(\DateTimeInterface::ATOM),
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
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
        ];
    }
}
