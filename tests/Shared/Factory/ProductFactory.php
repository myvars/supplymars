<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
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
    public function __construct(private readonly MarkupCalculator $markupCalculator)
    {
    }

    public static function class(): string
    {
        return Product::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(implode(' ', (array) self::faker()->words(self::faker()->numberBetween(1, 3)))),
            'description' => ucfirst(implode(' ', (array) self::faker()->words(self::faker()->numberBetween(5, 10)))),
            'category' => LazyValue::memoize(fn (): Category => CategoryFactory::createOne()),
            'subcategory' => null,
            'manufacturer' => LazyValue::memoize(fn (): Manufacturer => ManufacturerFactory::createOne()),
            'mfrPartNumber' => self::faker()->regexify('[A-Z]{4}[0-4]{4}'),
            'owner' => LazyValue::memoize(fn (): User => UserFactory::new()->asStaff()->create()),
            'isActive' => true,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if (null !== $attributes['category']) {
                    $attributes['subcategory'] ??= LazyValue::memoize(
                        fn (): Subcategory => SubcategoryFactory::createOne(['category' => $attributes['category']])
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
            ->afterPersist(function (Product $product) use ($supplierProduct): void {
                if (!$supplierProduct instanceof SupplierProduct) {
                    $supplierProduct = SupplierProductFactory::createOne(['product' => null]);
                }

                $product->addSupplierProduct($this->markupCalculator, $supplierProduct);
            });
    }
}
