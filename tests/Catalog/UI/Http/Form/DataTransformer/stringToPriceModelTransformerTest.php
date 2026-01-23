<?php

namespace App\Tests\Catalog\UI\Http\Form\DataTransformer;

use App\Catalog\UI\Http\Form\DataTransformer\stringToPriceModelTransformer;
use App\Shared\Domain\ValueObject\PriceModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class stringToPriceModelTransformerTest extends TestCase
{
    private stringToPriceModelTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new stringToPriceModelTransformer();
    }

    public function testTransformReturnsNullOnNullValue(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformReturnsEnumFromLowercaseString(): void
    {
        self::assertSame(PriceModel::DEFAULT, $this->transformer->transform('default'));
    }

    public function testTransformThrowsOnInvalidValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Invalid price model value: invalid');

        $this->transformer->transform('invalid');
    }

    public function testReverseTransformReturnsNullOnNull(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformReturnsNullOnEmptyString(): void
    {
        // @phpstan-ignore argument.type (intentionally testing falsy input handling)
        self::assertNull($this->transformer->reverseTransform(''));
    }

    public function testReverseTransformReturnsLowercaseString(): void
    {
        self::assertSame('default', $this->transformer->reverseTransform(PriceModel::DEFAULT));
    }

    public function testReverseTransformThrowsOnNonPriceModel(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a PriceModel.');

        // @phpstan-ignore argument.type (intentionally testing wrong type handling)
        $this->transformer->reverseTransform(new \stdClass());
    }
}
