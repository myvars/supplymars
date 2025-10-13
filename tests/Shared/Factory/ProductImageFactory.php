<?php

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\ProductImage\ProductImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductImage>
 */
final class ProductImageFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return ProductImage::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn () => ProductFactory::createOne()),
            'uploadedFile' => LazyValue::memoize(fn () => new UploadedFile(
                __DIR__ . '/../../../tests/Shared/Resources/dummy-image.jpg',
                'dummy-image.jpg',
                'image/jpeg',
                null,
                true
            )),
            'position' => self::faker()->numberBetween(1, 100),
            'imageName' => self::faker()->word(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
                Instantiator::namedConstructor('createFromUploadedFile')
                ->allowExtra('imageName')
            )
            ->afterInstantiate(function (ProductImage $productImage, array $attributes): void {
                $productImage->changeImageName($attributes['imageName']);
            });
    }
}
