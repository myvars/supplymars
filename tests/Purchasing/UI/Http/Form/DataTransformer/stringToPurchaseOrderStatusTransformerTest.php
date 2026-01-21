<?php

namespace App\Tests\Purchasing\UI\Http\Form\DataTransformer;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\UI\Http\Form\DataTransformer\stringToPurchaseOrderStatusTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class stringToPurchaseOrderStatusTransformerTest extends TestCase
{
    private stringToPurchaseOrderStatusTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new stringToPurchaseOrderStatusTransformer();
    }

    public function testTransformReturnsNullOnNullValue(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformReturnsEnumFromLowercaseString(): void
    {
        self::assertSame(PurchaseOrderStatus::PENDING, $this->transformer->transform('pending'));
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
        self::assertSame('pending', $this->transformer->reverseTransform(PurchaseOrderStatus::PENDING));
    }

    public function testReverseTransformThrowsOnNonPurchaseOrderStatus(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a PurchaseOrderStatus.');

        $this->transformer->reverseTransform(new \stdClass());
    }
}
