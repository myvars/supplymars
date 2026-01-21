<?php

namespace App\Tests\Shared\Application\Identity;

use App\Shared\Application\Identity\PublicIdResolverInterface;
use App\Shared\Application\Identity\PublicIdResolverRegistry;
use App\Shared\Domain\ValueObject\AbstractUlidId;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class PublicIdResolverRegistryTest extends TestCase
{
    public function testResolveReturnsNullWhenNoResolverRegistered(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn(null);

        $registry = new PublicIdResolverRegistry($container);
        $publicId = TestPublicId::fromString('01JJZQ5YPXH8M9K7N6R4T2V3W1');

        $result = $registry->resolve($publicId);

        self::assertNull($result);
    }

    public function testResolveReturnsIdFromResolver(): void
    {
        $publicId = TestPublicId::fromString('01JJZQ5YPXH8M9K7N6R4T2V3W1');

        $resolver = $this->createStub(PublicIdResolverInterface::class);
        $resolver->method('resolve')->willReturn(42);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($resolver);

        $registry = new PublicIdResolverRegistry($container);

        $result = $registry->resolve($publicId);

        self::assertSame(42, $result);
    }

    public function testResolveCachesResultAndDoesNotCallResolverTwice(): void
    {
        $publicId = TestPublicId::fromString('01JJZQ5YPXH8M9K7N6R4T2V3W1');

        $resolver = $this->createMock(PublicIdResolverInterface::class);
        $resolver->expects(self::once())->method('resolve')->willReturn(99);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($resolver);

        $registry = new PublicIdResolverRegistry($container);

        $first = $registry->resolve($publicId);
        $second = $registry->resolve($publicId);

        self::assertSame(99, $first);
        self::assertSame(99, $second);
    }

    public function testResolveCachesNullResult(): void
    {
        $publicId = TestPublicId::fromString('01JJZQ5YPXH8M9K7N6R4T2V3W1');

        $resolver = $this->createMock(PublicIdResolverInterface::class);
        $resolver->expects(self::once())->method('resolve')->willReturn(null);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($resolver);

        $registry = new PublicIdResolverRegistry($container);

        $first = $registry->resolve($publicId);
        $second = $registry->resolve($publicId);

        self::assertNull($first);
        self::assertNull($second);
    }

    public function testResolveDifferentPublicIdsAreCachedSeparately(): void
    {
        $publicIdA = TestPublicId::fromString('01JJZQ5YPXH8M9K7N6R4T2V3W1');
        $publicIdB = TestPublicId::fromString('01JJZQ6ABC123DEF456GHJ789K');

        $resolver = $this->createStub(PublicIdResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(
            fn (AbstractUlidId $id): int => $id->value() === '01JJZQ5YPXH8M9K7N6R4T2V3W1' ? 10 : 20
        );

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($resolver);

        $registry = new PublicIdResolverRegistry($container);

        self::assertSame(10, $registry->resolve($publicIdA));
        self::assertSame(20, $registry->resolve($publicIdB));
    }
}

/**
 * Concrete implementation for testing.
 */
final readonly class TestPublicId extends AbstractUlidId
{
}
