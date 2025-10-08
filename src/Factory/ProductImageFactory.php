<?php

namespace App\Factory;

use App\Entity\ProductImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ProductImage>
 */
final class ProductImageFactory extends PersistentProxyObjectFactory
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
            'product' => LazyValue::memoize(fn (): ProductFactory => ProductFactory::new()),
            'uploadedFile' => LazyValue::memoize(fn (): UploadedFile => new UploadedFile(
                __DIR__.'/../../tests/Resources/dummy-image.jpg',
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
        return $this->instantiateWith(function (array $attributes): ProductImage {
            $productImage = ProductImage::createFromUploadedFile(
                $attributes['product'],
                $attributes['uploadedFile'],
                $attributes['position']
            );
            $productImage->updateImageName($attributes['imageName']);

            return $productImage;
        });
    }
}
