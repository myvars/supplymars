<?php

namespace App\Tests\Order\UI\Http\Form\DataTransformer;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\UI\Http\Form\DataTransformer\stringToOrderStatusTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class stringToOrderStatusTransformerTest extends TestCase
{
    private stringToOrderStatusTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new stringToOrderStatusTransformer();
    }

    public function testTransformReturnsNullOnNullValue(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformReturnsEnumFromLowercaseString(): void
    {
        self::assertSame(OrderStatus::PENDING, $this->transformer->transform('pending'));
    }

    public function testTransformThrowsOnInvalidValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Invalid status value: invalid');

        $this->transformer->transform('invalid');
    }

    public function testReverseTransformReturnsNullOnNull(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformReturnsNullOnEmptyString(): void
    {
        self::assertNull($this->transformer->reverseTransform(''));
    }

    public function testReverseTransformReturnsLowercaseString(): void
    {
        self::assertSame('pending', $this->transformer->reverseTransform(OrderStatus::PENDING));
    }

    public function testReverseTransformThrowsOnNonOrderStatus(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an OrderStatus.');

        $this->transformer->reverseTransform(new \stdClass());
    }
}
