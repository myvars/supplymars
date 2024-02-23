<?php

namespace App\Factory;

use App\Entity\PriceModel;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductPriceCalculator;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Product>
 *
 * @method        Product|Proxy                     create(array|callable $attributes = [])
 * @method static Product|Proxy                     createOne(array $attributes = [])
 * @method static Product|Proxy                     find(object|array|mixed $criteria)
 * @method static Product|Proxy                     findOrCreate(array $attributes)
 * @method static Product|Proxy                     first(string $sortedField = 'id')
 * @method static Product|Proxy                     last(string $sortedField = 'id')
 * @method static Product|Proxy                     random(array $attributes = [])
 * @method static Product|Proxy                     randomOrCreate(array $attributes = [])
 * @method static ProductRepository|RepositoryProxy repository()
 * @method static Product[]|Proxy[]                 all()
 * @method static Product[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Product[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Product[]|Proxy[]                 findBy(array $attributes)
 * @method static Product[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Product[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class ProductFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private ProductPriceCalculator $productPriceCalculator)
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
            'name' => self::faker()->text(100),
            'MfrPartNumber' => self::faker()->text(30),
            'category' => CategoryFactory::new(),
            'subcategory' => SubcategoryFactory::new(),
            'manufacturer' => ManufacturerFactory::new(),
            'cost' => self::faker()->randomNumber(5)/100,
            'isActive' => self::faker()->boolean(),
            'leadTimeDays' => self::faker()->randomNumber(2),
            'stock' => self::faker()->randomNumber(4),
            'weight' => self::faker()->randomNumber(4),
            'defaultMarkup' => self::faker()->randomNumber(4)/100,
            'markup' => self::faker()->randomNumber(4)/100,
            'priceModel' => PriceModel::NONE,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
             ->afterInstantiate(function(Product $product): void {
                 $this->productPriceCalculator->recalculatePrice($product);
             })
        ;
    }

    protected static function getClass(): string
    {
        return Product::class;
    }
}
