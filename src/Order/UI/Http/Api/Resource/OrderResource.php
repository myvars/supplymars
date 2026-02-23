<?php

namespace App\Order\UI\Http\Api\Resource;

use App\Customer\Domain\Model\Address\Address;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Shared\UI\Http\Api\ApiResourceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Order', description: 'An order resource')]
final readonly class OrderResource implements ApiResourceInterface
{
    /**
     * @param array{id: string, name: string}                                                                                                                   $customer
     * @param array{fullName: string, companyName: ?string, street: string, street2: ?string, city: string, county: ?string, postCode: string, country: string} $shippingAddress
     * @param array{fullName: string, companyName: ?string, street: string, street2: ?string, city: string, county: ?string, postCode: string, country: string} $billingAddress
     * @param array<array<string, mixed>>                                                                                                                       $items
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
        #[OA\Property(description: 'Shipping address', properties: [
            new OA\Property(property: 'fullName', type: 'string'),
            new OA\Property(property: 'companyName', type: 'string', nullable: true),
            new OA\Property(property: 'street', type: 'string'),
            new OA\Property(property: 'street2', type: 'string', nullable: true),
            new OA\Property(property: 'city', type: 'string'),
            new OA\Property(property: 'county', type: 'string', nullable: true),
            new OA\Property(property: 'postCode', type: 'string'),
            new OA\Property(property: 'country', type: 'string'),
        ], type: 'object')]
        public array $shippingAddress,
        #[OA\Property(description: 'Billing address', properties: [
            new OA\Property(property: 'fullName', type: 'string'),
            new OA\Property(property: 'companyName', type: 'string', nullable: true),
            new OA\Property(property: 'street', type: 'string'),
            new OA\Property(property: 'street2', type: 'string', nullable: true),
            new OA\Property(property: 'city', type: 'string'),
            new OA\Property(property: 'county', type: 'string', nullable: true),
            new OA\Property(property: 'postCode', type: 'string'),
            new OA\Property(property: 'country', type: 'string'),
        ], type: 'object')]
        public array $billingAddress,
        #[OA\Property(description: 'Order items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem'))]
        public array $items,
        #[OA\Property(description: 'Shipping price including VAT')]
        public string $shippingPrice,
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
        $items = [];
        foreach ($order->getCustomerOrderItems() as $item) {
            $items[] = OrderItemResource::fromEntity($item)->toArray();
        }

        return new self(
            id: $order->getPublicId()->value(),
            status: $order->getStatus()->value,
            customer: [
                'id' => $order->getCustomer()->getPublicId()->value(),
                'name' => $order->getCustomer()->getFullName(),
            ],
            shippingMethod: $order->getShippingMethod()->value,
            shippingAddress: self::formatAddress($order->getShippingAddress()),
            billingAddress: self::formatAddress($order->getBillingAddress()),
            items: $items,
            shippingPrice: $order->getShippingPriceIncVat(),
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
            'shippingAddress' => $this->shippingAddress,
            'billingAddress' => $this->billingAddress,
            'items' => $this->items,
            'shippingPrice' => $this->shippingPrice,
            'totalPrice' => $this->totalPrice,
            'dueDate' => $this->dueDate,
            'customerOrderRef' => $this->customerOrderRef,
            'createdAt' => $this->createdAt,
        ];
    }

    /**
     * @return array{fullName: string, companyName: ?string, street: string, street2: ?string, city: string, county: ?string, postCode: string, country: string}
     */
    private static function formatAddress(Address $address): array
    {
        return [
            'fullName' => $address->getFullName(),
            'companyName' => $address->getCompanyName(),
            'street' => $address->getStreet(),
            'street2' => $address->getStreet2(),
            'city' => $address->getCity(),
            'county' => $address->getCounty(),
            'postCode' => $address->getPostCode(),
            'country' => $address->getCountry(),
        ];
    }
}
