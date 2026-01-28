<?php

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
use App\Customer\Domain\Model\User\User;
use App\Review\Domain\Model\Review\ProductReview;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductReview>
 */
final class ProductReviewFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return ProductReview::class;
    }

    protected function defaults(): array
    {
        return [
            'customer' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::createOne()),
            'customerOrder' => null,
            'rating' => self::faker()->numberBetween(1, 5),
            'title' => self::faker()->sentence(4),
            'body' => self::faker()->paragraph(2),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if ($attributes['customerOrder'] === null) {
                    $order = CustomerOrderFactory::createOne([
                        'customer' => $attributes['customer'],
                    ]);
                    CustomerOrderItemFactory::createOne([
                        'customerOrder' => $order,
                        'product' => $attributes['product'],
                    ]);
                    $attributes['customerOrder'] = $order;
                }

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            );
    }

    public function published(): self
    {
        return $this->afterInstantiate(function (ProductReview $review): void {
            $moderator = UserFactory::new()->asStaff()->create();
            $review->approve($moderator);
        });
    }

    public function hidden(): self
    {
        return $this->afterInstantiate(function (ProductReview $review): void {
            $moderator = UserFactory::new()->asStaff()->create();
            $review->approve($moderator);
            $review->hide($moderator);
        });
    }
}
