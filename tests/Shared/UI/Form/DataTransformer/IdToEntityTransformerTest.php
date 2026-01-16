<?php

namespace App\Tests\Shared\UI\Form\DataTransformer;

use App\Shared\UI\Form\DataTransformer\IdToEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class IdToEntityTransformerTest extends TestCase
{
    public function testTransformReturnsEntityFromRepository(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $entity = new \stdClass();

        $em->expects(self::once())
            ->method('getRepository')
            ->with(\stdClass::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($entity);

        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        self::assertSame($entity, $transformer->transform('42'));
    }

    public function testTransformUsesCustomFinderClosure(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('getRepository');

        $entity = new \stdClass();
        $called = false;

        $finder = function (EntityManagerInterface $passedEm, int $id) use ($em, &$called, $entity): \stdClass {
            $called = true;
            TestCase::assertSame($em, $passedEm);
            TestCase::assertSame(7, $id);

            return $entity;
        };

        $transformer = new IdToEntityTransformer($em, \stdClass::class, $finder);

        self::assertSame($entity, $transformer->transform(7));
        self::assertTrue($called);
    }

    public function testTransformReturnsNullOnNullValue(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('getRepository');

        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        self::assertNull($transformer->transform(null));
    }

    public function testTransformThrowsWhenEntityNotFound(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $repo = $this->createStub(EntityRepository::class);

        $em->method('getRepository')->with(\stdClass::class)->willReturn($repo);
        $repo->method('find')->with(99)->willReturn(null);

        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('stdClass with id "99" not found.');
        $transformer->transform('99');
    }

    public function testReverseTransformReturnsIdFromEntity(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        $entity = new class {
            public function getId(): string
            {
                return '123';
            }
        };

        self::assertSame(123, $transformer->reverseTransform($entity));
    }

    public function testReverseTransformReturnsNullOnFalsy(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        self::assertNull($transformer->reverseTransform(null));
        self::assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransformThrowsForNonEntityWithoutGetId(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an entity with getId().');

        $transformer->reverseTransform(new \stdClass());
    }
}
