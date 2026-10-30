<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Catalog\Domain\Model\Product\Product;
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
    public static function class(): string
    {
        return ProductImage::class;
    }

    protected function defaults(): array
    {
        return [
            'product' => LazyValue::memoize(fn (): Product => ProductFactory::createOne()),
            'uploadedFile' => LazyValue::memoize(fn (): UploadedFile => new UploadedFile(
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
