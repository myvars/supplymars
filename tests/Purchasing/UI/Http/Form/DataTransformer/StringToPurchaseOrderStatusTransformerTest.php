<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Http\Form\DataTransformer;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\UI\Http\Form\DataTransformer\StringToPurchaseOrderStatusTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class StringToPurchaseOrderStatusTransformerTest extends TestCase
{
    private StringToPurchaseOrderStatusTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringToPurchaseOrderStatusTransformer();
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
        $this->expectExceptionMessageIsOrContains('Invalid status value: invalid');

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
        self::assertSame('pending', $this->transformer->reverseTransform(PurchaseOrderStatus::PENDING));
    }

    public function testReverseTransformThrowsOnNonPurchaseOrderStatus(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIsOrContains('Expected a PurchaseOrderStatus.');

        // @phpstan-ignore argument.type (intentionally testing wrong type handling)
        $this->transformer->reverseTransform(new \stdClass());
    }
}
