<?php

declare(strict_types=1);

namespace App\Review\Application\Handler;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Repository\OrderRepository;
use App\Review\Application\Command\CreateReview;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private UserRepository $users,
        private ProductRepository $products,
        private OrderRepository $orders,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateReview $command): Result
    {
        $customer = $this->users->get(UserId::fromInt($command->customerId));
        if (!$customer instanceof User) {
            return Result::fail('Customer not found.');
        }

        $product = $this->products->get(ProductId::fromInt($command->productId));
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $order = $this->orders->get(OrderId::fromInt($command->orderId));
        if (!$order instanceof CustomerOrder) {
            return Result::fail('Order not found.');
        }

        $review = ProductReview::create(
            customer: $customer,
            product: $product,
            customerOrder: $order,
            rating: $command->rating,
            title: $command->title,
            body: $command->body,
        );

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->reviews->add($review);
        $this->flusher->flush();

        return Result::ok('Review created', $review->getPublicId());
    }
}
