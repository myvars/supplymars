<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use Zenstruck\Foundry\LazyValue;

/**
 * @extends PersistentObjectFactory<SupplierProduct>
 */
final class SupplierProductFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private readonly ActiveSourceCalculator $activeSourceCalculator,
        private readonly ProductPriceCalculator $productPriceCalculator,
    ) {
    }

    public static function class(): string
    {
        return SupplierProduct::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(50),
            'productCode' => self::faker()->regexify('[A-Z]{2}[0-4]{5}'),
            'supplier' => LazyValue::memoize(fn (): Supplier => SupplierFactory::new()->create()),
            'supplierCategory' => null,
            'supplierSubcategory' => null,
            'supplierManufacturer' => null,
            'mfrPartNumber' => self::faker()->numerify('PART-####'),
            'weight' => self::faker()->numberBetween(1, 10000),
            'stock' => self::faker()->numberBetween(1, 1000),
            'leadTimeDays' => self::faker()->numberBetween(1, 99),
            'cost' => (string) self::faker()->numberBetween(1, 100000) / 100,
            'product' => LazyValue::memoize(fn (): ProductFactory => ProductFactory::new()),
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
                if (null !== $attributes['supplier']) {
                    $attributes['supplierCategory'] ??= LazyValue::memoize(
                        fn (): object => SupplierCategoryFactory::new()->with(['supplier' => $attributes['supplier']])->create()
                    );
                    $attributes['supplierSubcategory'] ??= LazyValue::memoize(
                        fn (): SupplierSubcategoryFactory => SupplierSubcategoryFactory::new()->with([
                            'supplier' => $attributes['supplier'],
                            'supplierCategory' => $attributes['supplierCategory'],
                        ])
                    );
                    $attributes['supplierManufacturer'] ??= LazyValue::memoize(
                        fn (): SupplierManufacturerFactory => SupplierManufacturerFactory::new()->with(['supplier' => $attributes['supplier']])
                    );
                }

                return $attributes;
            })
             ->afterInstantiate(function (SupplierProduct $supplierProduct): void {
                 $supplierProduct->getProduct()?->addSupplierProduct($supplierProduct);
             })
            ->afterPersist(function (SupplierProduct $supplierProduct): void {
                if ($supplierProduct->getProduct() instanceof Product) {
                    $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());
                }
            });
    }

    public function recalculatePrice(): self
    {
        return $this
            ->afterPersist(function (SupplierProduct $supplierProduct): void {
                if ($supplierProduct->getProduct() instanceof Product) {
                    $this->productPriceCalculator->recalculatePrice($supplierProduct->getProduct());
                }
            });
    }
}
