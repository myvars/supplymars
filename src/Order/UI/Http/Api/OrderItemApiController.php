<?php

namespace App\Order\UI\Http\Api;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Order\Application\Command\CancelOrderItem;
use App\Order\Application\Command\CreateOrderItem;
use App\Order\Application\Command\UpdateOrderItem;
use App\Order\Application\Handler\CancelOrderItemHandler;
use App\Order\Application\Handler\CreateOrderItemHandler;
use App\Order\Application\Handler\UpdateOrderItemHandler;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\UI\Http\Api\Payload\AddOrderItemPayload;
use App\Order\UI\Http\Api\Payload\UpdateOrderItemPayload;
use App\Order\UI\Http\Api\Resource\OrderItemResource;
use App\Shared\UI\Http\Api\AbstractApiController;
use App\Shared\UI\Http\Api\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/orders/{orderId}/items')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Order Items')]
class OrderItemApiController extends AbstractApiController
{
    #[Route('', name: 'api_order_item_add', methods: ['POST'])]
    #[OA\Post(summary: 'Add an order item')]
    public function add(
        string $orderId,
        OrderRepository $orders,
        ProductRepository $products,
        CreateOrderItemHandler $handler,
        OrderItemRepository $orderItems,
        #[MapRequestPayload] AddOrderItemPayload $payload,
    ): JsonResponse {
        $order = $this->resolveOrder($orders, $orderId);

        $product = $products->getByPublicId(ProductPublicId::fromString($payload->product));
        if (!$product instanceof Product) {
            return ApiResponse::error('Product not found.', 422);
        }

        $result = ($handler)(
            new CreateOrderItem(
                orderId: $order->getPublicId(),
                productId: $product->getId(),
                quantity: $payload->quantity,
            )
        );

        if (!$result->ok) {
            return ApiResponse::error($result->message ?? 'Operation failed.', 422);
        }

        $orderItem = $orderItems->getByPublicId($result->payload);
        $resource = OrderItemResource::fromEntity($orderItem);

        return ApiResponse::item($resource->toArray(), 201);
    }

    #[Route('/{itemId}', name: 'api_order_item_update', methods: ['PUT'])]
    #[OA\Put(summary: 'Update an order item')]
    public function update(
        string $orderId,
        string $itemId,
        OrderRepository $orders,
        OrderItemRepository $orderItems,
        UpdateOrderItemHandler $handler,
        #[MapRequestPayload] UpdateOrderItemPayload $payload,
    ): JsonResponse {
        $this->resolveOrder($orders, $orderId);
        $orderItem = $this->resolveOrderItem($orderItems, $itemId);

        $result = ($handler)(
            new UpdateOrderItem(
                orderItemId: $orderItem->getPublicId(),
                quantity: $payload->quantity,
                priceIncVat: $orderItem->getPriceIncVat(),
            )
        );

        $resource = OrderItemResource::fromEntity($orderItem);

        return $this->handleResult($result, onSuccess: $resource);
    }

    #[Route('/{itemId}', name: 'api_order_item_remove', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Remove an order item')]
    public function remove(
        string $orderId,
        string $itemId,
        OrderRepository $orders,
        OrderItemRepository $orderItems,
        CancelOrderItemHandler $handler,
    ): JsonResponse {
        $this->resolveOrder($orders, $orderId);
        $orderItem = $this->resolveOrderItem($orderItems, $itemId);

        $result = ($handler)(new CancelOrderItem($orderItem->getPublicId()));

        if (!$result->ok) {
            return ApiResponse::error($result->message ?? 'Failed to remove order item.', 422);
        }

        return ApiResponse::noContent();
    }

    private function resolveOrder(OrderRepository $orders, string $orderId): CustomerOrder
    {
        $order = $orders->getByPublicId(OrderPublicId::fromString($orderId));

        if (!$order instanceof CustomerOrder) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $order;
    }

    private function resolveOrderItem(OrderItemRepository $orderItems, string $itemId): CustomerOrderItem
    {
        $orderItem = $orderItems->getByPublicId(OrderItemPublicId::fromString($itemId));

        if (!$orderItem instanceof CustomerOrderItem) {
            throw new NotFoundHttpException('Order item not found.');
        }

        return $orderItem;
    }
}
