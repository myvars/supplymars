<?php

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private readonly MarkupCalculator $markupCalculator)
    {
    }

    public static function class(): string
    {
        return Product::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', self::faker()->words(random_int(1, 3)))),
            'description' => ucfirst(implode(' ', self::faker()->words(random_int(5, 10)))),
            'category' => LazyValue::memoize(fn () => CategoryFactory::createOne()),
            'subcategory' => null,
            'manufacturer' => LazyValue::memoize(fn () => ManufacturerFactory::createOne()),
            'mfrPartNumber' => self::faker()->regexify('[A-Z]{4}[0-4]{4}'),
            'owner' => LazyValue::memoize(fn () => UserFactory::createOne()),
            'isActive' => true,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if (null !== $attributes['category']) {
                    $attributes['subcategory'] ??= LazyValue::memoize(
                        fn () => SubcategoryFactory::createOne(['category' => $attributes['category']])
                    );
                }

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            );
    }

    public function withActiveSource(?SupplierProduct $supplierProduct = null): self
    {
        return $this
            ->afterInstantiate(function (Product $product) use ($supplierProduct): void {
                if (!$supplierProduct instanceof SupplierProduct) {
                    $supplierProduct = SupplierProductFactory::createOne(['product' => null]);
                }
                $product->addSupplierProduct($this->markupCalculator, $supplierProduct);
            });
    }
}
