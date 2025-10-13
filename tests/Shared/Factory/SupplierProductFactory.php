<?php

namespace App\Tests\Shared\Factory;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

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
    public function __construct(private readonly MarkupCalculator $markupCalculator)
    {
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
            'supplierCategory' => null,
            'supplierSubcategory' => null,
            'supplierManufacturer' => null,
            'mfrPartNumber' => self::faker()->numerify('PART-####'),
            'weight' => self::faker()->numberBetween(1, 10000),
            'supplier' => LazyValue::memoize(fn () => SupplierFactory::createOne()),
            'stock' => self::faker()->numberBetween(1, 1000),
            'leadTimeDays' => self::faker()->numberBetween(1, 99),
            'cost' => (string) self::faker()->numberBetween(1, 100000) / 100,
            'product' => LazyValue::memoize(fn () => ProductFactory::createOne()),
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
                        fn () => SupplierCategoryFactory::createOne(['supplier' => $attributes['supplier']])
                    );
                    $attributes['supplierSubcategory'] ??= LazyValue::memoize(
                        fn () => SupplierSubcategoryFactory::createOne([
                            'supplier' => $attributes['supplier'],
                            'supplierCategory' => $attributes['supplierCategory'],
                        ])
                    );
                    $attributes['supplierManufacturer'] ??= LazyValue::memoize(
                        fn () => SupplierManufacturerFactory::createOne(['supplier' => $attributes['supplier']])
                    );
                }

                return $attributes;
            })
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            )
             ->afterInstantiate(function (SupplierProduct $supplierProduct): void {
                 $supplierProduct->getProduct()?->addSupplierProduct(
                     $this->markupCalculator,
                     $supplierProduct
                 );
             });
    }
}
