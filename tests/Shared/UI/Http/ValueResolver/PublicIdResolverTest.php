<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http\ValueResolver;

use App\Shared\UI\Http\ValueResolver\PublicIdResolver;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicIdResolverTest extends TestCase
{
    private function makeArgument(?string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('entity', $type, false, false, null, false);
    }

    public function testReturnsEmptyWhenNoType(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $resolver = new PublicIdResolver($registry);

        $result = $resolver->resolve(new Request(), $this->makeArgument(null));
        self::assertSame([], $result);
    }

    public function testReturnsEmptyWhenTypeDoesNotExist(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $resolver = new PublicIdResolver($registry);

        $result = $resolver->resolve(new Request(), $this->makeArgument('No\\Such\\Class'));
        self::assertSame([], $result);
    }

    public function testReturnsEmptyWhenNoManagerForClass(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $resolver = new PublicIdResolver($registry);

        $result = $resolver->resolve(new Request(), $this->makeArgument(\stdClass::class));
        self::assertSame([], $result);
    }

    public function testReturnsEmptyWhenNoIdAttribute(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $manager = $this->createStub(ObjectManager::class);

        $registry->method('getManagerForClass')->willReturn($manager);

        $resolver = new PublicIdResolver($registry);

        $result = $resolver->resolve(new Request(), $this->makeArgument(\stdClass::class));
        self::assertSame([], $result);
    }

    public function testResolvesEntityWhenFound(): void
    {
        $entity = new class {
            public string $publicId = '01J9ZQ6Y8V4K3M2N1P0R5S7T9W';
        };

        $registry = $this->createStub(ManagerRegistry::class);
        $manager = $this->createStub(ObjectManager::class);
        $repo = $this->createMock(ObjectRepository::class);

        $registry->method('getManagerForClass')->willReturn($manager);
        $manager->method('getRepository')->willReturn($repo);

        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['publicId' => '01J9ZQ6Y8V4K3M2N1P0R5S7T9W'])
            ->willReturn($entity);

        $request = new Request([], [], ['id' => '01J9ZQ6Y8V4K3M2N1P0R5S7T9W']);
        $resolver = new PublicIdResolver($registry);

        $result = $resolver->resolve($request, $this->makeArgument(\stdClass::class));
        self::assertSame([$entity], $result);
    }

    public function testThrowsNotFoundWhenEntityMissing(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $manager = $this->createStub(ObjectManager::class);
        $repo = $this->createStub(ObjectRepository::class);

        $registry->method('getManagerForClass')->willReturn($manager);
        $manager->method('getRepository')->willReturn($repo);
        $repo->method('findOneBy')->willReturn(null);

        $request = new Request([], [], ['id' => '01J9ZQ6Y8V4K3M2N1P0R5S7T9X']);
        $resolver = new PublicIdResolver($registry);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageIsOrContains('stdClass "01J9ZQ6Y8V4K3M2N1P0R5S7T9X" not found.');

        iterator_to_array($resolver->resolve($request, $this->makeArgument(\stdClass::class)));
    }

    public function testThrowsNotFoundWithoutQueryingWhenIdIsNotAUlid(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $manager = $this->createMock(ObjectManager::class);

        $registry->method('getManagerForClass')->willReturn($manager);
        $manager->expects(self::never())->method('getRepository');

        $request = new Request([], [], ['id' => 'not-a-ulid']);
        $resolver = new PublicIdResolver($registry);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageIsOrContains('stdClass "not-a-ulid" not found.');

        iterator_to_array($resolver->resolve($request, $this->makeArgument(\stdClass::class)));
    }
}
