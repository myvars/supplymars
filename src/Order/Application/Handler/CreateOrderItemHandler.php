<?php

declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Order\Application\Command\CreateOrderItem;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateOrderItemHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OrderItemRepository $orderItems,
        private ProductRepository $products,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateOrderItem $command): Result
    {
        $order = $this->orders->getByPublicId($command->orderId);
        if (!$order instanceof CustomerOrder) {
            return Result::fail('Order not found.');
        }

        $product = $this->products->get(ProductId::fromInt($command->productId));
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $orderItem = CustomerOrderItem::createFromProduct(
            customerOrder: $order,
            product: $product,
            quantity: $command->quantity,
        );

        $errors = $this->validator->validate($orderItem);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->orderItems->add($orderItem);

        $this->flusher->flush();

        return Result::ok('Order item created', $orderItem->getPublicId());
    }
}
