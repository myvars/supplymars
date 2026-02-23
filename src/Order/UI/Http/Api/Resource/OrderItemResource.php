<?php

namespace App\Order\UI\Http\Api\Resource;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'OrderItem', description: 'An order item resource')]
final readonly class OrderItemResource implements ApiResourceInterface
{
    /**
     * @param array{id: string, name: string, mfrPartNumber: string} $product
     */
    public function __construct(
        #[OA\Property(description: 'Order item ULID')]
        public string $id,
        #[OA\Property(description: 'Product', properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'mfrPartNumber', type: 'string'),
        ], type: 'object')]
        public array $product,
        #[OA\Property(description: 'Quantity')]
        public int $quantity,
        #[OA\Property(description: 'Unit price including VAT')]
        public string $unitPrice,
        #[OA\Property(description: 'Total price including VAT')]
        public string $totalPrice,
        #[OA\Property(description: 'Item status')]
        public string $status,
    ) {
    }

    public static function fromEntity(CustomerOrderItem $item): self
    {
        return new self(
            id: $item->getPublicId()->value(),
            product: [
                'id' => $item->getProduct()->getPublicId()->value(),
                'name' => $item->getProduct()->getName(),
                'mfrPartNumber' => $item->getProduct()->getMfrPartNumber(),
            ],
            quantity: $item->getQuantity(),
            unitPrice: $item->getPriceIncVat(),
            totalPrice: $item->getTotalPriceIncVat(),
            status: $item->getStatus()->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product' => $this->product,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'totalPrice' => $this->totalPrice,
            'status' => $this->status,
        ];
    }
}
