<?php

namespace App\Order\UI\Http\Api\Resource;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'OrderList', description: 'An order list resource')]
final readonly class OrderListResource implements ApiResourceInterface
{
    /**
     * @param array{id: string, name: string} $customer
     */
    public function __construct(
        #[OA\Property(description: 'Order ULID')]
        public string $id,
        #[OA\Property(description: 'Order status')]
        public string $status,
        #[OA\Property(description: 'Customer', properties: [
            new OA\Property(property: 'id', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object')]
        public array $customer,
        #[OA\Property(description: 'Shipping method')]
        public string $shippingMethod,
        #[OA\Property(description: 'Number of items')]
        public int $itemCount,
        #[OA\Property(description: 'Total price including VAT')]
        public string $totalPrice,
        #[OA\Property(description: 'Due date (Y-m-d)')]
        public string $dueDate,
        #[OA\Property(description: 'Customer order reference', nullable: true)]
        public ?string $customerOrderRef,
        #[OA\Property(description: 'Creation timestamp (ISO 8601)', nullable: true)]
        public ?string $createdAt,
    ) {
    }

    public static function fromEntity(CustomerOrder $order): self
    {
        return new self(
            id: $order->getPublicId()->value(),
            status: $order->getStatus()->value,
            customer: [
                'id' => $order->getCustomer()->getPublicId()->value(),
                'name' => $order->getCustomer()->getFullName(),
            ],
            shippingMethod: $order->getShippingMethod()->value,
            itemCount: $order->getItemCount(),
            totalPrice: $order->getTotalPriceIncVat(),
            dueDate: $order->getDueDate()->format('Y-m-d'),
            customerOrderRef: $order->getCustomerOrderRef(),
            createdAt: $order->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'customer' => $this->customer,
            'shippingMethod' => $this->shippingMethod,
            'itemCount' => $this->itemCount,
            'totalPrice' => $this->totalPrice,
            'dueDate' => $this->dueDate,
            'customerOrderRef' => $this->customerOrderRef,
            'createdAt' => $this->createdAt,
        ];
    }
}
