<?php

namespace App\Order\Application\Service;

use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\AddressRepository;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Factory\RandomAddressFactory;
use App\Customer\Infrastructure\Factory\RandomUserFactory;
use App\Order\Application\Command\CreateOrder;
use App\Order\Application\Command\CreateOrderItem;
use App\Order\Application\Handler\CreateOrderHandler;
use App\Order\Application\Handler\CreateOrderItemHandler;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\ValueObject\ShippingMethod;

final readonly class DemoOrderCreator
{
    public const string REF_PREFIX = 'DEMO-';

    public const int MAX_ORDER_LINES = 5;

    public const int MAX_LINE_QTY = 5;

    public function __construct(
        private ProductRepository $products,
        private OrderRepository $orders,
        private UserRepository $customers,
        private AddressRepository $addresses,
        private RandomUserFactory $randomUserFactory,
        private RandomAddressFactory $randomAddressFactory,
        private CreateOrderHandler $createOrderHandler,
        private CreateOrderItemHandler $createOrderItemHandler,
        private FlusherInterface $flusher,
    ) {
    }

    public function create(string $refPrefix = self::REF_PREFIX): DemoOrderResult
    {
        $user = $this->getOrCreateUser();
        $this->ensureBillingAddress($user);

        $order = $this->placeOrder($user, $refPrefix);
        $this->addOrderItems($order);

        return new DemoOrderResult($order, $user);
    }

    private function getOrCreateUser(): User
    {
        if (random_int(0, 2) === 0) {
            $user = $this->customers->getRandomUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        $user = $this->randomUserFactory->create();
        $this->customers->add($user);

        $this->flusher->flush();

        return $user;
    }

    private function ensureBillingAddress(User $user): void
    {
        $address = $user->getBillingAddress();
        if ($address instanceof Address) {
            return;
        }

        $address = $this->randomAddressFactory->create($user, isShipping: true, isBilling: true);
        $this->addresses->add($address);
        $user->addAddress($address);

        $this->flusher->flush();
    }

    private function placeOrder(User $user, string $refPrefix): CustomerOrder
    {
        $shippingMethods = ShippingMethod::cases();
        $result = ($this->createOrderHandler)(
            new CreateOrder(
                $user->getId(),
                $shippingMethods[array_rand($shippingMethods)],
                $refPrefix . sprintf('%04d', $user->getId()),
            )
        );

        if (!$result->payload instanceof OrderPublicId) {
            throw new \RuntimeException('Failed to create customer order');
        }

        return $this->orders->getByPublicId($result->payload);
    }

    private function addOrderItems(CustomerOrder $order): void
    {
        $products = $this->products->findRandomProducts(random_int(1, self::MAX_ORDER_LINES));
        if ($products === []) {
            return;
        }

        foreach ($products as $product) {
            ($this->createOrderItemHandler)(
                new CreateOrderItem(
                    $order->getPublicId(),
                    $product->getId(),
                    random_int(1, self::MAX_LINE_QTY),
                )
            );
        }
    }
}
