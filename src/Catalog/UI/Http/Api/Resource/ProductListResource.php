<?php

namespace App\Catalog\UI\Http\Api\Resource;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProductList', description: 'A product list resource')]
final readonly class ProductListResource implements ApiResourceInterface
{
    /**
     * @param array{id: string, name: string} $category
     * @param array{id: string, name: string} $subcategory
     * @param array{id: string, name: string} $manufacturer
     */
    public function __construct(
        #[OA\Property(description: 'Product ULID')]
        public string $id,
        #[OA\Property(description: 'Product name')]
        public string $name,
        #[OA\Property(description: 'Manufacturer part number')]
        public string $mfrPartNumber,
        #[OA\Property(description: 'Sell price including VAT')]
        public string $price,
        #[OA\Property(description: 'Sell price excluding VAT')]
        public string $priceExVat,
        #[OA\Property(description: 'Current stock level')]
        public int $stock,
        #[OA\Property(description: 'Whether the product is in stock')]
        public bool $inStock,
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
        #[OA\Property(description: 'Primary image URL', nullable: true)]
        public ?string $image,
    ) {
    }

    public static function fromEntity(Product $product): self
    {
        return new self(
            id: $product->getPublicId()->value(),
            name: $product->getName(),
            mfrPartNumber: $product->getMfrPartNumber(),
            price: $product->getSellPriceIncVat(),
            priceExVat: $product->getSellPrice(),
            stock: $product->getStock(),
            inStock: $product->getStock() > 0,
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
            image: $product->hasProductImage()
                ? '/uploads/products/' . $product->getFirstImage()->getImageName()
                : null,
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
            'price' => $this->price,
            'priceExVat' => $this->priceExVat,
            'stock' => $this->stock,
            'inStock' => $this->inStock,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'manufacturer' => $this->manufacturer,
            'image' => $this->image,
        ];
    }
}
