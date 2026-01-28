<?php

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
use App\Review\Domain\Model\ReviewSummary\ProductReviewSummary;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductReviewSummary>
 */
final class ProductReviewSummaryFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return ProductReviewSummary::class;
    }

    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::createOne()),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create'));
    }
}
