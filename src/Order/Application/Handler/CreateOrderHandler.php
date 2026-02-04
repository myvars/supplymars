<?php

namespace App\Order\Application\Handler;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Application\Command\CreateOrder;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateOrderHandler
{
    private const string ROUTE = 'app_order_show';

    public function __construct(
        private OrderRepository $orders,
        private UserRepository $users,
        private VatRateRepository $vatRates,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateOrder $command): Result
    {
        $customer = $this->users->get(UserId::fromInt($command->customerId));
        if (!$customer instanceof User) {
            return Result::fail('Customer not found.');
        }

        $vatRate = $this->vatRates->getDefaultVatRate();
        if (!$vatRate instanceof VatRate) {
            return Result::fail('Default VAT rate not found.');
        }

        $order = CustomerOrder::createFromCustomer(
            customer: $customer,
            shippingMethod: $command->shippingMethod,
            vatRate: $vatRate,
            customerOrderRef: $command->customerOrderRef,
        );

        $errors = $this->validator->validate($order);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->orders->add($order);

        $this->flusher->flush();

        return Result::ok(
            message: 'Order created',
            payload: $order->getPublicId(),
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $order->getPublicId()->value()],
            ),
        );
    }
}
