<?php

namespace App\Order\UI\Http\Api;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Application\Command\AllocateOrder;
use App\Order\Application\Command\CancelOrder;
use App\Order\Application\Command\CreateOrder;
use App\Order\Application\Handler\AllocateOrderHandler;
use App\Order\Application\Handler\CancelOrderHandler;
use App\Order\Application\Handler\CreateOrderHandler;
use App\Order\Application\Search\OrderSearchCriteria;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\UI\Http\Api\Payload\CreateOrderPayload;
use App\Order\UI\Http\Api\Resource\OrderListResource;
use App\Order\UI\Http\Api\Resource\OrderResource;
use App\Shared\Domain\ValueObject\ShippingMethod;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use App\Shared\UI\Http\Api\AbstractApiController;
use App\Shared\UI\Http\Api\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/orders')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Orders')]
class OrderApiController extends AbstractApiController
{
    #[Route('', name: 'api_order_index', methods: ['GET'])]
    #[OA\Get(summary: 'List orders')]
    public function index(
        Request $request,
        OrderRepository $orders,
        Paginator $paginator,
        #[MapQueryString] OrderSearchCriteria $criteria = new OrderSearchCriteria(),
    ): JsonResponse {
        $criteria->customerId = $this->resolveFilterId($request, 'customer', UserPublicId::class);
        $pager = $paginator->searchPagination($orders, $criteria);

        return ApiResponse::collection(
            pager: $pager,
            resource: OrderListResource::class,
            request: $request
        );
    }

    #[Route('', name: 'api_order_create', methods: ['POST'])]
    #[OA\Post(summary: 'Create an order')]
    public function create(
        UserRepository $users,
        CreateOrderHandler $handler,
        OrderRepository $orders,
        #[MapRequestPayload] CreateOrderPayload $payload,
    ): JsonResponse {
        $customer = $users->getByPublicId(UserPublicId::fromString($payload->customer));
        if (!$customer instanceof User) {
            return ApiResponse::error('Customer not found.', 422);
        }

        $result = ($handler)(
            new CreateOrder(
                customerId: $customer->getId(),
                shippingMethod: ShippingMethod::from($payload->shippingMethod),
                customerOrderRef: $payload->customerOrderRef,
            )
        );

        if (!$result->ok) {
            return ApiResponse::error($result->message ?? 'Operation failed.', 422);
        }

        $order = $orders->getByPublicId($result->payload);
        $resource = OrderResource::fromEntity($order);

        return ApiResponse::item($resource->toArray(), 201);
    }

    #[Route('/{id}', name: 'api_order_show', methods: ['GET'])]
    #[OA\Get(summary: 'Get an order')]
    public function show(
        #[ValueResolver('public_id')] CustomerOrder $order,
    ): JsonResponse {
        $resource = OrderResource::fromEntity($order);

        return ApiResponse::item($resource->toArray());
    }

    #[Route('/{id}/cancel', name: 'api_order_cancel', methods: ['POST'])]
    #[OA\Post(summary: 'Cancel an order')]
    public function cancel(
        #[ValueResolver('public_id')] CustomerOrder $order,
        CancelOrderHandler $handler,
    ): JsonResponse {
        $result = ($handler)(new CancelOrder($order->getPublicId()));
        $resource = OrderResource::fromEntity($order);

        return $this->handleResult($result, onSuccess: $resource);
    }

    #[Route('/{id}/allocate', name: 'api_order_allocate', methods: ['POST'])]
    #[OA\Post(summary: 'Allocate an order')]
    public function allocate(
        #[ValueResolver('public_id')] CustomerOrder $order,
        AllocateOrderHandler $handler,
    ): JsonResponse {
        $result = ($handler)(new AllocateOrder($order->getPublicId()));
        $resource = OrderResource::fromEntity($order);

        return $this->handleResult($result, onSuccess: $resource);
    }
}
