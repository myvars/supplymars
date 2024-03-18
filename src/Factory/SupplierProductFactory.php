<?php

namespace App\Factory;

use App\Entity\SupplierProduct;
use App\Repository\SupplierProductRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<SupplierProduct>
 *
 * @method        SupplierProduct|Proxy                     create(array|callable $attributes = [])
 * @method static SupplierProduct|Proxy                     createOne(array $attributes = [])
 * @method static SupplierProduct|Proxy                     find(object|array|mixed $criteria)
 * @method static SupplierProduct|Proxy                     findOrCreate(array $attributes)
 * @method static SupplierProduct|Proxy                     first(string $sortedField = 'id')
 * @method static SupplierProduct|Proxy                     last(string $sortedField = 'id')
 * @method static SupplierProduct|Proxy                     random(array $attributes = [])
 * @method static SupplierProduct|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SupplierProductRepository|RepositoryProxy repository()
 * @method static SupplierProduct[]|Proxy[]                 all()
 * @method static SupplierProduct[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static SupplierProduct[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static SupplierProduct[]|Proxy[]                 findBy(array $attributes)
 * @method static SupplierProduct[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SupplierProduct[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SupplierProductFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(50),
            'productCode' => self::faker()->regexify('[A-Z]{2}[0-4]{5}'),
            'supplierCategory' => SupplierCategoryFactory::new(),
            'supplierSubcategory' => SupplierSubcategoryFactory::new(),
            'supplierManufacturer' => SupplierManufacturerFactory::new(),
            'mfrPartNumber' => self::faker()->numerify('PART-####'),
            'weight' => self::faker()->randomNumber(4),
            'stock' => self::faker()->randomNumber(4),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'cost' => self::faker()->randomNumber(5) / 100,
            'isActive' => self::faker()->boolean(),
            'supplier' => SupplierFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(SupplierProduct $supplierProduct): void {})
        ;
    }

    protected static function getClass(): string
    {
        return SupplierProduct::class;
    }
}
