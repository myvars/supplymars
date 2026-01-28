<?php

namespace App\Review\Application\Service;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Repository\OrderRepository;
use App\Review\Domain\Model\Review\ProductReview;
use App\Review\Domain\Repository\ReviewRepository;
use App\Review\Infrastructure\Persistence\Doctrine\ReviewDoctrineRepository;

class ReviewGenerator
{
    private const array TITLES = [
        5 => ['Excellent product!', 'Absolutely love it!', 'Best purchase ever!', 'Highly recommended!', 'Outstanding quality!'],
        4 => ['Great product', 'Very satisfied', 'Good quality', 'Would buy again', 'Solid choice'],
        3 => ['Decent product', "It's okay", 'Average quality', 'Does the job', 'Fair enough'],
        2 => ['Below expectations', 'Not great', 'Could be better', 'Disappointed', 'Needs improvement'],
        1 => ['Very disappointed', 'Would not recommend', 'Poor quality', 'Not as described', 'Waste of money'],
    ];

    private const array BODIES = [
        5 => [
            'This exceeded all my expectations. The quality is superb and it arrived quickly. Would definitely purchase again.',
            'Absolutely fantastic! Everything about this product is top-notch. The build quality, the packaging, everything.',
            "I've been using this for weeks now and I'm still impressed. It works exactly as described, if not better.",
        ],
        4 => [
            "Really good product overall. A few minor things could be improved but I'm happy with my purchase.",
            'Solid product that does what it says. Good value for money and quick delivery.',
            "Very pleased with this purchase. It's well made and works great. Minor cosmetic issues but nothing major.",
        ],
        3 => [
            "It's an average product. Does what it needs to do but nothing special. Acceptable quality for the price.",
            "Decent enough. Some things I like, some things I don't. It works but could be better in a few areas.",
            'Middle of the road product. Not bad, not amazing. Gets the job done.',
        ],
        2 => [
            "Not what I expected. The quality feels cheap and it doesn't quite work as advertised.",
            'Disappointed with this purchase. There are better options available at this price point.',
            'Had some issues with this product. It works somewhat but not reliably.',
        ],
        1 => [
            "Very unhappy with this purchase. The product arrived damaged and doesn't work properly.",
            'This is not at all what was described. Poor quality and poor customer experience.',
            'Regret buying this. Save your money and look elsewhere.',
        ],
    ];

    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly UserRepository $users,
        private readonly ProductRepository $products,
        private readonly OrderRepository $orders,
    ) {
    }

    public function generate(int $maxCount, ?int $productId = null): int
    {
        assert($this->reviews instanceof ReviewDoctrineRepository);
        $eligibleItems = $this->reviews->findEligibleOrderIds($maxCount, $productId);

        $created = 0;
        foreach ($eligibleItems as $item) {
            $customer = $this->users->get(UserId::fromInt((int) $item['customer_id']));
            $product = $this->products->get(ProductId::fromInt((int) $item['product_id']));
            $order = $this->orders->get(OrderId::fromInt((int) $item['order_id']));
            if (!$customer instanceof User) {
                continue;
            }

            if (!$product instanceof Product) {
                continue;
            }

            if (!$order instanceof CustomerOrder) {
                continue;
            }

            $rating = $this->generateRating();

            $review = ProductReview::create(
                customer: $customer,
                product: $product,
                customerOrder: $order,
                rating: $rating,
                title: $this->generateTitle($rating),
                body: $this->generateBody($rating),
            );

            $this->reviews->add($review);
            ++$created;
        }

        return $created;
    }

    private function generateRating(): int
    {
        $weights = [1 => 5, 2 => 10, 3 => 15, 4 => 35, 5 => 35];
        $rand = random_int(1, 100);
        $cumulative = 0;

        foreach ($weights as $rating => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $rating;
            }
        }

        return 4;
    }

    private function generateTitle(int $rating): string
    {
        $titles = self::TITLES[$rating];

        return $titles[array_rand($titles)];
    }

    private function generateBody(int $rating): string
    {
        $bodies = self::BODIES[$rating];

        return $bodies[array_rand($bodies)];
    }
}
